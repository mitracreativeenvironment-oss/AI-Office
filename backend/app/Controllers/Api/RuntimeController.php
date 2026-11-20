<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use App\Services\Runtime\HermesRuntimeAdapter;
use App\Services\Runtime\NineRouterGatewayAdapter;
use App\Services\Runtime\ExecutionAuthorizationService;
use App\Services\Runtime\ExecutionDispatchService;
use App\Services\Runtime\ToolAuthorizationService;
use App\Services\Runtime\AgentProvisioningService;
use App\Services\Runtime\RuntimeHealthService;

class RuntimeController extends BaseApiController
{
    protected HermesRuntimeAdapter $hermesAdapter;
    protected NineRouterGatewayAdapter $nineRouterAdapter;
    protected ExecutionAuthorizationService $authService;
    protected ExecutionDispatchService $dispatchService;
    protected ToolAuthorizationService $toolService;
    protected AgentProvisioningService $provisioningService;
    protected RuntimeHealthService $healthService;

    public function __construct()
    {
        $this->hermesAdapter = new HermesRuntimeAdapter();
        $this->nineRouterAdapter = new NineRouterGatewayAdapter();
        $this->authService = new ExecutionAuthorizationService();
        $this->dispatchService = new ExecutionDispatchService();
        $this->toolService = new ToolAuthorizationService();
        $this->provisioningService = new AgentProvisioningService();
        $this->healthService = new RuntimeHealthService();
    }

    // ------------------------------------------------------------------------
    // HEALTH & INFRASTRUCTURE
    // ------------------------------------------------------------------------

