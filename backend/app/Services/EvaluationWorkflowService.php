<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class EvaluationWorkflowService
{
    protected BaseConnection $db;
    protected AgentEvaluationService $agentEvalService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->agentEvalService = new AgentEvaluationService();
    }

    public function listCycles(array $filters = []): array
    {
        $builder = $this->db->table('performance_evaluation_cycles')->orderBy('id', 'DESC');
        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }
        if (!empty($filters['cycle_type'])) {
            $builder->where('cycle_type', $filters['cycle_type']);
        }
        return $builder->get()->getResultArray();
    }

    public function getCycle(int $id): ?array
    {
        $cycle = $this->db->table('performance_evaluation_cycles')->where('id', $id)->get()->getRowArray();
        if (!$cycle) return null;

        $cycle['evaluations'] = $this->db->table('agent_performance_evaluations e')
            ->select('e.*, a.agent_code, a.name as agent_name')
            ->join('ai_agent_registry a', 'a.id = e.agent_id', 'left')
            ->where('e.cycle_id', $id)
            ->get()
            ->getResultArray();

        return $cycle;
    }

    public function createCycle(array $data, ?int $userId = null): array
    {
        $now = date('Y-m-d H:i:s');
        $code = trim($data['cycle_code'] ?? 'CYC-' . date('Y-m') . '-' . strtoupper(substr(uniqid(), -4)));

        $insert = [
            'cycle_code'             => $code,
            'title'                  => trim($data['title'] ?? "Evaluation Cycle {$code}"),
            'cycle_type'             => $data['cycle_type'] ?? 'monthly',
            'period_start'           => $data['period_start'] ?? date('Y-m-01'),
            'period_end'             => $data['period_end'] ?? date('Y-m-t'),
            'status'                 => $data['status'] ?? 'open',
            'opened_by_user_id'      => $userId,
            'total_agents_evaluated' => 0,
            'notes'                  => $data['notes'] ?? null,
            'created_at'             => $now,
            'updated_at'             => $now,
        ];

        $this->db->table('performance_evaluation_cycles')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();

        return $insert;
    }

    public function transitionCycleStatus(int $cycleId, string $targetStatus, ?int $userId = null): ?array
    {
        $cycle = $this->getCycle($cycleId);
        if (!$cycle) return null;

        $validStatuses = ['draft', 'open', 'data_collection', 'evidence_aggregation', 'kpi_calculation', 'automated_validation', 'reviewer_assessment', 'review', 'approval_pending', 'finalized', 'archived'];
        if (!in_array($targetStatus, $validStatuses, true)) {
            throw new \InvalidArgumentException("Invalid cycle status '{$targetStatus}'.", 400);
        }

        $now = date('Y-m-d H:i:s');
        $update = [
            'status'     => $targetStatus,
            'updated_at' => $now,
        ];

        if ($targetStatus === 'finalized') {
            $update['finalized_by_user_id'] = $userId;
            $update['finalized_at'] = $now;
        }

        $this->db->table('performance_evaluation_cycles')->where('id', $cycleId)->update($update);
        return $this->getCycle($cycleId);
    }

    public function finalizeEvaluation(int $evalId, int $userId, string $comments = ''): array
    {
        $eval = $this->agentEvalService->getEvaluation($evalId);
        if (!$eval) {
            throw new \InvalidArgumentException("Evaluation #{$evalId} not found.", 404);
        }

        if ((int)$eval['is_finalized'] === 1) {
            return $eval; // Already frozen
        }

        return $this->agentEvalService->transitionStatus($evalId, 'finalized', $comments, $userId);
    }
}
