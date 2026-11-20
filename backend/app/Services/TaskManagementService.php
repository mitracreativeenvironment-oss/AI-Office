<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class TaskManagementService
{
    protected BaseConnection $db;

    // 17-state validated lifecycle transition matrix
    protected const ALLOWED_TRANSITIONS = [
        'draft'                 => ['proposed', 'awaiting_approval', 'ready', 'cancelled'],
        'proposed'              => ['awaiting_approval', 'ready', 'draft', 'cancelled'],
        'awaiting_approval'     => ['approved', 'draft', 'cancelled'],
        'approved'              => ['ready', 'assigned', 'cancelled'],
        'ready'                 => ['assigned', 'in_progress', 'cancelled'],
        'assigned'              => ['awaiting_acceptance', 'accepted', 'in_progress', 'cancelled'],
        'awaiting_acceptance'   => ['accepted', 'assigned', 'cancelled'],
        'accepted'              => ['in_progress', 'blocked', 'cancelled'],
        'in_progress'           => ['under_review', 'blocked', 'suspended', 'cancelled'],
        'blocked'               => ['in_progress', 'suspended', 'cancelled'],
        'under_review'          => ['revision_required', 'verified', 'completed', 'in_progress'],
        'revision_required'     => ['in_progress', 'under_review', 'cancelled'],
        'verified'              => ['completed', 'reopened'],
        'completed'             => ['reopened'],
        'suspended'             => ['in_progress', 'ready', 'cancelled'],
        'cancelled'             => ['reopened', 'draft'],
        'reopened'              => ['in_progress', 'ready', 'assigned'],
    ];

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function listTasks(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $builder = $this->db->table('workforce_tasks t')
            ->select('t.*, u.name as unit_name, u.code as unit_code,
                      ag.name as accountable_agent_name, ag.agent_code as accountable_agent_code, ag.classification as accountable_agent_classification,
                      sk.name as required_skill_name, sk.code as required_skill_code')
            ->join('organization_units u', 'u.id = t.unit_id')
            ->join('ai_agent_registry ag', 'ag.id = t.accountable_agent_id')
            ->join('skill_catalogue sk', 'sk.id = t.required_skill_id', 'left')
            ->where('t.deleted_at IS NULL');

        if (!empty($filters['unit_id'])) {
            $builder->where('t.unit_id', (int)$filters['unit_id']);
        }
        if (!empty($filters['status'])) {
            $builder->where('t.status', $filters['status']);
        }
        if (!empty($filters['priority'])) {
            $builder->where('t.priority', $filters['priority']);
        }
        if (!empty($filters['category'])) {
            $builder->where('t.category', $filters['category']);
        }
        if (!empty($filters['accountable_agent_id'])) {
            $builder->where('t.accountable_agent_id', (int)$filters['accountable_agent_id']);
        }
        if (!empty($filters['search'])) {
            $term = trim($filters['search']);
            $builder->groupStart()
                ->like('t.title', $term)
                ->orLike('t.task_code', $term)
                ->orLike('t.description', $term)
                ->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $builder->orderBy('t.id', 'DESC');
        $builder->limit($limit, $offset);
        $items = $builder->get()->getResultArray();

        foreach ($items as &$item) {
            $item['id'] = (int)$item['id'];
            $item['subtasks_count'] = $this->db->table('workforce_tasks')
                ->where('parent_task_id', $item['id'])
                ->where('deleted_at IS NULL')
                ->countAllResults();

            $item['deliverables_count'] = $this->db->table('task_deliverables')
                ->where('task_id', $item['id'])
                ->countAllResults();

            $item['collaborators_count'] = $this->db->table('task_assignments')
                ->where('task_id', $item['id'])
                ->where('status', 'active')
                ->countAllResults();
        }

        return ['items' => $items, 'total' => $total];
    }

    public function getTask(int $id): ?array
    {
        $task = $this->db->table('workforce_tasks t')
            ->select('t.*, u.name as unit_name, u.code as unit_code,
                      ag.name as accountable_agent_name, ag.agent_code as accountable_agent_code, ag.classification as accountable_agent_classification,
                      sk.name as required_skill_name, sk.code as required_skill_code')
            ->join('organization_units u', 'u.id = t.unit_id')
            ->join('ai_agent_registry ag', 'ag.id = t.accountable_agent_id')
            ->join('skill_catalogue sk', 'sk.id = t.required_skill_id', 'left')
            ->where('t.id', $id)
            ->where('t.deleted_at IS NULL')
            ->get()
            ->getRowArray();

        if (!$task) return null;

        $task['id'] = (int)$task['id'];

        // Assignments
        $task['assignments'] = $this->db->table('task_assignments a')
            ->select('a.*, ag.name as agent_name, ag.agent_code, ag.classification')
            ->join('ai_agent_registry ag', 'ag.id = a.agent_id')
            ->where('a.task_id', $id)
            ->orderBy('a.id', 'ASC')
            ->get()
            ->getResultArray();

        // Subtasks
        $task['subtasks'] = $this->db->table('workforce_tasks t')
            ->select('t.*, ag.name as accountable_agent_name, ag.agent_code')
            ->join('ai_agent_registry ag', 'ag.id = t.accountable_agent_id')
            ->where('t.parent_task_id', $id)
            ->where('t.deleted_at IS NULL')
            ->get()
            ->getResultArray();

        // Dependencies
        $task['dependencies'] = $this->db->table('task_dependencies d')
            ->select('d.*, t2.task_code as upstream_code, t2.title as upstream_title, t2.status as upstream_status')
            ->join('workforce_tasks t2', 't2.id = d.depends_on_task_id')
            ->where('d.task_id', $id)
            ->get()
            ->getResultArray();

        // Deliverables
        $task['deliverables'] = $this->db->table('task_deliverables del')
            ->select('del.*, ag.name as submitting_agent_name, ag.agent_code')
            ->join('ai_agent_registry ag', 'ag.id = del.submitting_agent_id')
            ->where('del.task_id', $id)
            ->orderBy('del.version', 'DESC')
            ->get()
            ->getResultArray();

        // Blockers
        $task['blockers'] = $this->db->table('task_blockers')
            ->where('task_id', $id)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        // Collaboration group
        $task['collaboration_group'] = $this->db->table('task_collaboration_groups')
            ->where('task_id', $id)
            ->where('status', 'active')
            ->get()
            ->getRowArray() ?: null;

        // Audit events
        $task['audit_events'] = $this->db->table('task_events_audit')
            ->where('task_id', $id)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        return $task;
    }

    public function createTask(array $data, ?int $actorUserId = null, ?int $actorAgentId = null): array
    {
        $title = trim($data['title'] ?? '');
        if (empty($title)) {
            throw new \InvalidArgumentException('Task title is required.', 422);
        }

        $unitId = (int)($data['unit_id'] ?? 1);
        $accountableAgentId = (int)($data['accountable_agent_id'] ?? 1);

        // Verify accountable agent exists and is active
        $agent = $this->db->table('ai_agent_registry')->where('id', $accountableAgentId)->get()->getRowArray();
        if (!$agent || $agent['lifecycle_state'] !== 'active') {
            throw new \InvalidArgumentException("Accountable Agent #{$accountableAgentId} is not active or not found in registry.", 400);
        }

        $now = date('Y-m-d H:i:s');
        $uuid = AgentRegistryService::generateUuid();
        $code = trim($data['task_code'] ?? 'TSK-' . date('Y') . '-' . rand(100, 999));

        $financialAmount = (float)($data['financial_amount'] ?? 0.00);
        $approvalLevel = $data['approval_level'] ?? 'routine';
        if ($financialAmount > 5000000.00) {
            $approvalLevel = 'A'; // Requires Owner approval under DoA Level A
        } elseif ($financialAmount > 1000000.00) {
            $approvalLevel = 'B'; // Requires Board approval under DoA Level B
        }

        $initialStatus = ($approvalLevel !== 'routine' && $financialAmount > 0) ? 'awaiting_approval' : ($data['status'] ?? 'draft');

        $insert = [
            'task_uuid'              => $uuid,
            'task_code'              => $code,
            'title'                  => $title,
            'description'            => $data['description'] ?? null,
            'objective'              => $data['objective'] ?? null,
            'expected_outcome'       => $data['expected_outcome'] ?? null,
            'parent_task_id'         => !empty($data['parent_task_id']) ? (int)$data['parent_task_id'] : null,
            'unit_id'                => $unitId,
            'category'               => $data['category'] ?? 'operational',
            'priority'               => $data['priority'] ?? 'medium',
            'status'                 => $initialStatus,
            'origin_type'            => $data['origin_type'] ?? 'board_decision',
            'origin_reference_id'    => $data['origin_reference_id'] ?? null,
            'is_binding_instruction' => !empty($data['is_binding_instruction']) ? 1 : 0,
            'accountable_agent_id'   => $accountableAgentId,
            'creator_user_id'        => $actorUserId,
            'creator_agent_id'       => $actorAgentId,
            'required_skill_id'      => !empty($data['required_skill_id']) ? (int)$data['required_skill_id'] : null,
            'min_skill_proficiency'  => $data['min_skill_proficiency'] ?? 'intermediate',
            'approval_level'         => $approvalLevel,
            'financial_amount'       => $financialAmount,
            'approved_by_user_id'    => null,
            'approved_at'            => null,
            'start_date'             => $data['start_date'] ?? date('Y-m-d'),
            'due_date'               => $data['due_date'] ?? date('Y-m-d', strtotime('+14 days')),
            'progress_percent'       => 0,
            'confidentiality_level'  => $data['confidentiality_level'] ?? 'internal',
            'created_at'             => $now,
            'updated_at'             => $now,
        ];

        $this->db->table('workforce_tasks')->insert($insert);
        $taskId = (int)$this->db->insertID();

        // Create primary assignment for accountable agent
        $this->db->table('task_assignments')->insert([
            'task_id'              => $taskId,
            'agent_id'             => $accountableAgentId,
            'assignment_role'      => 'lead_agent',
            'responsibility_scope' => 'Accountable lead for task lifecycle execution and deliverables.',
            'assigned_by_user_id'  => $actorUserId,
            'assigned_by_agent_id' => $actorAgentId,
            'acceptance_status'    => 'accepted',
            'accepted_at'          => $now,
            'status'               => 'active',
            'created_at'           => $now,
            'updated_at'           => $now,
        ]);

        // Audit
        $this->db->table('task_events_audit')->insert([
            'task_id'     => $taskId,
            'event_type'  => 'task_created',
            'from_status' => null,
            'to_status'   => $initialStatus,
            'actor_type'  => $actorUserId ? 'human' : 'agent',
            'actor_id'    => $actorUserId ?? $actorAgentId,
            'actor_name'  => $actorUserId ? "User #{$actorUserId}" : ($actorAgentId ? "Agent #{$actorAgentId}" : 'Task Control Plane'),
            'details'     => "Task {$code} created. Status: {$initialStatus}, Origin: {$insert['origin_type']}.",
            'created_at'  => $now,
        ]);

        return $this->getTask($taskId);
    }

    public function assignAgent(int $taskId, array $data, ?int $actorUserId = null): array
    {
        $task = $this->getTask($taskId);
        if (!$task) {
            throw new \InvalidArgumentException("Task #{$taskId} not found.", 404);
        }
        $agentId = (int)($data['agent_id'] ?? 0);
        if (!$agentId) {
            throw new \InvalidArgumentException('Agent ID is required.', 400);
        }
        $now = date('Y-m-d H:i:s');
        $assignment = [
            'task_id'              => $taskId,
            'agent_id'             => $agentId,
            'assignment_role'      => $data['assignment_role'] ?? 'collaborator',
            'responsibility_scope' => $data['responsibility_scope'] ?? 'Assigned task contributor.',
            'assigned_by_user_id'  => $actorUserId,
            'acceptance_status'    => $data['acceptance_status'] ?? 'accepted',
            'accepted_at'          => $now,
            'status'               => 'active',
            'created_at'           => $now,
            'updated_at'           => $now,
        ];
        $this->db->table('task_assignments')->insert($assignment);
        $assignment['id'] = (int)$this->db->insertID();

        return $assignment;
    }

    /**
     * Controlled 17-state lifecycle transition validation.
     */
    public function transitionTask(int $taskId, string $targetStatus, array $options = [], ?int $actorUserId = null, ?int $actorAgentId = null): array
    {
        $task = $this->getTask($taskId);
        if (!$task) {
            throw new \InvalidArgumentException("Task #{$taskId} not found.", 404);
        }

        $currentStatus = $task['status'];
        if ($currentStatus === $targetStatus) {
            return $task;
        }

        $allowed = self::ALLOWED_TRANSITIONS[$currentStatus] ?? [];
        if (!in_array($targetStatus, $allowed, true)) {
            throw new \RuntimeException(
                "Invalid task state transition: Cannot move from '{$currentStatus}' to '{$targetStatus}'. Permitted targets: [" . implode(', ', $allowed) . "].",
                400
            );
        }

        // Specific state constraints
        if ($targetStatus === 'in_progress') {
            // Cannot start if blocked or dependencies unsatisfied
            $unsatisfiedDeps = $this->db->table('task_dependencies')
                ->where('task_id', $taskId)
                ->where('is_satisfied', 0)
                ->countAllResults();

            if ($unsatisfiedDeps > 0) {
                throw new \RuntimeException("Cannot start task #{$taskId}: {$unsatisfiedDeps} prerequisite upstream dependencies are not satisfied.", 409);
            }
        }

        if ($targetStatus === 'completed') {
            // Task requires accepted deliverable or review if category is development/research
            $hasAcceptedDeliverable = $this->db->table('task_deliverables')
                ->where('task_id', $taskId)
                ->where('is_current_accepted', 1)
                ->countAllResults() > 0;

            if (!$hasAcceptedDeliverable && in_array($task['category'], ['development', 'research', 'maintenance'])) {
                throw new \RuntimeException("Cannot complete task #{$taskId}: Requires at least one formally verified and accepted deliverable.", 422);
            }
        }

        $now = date('Y-m-d H:i:s');
        $updates = [
            'status'     => $targetStatus,
            'updated_at' => $now,
        ];

        if ($targetStatus === 'completed') {
            $updates['completed_at'] = $now;
            $updates['progress_percent'] = 100;
        }

        $this->db->table('workforce_tasks')->where('id', $taskId)->update($updates);

        // If completed, re-evaluate downstream task dependencies
        if ($targetStatus === 'completed') {
            $this->db->table('task_dependencies')
                ->where('depends_on_task_id', $taskId)
                ->update([
                    'is_satisfied' => 1,
                    'satisfied_at' => $now,
                    'updated_at'   => $now,
                ]);
        }

        // Audit
        $this->db->table('task_events_audit')->insert([
            'task_id'     => $taskId,
            'event_type'  => 'status_transition',
            'from_status' => $currentStatus,
            'to_status'   => $targetStatus,
            'actor_type'  => $actorUserId ? 'human' : 'agent',
            'actor_id'    => $actorUserId ?? $actorAgentId,
            'actor_name'  => $actorUserId ? "User #{$actorUserId}" : ($actorAgentId ? "Agent #{$actorAgentId}" : 'Task State Machine'),
            'details'     => $options['reason'] ?? "Transitioned from {$currentStatus} to {$targetStatus}.",
            'created_at'  => $now,
        ]);

        return $this->getTask($taskId);
    }

    public function getMetrics(): array
    {
        $builder = $this->db->table('workforce_tasks')->where('deleted_at IS NULL');
        $total = $builder->countAllResults();

        $byStatus = [];
        $rows = $this->db->table('workforce_tasks')
            ->select('status, COUNT(*) as count')
            ->where('deleted_at IS NULL')
            ->groupBy('status')
            ->get()
            ->getResultArray();

        foreach ($rows as $r) {
            $byStatus[$r['status']] = (int)$r['count'];
        }

        $byPriority = [];
        $pRows = $this->db->table('workforce_tasks')
            ->select('priority, COUNT(*) as count')
            ->where('deleted_at IS NULL')
            ->groupBy('priority')
            ->get()
            ->getResultArray();

        foreach ($pRows as $r) {
            $byPriority[$r['priority']] = (int)$r['count'];
        }

        return [
            'total_tasks'  => $total,
            'by_status'    => $byStatus,
            'by_priority'  => $byPriority,
            'in_progress'  => $byStatus['in_progress'] ?? 0,
            'blocked'      => $byStatus['blocked'] ?? 0,
            'under_review' => $byStatus['under_review'] ?? 0,
            'completed'    => $byStatus['completed'] ?? 0,
        ];
    }

    /**
     * Surfaces active work for an agent preparing for Wallbook Handover or Off-Duty transition.
     */
    public function getActiveTasksForAgent(int $agentId): array
    {
        return $this->db->table('workforce_tasks t')
            ->select('t.*, a.assignment_role')
            ->join('task_assignments a', 'a.task_id = t.id')
            ->where('a.agent_id', $agentId)
            ->where('a.status', 'active')
            ->whereIn('t.status', ['assigned', 'awaiting_acceptance', 'accepted', 'in_progress', 'under_review', 'blocked'])
            ->where('t.deleted_at IS NULL')
            ->get()
            ->getResultArray();
    }
}
