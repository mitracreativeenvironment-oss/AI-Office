<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class WelfareRecognitionService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * List special recognition records with optional filters.
     */
    public function listRecognitions(array $filters = []): array
    {
        $builder = $this->db->table('welfare_special_recognitions r')
            ->select('r.*, ag.name as agent_name, ag.agent_code, ag.classification as agent_classification, u.username as approver_name')
            ->join('ai_agent_registry ag', 'ag.id = r.agent_id')
            ->join('users u', 'u.id = r.approved_by_user_id', 'left');

        if (!empty($filters['agent_id'])) {
            $builder->where('r.agent_id', (int)$filters['agent_id']);
        }
        if (!empty($filters['approval_status'])) {
            $builder->where('r.approval_status', $filters['approval_status']);
        }
        if (!empty($filters['category'])) {
            $builder->where('r.category', $filters['category']);
        }

        $list = $builder->orderBy('r.id', 'DESC')->get()->getResultArray();
        foreach ($list as &$item) {
            $item['id'] = (int)$item['id'];
            $item['agent_id'] = (int)$item['agent_id'];
            $item['awarded_tickets'] = (float)$item['awarded_tickets'];
        }

        return $list;
    }

    /**
     * Nominate an agent for special recognition.
     */
    public function nominateRecognition(array $data, ?int $actorUserId = null): array
    {
        $agentId = (int)($data['agent_id'] ?? 0);
        $agent = $this->db->table('ai_agent_registry')->where('id', $agentId)->get()->getRowArray();
        if (!$agent) {
            throw new \InvalidArgumentException("Agent #{$agentId} not found.", 404);
        }

        $recCode = 'REC-' . date('Y') . '-' . rand(1000, 9999);
        $amount = isset($data['awarded_tickets']) ? (float)$data['awarded_tickets'] : 10.00;
        $doaLevel = $data['doa_level_required'] ?? ($amount > 20.0 ? 'A' : 'B');
        $now = date('Y-m-d H:i:s');

        $insert = [
            'recognition_code'       => $recCode,
            'agent_id'               => $agentId,
            'title'                  => trim($data['title'] ?? 'Special Recognition Award'),
            'category'               => $data['category'] ?? 'exceptional_contribution',
            'justification'          => trim($data['justification'] ?? 'Demonstrated outstanding operational contribution.'),
            'evidence_ref'           => $data['evidence_ref'] ?? null,
            'awarded_tickets'        => $amount,
            'nominated_by_user_id'   => $actorUserId,
            'approved_by_user_id'    => null,
            'approval_status'        => 'pending_review',
            'doa_level_required'     => $doaLevel,
            'performance_history_id' => null,
            'issuance_status'        => 'pending',
            'created_at'             => $now,
            'updated_at'             => $now,
        ];

        $this->db->table('welfare_special_recognitions')->insert($insert);
        $recId = (int)$this->db->insertID();

        return $this->db->table('welfare_special_recognitions')->where('id', $recId)->get()->getRowArray();
    }

    /**
     * Approve special recognition and credit tickets in ledger.
     */
    public function approveRecognition(int $recognitionId, int $actorUserId, ?float $awardedAmount = null): array
    {
        $rec = $this->db->table('welfare_special_recognitions')->where('id', $recognitionId)->get()->getRowArray();
        if (!$rec) {
            throw new \InvalidArgumentException("Recognition #{$recognitionId} not found.", 404);
        }

        if ($rec['approval_status'] === 'approved' && $rec['issuance_status'] === 'issued') {
            throw new \RuntimeException("Recognition #{$recognitionId} is already approved and issued.", 422);
        }

        $agentId = (int)$rec['agent_id'];
        $amount = $awardedAmount !== null ? $awardedAmount : (float)$rec['awarded_tickets'];
        if ($amount <= 0) {
            throw new \RuntimeException("Recognition awarded amount must be greater than zero.", 422);
        }

        $now = date('Y-m-d H:i:s');

        // 1. Link to Individual Agent Performance History
        $agent = $this->db->table('ai_agent_registry')->where('id', $agentId)->get()->getRowArray();
        $perfHistoryId = null;
        if ($agent) {
            $this->db->table('ai_agent_performance_history')->insert([
                'agent_id'           => $agentId,
                'agent_uuid'         => $agent['agent_uuid'],
                'category'           => 'recognition',
                'title'              => "Special Recognition: {$rec['title']}",
                'description'        => "Awarded {$amount} WT under category '{$rec['category']}': {$rec['justification']}",
                'evidence_ref'       => $rec['evidence_ref'],
                'evidence_status'    => 'verified',
                'evaluator_identity' => "Governance Approver #{$actorUserId}",
                'created_at'         => $now,
            ]);
            $perfHistoryId = (int)$this->db->insertID();
        }

        // 2. Issue Credit in Immutable Ledger
        $idempotencyHash = hash('sha256', "recognition:{$agentId}:{$recognitionId}");
        $balRow = $this->db->table('welfare_agent_balances')->where('agent_id', $agentId)->get()->getRowArray();
        $currBal = (float)($balRow['current_balance'] ?? 0);
        $newBal = $currBal + $amount;

        $txCode = sprintf('TX-REC-A%d-%04d', $agentId, rand(100, 999));
        $this->db->table('welfare_ticket_transactions')->insert([
            'transaction_code'      => $txCode,
            'agent_id'              => $agentId,
            'ticket_category'       => 'special_recognition',
            'transaction_type'      => 'credit',
            'amount'                => $amount,
            'balance_after'         => $newBal,
            'source_recognition_id' => $recognitionId,
            'idempotency_hash'      => $idempotencyHash,
            'policy_version'        => '1.0.0',
            'status'                => 'completed',
            'reason'                => "Special Recognition Award: {$rec['title']}",
            'created_by_user_id'    => $actorUserId,
            'approved_by_user_id'   => $actorUserId,
            'created_at'            => $now,
            'updated_at'            => $now,
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

        // 4. Update Recognition Status
        $this->db->table('welfare_special_recognitions')->where('id', $recognitionId)->update([
            'awarded_tickets'        => $amount,
            'approval_status'        => 'approved',
            'approved_by_user_id'    => $actorUserId,
            'performance_history_id' => $perfHistoryId,
            'issuance_status'        => 'issued',
            'updated_at'             => $now,
        ]);

        return $this->db->table('welfare_special_recognitions')->where('id', $recognitionId)->get()->getRowArray();
    }
}
