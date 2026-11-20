<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class TaskDelegationService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function listDelegations(array $filters = []): array
    {
        $builder = $this->db->table('task_delegations d')
            ->select('d.*, 
                      t.task_code, t.title as task_title,
                      del_ag.name as delegating_agent_name, del_ag.agent_code as delegating_agent_code,
                      rec_ag.name as delegated_agent_name, rec_ag.agent_code as delegated_agent_code')
            ->join('workforce_tasks t', 't.id = d.task_id')
            ->join('ai_agent_registry del_ag', 'del_ag.id = d.delegating_agent_id')
            ->join('ai_agent_registry rec_ag', 'rec_ag.id = d.delegated_agent_id');

        if (!empty($filters['task_id'])) {
            $builder->where('d.task_id', (int)$filters['task_id']);
        }
        if (!empty($filters['status'])) {
            $builder->where('d.status', $filters['status']);
        }

        return $builder->orderBy('d.id', 'DESC')->get()->getResultArray();
    }

    public function getDelegation(int $id): ?array
    {
        return $this->db->table('task_delegations d')
            ->select('d.*, 
                      t.task_code, t.title as task_title,
                      del_ag.name as delegating_agent_name, del_ag.agent_code as delegating_agent_code,
                      rec_ag.name as delegated_agent_name, rec_ag.agent_code as delegated_agent_code')
            ->join('workforce_tasks t', 't.id = d.task_id')
            ->join('ai_agent_registry del_ag', 'del_ag.id = d.delegating_agent_id')
            ->join('ai_agent_registry rec_ag', 'rec_ag.id = d.delegated_agent_id')
            ->where('d.id', $id)
            ->get()
            ->getRowArray() ?: null;
    }

    public function createDelegation(array $data, ?int $actorUserId = null): array
    {
        $taskId = (int)($data['task_id'] ?? 0);
        $delegatingAgentId = (int)($data['delegating_agent_id'] ?? 0);
        $delegatedAgentId = (int)($data['delegated_agent_id'] ?? 0);
        $scope = trim($data['delegated_scope'] ?? '');

        if (!$taskId || !$delegatingAgentId || !$delegatedAgentId || empty($scope)) {
            throw new \InvalidArgumentException('Task ID, delegating agent, delegated agent, and delegation scope are required.', 422);
        }

        if ($delegatingAgentId === $delegatedAgentId) {
            throw new \InvalidArgumentException('An Agent cannot delegate a task to itself.', 400);
        }

        // Verify task exists
        $task = $this->db->table('workforce_tasks')->where('id', $taskId)->get()->getRowArray();
        if (!$task) {
            throw new \InvalidArgumentException("Task #{$taskId} not found.", 404);
        }

        // Prevent circular delegation
        $isCircular = $this->db->table('task_delegations')
            ->where('task_id', $taskId)
            ->where('delegating_agent_id', $delegatedAgentId)
            ->where('delegated_agent_id', $delegatingAgentId)
            ->where('status', 'accepted')
            ->countAllResults() > 0;

        if ($isCircular) {
            throw new \RuntimeException("Circular delegation detected: Agent #{$delegatedAgentId} has already delegated this task back to Agent #{$delegatingAgentId}.", 409);
        }

        // Verify delegator authority: must be accountable agent, assigned lead, or agent holding accepted delegation
        $hasAuthority = ($task['accountable_agent_id'] == $delegatingAgentId) ||
            ($this->db->table('task_assignments')
                ->where('task_id', $taskId)
                ->where('agent_id', $delegatingAgentId)
                ->where('assignment_role', 'lead_agent')
                ->countAllResults() > 0) ||
            ($this->db->table('task_delegations')
                ->where('task_id', $taskId)
                ->where('delegated_agent_id', $delegatingAgentId)
                ->where('status', 'accepted')
                ->countAllResults() > 0);

        if (!$hasAuthority) {
            throw new \RuntimeException("Delegating Agent #{$delegatingAgentId} lacks authority to delegate Task #{$taskId}.", 403);
        }

        // Verify delegated agent is active
        $targetAgent = $this->db->table('ai_agent_registry')->where('id', $delegatedAgentId)->get()->getRowArray();
        if (!$targetAgent || $targetAgent['lifecycle_state'] !== 'active') {
            throw new \InvalidArgumentException("Delegated Agent #{$delegatedAgentId} is not active in the registry.", 400);
        }

        $now = date('Y-m-d H:i:s');
        $insert = [
            'task_id'             => $taskId,
            'delegating_agent_id' => $delegatingAgentId,
            'delegated_agent_id'  => $delegatedAgentId,
            'delegated_scope'     => $scope,
            'expected_output'     => $data['expected_output'] ?? null,
            'authority_limit'     => $data['authority_limit'] ?? 'routine',
            'deadline'            => $data['deadline'] ?? null,
            'status'              => 'proposed',
            'rejection_reason'    => null,
            'accepted_at'         => null,
            'created_at'          => $now,
            'updated_at'          => $now,
        ];

        $this->db->table('task_delegations')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();

        // Audit
        $this->db->table('task_events_audit')->insert([
            'task_id'     => $taskId,
            'event_type'  => 'task_delegated',
            'from_status' => $task['status'],
            'to_status'   => $task['status'],
            'actor_type'  => $actorUserId ? 'human' : 'agent',
            'actor_id'    => $actorUserId ?? $delegatingAgentId,
            'actor_name'  => "Agent #{$delegatingAgentId}",
            'details'     => "Sub-scope '{$scope}' formally delegated to Agent #{$delegatedAgentId}.",
            'created_at'  => $now,
        ]);

        return $insert;
    }

    public function acceptDelegation(int $id, string $agentIdentity): array
    {
        $deleg = $this->getDelegation($id);
        if (!$deleg) {
            throw new \InvalidArgumentException("Delegation #{$id} not found.", 404);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('task_delegations')->where('id', $id)->update([
            'status'      => 'accepted',
            'accepted_at' => $now,
            'updated_at'  => $now,
        ]);

        // Add or ensure task assignment exists
        $assignExists = $this->db->table('task_assignments')
            ->where('task_id', $deleg['task_id'])
            ->where('agent_id', $deleg['delegated_agent_id'])
            ->countAllResults();

        if ($assignExists === 0) {
            $this->db->table('task_assignments')->insert([
                'task_id'              => $deleg['task_id'],
                'agent_id'             => $deleg['delegated_agent_id'],
                'assignment_role'      => 'collaborator',
                'responsibility_scope' => "Delegated Scope: {$deleg['delegated_scope']}",
                'assigned_by_agent_id' => $deleg['delegating_agent_id'],
                'acceptance_status'    => 'accepted',
                'accepted_at'          => $now,
                'status'               => 'active',
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);
        }

        // Audit
        $this->db->table('task_events_audit')->insert([
            'task_id'     => $deleg['task_id'],
            'event_type'  => 'delegation_accepted',
            'from_status' => null,
            'to_status'   => null,
            'actor_type'  => 'agent',
            'actor_id'    => $deleg['delegated_agent_id'],
            'actor_name'  => $agentIdentity,
            'details'     => "Delegation #{$id} accepted by incoming agent {$agentIdentity}.",
            'created_at'  => $now,
        ]);

        return $this->getDelegation($id);
    }

    public function rejectDelegation(int $id, string $reason): array
    {
        $deleg = $this->getDelegation($id);
        if (!$deleg) {
            throw new \InvalidArgumentException("Delegation #{$id} not found.", 404);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('task_delegations')->where('id', $id)->update([
            'status'           => 'rejected',
            'rejection_reason' => $reason,
            'updated_at'       => $now,
        ]);

        return $this->getDelegation($id);
    }
}

