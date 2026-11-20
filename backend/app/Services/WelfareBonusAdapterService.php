<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class WelfareBonusAdapterService
{
    protected BaseConnection $db;

    public const FINANCIAL_DOA_LIMIT_IDR = 5000000.00; // Level B autonomous ceiling: <= IDR 5,000,000

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function listBonuses(array $filters = []): array
    {
        $builder = $this->db->table('welfare_bonus_references wbr')
            ->select('wbr.*, a.agent_code, a.name as agent_name, ape.evaluation_code, ape.composite_score, ape.score_band')
            ->join('ai_agent_registry a', 'a.id = wbr.agent_id', 'left')
            ->join('agent_performance_evaluations ape', 'ape.id = wbr.evaluation_id', 'left')
            ->orderBy('wbr.id', 'DESC');

        if (!empty($filters['agent_id'])) {
            $builder->where('wbr.agent_id', (int)$filters['agent_id']);
        }
        if (!empty($filters['approval_status'])) {
            $builder->where('wbr.approval_status', $filters['approval_status']);
        }
        if (!empty($filters['financial_approval_level'])) {
            $builder->where('wbr.financial_approval_level', $filters['financial_approval_level']);
        }
        if (!empty($filters['bonus_category'])) {
            $builder->where('wbr.bonus_category', $filters['bonus_category']);
        }

        return $builder->get()->getResultArray();
    }

    public function getBonus(int $id): ?array
    {
        $bonus = $this->db->table('welfare_bonus_references wbr')
            ->select('wbr.*, a.agent_code, a.name as agent_name, ape.evaluation_code, ape.composite_score, ape.score_band, wcp.period_code')
            ->join('ai_agent_registry a', 'a.id = wbr.agent_id', 'left')
            ->join('agent_performance_evaluations ape', 'ape.id = wbr.evaluation_id', 'left')
            ->join('welfare_compensation_periods wcp', 'wcp.id = wbr.welfare_period_id', 'left')
            ->where('wbr.id', $id)
            ->get()
            ->getRowArray();

        return $bonus ?: null;
    }

    public function recommendBonus(array $data, ?int $userId = null): array
    {
        $now = date('Y-m-d H:i:s');
        $code = trim($data['reference_code'] ?? 'WBR-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4)));

        $financialImpact = (float)($data['financial_impact_idr'] ?? 0.00);
        // Automatic DoA level determination: > 5M IDR requires Level A (Human Owner)
        $approvalLevel = ($financialImpact > self::FINANCIAL_DOA_LIMIT_IDR) ? 'level_a' : 'level_b';

        $insert = [
            'reference_code'           => $code,
            'evaluation_id'            => (int)($data['evaluation_id'] ?? 0),
            'agent_id'                 => (int)($data['agent_id'] ?? 0),
            'bonus_category'           => $data['bonus_category'] ?? 'high_complexity_delivery',
            'recommended_ticket_bonus' => (float)($data['recommended_ticket_bonus'] ?? 10.00),
            'justification'            => trim($data['justification'] ?? 'Exceptional performance verified in evaluation cycle'),
            'financial_approval_level' => $data['financial_approval_level'] ?? $approvalLevel,
            'financial_impact_idr'     => $financialImpact,
            'approval_status'          => 'pending',
            'welfare_period_id'        => !empty($data['welfare_period_id']) ? (int)$data['welfare_period_id'] : null,
            'created_at'               => $now,
            'updated_at'               => $now,
        ];

        $this->db->table('welfare_bonus_references')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();

        // Audit Trail
        $this->db->table('performance_audit_trail')->insert([
            'event_type'     => 'welfare_bonus_recommended',
            'target_entity'  => 'welfare_bonus_references',
            'target_id'      => $insert['id'],
            'actor_type'     => $userId ? 'human' : 'system',
            'actor_id'       => $userId,
            'action_summary' => "Recommended Welfare Bonus {$code} for Agent #{$insert['agent_id']} ({$insert['financial_approval_level']})",
            'created_at'     => $now,
        ]);

        return $insert;
    }

    public function approveBonus(int $id, int $userId, string $userRole = 'human_owner'): array
    {
        $bonus = $this->getBonus($id);
        if (!$bonus) {
            throw new \InvalidArgumentException("Bonus reference #{$id} not found.", 404);
        }

        // DoA Gatekeeper check: Level A bonus (> IDR 5,000,000) MUST be approved by human_owner
        if ($bonus['financial_approval_level'] === 'level_a' && !in_array($userRole, ['human_owner', 'owner', 'superadmin'], true)) {
            throw new \RuntimeException("Delegated Authority Violation: Level A bonuses exceeding IDR 5,000,000 require explicit Human Owner approval.", 403);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('welfare_bonus_references')->where('id', $id)->update([
            'approval_status'     => 'approved',
            'approved_by_user_id' => $userId,
            'approved_at'         => $now,
            'updated_at'          => $now,
        ]);

        // Audit Trail
        $this->db->table('performance_audit_trail')->insert([
            'event_type'     => 'welfare_bonus_approved',
            'target_entity'  => 'welfare_bonus_references',
            'target_id'      => $id,
            'actor_type'     => 'human',
            'actor_id'       => $userId,
            'action_summary' => "Approved Welfare Bonus {$bonus['reference_code']} (DoA: {$bonus['financial_approval_level']})",
            'created_at'     => $now,
        ]);

        return $this->getBonus($id);
    }

    public function rejectBonus(int $id, int $userId, string $reason = ''): array
    {
        $bonus = $this->getBonus($id);
        if (!$bonus) {
            throw new \InvalidArgumentException("Bonus reference #{$id} not found.", 404);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('welfare_bonus_references')->where('id', $id)->update([
            'approval_status' => 'rejected',
            'justification'   => trim($bonus['justification'] . " [Rejected by user #{$userId}: {$reason}]"),
            'updated_at'      => $now,
        ]);

        // Audit Trail
        $this->db->table('performance_audit_trail')->insert([
            'event_type'     => 'welfare_bonus_rejected',
            'target_entity'  => 'welfare_bonus_references',
            'target_id'      => $id,
            'actor_type'     => 'human',
            'actor_id'       => $userId,
            'action_summary' => "Rejected Welfare Bonus {$bonus['reference_code']}",
            'created_at'     => $now,
        ]);

        return $this->getBonus($id);
    }

    public function disburseBonus(int $id, ?int $welfarePeriodId = null, ?int $userId = null): array
    {
        $bonus = $this->getBonus($id);
        if (!$bonus) {
            throw new \InvalidArgumentException("Bonus reference #{$id} not found.", 404);
        }

        if ($bonus['approval_status'] !== 'approved') {
            throw new \RuntimeException("Bonus {$bonus['reference_code']} must be in 'approved' status before disbursement.", 400);
        }

        $now = date('Y-m-d H:i:s');
        $update = [
            'approval_status' => 'disbursed',
            'disbursed_at'    => $now,
            'updated_at'      => $now,
        ];
        if ($welfarePeriodId) {
            $update['welfare_period_id'] = $welfarePeriodId;
        }

        $this->db->table('welfare_bonus_references')->where('id', $id)->update($update);

        // Audit Trail
        $this->db->table('performance_audit_trail')->insert([
            'event_type'     => 'welfare_bonus_disbursed',
            'target_entity'  => 'welfare_bonus_references',
            'target_id'      => $id,
            'actor_type'     => $userId ? 'human' : 'system',
            'actor_id'       => $userId,
            'action_summary' => "Disbursed Welfare Bonus {$bonus['reference_code']} to Agent #{$bonus['agent_id']}",
            'created_at'     => $now,
        ]);

        return $this->getBonus($id);
    }

    public function evaluateEligibility(int $agentId, int $cycleId): array
    {
        $eval = $this->db->table('agent_performance_evaluations')
            ->where('cycle_id', $cycleId)
            ->where('agent_id', $agentId)
            ->get()
            ->getRowArray();

        if (!$eval) {
            return [
                'eligible' => false,
                'reason'   => "No evaluation found for Agent #{$agentId} in Cycle #{$cycleId}",
            ];
        }

        $score = (float)$eval['composite_score'];
        $complexity = (float)$eval['complexity_factor'];

        if ($score >= 90.0) {
            $tickets = round(($score - 85.0) * 2, 2);
            $idr = $tickets * 250000; // IDR 250k per ticket benchmark
            return [
                'eligible'                => true,
                'category'                => 'exceptional_quality',
                'recommended_tickets'     => $tickets,
                'estimated_financial_idr' => $idr,
                'financial_approval_level'=> $idr > self::FINANCIAL_DOA_LIMIT_IDR ? 'level_a' : 'level_b',
                'justification'           => "Composite score {$score} qualifies for exceptional quality welfare bonus.",
            ];
        }

        if ($complexity >= 1.25) {
            $tickets = 15.0;
            $idr = 3750000.0;
            return [
                'eligible'                => true,
                'category'                => 'high_complexity_delivery',
                'recommended_tickets'     => $tickets,
                'estimated_financial_idr' => $idr,
                'financial_approval_level'=> 'level_b',
                'justification'           => "Complexity multiplier {$complexity} reflects high-complexity project execution.",
            ];
        }

        return [
            'eligible' => false,
            'reason'   => "Score ({$score}) and complexity factor ({$complexity}) do not meet bonus thresholds.",
        ];
    }
}
