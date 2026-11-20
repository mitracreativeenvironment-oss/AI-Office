<?php

namespace App\Services;

use Config\Database;
use InvalidArgumentException;
use RuntimeException;

class ProjectResourceBudgetService
{
    protected $db;
    protected ProjectService $projectService;

    public function __construct(?ProjectService $projectService = null)
    {
        $this->db = Database::connect();
        $this->projectService = $projectService ?? new ProjectService();
    }

    /**
     * Team Members CRUD.
     */
    public function getTeamMembers(int $projectId): array
    {
        return $this->db->table('project_team_members tm')
            ->select('tm.*, 
                      a.name as agent_name, a.agent_code, a.classification as agent_classification,
                      u.username, u.full_name as user_full_name, u.role as user_system_role')
            ->join('ai_agent_registry a', 'a.id = tm.agent_id', 'left')
            ->join('users u', 'u.id = tm.user_id', 'left')
            ->where('tm.project_id', $projectId)
            ->orderBy('tm.id', 'ASC')
            ->get()->getResultArray();
    }

    public function addTeamMember(int $projectId, array $data, array $actor): array
    {
        $memberType = $data['member_type'] ?? 'agent';
        $userId = !empty($data['user_id']) ? (int)$data['user_id'] : null;
        $agentId = !empty($data['agent_id']) ? (int)$data['agent_id'] : null;

        if ($memberType === 'agent') {
            if (!$agentId) {
                throw new InvalidArgumentException('agent_id is required for agent member.');
            }
            // Check agent exists and active
            $agent = $this->db->table('ai_agent_registry')->where('id', $agentId)->get()->getRowArray();
            if (!$agent) {
                throw new RuntimeException("Agent #{$agentId} not found in registry.");
            }
        } else {
            if (!$userId) {
                throw new InvalidArgumentException('user_id is required for human member.');
            }
        }

        // Check for duplicate active assignment
        $builder = $this->db->table('project_team_members')
            ->where('project_id', $projectId)
            ->where('status', 'active');
        if ($memberType === 'agent') {
            $builder->where('agent_id', $agentId);
        } else {
            $builder->where('user_id', $userId);
        }
        $existing = $builder->get()->getRowArray();
        if ($existing) {
            throw new RuntimeException("Member is already assigned to this project.");
        }

        $now = date('Y-m-d H:i:s');
        $insertData = [
            'project_id'            => $projectId,
            'member_type'           => $memberType,
            'user_id'               => $userId,
            'agent_id'              => $agentId,
            'project_role'          => $data['project_role'] ?? 'contributor',
            'allocation_percentage' => (int)($data['allocation_percentage'] ?? 100),
            'status'                => 'active',
            'joined_at'             => $data['joined_at'] ?? $now,
            'released_at'           => null,
        ];

        $this->db->table('project_team_members')->insert($insertData);
        $id = (int)$this->db->insertID();

        $this->projectService->logAuditEvent($projectId, 'project.member_assigned', $actor, [
            'member_id'   => $id,
            'member_type' => $memberType,
            'role'        => $insertData['project_role'],
        ]);

        return $this->db->table('project_team_members')->where('id', $id)->get()->getRowArray();
    }

    public function updateTeamMember(int $memberId, array $data, array $actor): array
    {
        $fields = ['project_role', 'allocation_percentage', 'status'];
        $updateData = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $updateData[$f] = $data[$f];
            }
        }
        if (isset($data['status']) && $data['status'] === 'released') {
            $updateData['released_at'] = date('Y-m-d H:i:s');
        }

        $this->db->table('project_team_members')->where('id', $memberId)->update($updateData);

