<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class WelfareLedgerService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * List all ledger transactions (paginated & filterable).
     */
    public function listTransactions(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $builder = $this->db->table('welfare_ticket_transactions tx')
            ->select('tx.*, ag.name as agent_name, ag.agent_code, u.username as creator_name, app.username as approver_name')
            ->join('ai_agent_registry ag', 'ag.id = tx.agent_id')
            ->join('users u', 'u.id = tx.created_by_user_id', 'left')
            ->join('users app', 'app.id = tx.approved_by_user_id', 'left');

        if (!empty($filters['agent_id'])) {
            $builder->where('tx.agent_id', (int)$filters['agent_id']);
        }
        if (!empty($filters['ticket_category'])) {
            $builder->where('tx.ticket_category', $filters['ticket_category']);
        }
        if (!empty($filters['transaction_type'])) {
            $builder->where('tx.transaction_type', $filters['transaction_type']);
        }
        if (!empty($filters['status'])) {
            $builder->where('tx.status', $filters['status']);
        }

        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults();

        $items = $builder->orderBy('tx.id', 'DESC')->limit($limit, $offset)->get()->getResultArray();
        foreach ($items as &$item) {
            $item['id'] = (int)$item['id'];
            $item['agent_id'] = (int)$item['agent_id'];
            $item['amount'] = (float)$item['amount'];
            $item['balance_after'] = (float)$item['balance_after'];
        }

        return [
            'items'  => $items,
            'total'  => $total,
            'limit'  => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Get real-time balance for an agent with reconciliation status.
     */
    public function getAgentBalance(int $agentId): array
    {
        $agent = $this->db->table('ai_agent_registry')->where('id', $agentId)->get()->getRowArray();
        if (!$agent) {
            throw new \InvalidArgumentException("Agent #{$agentId} not found.", 404);
        }

        $balRow = $this->db->table('welfare_agent_balances')->where('agent_id', $agentId)->get()->getRowArray();
        if (!$balRow) {
            // Initialize if missing
            $now = date('Y-m-d H:i:s');
            $this->db->table('welfare_agent_balances')->insert([
                'agent_id'            => $agentId,
                'total_credits'       => 0.00,
                'total_debits'        => 0.00,
                'current_balance'     => 0.00,
                'last_transaction_id' => null,
                'last_reconciled_at'  => $now,
                'is_reconciled'       => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
            $balRow = $this->db->table('welfare_agent_balances')->where('agent_id', $agentId)->get()->getRowArray();
        }

        return [
            'agent_id'           => (int)$agentId,
            'agent_code'         => $agent['agent_code'],
            'agent_name'         => $agent['name'],
            'total_credits'      => (float)$balRow['total_credits'],
            'total_debits'       => (float)$balRow['total_debits'],
            'current_balance'    => (float)$balRow['current_balance'],
            'is_reconciled'      => (bool)$balRow['is_reconciled'],
            'last_reconciled_at' => $balRow['last_reconciled_at'],
        ];
    }

    /**
     * Reconcile agent balance by computing exact ledger sums.
     */
    public function reconcileLedger(int $agentId): array
    {
        $credits = $this->db->table('welfare_ticket_transactions')
            ->where('agent_id', $agentId)
            ->where('transaction_type', 'credit')
            ->where('status', 'completed')
            ->selectSum('amount')
            ->get()
            ->getRowArray()['amount'] ?? 0;

        $debits = $this->db->table('welfare_ticket_transactions')
            ->where('agent_id', $agentId)
            ->where('transaction_type', 'debit')
            ->where('status', 'completed')
            ->selectSum('amount')
            ->get()
            ->getRowArray()['amount'] ?? 0;

        $ledgerBalance = round((float)$credits - (float)$debits, 2);
        $now = date('Y-m-d H:i:s');

        $this->db->table('welfare_agent_balances')->where('agent_id', $agentId)->update([
            'total_credits'      => (float)$credits,
            'total_debits'       => (float)$debits,
            'current_balance'    => $ledgerBalance,
            'is_reconciled'      => 1,
            'last_reconciled_at' => $now,
            'updated_at'         => $now,
        ]);

        return $this->getAgentBalance($agentId);
    }

    /**
     * Post an administrative adjustment (Credit or Debit) without altering history.
     */
    public function postAdjustment(int $agentId, float $amount, string $reason, int $actorUserId, string $direction = 'credit'): array
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Adjustment amount must be positive.", 400);
        }

        $agent = $this->db->table('ai_agent_registry')->where('id', $agentId)->get()->getRowArray();
        if (!$agent) {
            throw new \InvalidArgumentException("Agent #{$agentId} not found.", 404);
        }

        $bal = $this->getAgentBalance($agentId);
        $currBal = (float)$bal['current_balance'];
        $newBal = $direction === 'credit' ? ($currBal + $amount) : ($currBal - $amount);
        if ($newBal < 0) {
            throw new \RuntimeException("Adjustment would result in negative ticket balance ({$newBal} WT).", 422);
        }

        $now = date('Y-m-d H:i:s');
        $txCode = 'TX-ADJ-' . uniqid();
        $idempotencyHash = hash('sha256', "adjustment:{$agentId}:{$direction}:{$amount}:" . microtime(true));

        $this->db->table('welfare_ticket_transactions')->insert([
            'transaction_code'    => $txCode,
            'agent_id'            => $agentId,
            'ticket_category'     => 'adjustment',
            'transaction_type'    => $direction,
            'amount'              => $amount,
            'balance_after'       => $newBal,
            'idempotency_hash'    => $idempotencyHash,
            'policy_version'      => '1.0.0',
            'status'              => 'completed',
            'reason'              => $reason,
            'created_by_user_id'  => $actorUserId,
            'approved_by_user_id' => $actorUserId,
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);
        $txId = (int)$this->db->insertID();

        // Update balance
        if ($direction === 'credit') {
            $this->db->table('welfare_agent_balances')->where('agent_id', $agentId)->update([
                'total_credits'       => (float)$bal['total_credits'] + $amount,
                'current_balance'     => $newBal,
                'last_transaction_id' => $txId,
                'last_reconciled_at'  => $now,
                'updated_at'          => $now,
            ]);
        } else {
            $this->db->table('welfare_agent_balances')->where('agent_id', $agentId)->update([
                'total_debits'        => (float)$bal['total_debits'] + $amount,
                'current_balance'     => $newBal,
                'last_transaction_id' => $txId,
                'last_reconciled_at'  => $now,
                'updated_at'          => $now,
            ]);
        }

        // Audit Trail
        $this->db->table('welfare_audit_trail')->insert([
            'event_uuid'     => \App\Services\AgentRegistryService::generateUuid(),
            'event_category' => 'adjustment',
            'action'         => 'adjustment_posted',
            'target_type'    => 'ticket_transaction',
            'target_id'      => $txId,
            'actor_type'     => 'human_user',
            'actor_id'       => $actorUserId,
            'details'        => json_encode(['direction' => $direction, 'amount' => $amount, 'reason' => $reason]),
            'created_at'     => $now,
        ]);

        return $this->db->table('welfare_ticket_transactions')->where('id', $txId)->get()->getRowArray();
    }

    /**
     * Reverse a previous transaction with audit reference.
     */
    public function postReversal(int $transactionId, string $reason, int $actorUserId): array
    {
        $origTx = $this->db->table('welfare_ticket_transactions')->where('id', $transactionId)->get()->getRowArray();
        if (!$origTx) {
            throw new \InvalidArgumentException("Transaction #{$transactionId} not found.", 404);
        }

        if ($origTx['status'] === 'reversed') {
            throw new \RuntimeException("Transaction #{$transactionId} is already reversed.", 422);
        }

        $agentId = (int)$origTx['agent_id'];
        $amount = (float)$origTx['amount'];
        $reverseType = ($origTx['transaction_type'] === 'credit') ? 'debit' : 'credit';

        $bal = $this->getAgentBalance($agentId);
        $currBal = (float)$bal['current_balance'];
        $newBal = ($reverseType === 'credit') ? ($currBal + $amount) : ($currBal - $amount);

        if ($newBal < 0) {
            throw new \RuntimeException("Reversal would result in negative ticket balance ({$newBal} WT).", 422);
        }

        $now = date('Y-m-d H:i:s');
        $txCode = 'TX-REV-' . uniqid();
        $idempotencyHash = hash('sha256', "reversal:{$transactionId}:" . microtime(true));

        $this->db->table('welfare_ticket_transactions')->insert([
            'transaction_code'            => $txCode,
            'agent_id'                    => $agentId,
            'ticket_category'             => 'reversal',
            'transaction_type'            => $reverseType,
            'amount'                      => $amount,
            'balance_after'               => $newBal,
            'reversal_ref_transaction_id' => $transactionId,
            'idempotency_hash'            => $idempotencyHash,
            'policy_version'              => $origTx['policy_version'],
            'status'                      => 'completed',
            'reason'                      => "Reversal of {$origTx['transaction_code']}: {$reason}",
            'created_by_user_id'          => $actorUserId,
            'approved_by_user_id'         => $actorUserId,
            'created_at'                  => $now,
            'updated_at'                  => $now,
        ]);
        $revTxId = (int)$this->db->insertID();

        // Mark original transaction as reversed
        $this->db->table('welfare_ticket_transactions')->where('id', $transactionId)->update([
            'status'     => 'reversed',
            'updated_at' => $now,
        ]);

        // Reconcile balance
        $this->reconcileLedger($agentId);

        return $this->db->table('welfare_ticket_transactions')->where('id', $revTxId)->get()->getRowArray();
    }
}
