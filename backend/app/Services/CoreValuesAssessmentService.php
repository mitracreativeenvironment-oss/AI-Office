<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class CoreValuesAssessmentService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getAgentCoreValues(int $agentId, ?int $cycleId = null): array
    {
        $builder = $this->db->table('core_values_assessments')
            ->where('agent_id', $agentId)
            ->orderBy('id', 'DESC');

        if ($cycleId) {
            $builder->where('cycle_id', $cycleId);
        }

        return $builder->get()->getResultArray();
    }

    public function recordAssessment(array $data, ?int $userId = null): array
    {
        $now = date('Y-m-d H:i:s');
        $validValues = ['integrity', 'innovation', 'collaboration', 'excellence', 'adaptability', 'customer_centric', 'sustainability'];

        $coreValue = strtolower(trim($data['core_value'] ?? 'integrity'));
        if (!in_array($coreValue, $validValues, true)) {
            throw new \InvalidArgumentException("Invalid core value '{$coreValue}'. Must be one of: " . implode(', ', $validValues), 400);
        }

        $score = isset($data['rating_score']) ? (float)$data['rating_score'] : 85.00;
        $level = 'proficient';
        if ($score >= 95.0) $level = 'exemplary';
        elseif ($score < 75.0) $level = 'developing';
        elseif ($score < 60.0) $level = 'unsatisfactory';

        $insert = [
            'evaluation_id'       => !empty($data['evaluation_id']) ? (int)$data['evaluation_id'] : null,
            'cycle_id'            => !empty($data['cycle_id']) ? (int)$data['cycle_id'] : null,
            'agent_id'            => (int)($data['agent_id'] ?? 1),
            'core_value'          => $coreValue,
            'rating_score'        => $score,
            'performance_level'   => $data['performance_level'] ?? $level,
            'behavioral_evidence' => $data['behavioral_evidence'] ?? 'Observed compliance with MCE standards.',
            'justification'       => $data['justification'] ?? 'Evaluated based on verifiable operational contributions.',
            'evaluator_identity'  => $userId ? "Human Evaluator User #{$userId}" : ($data['evaluator_identity'] ?? 'Continuous Executive Board'),
            'assessed_at'         => $now,
            'created_at'          => $now,
            'updated_at'          => $now,
        ];

        $this->db->table('core_values_assessments')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();

        return $insert;
    }

    public function getCompanyCoreValuesSummary(): array
    {
        $rows = $this->db->table('core_values_assessments')->get()->getResultArray();

        $summary = [
            'integrity'        => ['count' => 0, 'avg_score' => 0.0],
            'innovation'       => ['count' => 0, 'avg_score' => 0.0],
            'collaboration'    => ['count' => 0, 'avg_score' => 0.0],
            'excellence'       => ['count' => 0, 'avg_score' => 0.0],
            'adaptability'     => ['count' => 0, 'avg_score' => 0.0],
            'customer_centric' => ['count' => 0, 'avg_score' => 0.0],
            'sustainability'   => ['count' => 0, 'avg_score' => 0.0],
        ];

        $sums = array_fill_keys(array_keys($summary), 0.0);

        foreach ($rows as $r) {
            $val = $r['core_value'];
            if (isset($summary[$val])) {
                $summary[$val]['count']++;
                $sums[$val] += (float)$r['rating_score'];
            }
        }

        foreach ($summary as $key => &$item) {
            $item['avg_score'] = ($item['count'] > 0) ? round($sums[$key] / $item['count'], 2) : 90.00;
        }

        return [
            'total_assessments_logged' => count($rows),
            'dimensions'               => $summary,
            'calculated_at'            => date('Y-m-d H:i:s'),
        ];
    }

    public function getAgentAssessments(int $agentId, ?int $cycleId = null): array
    {
        return $this->getAgentCoreValues($agentId, $cycleId);
    }

    public function assessCoreValue(array $data, ?string $evaluator = null): array
    {
        if ($evaluator && empty($data['evaluator_identity'])) {
            $data['evaluator_identity'] = $evaluator;
        }
        return $this->recordAssessment($data);
    }

    public function getCoreValuesSummary(?int $cycleId = null): array
    {
        return $this->getCompanyCoreValuesSummary();
    }
}
