<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class WelfareBonusService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * List all task bonus evaluations with optional filters.
     */
    public function listTaskBonuses(array $filters = []): array
    {
        $builder = $this->db->table('welfare_task_bonus_evaluations e')
            ->select('e.*, t.task_code, t.title as task_title, t.priority as task_priority, ag.name as agent_name, ag.agent_code, u.username as approver_name')
            ->join('workforce_tasks t', 't.id = e.task_id')
            ->join('ai_agent_registry ag', 'ag.id = e.agent_id')
            ->join('users u', 'u.id = e.approved_by', 'left');

        if (!empty($filters['agent_id'])) {
            $builder->where('e.agent_id', (int)$filters['agent_id']);
        }
        if (!empty($filters['approval_status'])) {
            $builder->where('e.approval_status', $filters['approval_status']);
        }
        if (!empty($filters['issuance_status'])) {
            $builder->where('e.issuance_status', $filters['issuance_status']);
        }

        $list = $builder->orderBy('e.id', 'DESC')->get()->getResultArray();
        foreach ($list as &$item) {
            $item['id'] = (int)$item['id'];
            $item['task_id'] = (int)$item['task_id'];
            $item['agent_id'] = (int)$item['agent_id'];
            $item['calculated_bonus_tickets'] = (float)$item['calculated_bonus_tickets'];
            $item['approved_bonus_tickets'] = (float)$item['approved_bonus_tickets'];
            $item['acceptance_criteria_satisfied'] = (int)$item['acceptance_criteria_satisfied'];
            $item['is_verified'] = (int)$item['is_verified'];
        }

        return $list;
    }

    /**
     * Evaluate a task for verified bonus eligibility.
     */
    public function evaluateTaskBonus(int $taskId, ?int $actorUserId = null, array $options = []): array
    {
        $task = $this->db->table('workforce_tasks')->where('id', $taskId)->get()->getRowArray();
        if (!$task) {
            throw new \InvalidArgumentException("Task #{$taskId} not found.", 404);
        }

        // Must be completed or accepted
        if (!in_array($task['status'], ['completed', 'accepted', 'under_review'])) {
            throw new \RuntimeException("Task #{$taskId} status must be completed/accepted for bonus consideration (current: {$task['status']}).", 422);
        }

        $agentId = (int)$task['accountable_agent_id'];
        if (!$agentId) {
            throw new \RuntimeException("Task #{$taskId} has no accountable agent assigned.", 422);
        }

        // Check for duplicate active bonus evaluation
        $existing = $this->db->table('welfare_task_bonus_evaluations')
            ->where('task_id', $taskId)
            ->where('agent_id', $agentId)
            ->whereIn('approval_status', ['pending_review', 'approved'])
            ->get()
            ->getRowArray();

        if ($existing) {
            throw new \RuntimeException("Bonus evaluation for Task #{$taskId} and Agent #{$agentId} already exists (Status: {$existing['approval_status']}).", 422);
        }

        // Check Deliverable
        $deliverable = $this->db->table('task_deliverables')
            ->where('task_id', $taskId)
            ->where('is_current_accepted', 1)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        // Check Review
        $review = null;
        if ($deliverable) {
            $review = $this->db->table('task_reviews')
                ->where('deliverable_id', $deliverable['id'])
                ->where('is_verified_contribution', 1)
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();
        }

        // Zero-Self-Review Rule check
        if ($review && !empty($review['reviewer_agent_id']) && (int)$review['reviewer_agent_id'] === $agentId) {
            throw new \RuntimeException("Zero-Self-Review violation: Agent #{$agentId} cannot verify their own deliverable for bonus.", 422);
        }

        $isVerified = ($deliverable && $review && $review['review_outcome'] === 'accepted') ? 1 : 0;
        $category = $options['bonus_category'] ?? ($task['priority'] === 'critical' ? 'critical_fix' : 'completion_excellence');

        // Bonus amount determination
        $calculatedBonus = 5.00;
        if ($category === 'critical_fix') {
            $calculatedBonus = 10.00;
        } else if ($category === 'high_complexity') {
            $calculatedBonus = 8.00;
        } else if ($category === 'speed_efficiency') {
            $calculatedBonus = 6.00;
        }

        $now = date('Y-m-d H:i:s');
        $insert = [
            'task_id'                        => $taskId,
            'agent_id'                       => $agentId,
            'deliverable_id'                 => $deliverable ? (int)$deliverable['id'] : null,
            'review_id'                      => $review ? (int)$review['id'] : null,
            'bonus_category'                 => $category,
            'acceptance_criteria_satisfied'  => 1,
            'is_verified'                    => $isVerified,
            'verification_notes'             => $options['verification_notes'] ?? ($isVerified ? 'Deliverable verified by independent review.' : 'Pending formal independent review verification.'),
            'calculated_bonus_tickets'       => $calculatedBonus,
            'approved_bonus_tickets'         => 0.00,
            'approval_status'                => 'pending_review',
            'approved_by'                    => null,
            'issuance_status'                => 'pending',
            'created_at'                     => $now,
            'updated_at'                     => $now,
        ];

        $this->db->table('welfare_task_bonus_evaluations')->insert($insert);
        $evalId = (int)$this->db->insertID();

        return $this->db->table('welfare_task_bonus_evaluations')->where('id', $evalId)->get()->getRowArray();
    }

    /**
     * Approve task bonus & issue tickets in immutable ledger.
     */
    public function approveTaskBonus(int $evaluationId, int $actorUserId, ?float $approvedAmount = null): array
    {
        $eval = $this->db->table('welfare_task_bonus_evaluations')->where('id', $evaluationId)->get()->getRowArray();
        if (!$eval) {
            throw new \InvalidArgumentException("Bonus evaluation #{$evaluationId} not found.", 404);
        }

        if ($eval['approval_status'] === 'approved' && $eval['issuance_status'] === 'issued') {
            throw new \RuntimeException("Bonus evaluation #{$evaluationId} is already approved and issued.", 422);
        }

        $agentId = (int)$eval['agent_id'];
        $amount = $approvedAmount !== null ? $approvedAmount : (float)$eval['calculated_bonus_tickets'];
        if ($amount <= 0) {
            throw new \RuntimeException("Approved bonus amount must be greater than zero.", 422);
        }

        $now = date('Y-m-d H:i:s');

        // 1. Link to Individual Agent Performance History
        $agent = $this->db->table('ai_agent_registry')->where('id', $agentId)->get()->getRowArray();
        $perfHistoryId = null;
        if ($agent) {
            $task = $this->db->table('workforce_tasks')->where('id', $eval['task_id'])->get()->getRowArray();
            $taskCode = $task['task_code'] ?? "TSK-{$eval['task_id']}";

            $this->db->table('ai_agent_performance_history')->insert([
                'agent_id'           => $agentId,
                'agent_uuid'         => $agent['agent_uuid'],
                'category'           => 'task_completion',
                'title'              => "Verified Task Bonus: {$taskCode}",
                'description'        => "Awarded {$amount} WT for verified deliverable excellence in category '{$eval['bonus_category']}'.",
                'task_ref'           => $taskCode,
                'evidence_status'    => 'verified',
                'evaluator_identity' => "Human Approver #{$actorUserId}",
                'created_at'         => $now,
            ]);
            $perfHistoryId = (int)$this->db->insertID();
        }

        // 2. Issue Credit in Immutable Ledger
        $idempotencyHash = hash('sha256', "task_bonus:{$eval['task_id']}:{$agentId}:{$evaluationId}");
        $balRow = $this->db->table('welfare_agent_balances')->where('agent_id', $agentId)->get()->getRowArray();
        $currBal = (float)($balRow['current_balance'] ?? 0);
        $newBal = $currBal + $amount;

        $txCode = sprintf('TX-BONUS-T%d-A%d-%04d', $eval['task_id'], $agentId, rand(100, 999));
        $this->db->table('welfare_ticket_transactions')->insert([
            'transaction_code'     => $txCode,
            'agent_id'             => $agentId,
            'ticket_category'      => 'task_bonus',
            'transaction_type'     => 'credit',
            'amount'               => $amount,
            'balance_after'        => $newBal,
            'source_task_bonus_id' => $evaluationId,
            'idempotency_hash'     => $idempotencyHash,
            'policy_version'       => '1.0.0',
            'status'               => 'completed',
            'reason'               => "Verified task completion bonus for Task #{$eval['task_id']} ({$eval['bonus_category']}).",
            'created_by_user_id'   => $actorUserId,
            'approved_by_user_id'  => $actorUserId,
            'created_at'           => $now,
            'updated_at'           => $now,
        ]);
        $txId = (int)$this->db->insertID();

        // 3. Update Balance Cache
        if ($balRow) {
            $this->db->table('welfare_agent_balances')->where('agent_id', $agentId)->update([
                'total_credits'       => (float)$balRow['total_credits'] + $amount,
                'current_balance'     => $newBal,
                'last_transaction_id' => $txId,
                'last_reconciled_at'  => $now,
                'updated_at'          => $now,
            ]);
        } else {
            $this->db->table('welfare_agent_balances')->insert([
                'agent_id'            => $agentId,
                'total_credits'       => $amount,
                'total_debits'        => 0.00,
                'current_balance'     => $newBal,
                'last_transaction_id' => $txId,
                'last_reconciled_at'  => $now,
                'is_reconciled'       => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
        }

        // 4. Update Evaluation Record
        $this->db->table('welfare_task_bonus_evaluations')->where('id', $evaluationId)->update([
            'approved_bonus_tickets' => $amount,
            'approval_status'        => 'approved',
            'approved_by'            => $actorUserId,
            'performance_history_id' => $perfHistoryId,
            'issuance_status'        => 'issued',
            'updated_at'             => $now,
        ]);

        return $this->db->table('welfare_task_bonus_evaluations')->where('id', $evaluationId)->get()->getRowArray();
    }
}
