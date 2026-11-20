<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\WorkforceSchedulingService;
use App\Services\OperationalCoverageService;
use App\Services\AgentReplacementService;
use App\Services\WallbookHandoverService;
use App\Controllers\Api\SchedulingController;
use App\Controllers\Api\ReplacementController;
use App\Controllers\Api\WallbookController;

/**
 * @internal
 */
final class SchedulingTest extends CIUnitTestCase
{
    protected WorkforceSchedulingService $schedulingService;
    protected OperationalCoverageService $coverageService;
    protected AgentReplacementService $replacementService;
    protected WallbookHandoverService $wallbookService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schedulingService = new WorkforceSchedulingService();
        $this->coverageService = new OperationalCoverageService();
        $this->replacementService = new AgentReplacementService();
        $this->wallbookService = new WallbookHandoverService();
    }

    protected function tearDown(): void
    {
        // Clean up transient planned leaves created during off-duty & replacement test assertions
        \Config\Database::connect()->table('workforce_agent_availability')
            ->where('agent_id', 2)
            ->where('availability_type', 'planned_leave')
            ->delete();
        parent::tearDown();
    }

    public function testServicesAndControllersInstantiate(): void
    {
        $this->assertInstanceOf(WorkforceSchedulingService::class, $this->schedulingService);
        $this->assertInstanceOf(OperationalCoverageService::class, $this->coverageService);
        $this->assertInstanceOf(AgentReplacementService::class, $this->replacementService);
        $this->assertInstanceOf(WallbookHandoverService::class, $this->wallbookService);
        $this->assertInstanceOf(SchedulingController::class, new SchedulingController());
        $this->assertInstanceOf(ReplacementController::class, new ReplacementController());
        $this->assertInstanceOf(WallbookController::class, new WallbookController());
    }

    public function testListPoliciesAndShifts(): void
    {
        $policies = $this->schedulingService->listPolicies();
        $this->assertIsArray($policies);
        $this->assertGreaterThanOrEqual(1, count($policies));

        $defaultPolicy = $this->schedulingService->getDefaultPolicy();
        $this->assertNotNull($defaultPolicy);
        $this->assertEquals(5, (int)$defaultPolicy['working_days_count']);
        $this->assertEquals(2, (int)$defaultPolicy['rest_days_count']);

        $shifts = $this->schedulingService->listShifts();
        $this->assertIsArray($shifts);
        $this->assertGreaterThanOrEqual(3, count($shifts));
    }

    public function testRosterRetrievalAndStaggered52Assignments(): void
    {
        $rosters = $this->schedulingService->listRosters();
        $this->assertIsArray($rosters);
        $this->assertGreaterThanOrEqual(1, $rosters['total']);

        $firstRoster = $rosters['items'][0];
        $rosterDetail = $this->schedulingService->getRoster((int)$firstRoster['id']);
        $this->assertNotNull($rosterDetail);
        $this->assertArrayHasKey('assignments', $rosterDetail);
        $this->assertGreaterThanOrEqual(7, count($rosterDetail['assignments']));

        // Verify staggered duty: count working vs rest for an agent in the cycle
        $assignmentsByAgent = [];
        foreach ($rosterDetail['assignments'] as $a) {
            $assignmentsByAgent[$a['agent_id']][] = $a['duty_type'];
        }

        foreach ($assignmentsByAgent as $agentId => $duties) {
            if (count($duties) === 7) {
                $counts = array_count_values($duties);
                $this->assertEquals(5, $counts['working'] ?? 0, "Agent #{$agentId} must have exactly 5 working days under 5/2 policy.");
                $this->assertEquals(2, $counts['rest'] ?? 0, "Agent #{$agentId} must have exactly 2 rest days under 5/2 policy.");
            }
        }
    }

    public function testConflictDetectionOnOverlappingAssignments(): void
    {
        $rosters = $this->schedulingService->listRosters();
        $firstRoster = $rosters['items'][0];
        $rosterDetail = $this->schedulingService->getRoster((int)$firstRoster['id']);
        $existingAssignment = $rosterDetail['assignments'][0];

        $conflicts = $this->schedulingService->checkAssignmentConflicts(
            (int)$existingAssignment['agent_id'],
            $existingAssignment['assignment_date'],
            (int)$existingAssignment['shift_id']
        );

        $this->assertNotEmpty($conflicts);
        $this->assertStringContainsString('already has an existing', $conflicts[0]);
    }

    public function testOperationalCoverageEvaluationAndGapDetection(): void
    {
        $today = date('Y-m-d');
        $eval = $this->coverageService->evaluateCoverage($today);

        $this->assertIsArray($eval);
        $this->assertArrayHasKey('summary', $eval);
        $this->assertArrayHasKey('evaluations', $eval);
        $this->assertGreaterThanOrEqual(1, $eval['summary']['total_requirements']);

        $gaps = $this->coverageService->getCoverageGaps($today);
        $this->assertIsArray($gaps);
        $this->assertArrayHasKey('gaps', $gaps);
    }

    public function testOffDutyTransitionEnforcesReplacementAndHandoverPrerequisites(): void
    {
        // Try off-duty request for an agent with active scheduled duty
        $rosters = $this->schedulingService->listRosters();
        $rosterDetail = $this->schedulingService->getRoster((int)$rosters['items'][0]['id']);

        $workingAssignment = null;
        foreach ($rosterDetail['assignments'] as $a) {
            if ($a['duty_type'] === 'working' && $a['status'] === 'scheduled') {
                $workingAssignment = $a;
                break;
            }
        }
        $this->assertNotNull($workingAssignment);

        $res = $this->schedulingService->requestOffDuty(
            (int)$workingAssignment['agent_id'],
            [
                'start_date' => $workingAssignment['assignment_date'],
                'end_date'   => $workingAssignment['assignment_date'],
                'reason'     => 'Unit Test Emergency Off-Duty Verification',
            ],
            1
        );

        $this->assertIsArray($res);
        $this->assertArrayHasKey('is_fully_approved', $res);
        $this->assertArrayHasKey('replacement_satisfied', $res);
        $this->assertArrayHasKey('handover_satisfied', $res);

        // If replacement or handover are missing, it cannot be fully approved!
        if (!$res['replacement_satisfied'] || !$res['handover_satisfied']) {
            $this->assertFalse($res['is_fully_approved']);
            $this->assertEquals('pending', $res['request']['approval_status']);
            $this->assertNotNull($res['block_reason']);
        }
    }

    public function testReplacementCandidateDiscoveryAndProposalLifecycle(): void
    {
        $rosters = $this->schedulingService->listRosters();
        $rosterDetail = $this->schedulingService->getRoster((int)$rosters['items'][0]['id']);

        $workingDuty = null;
        foreach ($rosterDetail['assignments'] as $a) {
            if ($a['duty_type'] === 'working') {
                $workingDuty = $a;
                break;
            }
        }
        $this->assertNotNull($workingDuty);

        $discovery = $this->replacementService->findReplacementCandidates((int)$workingDuty['id']);
        $this->assertIsArray($discovery);
        $this->assertArrayHasKey('candidates', $discovery);

        if (!empty($discovery['candidates'])) {
            $bestCandidate = $discovery['candidates'][0];
            $this->assertGreaterThan(0, $bestCandidate['candidate_agent_id']);
            $this->assertTrue($bestCandidate['is_available']);

            // Create proposal
            $proposalCode = 'REP-TEST-' . rand(1000, 9999);
            $proposal = $this->replacementService->createProposal([
                'proposal_code'           => $proposalCode,
                'duty_assignment_id'      => (int)$workingDuty['id'],
                'incoming_agent_id'       => $bestCandidate['candidate_agent_id'],
                'original_responsibility' => 'Unit Test Coverage Task',
                'reason'                  => 'Automated test of candidate discovery and proposal flow.',
            ], 1);

            $this->assertNotNull($proposal);
            $this->assertEquals('proposed', $proposal['status']);

            // Accept
            $accepted = $this->replacementService->acceptProposal((int)$proposal['id'], 'AGT-TEST-IDENTITY');
            $this->assertEquals('accepted', $accepted['status']);
            $this->assertNotNull($accepted['accepted_at']);

            // Approve
            $approved = $this->replacementService->approveProposal((int)$proposal['id'], 1);
            $this->assertEquals('approved', $approved['status']);
            $this->assertNotNull($approved['approved_at']);

            // Activate (Atomic transaction)
            $activated = $this->replacementService->activateReplacement((int)$proposal['id'], 1);
            $this->assertEquals('activated', $activated['status']);
            $this->assertNotNull($activated['activated_at']);
        }
    }

    public function testWallbookHandoverMandatorySectionsAndLifecycle(): void
    {
        $handovers = $this->wallbookService->listHandovers();
        $this->assertIsArray($handovers);
        $this->assertGreaterThanOrEqual(1, $handovers['total']);

        // Create new draft handover
        $testCode = 'WB-TEST-' . rand(1000, 9999);
        $created = $this->wallbookService->createHandover([
            'handover_code'     => $testCode,
            'title'             => 'Test Operational Context Handover',
            'outgoing_agent_id' => 2,
            'incoming_agent_id' => 6,
            'unit_id'           => 2,
        ], 1);

        $this->assertNotNull($created);
        $this->assertEquals('draft', $created['status']);

        // Submitting without mandatory sections must throw 422 RuntimeException
        $threw = false;
        try {
            $this->wallbookService->submitHandover((int)$created['id'], 1);
        } catch (\RuntimeException $e) {
            $threw = true;
            $this->assertStringContainsString('Missing mandatory sections', $e->getMessage());
        }
        $this->assertTrue($threw, 'Submitting handover without mandatory sections must fail.');

        // Add mandatory items
        $this->wallbookService->addHandoverItem((int)$created['id'], [
            'section_category' => 'responsibilities',
            'title'            => 'Active Duty Responsibilities',
            'content'          => 'Responsible for system API uptime and test executions.',
        ]);
        $this->wallbookService->addHandoverItem((int)$created['id'], [
            'section_category' => 'active_tasks',
            'title'            => 'Active Tasks',
            'content'          => 'Executing unit tests for Prompt 20.',
        ]);
        $this->wallbookService->addHandoverItem((int)$created['id'], [
            'section_category' => 'operational_risks',
            'title'            => 'Known Operational Risks',
            'content'          => 'Ensure MySQL connection limits are respected.',
        ]);
        $this->wallbookService->addHandoverItem((int)$created['id'], [
            'section_category' => 'context_instructions',
            'title'            => 'Context & Instructions',
            'content'          => 'Run tests via spark or phpunit.',
        ]);

        // Submit now succeeds
        $submitted = $this->wallbookService->submitHandover((int)$created['id'], 1);
        $this->assertEquals('awaiting_acknowledgement', $submitted['status']);

        // Incoming agent acknowledges
        $acknowledged = $this->wallbookService->acknowledgeHandover((int)$created['id'], 6, 'Context acknowledged by Dev AI.');
        $this->assertEquals('acknowledged', $acknowledged['status']);
        $this->assertNotNull($acknowledged['acknowledged_at']);

        // Incoming agent formally accepts
        $accepted = $this->wallbookService->acceptHandover((int)$created['id'], 6, 'Duty assumed successfully.');
        $this->assertEquals('accepted', $accepted['status']);
        $this->assertNotNull($accepted['accepted_at']);
    }
}
