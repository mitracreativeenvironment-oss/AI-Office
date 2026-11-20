<?php

namespace App\Services\Runtime;

use Config\Database;
use CodeIgniter\Database\BaseConnection;
use App\Services\Runtime\ExecutionAuthorizationService;
use App\Services\Runtime\HermesRuntimeAdapter;
use App\Services\Runtime\NineRouterGatewayAdapter;
use App\Services\TaskManagementService;
use App\Services\TaskReviewService;

class ExecutionDispatchService
{
    protected BaseConnection $db;
    protected ExecutionAuthorizationService $authService;
    protected HermesRuntimeAdapter $hermesAdapter;
    protected NineRouterGatewayAdapter $nineRouterAdapter;
    protected TaskManagementService $taskService;
    protected TaskReviewService $reviewService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->authService = new ExecutionAuthorizationService();
        $this->hermesAdapter = new HermesRuntimeAdapter();
        $this->nineRouterAdapter = new NineRouterGatewayAdapter();
        $this->taskService = new TaskManagementService();
        $this->reviewService = new TaskReviewService();
    }

    /**
     * Dispatch authorized task execution to Hermes runtime.
     */
    public function dispatchTaskExecution(int $taskId, int $agentId, array $options = []): array
    {
        // 1. Authoritative authorization check
        $auth = $this->authService->authorizeExecution($taskId, $agentId);
        if (!$auth['authorized']) {
            throw new \RuntimeException("Execution authorization rejected: " . implode(' ', $auth['reasons']), 403);
        }

        $task = $this->taskService->getTask($taskId);
        $now = date('Y-m-d H:i:s');

        // 2. Idempotency validation
        $nonce = $options['idempotency_nonce'] ?? date('YmdHi');
        $idempotencyKey = hash('sha256', "task:{$taskId}:agent:{$agentId}:{$nonce}");

        $existingSession = $this->db->table('execution_sessions')
            ->where('idempotency_key', $idempotencyKey)
            ->whereIn('status', ['running', 'completed'])
            ->get()
            ->getRowArray();

        if ($existingSession) {
            return [
                'session'    => $existingSession,
                'is_replay'  => true,
                'message'    => 'Idempotent request: returning existing execution session.',
            ];
        }

        // 3. Resolve runtime & routing profiles
        $runtimeProfile = $this->db->table('runtime_profiles')
            ->where('is_active', 1)
            ->orderBy('id', 'ASC')
            ->get()
            ->getRowArray();

        $routingProfile = $this->db->table('model_routing_profiles')
            ->where('is_active', 1)
            ->orderBy('id', 'ASC')
            ->get()
            ->getRowArray();

        $environment = $this->db->table('runtime_environments')
            ->where('is_active', 1)
            ->orderBy('id', 'ASC')
            ->get()
            ->getRowArray();

        // 4. Scoped Execution Context Assembly (Privacy & Principle of Least Privilege)
        $contextPayload = [
            'task_id'               => $taskId,
            'task_code'             => $task['task_code'],
            'title'                 => $task['title'],
            'objective'             => $task['objective'],
            'confidentiality_level' => $task['confidentiality_level'],
            'assigned_agent_id'     => $agentId,
            'model_route'           => $routingProfile['profile_code'] ?? 'ROUTE-FAST-REASONING',
            'allowed_tools'         => json_decode($runtimeProfile['tool_policy_json'] ?? '[]', true)['allowed_categories'] ?? ['research'],
            'dispatched_by'         => $options['actor_user_id'] ?? 'Task Control Plane',
            'assembled_at'          => $now,
        ];

        // 5. Create execution session
        $sessionUuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $sessionInsert = [
            'session_uuid'             => $sessionUuid,
            'task_id'                  => $taskId,
            'agent_id'                 => $agentId,
            'runtime_profile_id'       => $runtimeProfile['id'] ?? 1,
            'model_routing_profile_id' => $routingProfile['id'] ?? 1,
            'environment_id'           => $environment['id'] ?? 1,
            'hermes_session_ref'       => 'hrm-sess-' . substr($sessionUuid, 0, 8),
            'status'                   => 'dispatched',
            'dispatch_payload_json'    => json_encode($contextPayload),
            'total_tokens_used'        => 0,
            'total_cost_idr'           => 0.00,
            'hermes_result_summary'    => null,
            'dispatched_at'            => $now,
            'idempotency_key'          => $idempotencyKey,
            'created_at'               => $now,
            'updated_at'               => $now,
        ];

        $this->db->table('execution_sessions')->insert($sessionInsert);
        $sessionId = (int)$this->db->insertID();
        $sessionInsert['id'] = $sessionId;

        // 6. Transition task to in_progress if ready/accepted
        if (in_array($task['status'], ['ready', 'assigned', 'accepted'], true)) {
            $this->taskService->transitionTask($taskId, 'in_progress', ['reason' => 'Hermes execution session dispatched.']);
        }

        // 7. Dispatch to Hermes Runtime Adapter
        $dispatchResult = $this->hermesAdapter->dispatchSession($sessionInsert, $contextPayload);

        // 8. Auto-submit deliverable for review & update task state
        $deliverable = $this->reviewService->submitDeliverable($taskId, [
            'title'               => "Runtime Output: {$task['title']}",
            'deliverable_type'    => 'source_code',
            'description'         => $dispatchResult['result_summary'] ?? 'Generated output by Hermes runtime agent execution.',
            'artifact_uri'        => "hermes://sessions/{$sessionUuid}/deliverables/output.patch",
        ], $agentId);

        // 9. Update task execution attempts tracking
        $this->db->table('task_execution_attempts')->insert([
            'task_id'           => $taskId,
            'agent_id'          => $agentId,
            'attempt_number'    => 1,
            'status'            => 'completed',
            'context_summary'   => "Execution via Hermes. Session: {$sessionUuid}",
            'execution_adapter' => $environment['runtime_type'] ?? 'hermes_simulation',
            'model_route_key'   => $routingProfile['profile_code'] ?? 'ROUTE-FAST-REASONING',
            'started_at'        => $now,
            'ended_at'          => date('Y-m-d H:i:s'),
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);

        return [
            'session_id'      => $sessionId,
            'session_uuid'    => $sessionUuid,
            'task_id'         => $taskId,
            'agent_id'        => $agentId,
            'status'          => 'completed',
            'execution_mode'  => $dispatchResult['mode'] ?? 'simulated',
            'tokens_used'     => $dispatchResult['tokens_used'] ?? 0,
            'deliverable_id'  => $deliverable['id'] ?? null,
            'result_summary'  => $dispatchResult['result_summary'] ?? '',
            'dispatched_at'   => $now,
        ];
    }

    public function listSessions(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $builder = $this->db->table('execution_sessions s')
            ->select('s.*, 
                      t.task_code, t.title as task_title,
                      ag.name as agent_name, ag.agent_code,
                      rp.name as runtime_profile_name,
                      mrp.profile_code as model_route_code, mrp.primary_model')
            ->join('workforce_tasks t', 't.id = s.task_id')
            ->join('ai_agent_registry ag', 'ag.id = s.agent_id')
            ->join('runtime_profiles rp', 'rp.id = s.runtime_profile_id')
            ->join('model_routing_profiles mrp', 'mrp.id = s.model_routing_profile_id');

        if (!empty($filters['task_id'])) {
            $builder->where('s.task_id', (int)$filters['task_id']);
        }
        if (!empty($filters['agent_id'])) {
            $builder->where('s.agent_id', (int)$filters['agent_id']);
        }
        if (!empty($filters['status'])) {
            $builder->where('s.status', $filters['status']);
        }

        $total = $builder->countAllResults(false);
        $items = $builder->orderBy('s.id', 'DESC')->limit($limit, $offset)->get()->getResultArray();

        return ['items' => $items, 'total' => $total];
    }

    public function getSession(int $id): ?array
    {
        $session = $this->db->table('execution_sessions s')
            ->select('s.*, 
                      t.task_code, t.title as task_title,
                      ag.name as agent_name, ag.agent_code,
                      rp.name as runtime_profile_name,
                      mrp.profile_code as model_route_code, mrp.primary_model,
                      env.name as environment_name, env.base_url as environment_url')
            ->join('workforce_tasks t', 't.id = s.task_id')
            ->join('ai_agent_registry ag', 'ag.id = s.agent_id')
            ->join('runtime_profiles rp', 'rp.id = s.runtime_profile_id')
            ->join('model_routing_profiles mrp', 'mrp.id = s.model_routing_profile_id')
            ->join('runtime_environments env', 'env.id = s.environment_id')
            ->where('s.id', $id)
            ->get()
            ->getRowArray();

        if (!$session) {
            return null;
        }

        $session['events'] = $this->db->table('runtime_execution_events')
            ->where('session_id', $id)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $session['tools'] = $this->db->table('runtime_tool_invocations')
            ->where('session_id', $id)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        return $session;
    }

    public function cancelSession(int $id, string $reason = 'Cancelled by Operator'): bool
    {
        return $this->hermesAdapter->cancelSession($id, $reason);
    }
}
