<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class SkillCoverageService
{
    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Get matrix of skills across agents with verified vs unverified distinctions.
     */
    public function getSkillCoverageMatrix(?int $unitId = null): array
    {
        $skills = $this->db->table('skill_catalogue')
            ->where('is_active', 1)
            ->orderBy('category', 'ASC')
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        $matrix = [];
        foreach ($skills as $skill) {
            $skillId = (int)$skill['id'];

            $builder = $this->db->table('ai_agent_skills s')
                ->select('s.*, a.agent_code, a.name as agent_name, a.operational_status, u.code as unit_code')
                ->join('ai_agent_registry a', 'a.id = s.agent_id')
                ->join('organization_units u', 'u.id = a.primary_unit_id', 'left')
                ->where('s.skill_id', $skillId)
                ->where('s.is_active', 1);

            if ($unitId) {
                $builder->where('a.primary_unit_id', $unitId);
            }

            $agentSkills = $builder->get()->getResultArray();

            $verifiedCount = 0;
            $unverifiedCount = 0;
            $proficiencyBreakdown = [
                'beginner'   => 0,
                'competent'  => 0,
                'proficient' => 0,
                'expert'     => 0,
                'master'     => 0,
            ];

            foreach ($agentSkills as $as) {
                if (in_array($as['verification_status'], ['verified', 'endorsed'])) {
                    $verifiedCount++;
                } else {
                    $unverifiedCount++;
                }

                $level = $as['proficiency_level'] ?? 'competent';
                if (isset($proficiencyBreakdown[$level])) {
                    $proficiencyBreakdown[$level]++;
                }
            }

            $totalAgentsWithSkill = count($agentSkills);
            $isSpof = ($totalAgentsWithSkill === 1);

            $matrix[] = [
                'skill_id'              => $skillId,
                'skill_code'            => $skill['code'],
                'skill_name'            => $skill['name'],
                'category'              => $skill['category'],
                'description'           => $skill['description'],
                'total_agents_count'    => $totalAgentsWithSkill,
                'verified_agents_count' => $verifiedCount,
                'pending_agents_count'  => $unverifiedCount,
                'proficiency_breakdown' => $proficiencyBreakdown,
                'is_single_point_of_failure' => $isSpof,
                'assigned_agents'       => $agentSkills,
            ];
        }

        return $matrix;
    }

    /**
     * Identify skills that are Single Points of Failure (only 1 qualified agent).
     */
    public function getSingleAgentDependencies(): array
    {
        $matrix = $this->getSkillCoverageMatrix();
        $spofSkills = [];

        foreach ($matrix as $item) {
            if ($item['is_single_point_of_failure']) {
                $spofSkills[] = [
                    'skill_id'             => $item['skill_id'],
                    'skill_code'           => $item['skill_code'],
                    'skill_name'           => $item['skill_name'],
                    'category'             => $item['category'],
                    'sole_agent'           => $item['assigned_agents'][0] ?? null,
                    'severity'             => 'high',
                    'recommendation'       => 'Cross-train at least one secondary agent to eliminate single point of dependency.',
                ];
            }
        }

        return $spofSkills;
    }

    /**
     * Identify critical skill bottlenecks where demand or open requests exceed verified capability.
     */
    public function getSkillBottlenecks(): array
    {
        $matrix = $this->getSkillCoverageMatrix();
        $bottlenecks = [];

        // Check open resource requests per skill
        $requestDemands = [];
        if ($this->db->tableExists('resource_requests')) {
            $demands = $this->db->table('resource_requests')
                ->select('required_skill_id, COUNT(id) as request_count, SUM(hours_per_week) as demand_hours')
                ->whereIn('lifecycle_status', ['submitted', 'under_review', 'capacity_assessed', 'approved'])
                ->where('required_skill_id IS NOT NULL')
                ->groupBy('required_skill_id')
                ->get()->getResultArray();

            foreach ($demands as $d) {
                $requestDemands[(int)$d['required_skill_id']] = [
                    'request_count' => (int)$d['request_count'],
                    'demand_hours'  => (float)$d['demand_hours'],
                ];
            }
        }

        foreach ($matrix as $item) {
            $skillId = $item['skill_id'];
            $demand = $requestDemands[$skillId] ?? ['request_count' => 0, 'demand_hours' => 0.00];

            $availableSupplyHours = $item['verified_agents_count'] * 20.00; // rough 20h net available per verified agent
            $deficitHours = max(0.00, $demand['demand_hours'] - $availableSupplyHours);

            if ($item['total_agents_count'] === 0 || $deficitHours > 0 || ($demand['request_count'] > 0 && $item['verified_agents_count'] <= 1)) {
                $bottlenecks[] = [
                    'skill_id'              => $skillId,
                    'skill_code'            => $item['skill_code'],
                    'skill_name'            => $item['skill_name'],
                    'category'              => $item['category'],
                    'verified_agents_count' => $item['verified_agents_count'],
                    'open_request_count'    => $demand['request_count'],
                    'demand_hours_per_week' => $demand['demand_hours'],
                    'deficit_hours'         => $deficitHours,
                    'severity'              => ($item['total_agents_count'] === 0 || $deficitHours > 20) ? 'critical' : 'warning',
                    'recommended_action'    => $item['total_agents_count'] === 0 
                        ? 'Urgent recruitment or skill assignment required.' 
                        : 'Initiate skill verification or shift schedule rebalancing.',
                ];
            }
        }

        return $bottlenecks;
    }
}
