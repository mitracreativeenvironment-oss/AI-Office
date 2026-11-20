<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\Runtime\HermesRuntimeAdapter;
use App\Services\Runtime\NineRouterGatewayAdapter;
use App\Services\Runtime\ExecutionAuthorizationService;
use App\Services\Runtime\ExecutionDispatchService;
use App\Services\Runtime\ToolAuthorizationService;
use App\Services\Runtime\AgentProvisioningService;
use App\Services\Runtime\RuntimeHealthService;
use App\Services\TaskManagementService;
use App\Controllers\Api\RuntimeController;

/**
 * @internal
 */
final class RuntimeIntegrationTest extends CIUnitTestCase
{
    protected HermesRuntimeAdapter $hermesAdapter;
    protected NineRouterGatewayAdapter $nineRouterAdapter;
    protected ExecutionAuthorizationService $authService;
    protected ExecutionDispatchService $dispatchService;
    protected ToolAuthorizationService $toolService;
    protected AgentProvisioningService $provisioningService;
    protected RuntimeHealthService $healthService;
    protected TaskManagementService $taskService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hermesAdapter = new HermesRuntimeAdapter();
        $this->nineRouterAdapter = new NineRouterGatewayAdapter();
        $this->authService = new ExecutionAuthorizationService();
        $this->dispatchService = new ExecutionDispatchService();
        $this->toolService = new ToolAuthorizationService();
        $this->provisioningService = new AgentProvisioningService();
        $this->healthService = new RuntimeHealthService();
        $this->taskService = new TaskManagementService();

