<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\WelfareOverviewService;
use App\Services\WelfarePolicyService;
use App\Services\WelfareCompensationService;
use App\Services\WelfareBonusService;
use App\Services\WelfareRecognitionService;
use App\Services\WelfareLedgerService;
use App\Services\WelfareFacilityService;
use App\Controllers\Api\WelfareController;
use Config\Database;

/**
 * @internal
 */
final class WelfareTest extends CIUnitTestCase
{
    protected WelfareOverviewService $overviewService;
    protected WelfarePolicyService $policyService;
    protected WelfareCompensationService $compensationService;
    protected WelfareBonusService $bonusService;
    protected WelfareRecognitionService $recognitionService;
    protected WelfareLedgerService $ledgerService;
    protected WelfareFacilityService $facilityService;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->overviewService = new WelfareOverviewService();
        $this->policyService = new WelfarePolicyService();
        $this->compensationService = new WelfareCompensationService();
        $this->bonusService = new WelfareBonusService();
        $this->recognitionService = new WelfareRecognitionService();
        $this->ledgerService = new WelfareLedgerService();
        $this->facilityService = new WelfareFacilityService();
        $this->db = Database::connect();
    }

    public function testServicesAndControllersInstantiate(): void
    {
        $this->assertInstanceOf(WelfareOverviewService::class, $this->overviewService);
        $this->assertInstanceOf(WelfarePolicyService::class, $this->policyService);
        $this->assertInstanceOf(WelfareCompensationService::class, $this->compensationService);
        $this->assertInstanceOf(WelfareBonusService::class, $this->bonusService);
        $this->assertInstanceOf(WelfareRecognitionService::class, $this->recognitionService);
        $this->assertInstanceOf(WelfareLedgerService::class, $this->ledgerService);
        $this->assertInstanceOf(WelfareFacilityService::class, $this->facilityService);
        $this->assertInstanceOf(WelfareController::class, new WelfareController());
    }

    public function testWelfarePolicyCreationAndActivation(): void
    {
        $code = 'POL-TEST-' . rand(1000, 9999);
        $created = $this->policyService->createPolicy([
            'policy_code'       => $code,
            'version'           => '1.2.0',
            'title'             => 'Test Welfare Policy Proposal',
            'base_duty_tickets' => 12.00,
        ], 1);

        $this->assertIsArray($created);
        $this->assertEquals($code, $created['policy_code']);
        $this->assertEquals('pending_approval', $created['approval_status']);
        $this->assertEquals(0, (int)$created['is_active']);

        // Approve and activate
        $approved = $this->policyService->approvePolicy((int)$created['id'], 1, 'Owner Board Approval');
        $this->assertEquals('approved', $approved['approval_status']);
        $this->assertEquals(1, (int)$approved['is_active']);

        $active = $this->policyService->getActivePolicy();
        $this->assertNotNull($active);
        $this->assertEquals($code, $active['policy_code']);

        // Restore original default policy as active
        $defaultPolicy = $this->db->table('welfare_policies')->where('policy_code', 'POL-WLF-2026-V1')->get()->getRowArray();
        if ($defaultPolicy) {
            $this->policyService->approvePolicy((int)$defaultPolicy['id'], 1, 'Restored default policy');
        }
    }

    public function testBaseCompensationPeriodCalculation(): void
    {
        $code = 'WCP-TEST-' . rand(1000, 9999);
        $period = $this->compensationService->createPeriod([
            'period_code' => $code,
            'start_date'  => '2026-11-16',
            'end_date'    => '2026-11-22',
            'notes'       => 'Test Weekly Compensation Cycle',
        ], 1);

        $this->assertIsArray($period);
        $this->assertEquals($code, $period['period_code']);
        $this->assertEquals('calculated', $period['status']);
        $this->assertGreaterThanOrEqual(6, $period['total_eligible_agents']);
        $this->assertGreaterThan(0, $period['total_base_tickets']);
        $this->assertNotEmpty($period['calculations']);

        $firstCalc = $period['calculations'][0];
        $this->assertGreaterThan(0, $firstCalc['base_tickets_amount']);
        $this->assertEquals(5, $firstCalc['scheduled_duty_days']);
        $this->assertEquals('eligible', $firstCalc['status']);
    }

    public function testPeriodApprovalAndTicketDistribution(): void
    {
        $code = 'WCP-DIST-' . rand(1000, 9999);
        $period = $this->compensationService->createPeriod([
            'period_code' => $code,
            'start_date'  => '2026-11-16',
            'end_date'    => '2026-11-22',
        ], 1);
        $periodId = (int)$period['id'];

        // Approve period
        $approved = $this->compensationService->approvePeriod($periodId, 1);
        $this->assertEquals('approved', $approved['status']);

        // Distribute tickets
        $distributed = $this->compensationService->distributePeriodTickets($periodId, 1);
        $this->assertEquals('distributed', $distributed['status']);
        $this->assertGreaterThan(0, $distributed['total_tickets_issued']);

        // Verify ledger transactions were created with valid idempotency hashes
        $txCount = $this->db->table('welfare_ticket_transactions')
            ->where('source_period_id', $periodId)
            ->countAllResults();
        $this->assertGreaterThanOrEqual(6, $txCount);
    }

    public function testVerifiedTaskBonusEvaluationAndPerformanceLinkage(): void
    {
        $task = $this->db->table('workforce_tasks')->where('id', 1)->get()->getRowArray();
        $this->assertNotNull($task);

        // Ensure task has completed status for test
        $this->db->table('workforce_tasks')->where('id', 1)->update(['status' => 'completed']);

        // Delete any duplicate test evaluations for task 1
        $this->db->table('welfare_task_bonus_evaluations')->where('task_id', 1)->delete();

        $eval = $this->bonusService->evaluateTaskBonus(1, 1, [
            'bonus_category'     => 'completion_excellence',
            'verification_notes' => 'Unit test verification acceptance checklist.',
        ]);

        $this->assertIsArray($eval);
        $this->assertEquals(1, $eval['task_id']);
        $this->assertEquals('pending_review', $eval['approval_status']);
        $this->assertGreaterThan(0, (float)$eval['calculated_bonus_tickets']);

        // Approve bonus
        $evalId = (int)$eval['id'];
        $approved = $this->bonusService->approveTaskBonus($evalId, 1, 7.50);
        $this->assertEquals('approved', $approved['approval_status']);
        $this->assertEquals('issued', $approved['issuance_status']);
        $this->assertEquals(7.50, (float)$approved['approved_bonus_tickets']);
        $this->assertNotNull($approved['performance_history_id']);

        // Verify Performance History record created
        $perf = $this->db->table('ai_agent_performance_history')->where('id', $approved['performance_history_id'])->get()->getRowArray();
        $this->assertNotNull($perf);
        $this->assertEquals('task_completion', $perf['category']);
        $this->assertEquals('verified', $perf['evidence_status']);
    }

    public function testSpecialRecognitionNominationAndApproval(): void
    {
        $nom = $this->recognitionService->nominateRecognition([
            'agent_id'        => 2, // AGT-TECH-001
            'title'           => 'Multi-Agent Safety Protocol Excellence',
            'category'        => 'innovation_contribution',
            'justification'   => 'Pioneered Zero-Self-Review verification engine.',
            'awarded_tickets' => 12.00,
        ], 1);

        $this->assertIsArray($nom);
        $this->assertEquals('pending_review', $nom['approval_status']);
        $this->assertEquals(12.00, (float)$nom['awarded_tickets']);

        $recId = (int)$nom['id'];
        $approved = $this->recognitionService->approveRecognition($recId, 1);
        $this->assertEquals('approved', $approved['approval_status']);
        $this->assertEquals('issued', $approved['issuance_status']);
        $this->assertNotNull($approved['performance_history_id']);

        // Verify ledger transaction
        $tx = $this->db->table('welfare_ticket_transactions')->where('source_recognition_id', $recId)->get()->getRowArray();
        $this->assertNotNull($tx);
        $this->assertEquals('special_recognition', $tx['ticket_category']);
        $this->assertEquals(12.00, (float)$tx['amount']);
    }

    public function testLedgerReconciliationAndBalanceIntegrity(): void
    {
        $agentId = 1; // Director AI
        $reconciled = $this->ledgerService->reconcileLedger($agentId);

        $this->assertIsArray($reconciled);
        $this->assertTrue($reconciled['is_reconciled']);
        $this->assertGreaterThanOrEqual(0, $reconciled['current_balance']);
        $this->assertNotNull($reconciled['last_reconciled_at']);
    }

    public function testAdministrativeAdjustmentAndNegativeBalancePrevention(): void
    {
        $agentId = 3; // AGT-SRV-001
        $adjCredit = $this->ledgerService->postAdjustment($agentId, 5.00, 'Test Credit Adjustment', 1, 'credit');
        $this->assertIsArray($adjCredit);
        $this->assertEquals('adjustment', $adjCredit['ticket_category']);
        $this->assertEquals('credit', $adjCredit['transaction_type']);
        $this->assertEquals(5.00, (float)$adjCredit['amount']);

        // Attempting an adjustment that forces negative balance must fail
        $threw = false;
        try {
            $this->ledgerService->postAdjustment($agentId, 999999.00, 'Illegal Excessive Debit', 1, 'debit');
        } catch (\RuntimeException $e) {
            $threw = true;
            $this->assertStringContainsString('negative', strtolower($e->getMessage()));
        }
        $this->assertTrue($threw, 'Debit adjustment exceeding balance must be rejected.');
    }

    public function testTransactionReversalImmutableAudit(): void
    {
        $agentId = 4; // AGT-ACD-001
        $adj = $this->ledgerService->postAdjustment($agentId, 4.00, 'Transient adjustment to reverse', 1, 'credit');
        $txId = (int)$adj['id'];

        $reversed = $this->ledgerService->postReversal($txId, 'Testing formal reversal workflow', 1);
        $this->assertIsArray($reversed);
        $this->assertEquals('reversal', $reversed['ticket_category']);
        $this->assertEquals('debit', $reversed['transaction_type']);
        $this->assertEquals(4.00, (float)$reversed['amount']);
        $this->assertEquals($txId, (int)$reversed['reversal_ref_transaction_id']);

        // Verify original marked as reversed
        $orig = $this->db->table('welfare_ticket_transactions')->where('id', $txId)->get()->getRowArray();
        $this->assertEquals('reversed', $orig['status']);
    }

    public function testWelfareFacilitiesAndSessionLifecycle(): void
    {
        $facilities = $this->facilityService->listFacilities();
        $this->assertGreaterThanOrEqual(2, count($facilities));

        $restFac = null;
        foreach ($facilities as $f) {
            if ($f['facility_type'] === 'rest_recovery') {
                $restFac = $f;
                break;
            }
        }
        $this->assertNotNull($restFac, 'Rest and recovery facility must exist.');

        // Clean any active sessions for AGT-HDW-001
        $this->db->table('welfare_facility_usage_logs')->where('agent_id', 6)->where('status', 'active')->update(['status' => 'completed']);

        // Check in
        $session = $this->facilityService->checkInFacility((int)$restFac['id'], 6, 'duty_rest_recovery', null, 'Recovery pod rest session.');
        $this->assertIsArray($session);
        $this->assertEquals('active', $session['status']);
        $this->assertEquals(6, (int)$session['agent_id']);

        // Check out
        $completed = $this->facilityService->checkOutFacility((int)$session['id']);
        $this->assertEquals('completed', $completed['status']);
        $this->assertNotNull($completed['end_time']);
    }

    public function testCampusWelfareOverviewMetrics(): void
    {
        $overview = $this->overviewService->getOverview();
        $this->assertIsArray($overview);
        $this->assertArrayHasKey('active_policy', $overview);
        $this->assertArrayHasKey('metrics', $overview);
        $this->assertArrayHasKey('agent_roster', $overview);
        $this->assertArrayHasKey('facilities', $overview);

        $metrics = $overview['metrics'];
        $this->assertGreaterThan(0, $metrics['total_tickets_issued']);
        $this->assertGreaterThanOrEqual(6, $metrics['total_active_agents']);
    }
}
