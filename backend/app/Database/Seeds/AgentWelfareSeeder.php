<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AgentWelfareSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        // Fetch Verified Agents Map
        $agents = [];
        $agentRows = $db->table('ai_agent_registry')->get()->getResultArray();
        foreach ($agentRows as $a) {
            $agents[$a['agent_code']] = (int)$a['id'];
        }

        // Fetch Welfare Building & Rooms
        $bld = $db->table('hq_buildings')->where('building_code', 'BLD-WELFARE')->get()->getRowArray();
        $buildingId = (int)($bld['id'] ?? 6);

        $restRoom = $db->table('hq_rooms')->where('room_code', 'ROOM-WLF-REST')->get()->getRowArray();
        $restRoomId = (int)($restRoom['id'] ?? 9);

        $learnRoom = $db->table('hq_rooms')->where('room_code', 'ROOM-WLF-LEARN')->get()->getRowArray();
        $learnRoomId = (int)($learnRoom['id'] ?? 10);

        // 1. Seed Master Welfare Policy
        $policyCode = 'POL-WLF-2026-V1';
        $policy = $db->table('welfare_policies')->where('policy_code', $policyCode)->get()->getRowArray();
        $policyId = 0;

        if (!$policy) {
            $db->table('welfare_policies')->insert([
                'policy_code'                  => $policyCode,
                'version'                      => '1.0.0',
                'title'                        => 'MCE AI Workforce Welfare & Merit Recognition Policy 2026',
                'description'                  => 'Governs standard 5/2 shift periodic base compensation, verified task completion bonus, and cross-division merit rewards.',
                'ticket_unit_name'             => 'Welfare Ticket',
                'ticket_unit_symbol'           => 'WT',
                'base_period'                  => 'weekly',
                'base_duty_tickets'            => 10.00,
                'bonus_calculation_method'     => 'task_category',
                'max_issuance_limit_per_batch' => 1000.00,
                'requires_human_approval'      => 1,
                'approval_status'              => 'approved',
                'effective_from'               => '2026-11-01',
                'created_by'                   => 1, // Human Owner
                'approved_by'                  => 1,
                'change_reason'                => 'Initial approved corporate policy establishing AI Agent Welfare Tickets.',
                'is_active'                    => 1,
                'created_at'                   => $now,
                'updated_at'                   => $now,
            ]);
            $policyId = (int)$db->insertID();
        } else {
            $policyId = (int)$policy['id'];
        }

        // 2. Seed Welfare Facilities (Cross-Division VHQ Facilities)
        $facilities = [
            [
                'facility_code'    => 'FAC-WLF-REST-01',
                'building_id'      => $buildingId,
                'room_id'          => $restRoomId,
                'name'             => 'Workforce Recovery & Quiet Standby Hub',
                'facility_type'    => 'rest_recovery',
                'description'      => 'Low-energy recovery pods for agents on scheduled 5/2 rest days and approved planned leave.',
                'capacity'         => 10,
                'access_policy'    => 'off_duty_only',
                'operating_status' => 'available',
                'is_active'        => 1,
            ],
            [
                'facility_code'    => 'FAC-WLF-LEARN-01',
                'building_id'      => $buildingId,
                'room_id'          => $learnRoomId,
                'name'             => 'Knowledge Enrichment & Capability Lounge',
                'facility_type'    => 'knowledge_enrichment',
                'description'      => 'Off-duty self-directed learning zone for historical task indexing and benchmark research.',
                'capacity'         => 8,
                'access_policy'    => 'cross_division_open',
                'operating_status' => 'available',
                'is_active'        => 1,
            ],
        ];

        foreach ($facilities as $f) {
            $existing = $db->table('welfare_facilities')->where('facility_code', $f['facility_code'])->get()->getRowArray();
            if (!$existing) {
                $db->table('welfare_facilities')->insert(array_merge($f, ['created_at' => $now, 'updated_at' => $now]));
            }
        }

        // 3. Initialize Agent Balances for All 6 Verified Workforce Agents
        $verifiedCodes = ['AGT-DIR-001', 'AGT-TECH-001', 'AGT-SRV-001', 'AGT-ACD-001', 'AGT-DEV-001', 'AGT-HDW-001'];
        foreach ($verifiedCodes as $code) {
            $agentId = $agents[$code] ?? null;
            if (!$agentId) continue;

            $existing = $db->table('welfare_agent_balances')->where('agent_id', $agentId)->get()->getRowArray();
            if (!$existing) {
                $db->table('welfare_agent_balances')->insert([
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
            }
        }

        // 4. Seed Baseline Completed Compensation Period (Week 46)
        $periodCode = 'WCP-2026-W46';
        $period = $db->table('welfare_compensation_periods')->where('period_code', $periodCode)->get()->getRowArray();
        $periodId = 0;

        if (!$period) {
            $db->table('welfare_compensation_periods')->insert([
                'period_code'           => $periodCode,
                'policy_id'             => $policyId,
                'period_type'           => 'weekly',
                'start_date'            => '2026-11-09',
                'end_date'              => '2026-11-15',
                'status'                => 'distributed',
                'total_eligible_agents' => count($verifiedCodes),
                'total_base_tickets'    => 60.00,
                'total_bonus_tickets'   => 0.00,
                'total_tickets_issued'  => 60.00,
                'created_by'            => 1,
                'approved_by'           => 1,
                'calculated_at'         => '2026-11-15 18:00:00',
                'approved_at'           => '2026-11-15 19:00:00',
                'distributed_at'        => '2026-11-15 19:05:00',
                'notes'                 => 'Baseline approved periodic compensation for 5/2 duty fulfillment.',
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);
            $periodId = (int)$db->insertID();

            // Insert calculations and credit transactions for each verified agent
            foreach ($verifiedCodes as $idx => $code) {
                $agentId = $agents[$code] ?? null;
                if (!$agentId) continue;

                $db->table('welfare_period_calculations')->insert([
                    'period_id'                  => $periodId,
                    'agent_id'                   => $agentId,
                    'policy_id'                  => $policyId,
                    'scheduled_duty_days'        => 5,
                    'fulfilled_duty_days'        => 5,
                    'leave_days'                 => 0,
                    'rest_days'                  => 2,
                    'base_tickets_amount'        => 10.00,
                    'verified_tasks_count'       => 0,
                    'task_bonus_amount'          => 0.00,
                    'special_recognition_amount' => 0.00,
                    'adjustment_amount'          => 0.00,
                    'net_tickets_calculated'     => 10.00,
                    'calculation_basis'          => 'Fulfilled 5 scheduled duty days under 5/2 roster policy.',
                    'status'                     => 'issued',
                    'created_at'                 => $now,
                    'updated_at'                 => $now,
                ]);

                // Create Immutable Ledger Entry
                $txCode = sprintf('TX-BASE-W46-%04d', $agentId);
                $idempotencyHash = hash('sha256', "base_comp:{$periodId}:{$agentId}:W46");
                $db->table('welfare_ticket_transactions')->insert([
                    'transaction_code'    => $txCode,
                    'agent_id'            => $agentId,
                    'ticket_category'     => 'base_compensation',
                    'transaction_type'    => 'credit',
                    'amount'              => 10.00,
                    'balance_after'       => 10.00,
                    'source_period_id'    => $periodId,
                    'idempotency_hash'    => $idempotencyHash,
                    'policy_version'      => '1.0.0',
                    'status'              => 'completed',
                    'reason'              => 'Weekly base welfare compensation for period W46 (5 duty days fulfilled).',
                    'created_by_user_id'  => 1,
                    'approved_by_user_id' => 1,
                    'created_at'          => '2026-11-15 19:05:00',
                    'updated_at'          => '2026-11-15 19:05:00',
                ]);
                $txId = (int)$db->insertID();

                // Update Materialized Balance
                $db->table('welfare_agent_balances')
                    ->where('agent_id', $agentId)
                    ->update([
                        'total_credits'       => 10.00,
                        'current_balance'     => 10.00,
                        'last_transaction_id' => $txId,
                        'last_reconciled_at'  => $now,
                    ]);
            }
        }

        // 5. Seed Verified Task Bonus for Dev AI (AGT-DEV-001)
        $devAgentId = $agents['AGT-DEV-001'] ?? 5;
        $existingBonus = $db->table('welfare_task_bonus_evaluations')->where('agent_id', $devAgentId)->get()->getRowArray();

        if (!$existingBonus) {
            // Find an existing task
            $task = $db->table('workforce_tasks')->where('id', 1)->get()->getRowArray();
            $taskId = (int)($task['id'] ?? 1);

            // Record performance history linkage
            $perfHistoryId = null;
            $agentUuid = $db->table('ai_agent_registry')->where('id', $devAgentId)->get()->getRowArray()['agent_uuid'] ?? '';
            if ($agentUuid) {
                $db->table('ai_agent_performance_history')->insert([
                    'agent_id'            => $devAgentId,
                    'agent_uuid'          => $agentUuid,
                    'category'            => 'task_completion',
                    'title'               => 'Verified Deliverable: Core Schema Architecture',
                    'description'         => 'High quality architectural output verified with zero defects.',
                    'task_ref'            => 'TSK-2026-001',
                    'evidence_status'     => 'verified',
                    'evaluator_identity'  => 'Human Owner & Board Review',
                    'created_at'          => $now,
                ]);
                $perfHistoryId = (int)$db->insertID();
            }

            $db->table('welfare_task_bonus_evaluations')->insert([
                'task_id'                        => $taskId,
                'agent_id'                       => $devAgentId,
                'bonus_category'                 => 'completion_excellence',
                'acceptance_criteria_satisfied'  => 1,
                'is_verified'                    => 1,
                'verification_notes'             => 'Deliverable code verified against unit tests and schema specifications.',
                'calculated_bonus_tickets'       => 5.00,
                'approved_bonus_tickets'         => 5.00,
                'approval_status'                => 'approved',
                'approved_by'                    => 1,
                'performance_history_id'         => $perfHistoryId,
                'issuance_status'                => 'issued',
                'created_at'                     => $now,
                'updated_at'                     => $now,
            ]);
            $bonusId = (int)$db->insertID();

            // Credit Bonus Transaction in Ledger
            $txCode = 'TX-BONUS-DEV-0001';
            $idempotencyHash = hash('sha256', "task_bonus:{$taskId}:{$devAgentId}:{$bonusId}");
            $currBal = (float)($db->table('welfare_agent_balances')->where('agent_id', $devAgentId)->get()->getRowArray()['current_balance'] ?? 0);
            $newBal = $currBal + 5.00;

            $db->table('welfare_ticket_transactions')->insert([
                'transaction_code'     => $txCode,
                'agent_id'             => $devAgentId,
                'ticket_category'      => 'task_bonus',
                'transaction_type'     => 'credit',
                'amount'               => 5.00,
                'balance_after'        => $newBal,
                'source_task_bonus_id' => $bonusId,
                'idempotency_hash'     => $idempotencyHash,
                'policy_version'       => '1.0.0',
                'status'               => 'completed',
                'reason'               => 'Verified task completion bonus for TSK-2026-001 deliverable excellence.',
                'created_by_user_id'   => 1,
                'approved_by_user_id'  => 1,
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);
            $txId = (int)$db->insertID();

            // Update balance
            $db->table('welfare_agent_balances')
                ->where('agent_id', $devAgentId)
                ->update([
                    'total_credits'       => $currBal + 5.00,
                    'current_balance'     => $newBal,
                    'last_transaction_id' => $txId,
                    'last_reconciled_at'  => $now,
                ]);
        }

        // 6. Seed Special Recognition for Tech Head AI (AGT-TECH-001)
        $techAgentId = $agents['AGT-TECH-001'] ?? 2;
        $existingRec = $db->table('welfare_special_recognitions')->where('agent_id', $techAgentId)->get()->getRowArray();

        if (!$existingRec) {
            $perfHistoryId = null;
            $agentUuid = $db->table('ai_agent_registry')->where('id', $techAgentId)->get()->getRowArray()['agent_uuid'] ?? '';
            if ($agentUuid) {
                $db->table('ai_agent_performance_history')->insert([
                    'agent_id'           => $techAgentId,
                    'agent_uuid'         => $agentUuid,
                    'category'           => 'recognition',
                    'title'              => 'Executive Merit: Runtime Orchestration Innovation',
                    'description'        => 'Exemplary architectural contribution to multi-agent sandbox safety.',
                    'evidence_status'    => 'verified',
                    'evaluator_identity' => 'Governance Board (DoA Level B)',
                    'created_at'         => $now,
                ]);
                $perfHistoryId = (int)$db->insertID();
            }

            $recCode = 'REC-2026-001';
            $db->table('welfare_special_recognitions')->insert([
                'recognition_code'       => $recCode,
                'agent_id'               => $techAgentId,
                'title'                  => 'Executive Recognition: Multi-Agent Safety Framework',
                'category'               => 'innovation_contribution',
                'justification'          => 'Designed fail-safe 15-precondition authorization engine preventing unauthorized execution.',
                'evidence_ref'           => 'REF-DOA-GOV-2026-01',
                'awarded_tickets'        => 15.00,
                'nominated_by_user_id'   => 1,
                'approved_by_user_id'    => 1,
                'approval_status'        => 'approved',
                'doa_level_required'     => 'B',
                'performance_history_id' => $perfHistoryId,
                'issuance_status'        => 'issued',
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);
            $recId = (int)$db->insertID();

            // Credit Recognition Transaction in Ledger
            $txCode = 'TX-REC-TECH-0001';
            $idempotencyHash = hash('sha256', "recognition:{$techAgentId}:{$recId}");
            $currBal = (float)($db->table('welfare_agent_balances')->where('agent_id', $techAgentId)->get()->getRowArray()['current_balance'] ?? 0);
            $newBal = $currBal + 15.00;

            $db->table('welfare_ticket_transactions')->insert([
                'transaction_code'       => $txCode,
                'agent_id'               => $techAgentId,
                'ticket_category'        => 'special_recognition',
                'transaction_type'       => 'credit',
                'amount'                 => 15.00,
                'balance_after'          => $newBal,
                'source_recognition_id'  => $recId,
                'idempotency_hash'       => $idempotencyHash,
                'policy_version'         => '1.0.0',
                'status'                 => 'completed',
                'reason'                 => 'Executive recognition for multi-agent sandbox safety innovation.',
                'created_by_user_id'     => 1,
                'approved_by_user_id'    => 1,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);
            $txId = (int)$db->insertID();

            // Update balance
            $db->table('welfare_agent_balances')
                ->where('agent_id', $techAgentId)
                ->update([
                    'total_credits'       => $currBal + 15.00,
                    'current_balance'     => $newBal,
                    'last_transaction_id' => $txId,
                    'last_reconciled_at'  => $now,
                ]);
        }
    }
}
