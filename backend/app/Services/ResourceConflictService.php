<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class ResourceConflictService
{
    protected BaseConnection $db;
    protected AgentCapacityService $agentCapacityService;

    public function __construct(?BaseConnection $db = null, ?AgentCapacityService $agentCapacityService = null)
    {
        $this->db = $db ?? Database::connect();
        $this->agentCapacityService = $agentCapacityService ?? new AgentCapacityService($this->db);
    }

    public function getConflicts(array $filters = []): array
    {
        $builder = $this->db->table('resource_conflicts c')
            ->select('c.*, a.name as agent_name, a.agent_code, r.name as resource_name, r.resource_code, p.name as project_name, p.project_code, u.username as resolver_username')
            ->join('ai_agent_registry a', 'a.id = c.agent_id', 'left')
            ->join('resource_registry r', 'r.id = c.resource_id', 'left')
            ->join('projects p', 'p.id = c.project_id', 'left')
            ->join('users u', 'u.id = c.resolved_by_user_id', 'left')
            ->orderBy('c.id', 'DESC');

        if (!empty($filters['conflict_type'])) {
            $builder->where('c.conflict_type', $filters['conflict_type']);
        }
        if (!empty($filters['severity'])) {
            $builder->where('c.severity', $filters['severity']);
        }
        if (!empty($filters['resolution_status'])) {
            $builder->where('c.resolution_status', $filters['resolution_status']);
        }
        if (!empty($filters['agent_id'])) {
            $builder->where('c.agent_id', (int)$filters['agent_id']);
        }

        return $builder->get()->getResultArray();
    }

    public function getConflictById(int $id): ?array
    {
        return $this->db->table('resource_conflicts c')
            ->select('c.*, a.name as agent_name, a.agent_code, r.name as resource_name, r.resource_code, p.name as project_name, p.project_code, u.username as resolver_username')
            ->join('ai_agent_registry a', 'a.id = c.agent_id', 'left')
            ->join('resource_registry r', 'r.id = c.resource_id', 'left')
            ->join('projects p', 'p.id = c.project_id', 'left')
            ->join('users u', 'u.id = c.resolved_by_user_id', 'left')
            ->where('c.id', $id)
            ->get()->getRowArray();
    }

    /**
     * Scan and detect conflicts across workforce and operational resources.
     */
    public function detectAllConflicts(?array $actor = null): array
    {
        $now = date('Y-m-d H:i:s');
        $detected = [];

        // 1. Scan for Workforce Overallocations
        $agents = $this->agentCapacityService->getAllAgentsCapacity();
        foreach ($agents as $agent) {
            if ($agent['utilization_classification'] === 'overallocated' || $agent['utilization_percent'] > 100.00) {
                $code = 'CNF-OVR-' . $agent['agent_id'] . '-' . date('W');
                $existing = $this->db->table('resource_conflicts')->where('conflict_code', $code)->get()->getRowArray();
                
                $committedHours = $agent['allocated_capacity_hours'] + $agent['reserved_capacity_hours'];
                $excessHours = $committedHours - $agent['scheduled_capacity_hours'];

                $conflictData = [
                    'conflict_code'          => $code,
                    'conflict_type'          => 'overallocation',
                    'severity'               => ($agent['utilization_percent'] > 120.00) ? 'critical' : 'warning',
                    'entity_type'            => 'agent',
                    'agent_id'               => $agent['agent_id'],
                    'resource_id'            => null,
                    'project_id'             => null,
                    'allocation_id'          => null,
                    'title'                  => "Agent {$agent['agent_code']} Overallocated ({$agent['utilization_percent']}%)",
                    'description'            => "Total committed workload ({$committedHours}h/wk) exceeds scheduled capacity ({$agent['scheduled_capacity_hours']}h/wk) by {$excessHours} hours.",
                    'impact_summary'         => "Burnout risk, degraded response times in Hermes runtime, and potential delivery slippage.",
                    'recommended_resolution' => "Reassign non-critical tasks or reduce project allocation percentage.",
                    'resolution_status'      => $existing ? $existing['resolution_status'] : 'detected',
                    'detected_at'            => $now,
                    'created_at'             => $existing ? $existing['created_at'] : $now,
                    'updated_at'             => $now,
                ];

                if ($existing) {
                    $this->db->table('resource_conflicts')->where('id', $existing['id'])->update($conflictData);
                    $conflictData['id'] = $existing['id'];
                } else {
                    $this->db->table('resource_conflicts')->insert($conflictData);
                    $conflictData['id'] = (int)$this->db->insertID();
                }
                $detected[] = $conflictData;
            }
        }

        // 2. Scan for Operational Resources in Maintenance with Active Allocations
        $maintResources = $this->db->table('resource_registry')
            ->where('status', 'maintenance')
            ->where('allocated_capacity >', 0)
            ->get()->getResultArray();

        foreach ($maintResources as $res) {
            $code = 'CNF-MNT-' . $res['id'];
            $existing = $this->db->table('resource_conflicts')->where('conflict_code', $code)->get()->getRowArray();

            $conflictData = [
                'conflict_code'          => $code,
                'conflict_type'          => 'resource_maintenance',
                'severity'               => 'critical',
                'entity_type'            => 'resource',
                'agent_id'               => null,
                'resource_id'            => (int)$res['id'],
                'project_id'             => null,
                'allocation_id'          => null,
                'title'                  => "Operational Resource Under Maintenance Has Active Commitments",
                'description'            => "Resource {$res['resource_code']} ({$res['name']}) is set to maintenance status but has {$res['allocated_capacity']} units allocated.",
                'impact_summary'         => "Dependent workflows, tasks, or client demonstrations will fail or experience timeouts.",
                'recommended_resolution' => "Switch traffic to standby cluster or expedite maintenance maintenance window conclusion.",
                'resolution_status'      => $existing ? $existing['resolution_status'] : 'detected',
                'detected_at'            => $now,
                'created_at'             => $existing ? $existing['created_at'] : $now,
                'updated_at'             => $now,
            ];

            if ($existing) {
                $this->db->table('resource_conflicts')->where('id', $existing['id'])->update($conflictData);
                $conflictData['id'] = $existing['id'];
            } else {
                $this->db->table('resource_conflicts')->insert($conflictData);
                $conflictData['id'] = (int)$this->db->insertID();
            }
            $detected[] = $conflictData;
        }

        $this->logAuditEvent('conflicts_scanned', 'conflict', 0, ['detected_count' => count($detected)], $actor);

        return $this->getConflicts();
    }

    public function resolveConflict(int $id, string $resolutionNotes, ?array $actor = null): ?array
    {
        $conflict = $this->getConflictById($id);
        if (!$conflict) {
            return null;
        }

        $now = date('Y-m-d H:i:s');
        $updateData = [
            'resolution_status'   => 'resolved',
            'resolved_by_user_id' => $actor['id'] ?? 1,
            'resolution_notes'    => $resolutionNotes,
            'resolved_at'         => $now,
            'updated_at'          => $now,
        ];

        $this->db->table('resource_conflicts')->where('id', $id)->update($updateData);

        $this->logAuditEvent('conflict_resolved', 'conflict', $id, $updateData, $actor);

        return $this->getConflictById($id);
    }

    public function dismissConflict(int $id, string $reason, ?array $actor = null): ?array
    {
        $conflict = $this->getConflictById($id);
        if (!$conflict) {
            return null;
        }

        $now = date('Y-m-d H:i:s');
        $updateData = [
            'resolution_status'   => 'ignored',
            'resolved_by_user_id' => $actor['id'] ?? 1,
            'resolution_notes'    => 'Dismissed / Ignored: ' . $reason,
            'resolved_at'         => $now,
            'updated_at'          => $now,
        ];

        $this->db->table('resource_conflicts')->where('id', $id)->update($updateData);

        $this->logAuditEvent('conflict_dismissed', 'conflict', $id, $updateData, $actor);

        return $this->getConflictById($id);
    }

    protected function logAuditEvent(string $eventType, string $targetType, int $targetId, array $metadata, ?array $actor = null): void
    {
        $this->db->table('resource_audit_events')->insert([
            'event_type'    => $eventType,
            'actor_type'    => $actor['type'] ?? 'system',
            'actor_id'      => $actor['id'] ?? null,
            'actor_name'    => $actor['name'] ?? 'System Conflict Engine',
            'target_type'   => $targetType,
            'target_id'     => $targetId,
            'metadata_json' => json_encode($metadata),
            'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }
}