        // Clean up any lingering transient leaves created by prior scheduling tests so Agent 2 is active on duty
        \Config\Database::connect()->table('workforce_agent_availability')
            ->where('agent_id', 2)
            ->where('availability_type', 'planned_leave')
            ->delete();
    }

    public function testServicesAndControllerInstantiate(): void
    {
        $this->assertInstanceOf(HermesRuntimeAdapter::class, $this->hermesAdapter);
        $this->assertInstanceOf(NineRouterGatewayAdapter::class, $this->nineRouterAdapter);
        $this->assertInstanceOf(ExecutionAuthorizationService::class, $this->authService);
        $this->assertInstanceOf(ExecutionDispatchService::class, $this->dispatchService);
        $this->assertInstanceOf(ToolAuthorizationService::class, $this->toolService);
        $this->assertInstanceOf(AgentProvisioningService::class, $this->provisioningService);
        $this->assertInstanceOf(RuntimeHealthService::class, $this->healthService);
        $this->assertInstanceOf(RuntimeController::class, new RuntimeController());
    }

    public function testHermesHealthCheckAndModeDetection(): void
    {
        $health = $this->hermesAdapter->checkHealth();
        $this->assertIsArray($health);
        $this->assertEquals('hermes_runtime', $health['component']);
        $this->assertContains($health['status'], ['connected', 'simulated']);
        $this->assertArrayHasKey('latency_ms', $health);
        $this->assertArrayHasKey('mode', $health);
    }

    public function testNineRouterGatewayHealthAndRoutingProfiles(): void
    {
        $health = $this->nineRouterAdapter->checkHealth();
        $this->assertIsArray($health);
        $this->assertEquals('9router_gateway', $health['component']);
        $this->assertTrue($health['sole_gateway_enforced']);
        $this->assertEquals('BLOCKED', $health['direct_bypass_policy']);

        $routes = $this->nineRouterAdapter->listRoutes();
        $this->assertIsArray($routes);
        $this->assertGreaterThanOrEqual(1, count($routes));

        $codes = array_column($routes, 'profile_code');
        $this->assertContains('ROUTE-FAST-REASONING', $codes);
        $this->assertContains('ROUTE-DEEP-RESEARCH', $codes);
    }

    public function testNineRouterCompletionAndUsageAccounting(): void
    {
        $res = $this->nineRouterAdapter->requestCompletion([
            'agent_id'     => 2,
            'task_id'      => 2,
            'profile_code' => 'ROUTE-FAST-REASONING',
            'prompt'       => 'Validate system runtime health status.',
        ]);

        $this->assertIsArray($res);
        $this->assertEquals('success', $res['status']);
        $this->assertArrayHasKey('correlation_id', $res);
        $this->assertGreaterThan(0, $res['total_tokens']);
        $this->assertGreaterThanOrEqual(0, $res['estimated_cost_idr']);

        $usage = $this->nineRouterAdapter->getUsageSummary();
        $this->assertGreaterThanOrEqual(1, $usage['total_requests']);
        $this->assertGreaterThan(0, $usage['total_tokens']);
    }

    public function testExecutionAuthorization15Preconditions(): void
    {
        // Valid task & agent (TSK-2026-002, Tech Head AI #2)
        $auth = $this->authService->authorizeExecution(2, 2);
        $this->assertIsArray($auth);
        $this->assertTrue($auth['authorized']);
        $this->assertEmpty($auth['reasons']);
        $this->assertTrue($auth['preconditions']['task_exists']);
        $this->assertTrue($auth['preconditions']['agent_active']);
        $this->assertTrue($auth['preconditions']['task_assigned']);

        // Inactive/Nonexistent agent
        $invalidAgentAuth = $this->authService->authorizeExecution(2, 9999);
        $this->assertFalse($invalidAgentAuth['authorized']);
        $this->assertNotEmpty($invalidAgentAuth['reasons']);

        // Nonexistent task
        $invalidTaskAuth = $this->authService->authorizeExecution(99999, 2);
        $this->assertFalse($invalidTaskAuth['authorized']);
    }

    public function testExecutionDispatchAndIdempotency(): void
    {
        $testCode = 'TSK-RT-' . rand(1000, 9999);
        $task = $this->taskService->createTask([
            'task_code'            => $testCode,
            'title'                => 'Runtime Dispatch Verification Task',
            'unit_id'              => 2,
            'accountable_agent_id' => 2,
        ], 1);

        $nonce = 'nonce-' . uniqid();
        $dispatch1 = $this->dispatchService->dispatchTaskExecution((int)$task['id'], 2, [
            'idempotency_nonce' => $nonce,
        ]);

        $this->assertIsArray($dispatch1);
        $this->assertEquals('completed', $dispatch1['status']);
        $this->assertNotEmpty($dispatch1['session_uuid']);
        $this->assertNotNull($dispatch1['deliverable_id']);

        // Second dispatch with identical idempotency nonce must return existing session
        $dispatch2 = $this->dispatchService->dispatchTaskExecution((int)$task['id'], 2, [
            'idempotency_nonce' => $nonce,
        ]);

        $this->assertTrue($dispatch2['is_replay'] ?? false);
        $this->assertEquals($dispatch1['session_id'], $dispatch2['session']['id']);
    }

    public function testToolRegistryAndSanitization(): void
    {
        $tools = $this->toolService->listTools();
        $this->assertIsArray($tools);
        $this->assertGreaterThanOrEqual(1, count($tools));

        // Create tool
        $code = 'TOOL-TEST-' . rand(100, 999);
        $created = $this->toolService->createTool([
            'tool_code'   => $code,
            'name'        => 'Test Verification Tool',
            'category'    => 'analysis',
            'description' => 'Test tool for unit testing.',
        ]);

        $this->assertNotNull($created);
        $this->assertEquals($code, $created['tool_code']);

        // Authorize and invoke
        $session = $this->dispatchService->getSession(1);
        $this->assertNotNull($session);

        $invoked = $this->toolService->authorizeAndInvokeTool((int)$session['id'], (int)$created['id'], [
            'query' => '<script>alert("test")</script>clean_query',
        ]);

        $this->assertEquals('completed', $invoked['status']);
        $this->assertEquals($code, $invoked['tool_code']);
    }

    public function testCriticalToolHumanApprovalEnforcement(): void
    {
        $critTool = $this->toolService->createTool([
            'tool_code'               => 'TOOL-CRIT-' . rand(100, 999),
            'name'                    => 'Critical Destructive Tool',
            'category'                => 'system',
            'risk_level'              => 'critical',
            'requires_human_approval' => 1,
        ]);

        $threw = false;
        try {
            $this->toolService->authorizeAndInvokeTool(1, (int)$critTool['id'], [
                'param' => 'unapproved_payload',
            ]);
        } catch (\RuntimeException $e) {
            $threw = true;
            $this->assertStringContainsString('Requires human approval', $e->getMessage());
        }

        $this->assertTrue($threw, 'Critical tool without approval token must be rejected.');
    }

    public function testAgentProvisioningWorkflow(): void
    {
        $provision = $this->provisioningService->provisionAgent(6, [
            'runtime_profile_reference' => 'PROF-DEV-CODER',
        ]);

        $this->assertIsArray($provision);
        $this->assertEquals('ready', $provision['integration_status']);
        $this->assertEquals('PROF-DEV-CODER', $provision['runtime_profile']);

        $readiness = $this->provisioningService->validateReadiness(6);
        $this->assertTrue($readiness['ready']);
        $this->assertEquals('ready', $readiness['status']);

        // Suspend
        $suspended = $this->provisioningService->suspendRuntime(6, 'Testing suspension');
        $this->assertTrue($suspended);

        $postReadiness = $this->provisioningService->validateReadiness(6);
        $this->assertFalse($postReadiness['ready']);

        // Re-provision
        $this->provisioningService->provisionAgent(6, [
            'runtime_profile_reference' => 'PROF-DEV-CODER',
        ]);
    }

    public function testRuntimeHealthSnapshotAggregation(): void
    {
        $health = $this->healthService->getSystemHealth();
        $this->assertIsArray($health);
        $this->assertArrayHasKey('overall_status', $health);
        $this->assertArrayHasKey('hermes_runtime', $health);
        $this->assertArrayHasKey('nine_router_gateway', $health);
        $this->assertArrayHasKey('sessions', $health);
        $this->assertTrue($health['sole_gateway_enforced']);
    }
}
