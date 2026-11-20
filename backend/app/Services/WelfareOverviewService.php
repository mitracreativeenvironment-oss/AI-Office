<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class WelfareOverviewService
{
    protected BaseConnection $db;
    protected WelfarePolicyService $policyService;
    protected WelfareLedgerService $ledgerService;
    protected WelfareFacilityService $facilityService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->policyService = new WelfarePolicyService();
        $this->ledgerService = new WelfareLedgerService();
        $this->facilityService = new WelfareFacilityService();
    }

    /**
     * Aggregate high-level campus welfare overview and financial governance metrics.
     */
    public function getOverview(): array
    {
        $policy = $this->policyService->getActivePolicy();

        // 1. Transaction volume by category
        $categories = ['base_compensation', 'task_bonus', 'special_recognition', 'adjustment', 'reversal'];
        $totalsByCategory = [];
        $grandTotalCredits = 0.0;
        $grandTotalDebits = 0.0;

        foreach ($categories as $cat) {
            $sum = $this->db->table('welfare_ticket_transactions')
                ->where('ticket_category', $cat)
                ->where('status', 'completed')
                ->where('transaction_type', 'credit')
                ->selectSum('amount')
                ->get()
                ->getRowArray()['amount'] ?? 0;

            $totalsByCategory[$cat] = (float)$sum;
            $grandTotalCredits += (float)$sum;
        }

        $debits = $this->db->table('welfare_ticket_transactions')
            ->where('status', 'completed')
            ->where('transaction_type', 'debit')
            ->selectSum('amount')
            ->get()
            ->getRowArray()['amount'] ?? 0;
        $grandTotalDebits = (float)$debits;

        // 2. Pending approvals count
        $pendingPeriods = $this->db->table('welfare_compensation_periods')
            ->where('status', 'calculated')
            ->countAllResults();

        $pendingBonuses = $this->db->table('welfare_task_bonus_evaluations')
            ->where('approval_status', 'pending_review')
            ->countAllResults();

        $pendingRecognitions = $this->db->table('welfare_special_recognitions')
            ->where('approval_status', 'pending_review')
            ->countAllResults();

        // 3. Agent Balances & Roster
        $agents = $this->db->table('ai_agent_registry ag')
            ->select('ag.id as agent_id, ag.name, ag.agent_code, ag.classification, u.name as unit_name, b.total_credits, b.total_debits, b.current_balance, b.is_reconciled')
            ->join('organization_units u', 'u.id = ag.primary_unit_id', 'left')
            ->join('welfare_agent_balances b', 'b.agent_id = ag.id', 'left')
            ->where('ag.lifecycle_state', 'active')
            ->orderBy('b.current_balance', 'DESC')
            ->get()
            ->getResultArray();

        foreach ($agents as &$ag) {
            $ag['agent_id'] = (int)$ag['agent_id'];
            $ag['total_credits'] = (float)($ag['total_credits'] ?? 0);
            $ag['total_debits'] = (float)($ag['total_debits'] ?? 0);
            $ag['current_balance'] = (float)($ag['current_balance'] ?? 0);
            $ag['is_reconciled'] = (bool)($ag['is_reconciled'] ?? false);
        }

        // 4. Facilities summary
        $facilities = $this->facilityService->listFacilities();

        return [
            'active_policy' => $policy,
            'metrics'       => [
                'total_tickets_issued'     => $grandTotalCredits,
                'total_tickets_deducted'   => $grandTotalDebits,
                'net_circulation'          => round($grandTotalCredits - $grandTotalDebits, 2),
                'by_category'              => $totalsByCategory,
                'total_active_agents'      => count($agents),
                'pending_approvals_total'  => $pendingPeriods + $pendingBonuses + $pendingRecognitions,
                'pending_periods_count'    => $pendingPeriods,
                'pending_bonuses_count'    => $pendingBonuses,
                'pending_recognitions_count' => $pendingRecognitions,
            ],
            'agent_roster'  => $agents,
            'facilities'    => $facilities,
        ];
    }
}
