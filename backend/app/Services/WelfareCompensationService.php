<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class WelfareCompensationService
{
    protected BaseConnection $db;
    protected WelfarePolicyService $policyService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->policyService = new WelfarePolicyService();
    }

    /**
     * List all compensation periods with summary metrics.
     */
    public function listPeriods(array $filters = []): array
    {
        $builder = $this->db->table('welfare_compensation_periods p')
            ->select('p.*, pol.policy_code, pol.version as policy_version, u.username as creator_name')
            ->join('welfare_policies pol', 'pol.id = p.policy_id', 'left')
            ->join('users u', 'u.id = p.created_by', 'left');

        if (!empty($filters['status'])) {
            $builder->where('p.status', $filters['status']);
        }
        if (!empty($filters['period_type'])) {
            $builder->where('p.period_type', $filters['period_type']);
        }

        $periods = $builder->orderBy('p.id', 'DESC')->get()->getResultArray();
        foreach ($periods as &$p) {
            $p['id'] = (int)$p['id'];
            $p['total_eligible_agents'] = (int)$p['total_eligible_agents'];
            $p['total_base_tickets'] = (float)$p['total_base_tickets'];
            $p['total_bonus_tickets'] = (float)$p['total_bonus_tickets'];
            $p['total_tickets_issued'] = (float)$p['total_tickets_issued'];
        }

        return $periods;
    }

    /**
     * Get period details with calculation breakdown per agent.
     */
    public function getPeriodDetail(int $periodId): ?array
    {
        $period = $this->db->table('welfare_compensation_periods p')
            ->select('p.*, pol.policy_code, pol.version as policy_version, pol.base_duty_tickets as policy_base_tickets')
            ->join('welfare_policies pol', 'pol.id = p.policy_id', 'left')
            ->where('p.id', $periodId)
            ->get()
            ->getRowArray();

        if (!$period) {
            return null;
        }

        $period['id'] = (int)$period['id'];
        $period['total_eligible_agents'] = (int)$period['total_eligible_agents'];
        $period['total_base_tickets'] = (float)$period['total_base_tickets'];
        $period['total_bonus_tickets'] = (float)$period['total_bonus_tickets'];
        $period['total_tickets_issued'] = (float)$period['total_tickets_issued'];

        // Calculations list
        $calculations = $this->db->table('welfare_period_calculations c')
            ->select('c.*, ag.name as agent_name, ag.agent_code, ag.classification as agent_classification, u.name as unit_name')
            ->join('ai_agent_registry ag', 'ag.id = c.agent_id')
            ->join('organization_units u', 'u.id = ag.primary_unit_id', 'left')
            ->where('c.period_id', $periodId)
            ->orderBy('c.id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($calculations as &$calc) {
            $calc['id'] = (int)$calc['id'];
            $calc['scheduled_duty_days'] = (int)$calc['scheduled_duty_days'];
            $calc['fulfilled_duty_days'] = (int)$calc['fulfilled_duty_days'];
            $calc['leave_days'] = (int)$calc['leave_days'];
            $calc['rest_days'] = (int)$calc['rest_days'];
            $calc['base_tickets_amount'] = (float)$calc['base_tickets_amount'];
            $calc['verified_tasks_count'] = (int)$calc['verified_tasks_count'];
            $calc['task_bonus_amount'] = (float)$calc['task_bonus_amount'];
            $calc['special_recognition_amount'] = (float)$calc['special_recognition_amount'];
            $calc['adjustment_amount'] = (float)$calc['adjustment_amount'];
            $calc['net_tickets_calculated'] = (float)$calc['net_tickets_calculated'];
        }

        $period['calculations'] = $calculations;
        return $period;
    }

    /**
     * Create a new compensation cycle period.
     */
    public function createPeriod(array $data, ?int $actorUserId = null): array
    {
        $policy = $this->policyService->getActivePolicy();
        if (!$policy) {
            throw new \RuntimeException('No active approved Welfare Policy found.', 422);
        }

        $periodCode = trim($data['period_code'] ?? 'WCP-' . date('Y') . '-W' . rand(48, 52));
        $startDate = $data['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
        $endDate = $data['end_date'] ?? date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        $insert = [
            'period_code'           => $periodCode,
            'policy_id'             => (int)$policy['id'],
            'period_type'           => $data['period_type'] ?? 'weekly',
            'start_date'            => $startDate,
            'end_date'              => $endDate,
            'status'                => 'draft',
            'total_eligible_agents' => 0,
            'total_base_tickets'    => 0.00,
            'total_bonus_tickets'   => 0.00,
            'total_tickets_issued'  => 0.00,
            'created_by'            => $actorUserId,
            'notes'                 => $data['notes'] ?? 'Regular periodic compensation calculation cycle.',
            'created_at'            => $now,
            'updated_at'            => $now,
        ];

        $this->db->table('welfare_compensation_periods')->insert($insert);
        $periodId = (int)$this->db->insertID();

        // Automatically run initial calculation
        return $this->calculatePeriod($periodId, $actorUserId);
    }

    /**
     * Base Compensation Engine: evaluates duty rosters, fulfilled days, and task bonuses.
     */
    public function calculatePeriod(int $periodId, ?int $actorUserId = null): array
    {
        $period = $this->db->table('welfare_compensation_periods')->where('id', $periodId)->get()->getRowArray();
        if (!$period) {
            throw new \InvalidArgumentException("Period #{$periodId} not found.", 404);
        }

        if (in_array($period['status'], ['distributed', 'cancelled'])) {
            throw new \RuntimeException("Period #{$periodId} is already {$period['status']} and cannot be recalculated.", 422);
        }

        $policy = $this->db->table('welfare_policies')->where('id', $period['policy_id'])->get()->getRowArray();
        $baseRate = (float)($policy['base_duty_tickets'] ?? 10.00);

        // Fetch all active workforce agents
        $agents = $this->db->table('ai_agent_registry')
            ->where('lifecycle_state', 'active')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $totalBase = 0.0;
        $totalBonus = 0.0;
        $eligibleCount = 0;
        $now = date('Y-m-d H:i:s');

        // Clear any previous calculations for draft recalculation
        $this->db->table('welfare_period_calculations')->where('period_id', $periodId)->delete();

        foreach ($agents as $ag) {
            $agentId = (int)$ag['id'];

            // 1. Evaluate Duty Assignments within period
            $duties = $this->db->table('workforce_duty_assignments')
                ->where('agent_id', $agentId)
                ->where('assignment_date >=', $period['start_date'])
                ->where('assignment_date <=', $period['end_date'])
                ->get()
                ->getResultArray();

            $scheduledDutyDays = 0;
            $fulfilledDutyDays = 0;
            $restDays = 0;

            foreach ($duties as $d) {
                if ($d['duty_type'] === 'working') {
                    $scheduledDutyDays++;
                    // Fulfilled if completed or active scheduled duty
                    if (in_array($d['status'], ['scheduled', 'completed', 'active'])) {
                        $fulfilledDutyDays++;
                    }
                } else if ($d['duty_type'] === 'rest') {
                    $restDays++;
                }
            }

            // Defaults if roster wasn't generated for historical dates
            if ($scheduledDutyDays === 0) {
                $scheduledDutyDays = 5;
                $fulfilledDutyDays = 5;
                $restDays = 2;
            }

            // 2. Evaluate Leaves
            $leaves = $this->db->table('workforce_agent_availability')
                ->where('agent_id', $agentId)
                ->whereIn('availability_type', ['planned_leave', 'temporary_unavailability'])
                ->where('approval_status', 'approved')
                ->where('start_time <=', $period['end_date'] . ' 23:59:59')
                ->where('end_time >=', $period['start_date'] . ' 00:00:00')
                ->countAllResults();

            // Calculate Base Compensation (Pro-rated for 5-day cycle)
            $ratio = min(1.0, max(0.0, $fulfilledDutyDays / 5.0));
            $agentBaseTickets = round($ratio * $baseRate, 2);

            // 3. Evaluate Verified Task Bonuses for this period
            $bonuses = $this->db->table('welfare_task_bonus_evaluations')
                ->where('agent_id', $agentId)
                ->where('approval_status', 'approved')
                ->where('created_at >=', $period['start_date'] . ' 00:00:00')
                ->where('created_at <=', $period['end_date'] . ' 23:59:59')
                ->get()
                ->getResultArray();

            $agentBonusTickets = 0.0;
            $verifiedTasksCount = count($bonuses);
            foreach ($bonuses as $b) {
                $agentBonusTickets += (float)$b['approved_bonus_tickets'];
            }

            // 4. Special Recognitions
            $recs = $this->db->table('welfare_special_recognitions')
                ->where('agent_id', $agentId)
                ->where('approval_status', 'approved')
                ->where('created_at >=', $period['start_date'] . ' 00:00:00')
                ->where('created_at <=', $period['end_date'] . ' 23:59:59')
                ->get()
                ->getResultArray();

            $agentRecTickets = 0.0;
            foreach ($recs as $r) {
                $agentRecTickets += (float)$r['awarded_tickets'];
            }

            $netTickets = $agentBaseTickets + $agentBonusTickets + $agentRecTickets;
            $isEligible = ($netTickets > 0);
            if ($isEligible) $eligibleCount++;

            $totalBase += $agentBaseTickets;
            $totalBonus += ($agentBonusTickets + $agentRecTickets);

            $this->db->table('welfare_period_calculations')->insert([
                'period_id'                  => $periodId,
                'agent_id'                   => $agentId,
                'policy_id'                  => (int)$policy['id'],
                'scheduled_duty_days'        => $scheduledDutyDays,
                'fulfilled_duty_days'        => $fulfilledDutyDays,
                'leave_days'                 => $leaves,
                'rest_days'                  => $restDays,
                'base_tickets_amount'        => $agentBaseTickets,
                'verified_tasks_count'       => $verifiedTasksCount,
                'task_bonus_amount'          => $agentBonusTickets,
                'special_recognition_amount' => $agentRecTickets,
                'adjustment_amount'          => 0.00,
                'net_tickets_calculated'     => $netTickets,
                'calculation_basis'          => "5/2 roster cycle: {$fulfilledDutyDays}/{$scheduledDutyDays} fulfilled duty days; {$verifiedTasksCount} verified task bonuses.",
                'status'                     => $isEligible ? 'eligible' : 'ineligible',
                'ineligibility_reason'       => $isEligible ? null : 'No active scheduled duties fulfilled during period.',
                'created_at'                 => $now,
                'updated_at'                 => $now,
            ]);
        }

        // Update Period Summary
        $this->db->table('welfare_compensation_periods')->where('id', $periodId)->update([
            'status'                => 'calculated',
            'total_eligible_agents' => $eligibleCount,
            'total_base_tickets'    => $totalBase,
            'total_bonus_tickets'   => $totalBonus,
            'total_tickets_issued'  => 0.00,
            'calculated_at'         => $now,
            'updated_at'            => $now,
        ]);

        return $this->getPeriodDetail($periodId);
    }

    /**
     * Approve calculated compensation period (Requires DoA Level B/C).
     */
    public function approvePeriod(int $periodId, int $actorUserId): array
    {
        $period = $this->db->table('welfare_compensation_periods')->where('id', $periodId)->get()->getRowArray();
        if (!$period) {
            throw new \InvalidArgumentException("Period #{$periodId} not found.", 404);
        }

        if ($period['status'] !== 'calculated') {
            throw new \RuntimeException("Period #{$periodId} status must be 'calculated' before approval (current: {$period['status']}).", 422);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('welfare_compensation_periods')->where('id', $periodId)->update([
            'status'      => 'approved',
            'approved_by' => $actorUserId,
            'approved_at' => $now,
            'updated_at'  => $now,
        ]);

        $this->db->table('welfare_period_calculations')
            ->where('period_id', $periodId)
            ->where('status', 'eligible')
            ->update(['status' => 'approved', 'updated_at' => $now]);

        // Audit Trail
        $this->db->table('welfare_audit_trail')->insert([
            'event_uuid'     => \App\Services\AgentRegistryService::generateUuid(),
            'event_category' => 'approval',
            'action'         => 'period_approved',
            'target_type'    => 'compensation_period',
            'target_id'      => $periodId,
            'actor_type'     => 'human_user',
            'actor_id'       => $actorUserId,
            'details'        => json_encode(['period_code' => $period['period_code'], 'total_tickets' => $period['total_base_tickets'] + $period['total_bonus_tickets']]),
            'created_at'     => $now,
        ]);

        return $this->getPeriodDetail($periodId);
    }

    /**
     * Distribute tickets to agent balances & append to immutable ledger.
     */
    public function distributePeriodTickets(int $periodId, int $actorUserId): array
    {
        $period = $this->db->table('welfare_compensation_periods')->where('id', $periodId)->get()->getRowArray();
        if (!$period) {
            throw new \InvalidArgumentException("Period #{$periodId} not found.", 404);
        }

        if ($period['status'] !== 'approved') {
            throw new \RuntimeException("Period #{$periodId} must be approved before distribution (current: {$period['status']}).", 422);
        }

        $calcs = $this->db->table('welfare_period_calculations')
            ->where('period_id', $periodId)
            ->where('status', 'approved')
            ->get()
            ->getResultArray();

        $now = date('Y-m-d H:i:s');
        $totalIssued = 0.0;

        foreach ($calcs as $c) {
            $agentId = (int)$c['agent_id'];
            $amount = (float)$c['net_tickets_calculated'];
            if ($amount <= 0) continue;

            $idempotencyHash = hash('sha256', "distribute:{$periodId}:{$agentId}:" . $period['period_code']);

            // Check if already distributed
            $existingTx = $this->db->table('welfare_ticket_transactions')
                ->where('idempotency_hash', $idempotencyHash)
                ->get()
                ->getRowArray();

            if (!$existingTx) {
                // Ensure balance row exists
                $balRow = $this->db->table('welfare_agent_balances')->where('agent_id', $agentId)->get()->getRowArray();
                $currentBal = (float)($balRow['current_balance'] ?? 0);
                $newBal = $currentBal + $amount;

                $txCode = sprintf('TX-DIST-%s-%04d', $period['period_code'], $agentId);
                $this->db->table('welfare_ticket_transactions')->insert([
                    'transaction_code'    => $txCode,
                    'agent_id'            => $agentId,
                    'ticket_category'     => 'base_compensation',
                    'transaction_type'    => 'credit',
                    'amount'              => $amount,
                    'balance_after'       => $newBal,
                    'source_period_id'    => $periodId,
                    'idempotency_hash'    => $idempotencyHash,
                    'policy_version'      => '1.0.0',
                    'status'              => 'completed',
                    'reason'              => "Periodic welfare compensation distribution for {$period['period_code']}.",
                    'created_by_user_id'  => $actorUserId,
                    'approved_by_user_id' => $period['approved_by'] ?? $actorUserId,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]);
                $txId = (int)$this->db->insertID();

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

                $totalIssued += $amount;
            }

            $this->db->table('welfare_period_calculations')->where('id', $c['id'])->update([
                'status'     => 'issued',
                'updated_at' => $now,
            ]);
        }

        $this->db->table('welfare_compensation_periods')->where('id', $periodId)->update([
            'status'               => 'distributed',
            'total_tickets_issued' => $totalIssued,
            'distributed_at'       => $now,
            'updated_at'           => $now,
        ]);

        return $this->getPeriodDetail($periodId);
    }
}
