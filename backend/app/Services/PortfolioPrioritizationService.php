<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class PortfolioPrioritizationService
{
    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    public function getPortfolioPriorities(?string $period = null): array
    {
        $builder = $this->db->table('portfolio_priorities pp')
            ->select('pp.*, p.name as project_name, p.project_code, p.project_type, p.status as project_status, p.lifecycle_stage, p.priority as declared_priority, p.health_status, u.username as evaluator_username')
            ->join('projects p', 'p.id = pp.project_id')
            ->join('users u', 'u.id = pp.evaluated_by_user_id', 'left')
            ->orderBy('pp.calculated_rank', 'ASC');

        if ($period) {
            $builder->where('pp.evaluation_period', $period);
        }

        return $builder->get()->getResultArray();
    }

    public function getPriorityByProjectId(int $projectId): ?array
    {
        return $this->db->table('portfolio_priorities pp')
            ->select('pp.*, p.name as project_name, p.project_code, p.project_type, p.status as project_status, p.lifecycle_stage, p.priority as declared_priority, p.health_status, u.username as evaluator_username')
            ->join('projects p', 'p.id = pp.project_id')
            ->join('users u', 'u.id = pp.evaluated_by_user_id', 'left')
            ->where('pp.project_id', $projectId)
            ->get()->getRowArray();
    }

    public function evaluateProjectPriority(int $projectId, array $scores, ?array $actor = null): array
    {
        $now = date('Y-m-d H:i:s');
        $period = $scores['evaluation_period'] ?? '2026-Q4';

        $strat = (float)($scores['strategic_alignment_score'] ?? 50.00);
        $cust  = (float)($scores['customer_commitment_score'] ?? 50.00);
        $biz   = (float)($scores['business_value_score'] ?? 50.00);
        $urg   = (float)($scores['urgency_score'] ?? 50.00);
        $feas  = (float)($scores['feasibility_score'] ?? 50.00);
        $risk  = (float)($scores['risk_penalty_score'] ?? 20.00);

        // Weighted Composite Score formula
        $composite = (0.25 * $strat) + (0.25 * $cust) + (0.20 * $biz) + (0.15 * $urg) + (0.15 * $feas) - (0.10 * $risk);
        $composite = max(0.00, min(100.00, round($composite, 2)));

        // Determine Priority Tier
        if ($composite >= 80.00) {
            $tier = 'tier_1_critical';
        } elseif ($composite >= 65.00) {
            $tier = 'tier_2_high';
        } elseif ($composite >= 50.00) {
            $tier = 'tier_3_medium';
        } else {
            $tier = 'tier_4_deferrable';
        }

        $existing = $this->db->table('portfolio_priorities')->where('project_id', $projectId)->get()->getRowArray();

        $saveData = [
            'project_id'                => $projectId,
            'evaluation_period'         => $period,
            'strategic_alignment_score' => $strat,
            'customer_commitment_score' => $cust,
            'business_value_score'      => $biz,
            'urgency_score'             => $urg,
            'feasibility_score'         => $feas,
            'risk_penalty_score'        => $risk,
            'composite_priority_score'  => $composite,
            'priority_tier'             => $tier,
            'evaluation_rationale'      => $scores['evaluation_rationale'] ?? 'Scored via multi-criteria evaluation matrix.',
            'evaluated_by_user_id'      => $actor['id'] ?? 1,
            'evaluated_at'              => $now,
            'is_overridden'             => 0,
            'override_reason'           => null,
            'updated_at'                => $now,
        ];

        if ($existing) {
            $this->db->table('portfolio_priorities')->where('id', $existing['id'])->update($saveData);
            $id = $existing['id'];
        } else {
            $saveData['calculated_rank'] = 999;
            $saveData['created_at'] = $now;
            $this->db->table('portfolio_priorities')->insert($saveData);
            $id = (int)$this->db->insertID();
        }

        $this->recalculatePortfolioRanks($period);
        $this->logAuditEvent('portfolio_evaluated', 'priority', $id, $saveData, $actor);

        return $this->getPriorityByProjectId($projectId);
    }

    public function recalculatePortfolioRanks(?string $period = '2026-Q4'): void
    {
        $builder = $this->db->table('portfolio_priorities');
        if ($period) {
            $builder->where('evaluation_period', $period);
        }

        $items = $builder->orderBy('composite_priority_score', 'DESC')->get()->getResultArray();
        $rank = 1;
        foreach ($items as $item) {
            if ((int)$item['is_overridden'] !== 1) {
                $this->db->table('portfolio_priorities')->where('id', $item['id'])->update(['calculated_rank' => $rank]);
                $rank++;
            }
        }
    }

    public function overridePriority(int $projectId, int $newRank, string $tier, string $reason, ?array $actor = null): ?array
    {
        $existing = $this->db->table('portfolio_priorities')->where('project_id', $projectId)->get()->getRowArray();
        if (!$existing) {
            return null;
        }

        $now = date('Y-m-d H:i:s');
        $updateData = [
            'calculated_rank' => $newRank,
            'priority_tier'   => $tier,
            'is_overridden'   => 1,
            'override_reason' => $reason,
            'updated_at'      => $now,
        ];

        $this->db->table('portfolio_priorities')->where('id', $existing['id'])->update($updateData);

        $this->logAuditEvent('portfolio_priority_overridden', 'priority', $existing['id'], $updateData, $actor);

        return $this->getPriorityByProjectId($projectId);
    }

    protected function logAuditEvent(string $eventType, string $targetType, int $targetId, array $metadata, ?array $actor = null): void
    {
        $this->db->table('resource_audit_events')->insert([
            'event_type'    => $eventType,
            'actor_type'    => $actor['type'] ?? 'user',
            'actor_id'      => $actor['id'] ?? null,
            'actor_name'    => $actor['name'] ?? 'System Prioritization',
            'target_type'   => $targetType,
            'target_id'     => $targetId,
            'metadata_json' => json_encode($metadata),
            'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }
}