    public function health()
    {
        try {
            $data = $this->healthService->getSystemHealth();
            return $this->respondSuccess($data, 'Runtime infrastructure health retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function environments()
    {
        try {
            $db = \Config\Database::connect();
            $envs = $db->table('runtime_environments')->orderBy('id', 'ASC')->get()->getResultArray();
            return $this->respondSuccess($envs, 'Runtime environments retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function profiles()
    {
        try {
            $db = \Config\Database::connect();
            $profiles = $db->table('runtime_profiles rp')
                ->select('rp.*, mrp.profile_code as routing_profile_code, mrp.primary_model')
                ->join('model_routing_profiles mrp', 'mrp.id = rp.model_routing_profile_id')
                ->orderBy('rp.id', 'ASC')
                ->get()
                ->getResultArray();
            return $this->respondSuccess($profiles, 'Agent runtime profiles retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ------------------------------------------------------------------------
    // AGENT PROVISIONING & READINESS
    // ------------------------------------------------------------------------

    public function getAgentRuntime($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Agent ID required.');
        }

        try {
            $db = \Config\Database::connect();
            $mapping = $db->table('ai_agent_runtime_mappings')
                ->where('agent_id', (int)$id)
                ->get()
                ->getRowArray();

            if (!$mapping) {
                return $this->respondNotFound("No runtime mapping found for Agent #{$id}.");
            }

            return $this->respondSuccess($mapping, 'Agent runtime mapping retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function provisionAgent($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Agent ID required.');
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        try {
            $actorUserId = $this->request->authenticatedUser->id ?? null;
            $res = $this->provisioningService->provisionAgent((int)$id, array_merge($json, ['actor_user_id' => $actorUserId]));
            return $this->respondSuccess($res, 'Agent successfully provisioned to Hermes runtime.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function suspendAgent($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Agent ID required.');
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $reason = $json['reason'] ?? 'Suspended by operator';

        try {
            $ok = $this->provisioningService->suspendRuntime((int)$id, $reason);
            if (!$ok) {
                return $this->respondNotFound("Agent #{$id} runtime mapping not found.");
            }
            return $this->respondSuccess(['suspended' => true, 'agent_id' => (int)$id], 'Agent runtime suspended.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function validateAgent($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Agent ID required.');
        }

        try {
            $res = $this->provisioningService->validateReadiness((int)$id);
            return $this->respondSuccess($res, 'Agent runtime readiness evaluated.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ------------------------------------------------------------------------
    // EXECUTION DISPATCH & SESSIONS
    // ------------------------------------------------------------------------

    public function listExecutions()
    {
        $filters = [
            'task_id'  => $this->request->getGet('task_id'),
            'agent_id' => $this->request->getGet('agent_id'),
            'status'   => $this->request->getGet('status'),
        ];
        $limit = max(1, min(100, (int)($this->request->getGet('limit') ?? 20)));
        $offset = max(0, (int)($this->request->getGet('offset') ?? 0));

        try {
            $res = $this->dispatchService->listSessions($filters, $limit, $offset);
            return $this->respondSuccess($res['items'], 'Execution sessions retrieved.', ResponseInterface::HTTP_OK, [
                'total'  => $res['total'],
                'limit'  => $limit,
                'offset' => $offset,
            ]);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getExecution($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Execution Session ID required.');
        }

        try {
            $session = $this->dispatchService->getSession((int)$id);
            if (!$session) {
                return $this->respondNotFound("Execution session #{$id} not found.");
            }
            return $this->respondSuccess($session, 'Execution session details retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function dispatchExecution()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $taskId = (int)($json['task_id'] ?? 0);
        $agentId = (int)($json['agent_id'] ?? 0);

        if (!$taskId || !$agentId) {
            return $this->respondError('task_id and agent_id are required.', ResponseInterface::HTTP_BAD_REQUEST);
        }

        try {
            $actorUserId = $this->request->authenticatedUser->id ?? null;
            $res = $this->dispatchService->dispatchTaskExecution($taskId, $agentId, array_merge($json, ['actor_user_id' => $actorUserId]));
            return $this->respondSuccess($res, 'Task execution dispatched and processed successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_FORBIDDEN);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function cancelExecution($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Execution Session ID required.');
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $reason = $json['reason'] ?? 'Cancelled by operator';

        try {
            $ok = $this->dispatchService->cancelSession((int)$id, $reason);
            if (!$ok) {
                return $this->respondError('Session cannot be cancelled (already completed, cancelled, or not found).', ResponseInterface::HTTP_BAD_REQUEST);
            }
            return $this->respondSuccess(['cancelled' => true, 'session_id' => (int)$id], 'Execution session cancelled.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ------------------------------------------------------------------------
    // CONTROLLED TOOLS REGISTRY
    // ------------------------------------------------------------------------

    public function listTools()
    {
        $filters = [
            'category'   => $this->request->getGet('category'),
            'risk_level' => $this->request->getGet('risk_level'),
            'is_active'  => $this->request->getGet('is_active'),
        ];

        try {
            $tools = $this->toolService->listTools($filters);
            return $this->respondSuccess($tools, 'Runtime tools retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createTool()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        try {
            $tool = $this->toolService->createTool($json);
            return $this->respondSuccess($tool, 'Tool registered successfully in Runtime Tool Registry.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function invokeTool()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $sessionId = (int)($json['session_id'] ?? 0);
        $toolId = (int)($json['tool_id'] ?? 0);
        $payload = $json['input_payload'] ?? [];

        if (!$sessionId || !$toolId) {
            return $this->respondError('session_id and tool_id are required.', ResponseInterface::HTTP_BAD_REQUEST);
        }

        try {
            $res = $this->toolService->authorizeAndInvokeTool($sessionId, $toolId, $payload);
            return $this->respondSuccess($res, 'Tool authorized and executed within sandbox.');
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_FORBIDDEN);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ------------------------------------------------------------------------
    // 9ROUTER MODEL GATEWAY (SOLE GATEWAY)
    // ------------------------------------------------------------------------

    public function gatewayStatus()
    {
        try {
            $status = $this->nineRouterAdapter->checkHealth();
            return $this->respondSuccess($status, '9Router model gateway status retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function gatewayRoutes()
    {
        try {
            $routes = $this->nineRouterAdapter->listRoutes();
            return $this->respondSuccess($routes, '9Router model routing profiles retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function gatewayUsage()
    {
        try {
            $usage = $this->nineRouterAdapter->getUsageSummary();
            return $this->respondSuccess($usage, '9Router model usage accounting retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function gatewayComplete()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        try {
            $res = $this->nineRouterAdapter->requestCompletion($json);
            return $this->respondSuccess($res, 'Model completion routed through 9Router.');
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_GATEWAY);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
