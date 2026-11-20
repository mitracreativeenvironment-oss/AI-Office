<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class OperationalCoverageService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function listRequirements(?int $unitId = null): array
    {
        $builder = $this->db->table('workforce_coverage_requirements cr')
            ->select('cr.*, u.name as unit_name, u.code as unit_code, s.name as shift_name, s.shift_code, s.start_time, s.end_time, sk.name as skill_name, sk.code as skill_code')
            ->join('organization_units u', 'u.id = cr.unit_id')
            ->join('workforce_shifts s', 's.id = cr.shift_id', 'left')
            ->join('skill_catalogue sk', 'sk.id = cr.required_skill_id', 'left')
            ->where('cr.is_active', 1);

        if ($unitId) {
            $builder->where('cr.unit_id', $unitId);
        }

        return $builder->orderBy('cr.criticality', 'ASC')->get()->getResultArray();
    }

    public function getRequirement(int $id): ?array
    {
        return $this->db->table('workforce_coverage_requirements cr')
            ->select('cr.*, u.name as unit_name, u.code as unit_code, s.name as shift_name, s.shift_code, sk.name as skill_name, sk.code as skill_code')
            ->join('organization_units u', 'u.id = cr.unit_id')
            ->join('workforce_shifts s', 's.id = cr.shift_id', 'left')
            ->join('skill_catalogue sk', 'sk.id = cr.required_skill_id', 'left')
            ->where('cr.id', $id)
            ->get()
            ->getRowArray() ?: null;
    }

    public function createRequirement(array $data): array
    {
        $now = date('Y-m-d H:i:s');
        $code = trim($data['requirement_code'] ?? 'REQ-COV-' . rand(100, 999));

        $insert = [
            'requirement_code'    => $code,
            'name'                => trim($data['name'] ?? 'Coverage Requirement'),
            'unit_id'             => (int)($data['unit_id'] ?? 1),
            'shift_id'            => !empty($data['shift_id']) ? (int)$data['shift_id'] : null,
            'day_of_week'         => $data['day_of_week'] ?? 'all',
            'min_agents_required' => (int)($data['min_agents_required'] ?? 1),
            'required_skill_id'   => !empty($data['required_skill_id']) ? (int)$data['required_skill_id'] : null,
            'min_proficiency'     => $data['min_proficiency'] ?? 'intermediate',
            'criticality'         => $data['criticality'] ?? 'medium',
            'is_continuous_24_7'  => !empty($data['is_continuous_24_7']) ? 1 : 0,
            'is_active'           => 1,
            'created_at'          => $now,
            'updated_at'          => $now,
        ];

        $this->db->table('workforce_coverage_requirements')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();
        return $insert;
    }

    /**
     * Real-time operational coverage evaluation based on actual duty assignments,
     * verified agent skills, and active availability/leave states.
     */
    public function evaluateCoverage(string $date, ?int $unitId = null): array
    {
        $dayOfWeekIso = (int)date('N', strtotime($date)); // 1=Mon, 7=Sun
        $isWeekend = ($dayOfWeekIso >= 6);
        $dayName = strtolower(date('D', strtotime($date))); // mon, tue, etc.

        $requirements = $this->listRequirements($unitId);

        $results = [];
        $summary = [
            'total_requirements' => count($requirements),
            'covered'            => 0,
            'partially_covered'  => 0,
            'uncovered'          => 0,
            'coverage_at_risk'   => 0,
            'pending_replacement'=> 0,
        ];

        foreach ($requirements as $req) {
            // Check day_of_week applicability
            $applies = false;
            if ($req['day_of_week'] === 'all') {
                $applies = true;
            } elseif ($req['day_of_week'] === 'weekday' && !$isWeekend) {
                $applies = true;
            } elseif ($req['day_of_week'] === 'weekend' && $isWeekend) {
                $applies = true;
            } elseif ($req['day_of_week'] === $dayName) {
                $applies = true;
            }

            if (!$applies) {
                continue;
            }

            // Find scheduled working agents
            $builder = $this->db->table('workforce_duty_assignments a')
                ->select('a.*, ag.name as agent_name, ag.agent_code, ag.classification, ag.primary_unit_id')
                ->join('ai_agent_registry ag', 'ag.id = a.agent_id')
                ->where('a.assignment_date', $date)
                ->where('a.duty_type', 'working');

            if ($req['shift_id']) {
                $builder->where('a.shift_id', $req['shift_id']);
            }

            $assignedRows = $builder->get()->getResultArray();

            $qualifiedAgents = [];
            $atRiskFlag = false;
            $pendingReplacementFlag = false;

            foreach ($assignedRows as $assigned) {
                // Must belong to target unit or have assignment in unit
                $agentId = (int)$assigned['agent_id'];
                $isUnitMatch = ($assigned['primary_unit_id'] == $req['unit_id']);

                if (!$isUnitMatch) {
                    // Check active secondary assignments
                    $hasUnitAssignment = $this->db->table('ai_agent_assignments')
                        ->where('agent_id', $agentId)
                        ->where('unit_id', $req['unit_id'])
                        ->where('status', 'active')
                        ->countAllResults() > 0;
                    if (!$hasUnitAssignment) continue;
                }

                // Check skill requirement
                if ($req['required_skill_id']) {
                    $hasSkill = $this->db->table('ai_agent_skills')
                        ->where('agent_id', $agentId)
                        ->where('skill_id', $req['required_skill_id'])
                        ->where('is_active', 1)
                        ->countAllResults() > 0;
                    if (!$hasSkill) {
                        continue; // Not qualified
                    }
                }

                // Check availability
                $leave = $this->db->table('workforce_agent_availability')
                    ->where('agent_id', $agentId)
                    ->where('approval_status', 'approved')
                    ->where("DATE(start_time) <=", $date)
                    ->where("(end_time IS NULL OR DATE(end_time) >= '{$date}')")
                    ->whereIn('availability_type', ['planned_leave', 'temporary_unavailability'])
                    ->get()
                    ->getRowArray();

                if ($leave) {
                    // Check if replacement exists
                    $replacement = $this->db->table('workforce_replacement_proposals')
                        ->where('duty_assignment_id', $assigned['id'])
                        ->where('status', 'activated')
                        ->get()
                        ->getRowArray();

                    if (!$replacement) {
                        $pendingReplacementFlag = true;
                        continue; // Unavailable without activated replacement
                    }
                }

                // Check pending replacement proposal (risk indicator)
                $pendingProp = $this->db->table('workforce_replacement_proposals')
                    ->where('duty_assignment_id', $assigned['id'])
                    ->whereIn('status', ['proposed', 'accepted', 'approved'])
                    ->countAllResults() > 0;

                if ($pendingProp) {
                    $atRiskFlag = true;
                }

                $qualifiedAgents[] = [
                    'agent_id'       => $agentId,
                    'agent_name'     => $assigned['agent_name'],
                    'agent_code'     => $assigned['agent_code'],
                    'classification' => $assigned['classification'],
                ];
            }

            $count = count($qualifiedAgents);
            $minRequired = (int)$req['min_agents_required'];

            if ($count >= $minRequired) {
                $status = $atRiskFlag ? 'coverage_at_risk' : 'covered';
            } elseif ($count > 0 && $count < $minRequired) {
                $status = 'partially_covered';
            } else {
                $status = $pendingReplacementFlag ? 'pending_replacement' : 'uncovered';
            }

            if (isset($summary[$status])) {
                $summary[$status]++;
            }

            $results[] = [
                'requirement_id'      => (int)$req['id'],
                'requirement_code'    => $req['requirement_code'],
                'requirement_name'    => $req['name'],
                'unit_id'             => (int)$req['unit_id'],
                'unit_name'           => $req['unit_name'],
                'shift_id'            => $req['shift_id'] ? (int)$req['shift_id'] : null,
                'shift_name'          => $req['shift_name'] ?? 'All / Flexible',
                'criticality'         => $req['criticality'],
                'min_agents_required' => $minRequired,
                'actual_agents_count' => $count,
                'status'              => $status,
                'qualified_agents'    => $qualifiedAgents,
                'gap'                 => max(0, $minRequired - $count),
            ];
        }

        return [
            'date'         => $date,
            'summary'      => $summary,
            'evaluations'  => $results,
        ];
    }

    public function getCoverageGaps(string $date, ?int $unitId = null): array
    {
        $eval = $this->evaluateCoverage($date, $unitId);
        $gaps = array_filter($eval['evaluations'], function ($item) {
            return in_array($item['status'], ['uncovered', 'partially_covered', 'coverage_at_risk', 'pending_replacement']);
        });

        return [
            'date'  => $date,
            'total' => count($gaps),
            'gaps'  => array_values($gaps),
        ];
    }
}
