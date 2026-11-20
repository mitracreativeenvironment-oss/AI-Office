<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class ResourceRequestService
{
    protected BaseConnection $db;
    protected AgentCapacityService $agentCapacityService;
    protected ResourceRegistryService $resourceRegistryService;

    public function __construct(
        ?BaseConnection $db = null,
        ?AgentCapacityService $agentCapacityService = null,
        ?ResourceRegistryService $resourceRegistryService = null
    ) {
        $this->db = $db ?? Database::connect();
        $this->agentCapacityService = $agentCapacityService ?? new AgentCapacityService($this->db);
        $this->resourceRegistryService = $resourceRegistryService ?? new ResourceRegistryService($this->db);
    }

    public function getRequests(array $filters = []): array
    {
        $builder = $this->db->table('resource_requests r')
            ->select('r.*, p.name as project_name, p.project_code, u.name as unit_name, u.code as unit_code, s.name as required_skill_name, s.code as required_skill_code, usr.username as requester_username, app.username as approver_username')
            ->join('projects p', 'p.id = r.project_id', 'left')
            ->join('organization_units u', 'u.id = r.requesting_unit_id', 'left')
            ->join('skill_catalogue s', 's.id = r.required_skill_id', 'left')
            ->join('users usr', 'usr.id = r.requester_user_id', 'left')
            ->join('users app', 'app.id = r.approved_by_user_id', 'left')
            ->orderBy('r.id', 'DESC');

        if (!empty($filters['lifecycle_status'])) {
            $builder->where('r.lifecycle_status', $filters['lifecycle_status']);
        }
        if (!empty($filters['project_id'])) {
            $builder->where('r.project_id', (int)$filters['project_id']);
        }
        if (!empty($filters['resource_category'])) {
            $builder->where('r.resource_category', $filters['resource_category']);
        }
        if (!empty($filters['priority'])) {
            $builder->where('r.priority', $filters['priority']);
        }

        return $builder->get()->getResultArray();
    }

    public function getRequestById(int $id): ?array
    {
        return $this->db->table('resource_requests r')
            ->select('r.*, p.name as project_name, p.project_code, u.name as unit_name, u.code as unit_code, s.name as required_skill_name, s.code as required_skill_code, usr.username as requester_username, app.username as approver_username')
            ->join('projects p', 'p.id = r.project_id', 'left')
            ->join('organization_units u', 'u.id = r.requesting_unit_id', 'left')
            ->join('skill_catalogue s', 's.id = r.required_skill_id', 'left')
            ->join('users usr', 'usr.id = r.requester_user_id', 'left')
            ->join('users app', 'app.id = r.approved_by_user_id', 'left')
            ->where('r.id', $id)
            ->get()->getRowArray();
    }

    public function createRequest(array $data, ?array $actor = null): array
    {
        $now = date('Y-m-d H:i:s');
        $code = $data['request_code'] ?? 'REQ-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        $cost = (float)($data['estimated_cost_idr'] ?? 0.00);
        // DoA Limit: If cost > 5,000,000 IDR -> Level A (Human Owner)
        $approvalLevel = ($cost > 5000000.00) ? 'A' : 'B';

        $insertData = [
            'request_code'              => $code,
            'project_id'                => !empty($data['project_id']) ? (int)$data['project_id'] : null,
            'requesting_unit_id'        => !empty($data['requesting_unit_id']) ? (int)$data['requesting_unit_id'] : null,
            'requester_user_id'         => $actor['id'] ?? (!empty($data['requester_user_id']) ? (int)$data['requester_user_id'] : 1),
            'requester_agent_id'        => !empty($data['requester_agent_id']) ? (int)$data['requester_agent_id'] : null,
            'resource_category'         => $data['resource_category'] ?? 'workforce_agent',
            'title'                     => $data['title'],
            'purpose'                   => $data['purpose'] ?? '',
            'required_skill_id'         => !empty($data['required_skill_id']) ? (int)$data['required_skill_id'] : null,
            'required_proficiency'      => $data['required_proficiency'] ?? null,
            'quantity_requested'        => (float)($data['quantity_requested'] ?? 1.00),
            'hours_per_week'            => (float)($data['hours_per_week'] ?? 20.00),
            'start_date'                => $data['start_date'] ?? date('Y-m-d'),
            'end_date'                  => $data['end_date'] ?? null,
            'priority'                  => $data['priority'] ?? 'medium',
            'estimated_cost_idr'        => $cost,
            'lifecycle_status'          => $data['lifecycle_status'] ?? 'draft',
            'capacity_assessment_notes' => null,
            'is_capacity_sufficient'    => 1,
            'approval_authority_level'  => $approvalLevel,
            'approved_by_user_id'       => null,
            'approved_at'               => null,
            'rejection_reason'          => null,
            'created_at'                => $now,
            'updated_at'                => $now,
        ];

        $this->db->table('resource_requests')->insert($insertData);
        $id = (int)$this->db->insertID();

        $this->logAuditEvent('request_created', 'request', $id, $insertData, $actor);

        return $this->getRequestById($id);
    }

    public function submitRequest(int $id, ?array $actor = null): ?array
    {
        $request = $this->getRequestById($id);
        if (!$request) {
            return null;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('resource_requests')->where('id', $id)->update([
            'lifecycle_status' => 'submitted',
            'updated_at'       => $now,
        ]);

        $this->logAuditEvent('request_submitted', 'request', $id, ['status' => 'submitted'], $actor);

        return $this->getRequestById($id);
    }

    public function assessCapacity(int $id, ?string $notes = null, ?array $actor = null): ?array
    {
        $request = $this->getRequestById($id);
        if (!$request) {
            return null;
        }

        $now = date('Y-m-d H:i:s');
        $sufficient = true;
        $assessmentNotes = $notes ?? 'Automated capacity check passed.';

        // If requesting workforce agent with skill, check available agents
        if ($request['resource_category'] === 'workforce_agent' && $request['required_skill_id']) {
            $agentsWithSkill = $this->db->table('ai_agent_skills s')
                ->select('s.agent_id')
                ->where('s.skill_id', (int)$request['required_skill_id'])
                ->where('s.is_active', 1)
                ->get()->getResultArray();

            $foundAvailable = false;
            foreach ($agentsWithSkill as $as) {
                $cap = $this->agentCapacityService->getAgentCapacity((int)$as['agent_id']);
                if ($cap && (float)$cap['available_capacity_hours'] >= (float)$request['hours_per_week']) {
                    $foundAvailable = true;
                    $assessmentNotes = "Qualified agent {$cap['agent_code']} has {$cap['available_capacity_hours']}h available.";
                    break;
                }
            }

            if (!$foundAvailable && count($agentsWithSkill) > 0) {
                $sufficient = false;
                $assessmentNotes = "Existing agents with skill are high utilization or overallocated; rebalancing or overtime approval needed.";
            }
        }

        $updateData = [
            'lifecycle_status'          => 'capacity_assessed',
            'is_capacity_sufficient'    => $sufficient ? 1 : 0,
            'capacity_assessment_notes' => $assessmentNotes,
            'updated_at'                => $now,
        ];

        $this->db->table('resource_requests')->where('id', $id)->update($updateData);

        $this->logAuditEvent('request_capacity_assessed', 'request', $id, $updateData, $actor);

        return $this->getRequestById($id);
    }

    public function approveRequest(int $id, ?array $actor = null): ?array
    {
        $request = $this->getRequestById($id);
        if (!$request) {
            return null;
        }

        $now = date('Y-m-d H:i:s');
        $approverId = $actor['id'] ?? 1;

        $updateData = [
            'lifecycle_status'    => 'approved',
            'approved_by_user_id' => $approverId,
            'approved_at'         => $now,
            'updated_at'          => $now,
        ];

        $this->db->table('resource_requests')->where('id', $id)->update($updateData);

        $this->logAuditEvent('request_approved', 'request', $id, $updateData, $actor);

        return $this->getRequestById($id);
    }

    public function rejectRequest(int $id, string $reason, ?array $actor = null): ?array
    {
        $request = $this->getRequestById($id);
        if (!$request) {
            return null;
        }

        $now = date('Y-m-d H:i:s');
        $updateData = [
            'lifecycle_status' => 'rejected',
            'rejection_reason' => $reason,
            'updated_at'       => $now,
        ];

        $this->db->table('resource_requests')->where('id', $id)->update($updateData);

        $this->logAuditEvent('request_rejected', 'request', $id, $updateData, $actor);

        return $this->getRequestById($id);
    }

    public function allocateRequest(int $id, array $allocationData, ?array $actor = null): ?array
    {
        $request = $this->getRequestById($id);
        if (!$request) {
            return null;
        }

        $now = date('Y-m-d H:i:s');
        $allocCode = 'ALC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        $hours = (float)($allocationData['allocated_hours_per_week'] ?? $request['hours_per_week']);
        $pct = (int)($allocationData['allocated_percentage'] ?? round(($hours / 40.00) * 100));
        $unitCost = (float)($allocationData['unit_cost_idr'] ?? 175000.00);
        $totalCost = $hours * $unitCost;

        $insertAlloc = [
            'allocation_code'          => $allocCode,
            'request_id'               => $id,
            'project_id'               => $request['project_id'],
            'allocation_type'          => $allocationData['allocation_type'] ?? ($request['resource_category'] === 'workforce_agent' ? 'workforce_agent' : 'operational_resource'),
            'agent_id'                 => !empty($allocationData['agent_id']) ? (int)$allocationData['agent_id'] : null,
            'user_id'                  => !empty($allocationData['user_id']) ? (int)$allocationData['user_id'] : null,
            'resource_id'              => !empty($allocationData['resource_id']) ? (int)$allocationData['resource_id'] : null,
            'unit_id'                  => $request['requesting_unit_id'],
            'allocated_percentage'     => $pct,
            'allocated_hours_per_week' => $hours,
            'start_date'               => $allocationData['start_date'] ?? $request['start_date'],
            'end_date'                 => $allocationData['end_date'] ?? $request['end_date'],
            'status'                   => 'active',
            'unit_cost_idr'            => $unitCost,
            'total_cost_idr'           => $totalCost,
            'notes'                    => $allocationData['notes'] ?? 'Allocated via ResourceRequest #' . $id,
            'created_at'               => $now,
            'updated_at'               => $now,
        ];

        $this->db->table('resource_allocations_master')->insert($insertAlloc);
        $allocId = (int)$this->db->insertID();

        // If operational resource was allocated, update resource allocated_capacity
        if (!empty($insertAlloc['resource_id'])) {
            $this->db->query(
                "UPDATE resource_registry SET allocated_capacity = allocated_capacity + ? WHERE id = ?",
                [$hours, $insertAlloc['resource_id']]
            );
        }

        // Update request lifecycle status to allocated
        $this->db->table('resource_requests')->where('id', $id)->update([
            'lifecycle_status' => 'allocated',
            'updated_at'       => $now,
        ]);

        $this->logAuditEvent('request_allocated', 'allocation', $allocId, $insertAlloc, $actor);

        return $this->getRequestById($id);
    }

    public function getAllocations(array $filters = []): array
    {
        $builder = $this->db->table('resource_allocations_master a')
            ->select('a.*, p.name as project_name, p.project_code, ag.name as agent_name, ag.agent_code, u.username, res.name as resource_name, res.resource_code, un.name as unit_name, un.code as unit_code')
            ->join('projects p', 'p.id = a.project_id', 'left')
            ->join('ai_agent_registry ag', 'ag.id = a.agent_id', 'left')
            ->join('users u', 'u.id = a.user_id', 'left')
            ->join('resource_registry res', 'res.id = a.resource_id', 'left')
            ->join('organization_units un', 'un.id = a.unit_id', 'left')
            ->orderBy('a.id', 'DESC');

        if (!empty($filters['status'])) {
            $builder->where('a.status', $filters['status']);
        }
        if (!empty($filters['project_id'])) {
            $builder->where('a.project_id', (int)$filters['project_id']);
        }
        if (!empty($filters['agent_id'])) {
            $builder->where('a.agent_id', (int)$filters['agent_id']);
        }

        return $builder->get()->getResultArray();
    }

    protected function logAuditEvent(string $eventType, string $targetType, int $targetId, array $metadata, ?array $actor = null): void
    {
        $this->db->table('resource_audit_events')->insert([
            'event_type'    => $eventType,
            'actor_type'    => $actor['type'] ?? 'user',
            'actor_id'      => $actor['id'] ?? null,
            'actor_name'    => $actor['name'] ?? 'System',
            'target_type'   => $targetType,
            'target_id'     => $targetId,
            'metadata_json' => json_encode($metadata),
            'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }
}
