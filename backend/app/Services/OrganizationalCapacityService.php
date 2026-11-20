<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class OrganizationalCapacityService
{
    protected BaseConnection $db;
    protected AgentCapacityService $agentCapacityService;

    public function __construct(?BaseConnection $db = null, ?AgentCapacityService $agentCapacityService = null)
    {
        $this->db = $db ?? Database::connect();
        $this->agentCapacityService = $agentCapacityService ?? new AgentCapacityService($this->db);
    }

    /**
     * Compute capacity summary for a specific unit or root organization.
     */
    public function getUnitCapacitySummary(?int $unitId = null): array
    {
        $allAgentsCapacity = $this->agentCapacityService->getAllAgentsCapacity($unitId);

        $headcount = count($allAgentsCapacity);
        $totalGross = 0.00;
        $totalScheduled = 0.00;
        $totalAllocated = 0.00;
        $totalReserved = 0.00;
        $totalUnavailable = 0.00;
        $totalAvailable = 0.00;

        $classificationCounts = [
            'underutilized'    => 0,
            'balanced'         => 0,
            'high_utilization' => 0,
            'overallocated'    => 0,
            'unavailable'      => 0,
        ];

        foreach ($allAgentsCapacity as $ac) {
            $totalGross += (float)$ac['gross_capacity_hours'];
            $totalScheduled += (float)$ac['scheduled_capacity_hours'];
            $totalAllocated += (float)$ac['allocated_capacity_hours'];
            $totalReserved += (float)$ac['reserved_capacity_hours'];
            $totalUnavailable += (float)$ac['unavailable_capacity_hours'];
            $totalAvailable += (float)$ac['available_capacity_hours'];

            $class = $ac['utilization_classification'];
            if (isset($classificationCounts[$class])) {
                $classificationCounts[$class]++;
            }
        }

        $overallUtilization = $totalScheduled > 0 
            ? round((($totalAllocated + $totalReserved) / $totalScheduled) * 100, 2) 
            : 0.00;

        $unitInfo = null;
        if ($unitId) {
            $unitInfo = $this->db->table('organization_units')->where('id', $unitId)->get()->getRowArray();
        }

        return [
            'unit_id'                    => $unitId,
            'unit_name'                  => $unitInfo['name'] ?? 'MCE Full Organization',
            'unit_code'                  => $unitInfo['code'] ?? 'ORG-MCE',
            'headcount'                  => $headcount,
            'gross_capacity_hours'       => round($totalGross, 2),
            'scheduled_capacity_hours'   => round($totalScheduled, 2),
            'allocated_capacity_hours'   => round($totalAllocated, 2),
            'reserved_capacity_hours'    => round($totalReserved, 2),
            'unavailable_capacity_hours' => round($totalUnavailable, 2),
            'available_capacity_hours'   => round($totalAvailable, 2),
            'utilization_percent'        => $overallUtilization,
            'classification_counts'      => $classificationCounts,
            'agents'                     => $allAgentsCapacity,
        ];
    }

    /**
     * Divisional comparison roll-up.
     */
    public function getDivisionalComparison(): array
    {
        $divisions = $this->db->table('organization_units')
            ->where('type', 'division')
            ->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();

        $comparison = [];
        foreach ($divisions as $div) {
            $summary = $this->getUnitCapacitySummary((int)$div['id']);
            $comparison[] = [
                'division_id'              => (int)$div['id'],
                'division_code'            => $div['code'],
                'division_name'            => $div['name'],
                'headcount'                => $summary['headcount'],
                'scheduled_capacity_hours' => $summary['scheduled_capacity_hours'],
                'allocated_capacity_hours' => $summary['allocated_capacity_hours'],
                'reserved_capacity_hours'  => $summary['reserved_capacity_hours'],
                'available_capacity_hours' => $summary['available_capacity_hours'],
                'utilization_percent'      => $summary['utilization_percent'],
                'classification_counts'    => $summary['classification_counts'],
            ];
        }

        return $comparison;
    }

    /**
     * Complete high-level organizational overview.
     */
    public function getOrganizationCapacityOverview(): array
    {
        $orgSummary = $this->getUnitCapacitySummary(null);
        $divisionalComparison = $this->getDivisionalComparison();

        // Operational resource roll-up
        $resBuilder = $this->db->table('resource_registry');
        $resStats = [
            'total_resources'     => $resBuilder->countAllResults(false),
            'active_resources'    => $resBuilder->where('status', 'active')->countAllResults(false),
            'maintenance_count'   => $this->db->table('resource_registry')->where('status', 'maintenance')->countAllResults(),
        ];

        return [
            'overview'              => $orgSummary,
            'divisional_breakdown'  => $divisionalComparison,
            'operational_resources' => $resStats,
            'generated_at'          => date('Y-m-d H:i:s'),
        ];
    }
}
