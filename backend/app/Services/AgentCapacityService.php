<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class AgentCapacityService
{
    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Compute comprehensive capacity metrics for a specific agent.
     */
    public function getAgentCapacity(int $agentId, ?string $startDate = null, ?string $endDate = null): ?array
    {
        $agent = $this->db->table('ai_agent_registry a')
            ->select('a.*, u.name as unit_name, u.code as unit_code, p.title as position_title')
            ->join('organization_units u', 'u.id = a.primary_unit_id', 'left')
            ->join('organization_positions p', 'p.id = a.primary_position_id', 'left')
            ->where('a.id', $agentId)
            ->get()->getRowArray();

        if (!$agent) {
            return null;
        }

        // Standard gross capacity: 40 hours per week (5 days x 8 hours)
        $grossCapacityHours = 40.00;

        // Check if agent is on leave or unavailable in agent_availability_leaves
        $unavailableHours = 0.00;
        if ($this->db->tableExists('agent_availability_leaves')) {
            $leaveQuery = $this->db->table('agent_availability_leaves')
                ->where('agent_id', $agentId)
                ->whereIn('availability_type', ['planned_leave', 'temporary_unavailability', 'under_handover'])
                ->where('approval_status', 'approved');
            
            if ($startDate && $endDate) {
                $leaveQuery->where('start_time <=', $endDate . ' 23:59:59')
                    ->where('end_time >=', $startDate . ' 00:00:00');
            } else {
                $now = date('Y-m-d H:i:s');
                $leaveQuery->where('start_time <=', $now)
                    ->groupStart()
                        ->where('end_time >=', $now)
                        ->orWhere('end_time IS NULL')
                    ->groupEnd();
            }

            $leaveRow = $leaveQuery->get()->getRowArray();
            if ($leaveRow) {
                $unavailableHours = 40.00; // Entire week unavailable
            }
        }

        if (in_array($agent['operational_status'], ['on_leave', 'offline'])) {
            $unavailableHours = max($unavailableHours, 40.00);
        }

        // Scheduled Capacity: Gross minus unavailable
        $scheduledCapacityHours = max(0.00, $grossCapacityHours - $unavailableHours);

        // Calculate Existing Allocations from resource_allocations_master
        $allocationsBuilder = $this->db->table('resource_allocations_master')
            ->select('SUM(allocated_hours_per_week) as total_alloc_hours, SUM(allocated_percentage) as total_alloc_pct')
            ->where('agent_id', $agentId)
            ->whereIn('status', ['active', 'confirmed']);
        
        $allocRow = $allocationsBuilder->get()->getRowArray();
        $allocatedHours = (float)($allocRow['total_alloc_hours'] ?? 0.00);

        // Also check active tasks in workforce_tasks for this agent
        $activeTasksHours = 0.00;
        if ($this->db->tableExists('workforce_tasks')) {
            $taskRow = $this->db->table('workforce_tasks')
                ->select('COUNT(id) as task_count')
                ->where('accountable_agent_id', $agentId)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->get()->getRowArray();
            
            // Assume active tasks represent ~4 hours workload each if not explicitly allocated in master
            $activeTasksCount = (int)($taskRow['task_count'] ?? 0);
            if ($activeTasksCount > 0 && $allocatedHours === 0.00) {
                $activeTasksHours = min(20.00, $activeTasksCount * 4.00);
            }
        }

        $totalAllocatedHours = $allocatedHours + $activeTasksHours;

        // Calculate Reserved Capacity from resource_capacity_reservations
        $resBuilder = $this->db->table('resource_capacity_reservations')
            ->select('SUM(reserved_hours_per_week) as total_reserved_hours')
            ->where('status', 'active')
            ->groupStart()
                ->where('target_agent_id', $agentId)
                ->orGroupStart()
                    ->where('target_agent_id IS NULL')
                    ->where('scope_unit_id', $agent['primary_unit_id'])
                ->groupEnd()
            ->groupEnd();
        
        $resRow = $resBuilder->get()->getRowArray();
        $reservedHours = (float)($resRow['total_reserved_hours'] ?? 0.00);

        // Formula: Available = Scheduled - Existing Allocations - Reserved Capacity - Unavailable Capacity
        $netAvailableHours = max(0.00, $scheduledCapacityHours - $totalAllocatedHours - $reservedHours);

        // Utilization Calculation
        $committedHours = $totalAllocatedHours + $reservedHours;
        $utilizationPercent = $scheduledCapacityHours > 0 
            ? round(($committedHours / $scheduledCapacityHours) * 100, 2) 
            : 0.00;

        // Classify utilization
        if ($scheduledCapacityHours <= 0 || $unavailableHours >= 40.00) {
            $utilizationClassification = 'unavailable';
        } elseif ($utilizationPercent < 50.00) {
            $utilizationClassification = 'underutilized';
        } elseif ($utilizationPercent <= 85.00) {
            $utilizationClassification = 'balanced';
        } elseif ($utilizationPercent <= 100.00) {
            $utilizationClassification = 'high_utilization';
        } else {
            $utilizationClassification = 'overallocated';
        }

        // Fetch skills for this agent
        $skills = $this->db->table('ai_agent_skills s')
            ->select('s.*, c.code as skill_code, c.name as skill_name, c.category as skill_category')
            ->join('skill_catalogue c', 'c.id = s.skill_id')
            ->where('s.agent_id', $agentId)
            ->where('s.is_active', 1)
            ->get()->getResultArray();

        return [
            'agent_id'                   => (int)$agent['id'],
            'agent_uuid'                 => $agent['agent_uuid'],
            'agent_code'                 => $agent['agent_code'],
            'name'                       => $agent['name'],
            'display_name'               => $agent['display_name'],
            'classification'             => $agent['classification'],
            'operational_status'         => $agent['operational_status'],
            'unit_id'                    => (int)$agent['primary_unit_id'],
            'unit_name'                  => $agent['unit_name'],
            'unit_code'                  => $agent['unit_code'],
            'position_title'             => $agent['position_title'],
            'roster_schedule_type'       => $agent['roster_schedule_type'],
            'gross_capacity_hours'       => round($grossCapacityHours, 2),
            'scheduled_capacity_hours'   => round($scheduledCapacityHours, 2),
            'allocated_capacity_hours'   => round($totalAllocatedHours, 2),
            'reserved_capacity_hours'    => round($reservedHours, 2),
            'unavailable_capacity_hours' => round($unavailableHours, 2),
            'available_capacity_hours'   => round($netAvailableHours, 2),
            'utilization_percent'        => $utilizationPercent,
            'utilization_classification' => $utilizationClassification,
            'skills_count'               => count($skills),
            'skills'                     => $skills,
        ];
    }

    /**
     * Compute capacity for all active agents, optionally filtered by organizational unit.
     */
    public function getAllAgentsCapacity(?int $unitId = null): array
    {
        $builder = $this->db->table('ai_agent_registry a')
            ->select('a.id')
            ->whereIn('a.lifecycle_state', ['active', 'approved']);

        if ($unitId) {
            $builder->where('a.primary_unit_id', $unitId);
        }

        $agentIds = $builder->orderBy('a.id', 'ASC')->get()->getResultArray();
        $results = [];

        foreach ($agentIds as $row) {
            $capacity = $this->getAgentCapacity((int)$row['id']);
            if ($capacity) {
                $results[] = $capacity;
            }
        }

        return $results;
    }

    /**
     * Weekly timeline forecast for an agent.
     */
    public function getAgentWorkloadTimeline(int $agentId, int $weeks = 4): array
    {
        $timeline = [];
        $baseCapacity = $this->getAgentCapacity($agentId);
        if (!$baseCapacity) {
            return [];
        }

        $now = new \DateTime();

        for ($i = 0; $i < $weeks; $i++) {
            $weekStart = clone $now;
            $weekStart->modify("+{$i} weeks")->modify('monday this week');
            $weekEnd = clone $weekStart;
            $weekEnd->modify('+6 days');

            $timeline[] = [
                'week_number'                => (int)$weekStart->format('W'),
                'year'                       => (int)$weekStart->format('Y'),
                'start_date'                 => $weekStart->format('Y-m-d'),
                'end_date'                   => $weekEnd->format('Y-m-d'),
                'scheduled_capacity_hours'   => $baseCapacity['scheduled_capacity_hours'],
                'allocated_capacity_hours'   => $baseCapacity['allocated_capacity_hours'],
                'reserved_capacity_hours'    => $baseCapacity['reserved_capacity_hours'],
                'available_capacity_hours'   => $baseCapacity['available_capacity_hours'],
                'utilization_percent'        => $baseCapacity['utilization_percent'],
                'utilization_classification' => $baseCapacity['utilization_classification'],
            ];
        }

        return $timeline;
    }
}
