<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class OrganizationalPerformanceService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Normalized performance evaluation across organizational divisions.
     */
    public function getDivisionalPerformance(): array
    {
        $divisions = $this->db->table('organization_units')
            ->where('type', 'division')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $results = [];

        foreach ($divisions as $d) {
            $unitId = (int)$d['id'];

            // Agent headcount in division
            $headcount = $this->db->table('ai_agent_registry')
                ->where('primary_unit_id', $unitId)
                ->where('lifecycle_state', 'active')
                ->countAllResults();

            // Tasks assigned to this unit
            $tasks = $this->db->table('workforce_tasks')
                ->where('unit_id', $unitId)
                ->get()
                ->getResultArray();

            $totalTasks = count($tasks);
            $completedTasks = 0;
            foreach ($tasks as $t) {
                if (in_array($t['status'], ['accepted', 'completed'], true)) {
                    $completedTasks++;
                }
            }

            $completionRate = ($totalTasks > 0) ? round(($completedTasks / $totalTasks) * 100, 2) : 90.00;

            // Deliverables review acceptance for this unit
            $deliverables = $this->db->table('task_deliverables td')
                ->join('workforce_tasks wt', 'wt.id = td.task_id')
                ->where('wt.unit_id', $unitId)
                ->get()
                ->getResultArray();

            $totalDeliv = count($deliverables);
            $acceptedDeliv = 0;
            foreach ($deliverables as $del) {
                if ($del['review_status'] === 'accepted') $acceptedDeliv++;
            }
            $qualityRate = ($totalDeliv > 0) ? round(($acceptedDeliv / $totalDeliv) * 100, 2) : 88.00;

            // Compute composite division performance index (Scale 0-100)
            $divisionIndex = round(($completionRate * 0.5) + ($qualityRate * 0.5), 2);

            $results[] = [
                'division_id'       => $unitId,
                'unit_code'         => $d['code'] ?? ('DIV-' . $unitId),
                'name'              => $d['name'],
                'division_head'     => $d['head_title'] ?? 'Division Head AI',
                'active_headcount'  => $headcount,
                'total_tasks'       => $totalTasks,
                'completed_tasks'   => $completedTasks,
                'completion_rate'   => $completionRate,
                'deliverables_count'=> $totalDeliv,
                'quality_rate'      => $qualityRate,
                'performance_score' => $divisionIndex,
                'health_status'     => ($divisionIndex >= 85) ? 'high_performing' : (($divisionIndex >= 70) ? 'satisfactory' : 'needs_attention'),
            ];
        }

        return $results;
    }

    public function getDivisionalComparison(): array
    {
        $divisions = $this->getDivisionalPerformance();
        foreach ($divisions as &$d) {
            $d['division_name'] = $d['name'];
            $d['agent_count'] = $d['active_headcount'];
            $d['efficiency_score'] = $d['performance_score'];
        }
        return $divisions;
    }

    public function getUnitPerformance(int $unitId): ?array
    {
        $unit = $this->db->table('organization_units')->where('id', $unitId)->get()->getRowArray();
        if (!$unit) return null;

        $agents = $this->db->table('ai_agent_registry')
            ->where('primary_unit_id', $unitId)
            ->where('lifecycle_state', 'active')
            ->get()
            ->getResultArray();

        $tasks = $this->db->table('workforce_tasks')
            ->where('unit_id', $unitId)
            ->orderBy('id', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        return [
            'unit'            => $unit,
            'active_agents'   => $agents,
            'recent_tasks'    => $tasks,
            'calculated_at'   => date('Y-m-d H:i:s'),
        ];
    }
}
