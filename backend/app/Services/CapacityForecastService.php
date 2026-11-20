<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class CapacityForecastService
{
    protected BaseConnection $db;
    protected OrganizationalCapacityService $orgCapacityService;

    public function __construct(?BaseConnection $db = null, ?OrganizationalCapacityService $orgCapacityService = null)
    {
        $this->db = $db ?? Database::connect();
        $this->orgCapacityService = $orgCapacityService ?? new OrganizationalCapacityService($this->db);
    }

    public function getForecastSnapshots(string $periodType = 'weekly'): array
    {
        $rows = $this->db->table('capacity_forecast_snapshots s')
            ->select('s.*, u.name as unit_name, u.code as unit_code')
            ->join('organization_units u', 'u.id = s.unit_id', 'left')
            ->where('s.period_type', $periodType)
            ->orderBy('s.period_start_date', 'ASC')
            ->get()->getResultArray();

        foreach ($rows as &$r) {
            $r['forecast_assumptions'] = !empty($r['forecast_assumptions_json']) ? json_decode($r['forecast_assumptions_json'], true) : [];
        }

        return $rows;
    }

    public function generateForecastSnapshot(string $startDate, string $endDate, ?int $unitId = null): array
    {
        $now = date('Y-m-d H:i:s');
        $unitSummary = $this->orgCapacityService->getUnitCapacitySummary($unitId);

        $headcount = $unitSummary['headcount'];
        $gross = $unitSummary['gross_capacity_hours'];
        $scheduled = $unitSummary['scheduled_capacity_hours'];
        $reserved = $unitSummary['reserved_capacity_hours'];
        $allocated = $unitSummary['allocated_capacity_hours'];

        // Assume projected demand includes active allocations + open approved requests
        $openDemand = 0.00;
        if ($this->db->tableExists('resource_requests')) {
            $reqRow = $this->db->table('resource_requests')
                ->select('SUM(hours_per_week) as demand')
                ->whereIn('lifecycle_status', ['approved', 'under_review', 'capacity_assessed'])
                ->get()->getRowArray();
            $openDemand = (float)($reqRow['demand'] ?? 0.00);
        }

        $projectedDemand = $allocated + $openDemand;
        $netAvailable = max(-999.00, $scheduled - $reserved - $projectedDemand);
        $utilization = $scheduled > 0 ? round((($projectedDemand + $reserved) / $scheduled) * 100, 2) : 0.00;

        if ($utilization > 105.00) {
            $status = 'critical_shortage';
        } elseif ($utilization > 95.00) {
            $status = 'deficit';
        } elseif ($utilization < 60.00) {
            $status = 'surplus';
        } else {
            $status = 'balanced';
        }

        $code = 'FCS-' . date('Y-W', strtotime($startDate)) . ($unitId ? "-U{$unitId}" : '-ALL');
        $existing = $this->db->table('capacity_forecast_snapshots')->where('snapshot_code', $code)->get()->getRowArray();

        $snapshotData = [
            'snapshot_code'                 => $code,
            'period_type'                   => 'weekly',
            'period_start_date'             => $startDate,
            'period_end_date'               => $endDate,
            'unit_id'                       => $unitId,
            'total_agents_headcount'        => $headcount,
            'gross_capacity_hours'          => $gross,
            'scheduled_capacity_hours'      => $scheduled,
            'reserved_capacity_hours'       => $reserved,
            'projected_demand_hours'        => $projectedDemand,
            'net_available_capacity_hours'  => $netAvailable,
            'utilization_projected_percent' => $utilization,
            'status'                        => $status,
            'forecast_assumptions_json'     => json_encode(['open_requests_demand_hours' => $openDemand, 'base_headcount' => $headcount]),
            'is_forecast_data'              => 1, // Explicit flag
            'created_at'                    => $existing ? $existing['created_at'] : $now,
        ];

        if ($existing) {
            $this->db->table('capacity_forecast_snapshots')->where('id', $existing['id'])->update($snapshotData);
            $id = $existing['id'];
        } else {
            $this->db->table('capacity_forecast_snapshots')->insert($snapshotData);
            $id = (int)$this->db->insertID();
        }

        $snapshotData['id'] = $id;
        $snapshotData['forecast_assumptions'] = json_decode($snapshotData['forecast_assumptions_json'], true);

        return $snapshotData;
    }

    public function getGapAnalyses(array $filters = []): array
    {
        $builder = $this->db->table('capacity_gap_analyses g')
            ->select('g.*, u.name as unit_name, u.code as unit_code, s.name as skill_name, s.code as skill_code')
            ->join('organization_units u', 'u.id = g.unit_id', 'left')
            ->join('skill_catalogue s', 's.id = g.skill_id', 'left')
            ->orderBy('g.id', 'DESC');

        if (!empty($filters['status'])) {
            $builder->where('g.status', $filters['status']);
        }
        if (!empty($filters['severity'])) {
            $builder->where('g.severity', $filters['severity']);
        }
        if (!empty($filters['gap_category'])) {
            $builder->where('g.gap_category', $filters['gap_category']);
        }

        $results = $builder->get()->getResultArray();
        foreach ($results as &$row) {
            $row['impacted_projects'] = !empty($row['impacted_projects_json']) ? json_decode($row['impacted_projects_json'], true) : [];
        }

        return $results;
    }

    public function createGapAnalysis(array $data, ?array $actor = null): array
    {
        $now = date('Y-m-d H:i:s');
        $code = $data['gap_code'] ?? 'GAP-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4));

        $req = (float)($data['required_capacity_hours'] ?? 0.00);
        $avail = (float)($data['available_capacity_hours'] ?? 0.00);
        $deficit = max(0.00, $req - $avail);

        $insertData = [
            'gap_code'                 => $code,
            'unit_id'                  => !empty($data['unit_id']) ? (int)$data['unit_id'] : null,
            'skill_id'                 => !empty($data['skill_id']) ? (int)$data['skill_id'] : null,
            'gap_category'             => $data['gap_category'] ?? 'skill_shortage',
            'severity'                 => $data['severity'] ?? 'medium',
            'required_capacity_hours'  => $req,
            'available_capacity_hours' => $avail,
            'deficit_hours'            => $deficit,
            'impacted_projects_json'   => isset($data['impacted_projects']) ? json_encode($data['impacted_projects']) : null,
            'recommended_action'       => $data['recommended_action'] ?? 'train_existing_agent',
            'action_plan_description'  => $data['action_plan_description'] ?? null,
            'status'                   => 'identified',
            'created_at'               => $now,
            'updated_at'               => $now,
        ];

        $this->db->table('capacity_gap_analyses')->insert($insertData);
        $id = (int)$this->db->insertID();

        $this->logAuditEvent('gap_analysis_created', 'resource', $id, $insertData, $actor);

        $insertData['id'] = $id;
        return $insertData;
    }

    public function resolveGapAnalysis(int $id, string $actionNotes, ?array $actor = null): ?array
    {
        $gap = $this->db->table('capacity_gap_analyses')->where('id', $id)->get()->getRowArray();
        if (!$gap) {
            return null;
        }

        $now = date('Y-m-d H:i:s');
        $updateData = [
            'status'                  => 'resolved',
            'action_plan_description' => $gap['action_plan_description'] . " [Resolved: {$actionNotes}]",
            'updated_at'              => $now,
        ];

        $this->db->table('capacity_gap_analyses')->where('id', $id)->update($updateData);

        $this->logAuditEvent('gap_analysis_resolved', 'resource', $id, $updateData, $actor);

        return $this->db->table('capacity_gap_analyses')->where('id', $id)->get()->getRowArray();
    }

    protected function logAuditEvent(string $eventType, string $targetType, int $targetId, array $metadata, ?array $actor = null): void
    {
        $this->db->table('resource_audit_events')->insert([
            'event_type'    => $eventType,
            'actor_type'    => $actor['type'] ?? 'user',
            'actor_id'      => $actor['id'] ?? null,
            'actor_name'    => $actor['name'] ?? 'System Forecast',
            'target_type'   => $targetType,
            'target_id'     => $targetId,
            'metadata_json' => json_encode($metadata),
            'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }
}