        return $this->db->table('project_team_members')->where('id', $memberId)->get()->getRowArray();
    }

    public function removeTeamMember(int $memberId, array $actor): bool
    {
        $now = date('Y-m-d H:i:s');
        return $this->db->table('project_team_members')->where('id', $memberId)->update([
            'status'      => 'released',
            'released_at' => $now,
        ]);
    }

    /**
     * Resource Allocations CRUD.
     */
    public function getResourceAllocations(int $projectId): array
    {
        return $this->db->table('project_resource_allocations')
            ->where('project_id', $projectId)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();
    }

    public function addResourceAllocation(int $projectId, array $data, array $actor): array
    {
        $name = trim($data['resource_name'] ?? '');
        if (empty($name)) {
            throw new InvalidArgumentException('Resource name is required.');
        }

        $qty = (float)($data['allocated_quantity'] ?? 0.00);
        $unitCost = (float)($data['unit_cost_idr'] ?? 0.00);
        $totalCost = (float)($data['total_allocated_cost_idr'] ?? ($qty * $unitCost));

        $now = date('Y-m-d H:i:s');
        $insertData = [
            'project_id'               => $projectId,
            'resource_type'            => $data['resource_type'] ?? 'ai_agent_runtime',
            'resource_name'            => $name,
            'allocated_quantity'       => $qty,
            'utilized_quantity'        => (float)($data['utilized_quantity'] ?? 0.00),
            'unit_cost_idr'            => $unitCost,
            'total_allocated_cost_idr' => $totalCost,
            'notes'                    => $data['notes'] ?? null,
            'created_at'               => $now,
        ];

        $this->db->table('project_resource_allocations')->insert($insertData);
        $id = (int)$this->db->insertID();

        return $this->db->table('project_resource_allocations')->where('id', $id)->get()->getRowArray();
    }

    public function updateResourceUtilization(int $allocId, float $utilized, array $actor): array
    {
        $existing = $this->db->table('project_resource_allocations')->where('id', $allocId)->get()->getRowArray();
        if (!$existing) {
            throw new RuntimeException("Allocation #{$allocId} not found.");
        }

        $this->db->table('project_resource_allocations')->where('id', $allocId)->update([
            'utilized_quantity' => $utilized,
        ]);

        // Recalculate actual project cost
        $projectId = (int)$existing['project_id'];
        $allocations = $this->getResourceAllocations($projectId);
        $totalSpent = 0.0;
        foreach ($allocations as $a) {
            $totalSpent += ((float)$a['utilized_quantity'] * (float)$a['unit_cost_idr']);
        }

        $this->db->table('projects')->where('id', $projectId)->update([
            'actual_cost_incurred' => $totalSpent,
            'updated_at'           => date('Y-m-d H:i:s'),
        ]);

        return $this->db->table('project_resource_allocations')->where('id', $allocId)->get()->getRowArray();
    }

    /**
     * Budget Overview & Governance.
     */
    public function getBudgetSummary(int $projectId): array
    {
        $project = $this->db->table('projects')->where('id', $projectId)->get()->getRowArray();
        if (!$project) {
            throw new RuntimeException("Project #{$projectId} not found.");
        }

        $allocations = $this->getResourceAllocations($projectId);
        $proposed = (float)$project['proposed_budget'];
        $approved = (float)$project['approved_budget_limit'];
        $spent = (float)$project['actual_cost_incurred'];

        $remaining = max(0.0, $approved - $spent);
        $utilizationPercent = $approved > 0 ? round(($spent / $approved) * 100, 1) : 0;

        // Breakdown by resource type
        $breakdown = [];
        foreach ($allocations as $a) {
            $type = $a['resource_type'];
            if (!isset($breakdown[$type])) {
                $breakdown[$type] = [
                    'allocated_cost' => 0.0,
                    'utilized_cost'  => 0.0,
                    'count'          => 0,
                ];
            }
            $breakdown[$type]['allocated_cost'] += (float)$a['total_allocated_cost_idr'];
            $breakdown[$type]['utilized_cost'] += ((float)$a['utilized_quantity'] * (float)$a['unit_cost_idr']);
            $breakdown[$type]['count']++;
        }

        return [
            'currency'            => $project['budget_currency'] ?? 'IDR',
            'proposed_budget'     => $proposed,
            'approved_budget'     => $approved,
            'actual_cost_spent'   => $spent,
            'remaining_budget'    => $remaining,
            'utilization_percent' => $utilizationPercent,
            'is_over_budget'      => $spent > $approved && $approved > 0,
            'breakdown'           => $breakdown,
        ];
    }

    public function updateBudget(int $projectId, float $proposed, float $approved, array $actor): array
    {
        $project = $this->db->table('projects')->where('id', $projectId)->get()->getRowArray();
        if (!$project) {
            throw new RuntimeException("Project #{$projectId} not found.");
        }

        $roles = $actor['roles'] ?? [];
        $isOwner = in_array('owner', $roles) || in_array('superadmin', $roles);
        $isBoard = in_array('board', $roles) || in_array('executive', $roles);

        // DoA Limit Enforcement:
        // Increasing approved budget > 5,000,000 IDR requires Human Owner approval
        if ($approved > 5000000.00 && !$isOwner) {
            throw new RuntimeException("Approving project budget > IDR 5,000,000 exceeds Executive Board limit and requires Human Owner sign-off.");
        }

        if (!$isOwner && !$isBoard) {
            throw new RuntimeException("Only Executive Board or Human Owner can modify approved project budget limit.");
        }

        $this->db->table('projects')->where('id', $projectId)->update([
            'proposed_budget'       => $proposed,
            'approved_budget_limit' => $approved,
            'updated_at'            => date('Y-m-d H:i:s'),
        ]);

        $this->projectService->logAuditEvent($projectId, 'project.budget_updated', $actor, [
            'proposed' => $proposed,
            'approved' => $approved,
        ]);

        return $this->getBudgetSummary($projectId);
    }
}
