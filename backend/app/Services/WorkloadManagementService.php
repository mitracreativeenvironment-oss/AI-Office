<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class WorkloadManagementService
{
    protected BaseConnection $db;
    protected AgentCapacityService $agentCapacityService;

    public function __construct(?BaseConnection $db = null, ?AgentCapacityService $agentCapacityService = null)
    {
        $this->db = $db ?? Database::connect();
        $this->agentCapacityService = $agentCapacityService ?? new AgentCapacityService($this->db);
    }

    /**
     * Compute workload heatmap (agents x timeline).
     */
    public function getWorkloadHeatmap(int $weeks = 4, ?int $unitId = null): array
    {
        $agents = $this->agentCapacityService->getAllAgentsCapacity($unitId);
        $heatmap = [];

        foreach ($agents as $agent) {
            $timeline = $this->agentCapacityService->getAgentWorkloadTimeline($agent['agent_id'], $weeks);
            $heatmap[] = [
                'agent_id'                   => $agent['agent_id'],
                'agent_code'                 => $agent['agent_code'],
                'name'                       => $agent['name'],
                'unit_code'                  => $agent['unit_code'],
                'position_title'             => $agent['position_title'],
                'current_utilization_pct'    => $agent['utilization_percent'],
                'current_classification'     => $agent['utilization_classification'],
                'timeline'                   => $timeline,
            ];
        }

        return $heatmap;
    }

    /**
     * High-level workload summary and distribution.
     */
    public function getWorkloadSummary(): array
    {
        $agents = $this->agentCapacityService->getAllAgentsCapacity();
        $counts = [
            'underutilized'    => 0,
            'balanced'         => 0,
            'high_utilization' => 0,
            'overallocated'    => 0,
            'unavailable'      => 0,
        ];

        $totalAllocatedHours = 0.00;
        $totalReservedHours = 0.00;
        $totalScheduledHours = 0.00;

        foreach ($agents as $a) {
            $class = $a['utilization_classification'];
            if (isset($counts[$class])) {
                $counts[$class]++;
            }
            $totalAllocatedHours += (float)$a['allocated_capacity_hours'];
            $totalReservedHours += (float)$a['reserved_capacity_hours'];
            $totalScheduledHours += (float)$a['scheduled_capacity_hours'];
        }

        $overallPct = $totalScheduledHours > 0
            ? round((($totalAllocatedHours + $totalReservedHours) / $totalScheduledHours) * 100, 2)
            : 0.00;

        return [
            'total_agents_evaluated' => count($agents),
            'counts_by_tier'         => $counts,
            'total_scheduled_hours'  => round($totalScheduledHours, 2),
            'total_allocated_hours'  => round($totalAllocatedHours, 2),
            'total_reserved_hours'   => round($totalReservedHours, 2),
            'overall_utilization_pct'=> $overallPct,
            'status'                 => $counts['overallocated'] > 0 ? 'action_required' : 'healthy',
        ];
    }
}
