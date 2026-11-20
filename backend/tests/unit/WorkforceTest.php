<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\AgentRegistryService;
use App\Services\AgentWorkforceService;
use App\Controllers\Api\WorkforceController;

/**
 * @internal
 */
final class WorkforceTest extends CIUnitTestCase
{
    protected AgentRegistryService $registryService;
    protected AgentWorkforceService $workforceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registryService = new AgentRegistryService();
        $this->workforceService = new AgentWorkforceService();
    }

    public function testServicesAndControllerInstantiate(): void
    {
        $this->assertInstanceOf(AgentRegistryService::class, $this->registryService);
        $this->assertInstanceOf(AgentWorkforceService::class, $this->workforceService);
        $this->assertInstanceOf(WorkforceController::class, new WorkforceController());
    }

    public function testUuidGenerationV4(): void
    {
        $uuid1 = AgentRegistryService::generateUuid();
        $uuid2 = AgentRegistryService::generateUuid();

        $this->assertNotEquals($uuid1, $uuid2);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid1);
    }

    public function testListAgentsAndMetrics(): void
    {
        $res = $this->registryService->listAgents([], 10, 0);
        $this->assertIsArray($res);
        $this->assertArrayHasKey('items', $res);
        $this->assertArrayHasKey('total', $res);
        $this->assertGreaterThanOrEqual(6, $res['total']);

        $metrics = $this->registryService->getMetrics();
        $this->assertIsArray($metrics);
        $this->assertGreaterThanOrEqual(6, $metrics['total_registered_agents']);
        $this->assertArrayHasKey('active_agents', $metrics);
        $this->assertArrayHasKey('by_lifecycle_state', $metrics);
    }

    public function testAgentCreationAndProfile(): void
    {
        $testCode = 'AGT-TEST-' . rand(1000, 9999);
        $created = $this->registryService->createAgent([
            'name'         => 'Test Specialist AI',
            'display_name' => 'QA Bot Tester',
            'agent_code'   => $testCode,
            'description'  => 'Automated test suite agent for Prompt 19 verification.',
        ], 1);

        $this->assertNotNull($created);
        $this->assertEquals($testCode, $created['agent_code']);
        $this->assertEquals('draft', $created['lifecycle_state']);
        $this->assertNotEmpty($created['agent_uuid']);
        $this->assertNotNull($created['profile']);

        // Test Lifecycle Transitions
        // draft -> proposed (Valid)
        $proposed = $this->workforceService->transitionState($created['id'], 'proposed', 'Ready for review', 'REF-TEST-01', 1);
        $this->assertEquals('proposed', $proposed['lifecycle_state']);

        // proposed -> approved (Invalid! Must go to under_review first)
        $this->expectException(\InvalidArgumentException::class);
        $this->workforceService->transitionState($created['id'], 'approved', 'Bypass review attempt', null, 1);
    }

    public function testValidLifecycleChainToActive(): void
    {
        $testCode = 'AGT-CHAIN-' . rand(1000, 9999);
        $agent = $this->registryService->createAgent([
            'name'         => 'Chain Transition Agent',
            'agent_code'   => $testCode,
        ], 1);

        $id = $agent['id'];

        // draft -> proposed
        $agent = $this->workforceService->transitionState($id, 'proposed', 'Submission', null, 1);
        $this->assertEquals('proposed', $agent['lifecycle_state']);

        // proposed -> under_review
        $agent = $this->workforceService->transitionState($id, 'under_review', 'Reviewing competence', null, 1);
        $this->assertEquals('under_review', $agent['lifecycle_state']);

        // under_review -> approved
        $agent = $this->workforceService->transitionState($id, 'approved', 'Approved by Board', 'BOARD-DEC-01', 1);
        $this->assertEquals('approved', $agent['lifecycle_state']);

        // approved -> active
        $agent = $this->workforceService->transitionState($id, 'active', 'Activated to workforce', 'MCE-ACT-01', 1);
        $this->assertEquals('active', $agent['lifecycle_state']);
        $this->assertNotNull($agent['activated_at']);
    }

    public function testAssignmentHistoryPreservation(): void
    {
        $testCode = 'AGT-ASGN-' . rand(1000, 9999);
        $agent = $this->registryService->createAgent([
            'name'       => 'Assignment Test Agent',
            'agent_code' => $testCode,
        ], 1);
        $id = $agent['id'];

        // Initial assignment to DIV-TECH (id 2)
        $asgn1 = $this->workforceService->assignAgent($id, 2, null, 'primary', 'Initial placement', null, 1);
        $this->assertEquals('active', $asgn1['status']);

        // Transfer to DIV-OPS (id 3)
        $asgn2 = $this->workforceService->assignAgent($id, 3, null, 'primary', 'Transferred to Ops', 'TRANS-01', 1);
        $this->assertEquals('active', $asgn2['status']);

        // History check: asgn1 must still exist and be marked 'transferred'
        $history = $this->workforceService->getAssignments($id);
        $this->assertCount(2, $history);

        $statuses = array_column($history, 'status');
        $this->assertContains('transferred', $statuses);
        $this->assertContains('active', $statuses);
    }

    public function testSkillAssignmentAndVerification(): void
    {
        $testCode = 'AGT-SKILL-' . rand(1000, 9999);
        $agent = $this->registryService->createAgent([
            'name'       => 'Skill Test Agent',
            'agent_code' => $testCode,
        ], 1);
        $id = $agent['id'];

        // Assign skill
        $assigned = $this->workforceService->assignSkill($id, 1, 'proficient', 'Unit test evidence', 1);
        $this->assertEquals('pending', $assigned['verification_status']);

        // Verify skill
        $verified = $this->workforceService->verifySkill($assigned['id'], 'verified', 'Auditor Bot', 1);
        $this->assertEquals('verified', $verified['verification_status']);
    }

    public function testPerformanceHistoryAndImmutability(): void
    {
        $testCode = 'AGT-PERF-' . rand(1000, 9999);
        $agent = $this->registryService->createAgent([
            'name'       => 'Performance Test Agent',
            'agent_code' => $testCode,
        ], 1);
        $id = $agent['id'];

        $event = $this->workforceService->recordPerformanceEvent($id, [
            'category'        => 'task_completion',
            'title'           => 'Prompt 19 Verification Run',
            'description'     => 'Completed automated workforce validation tests.',
            'evidence_status' => 'submitted',
        ], 1);

        $historyId = $event['id'];

        // Amend performance record
        $amended = $this->workforceService->amendPerformanceRecord($historyId, [
            'title'           => 'Prompt 19 Verification Run (Audited)',
            'evidence_status' => 'verified',
        ], 'Formal audit check', 1);

        $this->assertNotEquals($historyId, $amended['id']);

        // Verify original record was NOT deleted, but superseded
        $db = \Config\Database::connect();
        $original = $db->table('ai_agent_performance_history')->where('id', $historyId)->get()->getRowArray();
        $this->assertNotNull($original);
        $this->assertEquals($amended['id'], $original['superseded_by_id']);
    }
}
