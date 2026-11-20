<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAgentWelfareAndTicketsSchema extends Migration
{
    public function up()
    {
        // ---------------------------------------------------------------------
        // 1. WELFARE POLICIES & CONFIGURATION
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'policy_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'version' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => '1.0.0',
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'ticket_unit_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'Welfare Ticket',
            ],
            'ticket_unit_symbol' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'default'    => 'WT',
            ],
            'base_period' => [
                'type'       => 'ENUM',
                'constraint' => ['weekly', 'biweekly', 'monthly', 'custom'],
                'default'    => 'weekly',
            ],
            'base_duty_tickets' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '10.00',
            ],
            'bonus_calculation_method' => [
                'type'       => 'ENUM',
                'constraint' => ['fixed', 'task_category', 'complexity_based', 'quality_based', 'special_approved'],
                'default'    => 'task_category',
            ],
            'max_issuance_limit_per_batch' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '1000.00',
            ],
            'requires_human_approval' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'approval_status' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'pending_approval', 'approved', 'rejected'],
                'default'    => 'approved',
            ],
            'effective_from' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'effective_until' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'approved_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'change_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('approval_status');
        $this->forge->addKey('is_active');
        $this->forge->createTable('welfare_policies', true);

        // ---------------------------------------------------------------------
        // 2. WELFARE COMPENSATION PERIODS
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'period_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'policy_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'period_type' => [
                'type'       => 'ENUM',
                'constraint' => ['weekly', 'biweekly', 'monthly', 'custom'],
                'default'    => 'weekly',
            ],
            'start_date' => [
                'type' => 'DATE',
            ],
            'end_date' => [
                'type' => 'DATE',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'calculated', 'under_review', 'approved', 'distributed', 'cancelled'],
                'default'    => 'draft',
            ],
            'total_eligible_agents' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'total_base_tickets' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '0.00',
            ],
            'total_bonus_tickets' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '0.00',
            ],
            'total_tickets_issued' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '0.00',
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'approved_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'calculated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'approved_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'distributed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('policy_id');
        $this->forge->addKey('status');
        $this->forge->addKey(['start_date', 'end_date']);
        $this->forge->createTable('welfare_compensation_periods', true);

        // ---------------------------------------------------------------------
        // 3. PERIOD CALCULATIONS BREAKDOWN
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'period_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'agent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'policy_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'scheduled_duty_days' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 5,
            ],
            'fulfilled_duty_days' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 5,
            ],
            'leave_days' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 0,
            ],
            'rest_days' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 2,
            ],
            'base_tickets_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'verified_tasks_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'task_bonus_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'special_recognition_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'adjustment_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'net_tickets_calculated' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'calculation_basis' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'eligible', 'ineligible', 'under_review', 'approved', 'issued', 'rejected'],
                'default'    => 'eligible',
            ],
            'ineligibility_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['period_id', 'agent_id']);
        $this->forge->addKey('status');
        $this->forge->createTable('welfare_period_calculations', true);

        // ---------------------------------------------------------------------
        // 4. TASK BONUS EVALUATIONS
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'task_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'agent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'deliverable_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'review_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'bonus_category' => [
                'type'       => 'ENUM',
                'constraint' => ['completion_excellence', 'speed_efficiency', 'high_complexity', 'cross_division', 'critical_fix'],
                'default'    => 'completion_excellence',
            ],
            'acceptance_criteria_satisfied' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'is_verified' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'verification_notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'calculated_bonus_tickets' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'approved_bonus_tickets' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'approval_status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending_review', 'approved', 'rejected'],
                'default'    => 'pending_review',
            ],
            'approved_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'performance_history_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'issuance_status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'issued', 'cancelled'],
                'default'    => 'pending',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['task_id', 'agent_id']);
        $this->forge->addKey('approval_status');
        $this->forge->addKey('issuance_status');
        $this->forge->createTable('welfare_task_bonus_evaluations', true);

        // ---------------------------------------------------------------------
        // 5. SPECIAL RECOGNITIONS
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'recognition_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'agent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'category' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'exceptional_contribution',
                    'cross_division_assistance',
                    'innovation_contribution',
                    'critical_incident_support',
                    'knowledge_contribution',
                ],
                'default'    => 'exceptional_contribution',
            ],
            'justification' => [
                'type' => 'TEXT',
            ],
            'evidence_ref' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'awarded_tickets' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'nominated_by_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'approved_by_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'approval_status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending_review', 'approved', 'rejected'],
                'default'    => 'pending_review',
            ],
            'doa_level_required' => [
                'type'       => 'ENUM',
                'constraint' => ['A', 'B', 'C'],
                'default'    => 'B',
            ],
            'performance_history_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'issuance_status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'issued', 'cancelled'],
                'default'    => 'pending',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('agent_id');
        $this->forge->addKey('approval_status');
        $this->forge->addKey('issuance_status');
        $this->forge->createTable('welfare_special_recognitions', true);

        // ---------------------------------------------------------------------
        // 6. WELFARE TICKET TRANSACTIONS (IMMUTABLE LEDGER)
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'transaction_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'unique'     => true,
            ],
            'agent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'ticket_category' => [
                'type'       => 'ENUM',
                'constraint' => ['base_compensation', 'task_bonus', 'special_recognition', 'adjustment', 'redemption', 'reversal'],
                'default'    => 'base_compensation',
            ],
            'transaction_type' => [
                'type'       => 'ENUM',
                'constraint' => ['credit', 'debit'],
                'default'    => 'credit',
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'balance_after' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'source_period_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'source_task_bonus_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'source_recognition_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'reversal_ref_transaction_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'idempotency_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'unique'     => true,
            ],
            'policy_version' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => '1.0.0',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'completed', 'reversed', 'cancelled'],
                'default'    => 'completed',
            ],
            'reason' => [
                'type' => 'TEXT',
            ],
            'evidence_ref' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'approved_by_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('agent_id');
        $this->forge->addKey('ticket_category');
        $this->forge->addKey('status');
        $this->forge->createTable('welfare_ticket_transactions', true);

        // ---------------------------------------------------------------------
        // 7. AGENT WELFARE BALANCES
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'agent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'unique'     => true,
            ],
            'total_credits' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '0.00',
            ],
            'total_debits' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '0.00',
            ],
            'current_balance' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '0.00',
            ],
            'last_transaction_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'last_reconciled_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'is_reconciled' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('welfare_agent_balances', true);

        // ---------------------------------------------------------------------
        // 8. WELFARE FACILITIES (CROSS-DIVISION VHQ EXTENSION)
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'facility_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'building_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'room_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'facility_type' => [
                'type'       => 'ENUM',
                'constraint' => ['rest_recovery', 'quiet_focus', 'recreation', 'knowledge_enrichment', 'collaboration', 'resource_support'],
                'default'    => 'rest_recovery',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'capacity' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 10,
            ],
            'access_policy' => [
                'type'       => 'ENUM',
                'constraint' => ['cross_division_open', 'off_duty_only', 'approval_required'],
                'default'    => 'cross_division_open',
            ],
            'operating_status' => [
                'type'       => 'ENUM',
                'constraint' => ['available', 'busy', 'maintenance', 'reserved'],
                'default'    => 'available',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['building_id', 'room_id']);
        $this->forge->addKey('facility_type');
        $this->forge->addKey('operating_status');
        $this->forge->createTable('welfare_facilities', true);

        // ---------------------------------------------------------------------
        // 9. WELFARE FACILITY USAGE LOGS
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'facility_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'agent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'workstation_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'session_type' => [
                'type'       => 'ENUM',
                'constraint' => ['duty_rest_recovery', 'knowledge_study', 'collaboration_break'],
                'default'    => 'duty_rest_recovery',
            ],
            'start_time' => [
                'type' => 'DATETIME',
            ],
            'end_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'completed', 'cancelled'],
                'default'    => 'active',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['facility_id', 'agent_id']);
        $this->forge->addKey('status');
        $this->forge->createTable('welfare_facility_usage_logs', true);

        // ---------------------------------------------------------------------
        // 10. WELFARE AUDIT TRAIL
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'event_uuid' => [
                'type'       => 'VARCHAR',
                'constraint' => 36,
                'unique'     => true,
            ],
            'event_category' => [
                'type'       => 'ENUM',
                'constraint' => ['policy', 'calculation', 'verification', 'approval', 'issuance', 'adjustment', 'reversal', 'facility'],
                'default'    => 'issuance',
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'target_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'target_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'actor_type' => [
                'type'       => 'ENUM',
                'constraint' => ['human_user', 'governance_board', 'director_ai'],
                'default'    => 'human_user',
            ],
            'actor_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'actor_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'details' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('event_category');
        $this->forge->addKey(['target_type', 'target_id']);
        $this->forge->createTable('welfare_audit_trail', true);
    }

    public function down()
    {
        $this->forge->dropTable('welfare_audit_trail', true);
        $this->forge->dropTable('welfare_facility_usage_logs', true);
        $this->forge->dropTable('welfare_facilities', true);
        $this->forge->dropTable('welfare_agent_balances', true);
        $this->forge->dropTable('welfare_ticket_transactions', true);
        $this->forge->dropTable('welfare_special_recognitions', true);
        $this->forge->dropTable('welfare_task_bonus_evaluations', true);
        $this->forge->dropTable('welfare_period_calculations', true);
        $this->forge->dropTable('welfare_compensation_periods', true);
        $this->forge->dropTable('welfare_policies', true);
    }
}
