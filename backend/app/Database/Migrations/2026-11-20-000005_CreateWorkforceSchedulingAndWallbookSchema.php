<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWorkforceSchedulingAndWallbookSchema extends Migration
{
    public function up()
    {
        // 1. Workforce Schedule Policies
        $this->forge->addField([
            'id'                      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'policy_code'             => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'                    => ['type' => 'VARCHAR', 'constraint' => 128],
            'description'             => ['type' => 'TEXT', 'null' => true],
            'cycle_days'              => ['type' => 'INT', 'default' => 7],
            'working_days_count'      => ['type' => 'INT', 'default' => 5],
            'rest_days_count'         => ['type' => 'INT', 'default' => 2],
            'min_rest_interval_hours' => ['type' => 'INT', 'default' => 12],
            'applicable_unit_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'is_default'              => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'is_active'               => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'effective_from'          => ['type' => 'DATE', 'null' => true],
            'effective_to'            => ['type' => 'DATE', 'null' => true],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
            'updated_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('applicable_unit_id', 'organization_units', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('workforce_schedule_policies', true);

        // 2. Workforce Shift Definitions
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'shift_code'         => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'               => ['type' => 'VARCHAR', 'constraint' => 128],
            'start_time'         => ['type' => 'TIME'],
            'end_time'           => ['type' => 'TIME'],
            'timezone'           => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'Asia/Jakarta'],
            'duration_hours'     => ['type' => 'DECIMAL', 'constraint' => '4,2', 'default' => '8.00'],
            'crosses_midnight'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'applicable_unit_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'coverage_category'  => [
                'type'       => 'ENUM',
                'constraint' => ['continuous_24_7', 'business_hours', 'extended_hours', 'on_call'],
                'default'    => 'business_hours'
            ],
            'is_active'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('applicable_unit_id', 'organization_units', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('workforce_shifts', true);

        // 3. Workforce Rosters (Cycle-based Schedule container)
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'roster_code'          => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'title'                => ['type' => 'VARCHAR', 'constraint' => 160],
            'policy_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'start_date'           => ['type' => 'DATE'],
            'end_date'             => ['type' => 'DATE'],
            'unit_id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'status'               => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'published', 'archived'],
                'default'    => 'draft'
            ],
            'published_by_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'published_at'         => ['type' => 'DATETIME', 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['start_date', 'end_date']);
        $this->forge->addForeignKey('policy_id', 'workforce_schedule_policies', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('unit_id', 'organization_units', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('workforce_rosters', true);

        // 4. Workforce Duty Assignments (Individual Agent Shift Schedule)
        $this->forge->addField([
            'id'                      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'roster_id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'agent_id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'shift_id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'assignment_date'         => ['type' => 'DATE'],
            'duty_type'               => [
                'type'       => 'ENUM',
                'constraint' => ['working', 'rest', 'standby', 'on_call'],
                'default'    => 'working'
            ],
            'status'                  => [
                'type'       => 'ENUM',
                'constraint' => ['scheduled', 'in_progress', 'completed', 'missed', 'reassigned'],
                'default'    => 'scheduled'
            ],
            'is_actual_completed'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'completed_at'            => ['type' => 'DATETIME', 'null' => true],
            'replacement_proposal_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'notes'                   => ['type' => 'TEXT', 'null' => true],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
            'updated_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['agent_id', 'assignment_date']);
        $this->forge->addForeignKey('roster_id', 'workforce_rosters', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('shift_id', 'workforce_shifts', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('workforce_duty_assignments', true);

        // 5. Agent Availability & Leave Tracking
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'agent_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'availability_type'   => [
                'type'       => 'ENUM',
                'constraint' => [
                    'available', 'scheduled_duty', 'scheduled_rest', 'planned_leave',
                    'temporary_unavailability', 'under_handover', 'replacement_pending', 'duty_completed'
                ],
                'default'    => 'available'
            ],
            'start_time'          => ['type' => 'DATETIME'],
            'end_time'            => ['type' => 'DATETIME', 'null' => true],
            'reason'              => ['type' => 'TEXT', 'null' => true],
            'roster_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'duty_assignment_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'approval_status'     => [
                'type'       => 'ENUM',
                'constraint' => ['none', 'pending', 'approved', 'rejected'],
                'default'    => 'none'
            ],
            'approved_by_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'approved_at'         => ['type' => 'DATETIME', 'null' => true],
            'created_by_user_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['agent_id', 'start_time']);
        $this->forge->addForeignKey('agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('duty_assignment_id', 'workforce_duty_assignments', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('workforce_agent_availability', true);

        // 6. Workforce Operational Coverage Requirements
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'requirement_code'     => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'                 => ['type' => 'VARCHAR', 'constraint' => 160],
            'unit_id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'shift_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'day_of_week'          => [
                'type'       => 'ENUM',
                'constraint' => ['all', 'weekday', 'weekend', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
                'default'    => 'all'
            ],
            'min_agents_required'  => ['type' => 'INT', 'default' => 1],
            'required_skill_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'min_proficiency'      => [
                'type'       => 'ENUM',
                'constraint' => ['beginner', 'intermediate', 'advanced', 'expert', 'master'],
                'default'    => 'intermediate'
            ],
            'criticality'          => [
                'type'       => 'ENUM',
                'constraint' => ['critical', 'high', 'medium', 'low'],
                'default'    => 'medium'
            ],
            'is_continuous_24_7'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('unit_id', 'organization_units', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('shift_id', 'workforce_shifts', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('required_skill_id', 'skill_catalogue', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('workforce_coverage_requirements', true);

        // 7. Qualified Replacement Proposals & Approvals
        $this->forge->addField([
            'id'                          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'proposal_code'               => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'outgoing_agent_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'incoming_agent_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'duty_assignment_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'original_responsibility'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'start_time'                  => ['type' => 'DATETIME'],
            'end_time'                    => ['type' => 'DATETIME'],
            'reason'                      => ['type' => 'TEXT'],
            'qualification_notes'         => ['type' => 'TEXT', 'null' => true],
            'status'                      => [
                'type'       => 'ENUM',
                'constraint' => ['proposed', 'accepted', 'rejected', 'approved', 'activated', 'cancelled'],
                'default'    => 'proposed'
            ],
            'proposed_by_user_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'accepted_at'                 => ['type' => 'DATETIME', 'null' => true],
            'accepted_by_agent_identity'  => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'approved_by_user_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'approved_at'                 => ['type' => 'DATETIME', 'null' => true],
            'activated_at'                => ['type' => 'DATETIME', 'null' => true],
            'created_at'                  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'                  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('outgoing_agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('incoming_agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('duty_assignment_id', 'workforce_duty_assignments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('workforce_replacement_proposals', true);

        // 8. Wallbook Formal Handovers
        $this->forge->addField([
            'id'                      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'handover_code'           => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'title'                   => ['type' => 'VARCHAR', 'constraint' => 160],
            'outgoing_agent_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'incoming_agent_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'unit_id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'duty_assignment_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'replacement_proposal_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'shift_id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deadline_at'             => ['type' => 'DATETIME'],
            'status'                  => [
                'type'       => 'ENUM',
                'constraint' => [
                    'draft', 'submitted', 'under_review', 'awaiting_acknowledgement',
                    'acknowledged', 'clarification_required', 'accepted', 'rejected', 'escalated', 'completed'
                ],
                'default'    => 'draft'
            ],
            'submitted_at'            => ['type' => 'DATETIME', 'null' => true],
            'acknowledged_at'         => ['type' => 'DATETIME', 'null' => true],
            'accepted_at'             => ['type' => 'DATETIME', 'null' => true],
            'escalated_at'            => ['type' => 'DATETIME', 'null' => true],
            'escalated_to_user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'escalation_reason'       => ['type' => 'TEXT', 'null' => true],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
            'updated_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('outgoing_agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('incoming_agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('unit_id', 'organization_units', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('duty_assignment_id', 'workforce_duty_assignments', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('replacement_proposal_id', 'workforce_replacement_proposals', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('shift_id', 'workforce_shifts', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('wallbook_handovers', true);

        // 9. Wallbook Handover Items (Structured Transfer Content)
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'handover_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'section_category' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'responsibilities', 'active_tasks', 'task_progress', 'completed_work',
                    'pending_work', 'important_decisions', 'outstanding_approvals',
                    'operational_risks', 'known_issues', 'required_follow_up',
                    'relevant_documents', 'relevant_evidence', 'context_instructions'
                ]
            ],
            'item_order'       => ['type' => 'INT', 'default' => 1],
            'title'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'content'          => ['type' => 'TEXT'],
            'item_status'      => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'in_progress', 'transferred', 'resolved'],
                'default'    => 'transferred'
            ],
            'reference_type'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'reference_id'     => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['handover_id', 'section_category']);
        $this->forge->addForeignKey('handover_id', 'wallbook_handovers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('wallbook_handover_items', true);

        // 10. Wallbook Acknowledgements & Clarifications
        $this->forge->addField([
            'id'                      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'handover_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'agent_id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'action'                  => [
                'type'       => 'ENUM',
                'constraint' => ['acknowledge', 'request_clarification', 'accept', 'reject']
            ],
            'comments'                => ['type' => 'TEXT', 'null' => true],
            'clarification_questions' => ['type' => 'TEXT', 'null' => true],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('handover_id', 'wallbook_handovers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('wallbook_acknowledgements', true);

        // 11. Workforce Scheduling & Handover Audit Trail
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'event_type'     => ['type' => 'VARCHAR', 'constraint' => 64],
            'target_type'    => ['type' => 'VARCHAR', 'constraint' => 64],
            'target_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'agent_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'actor_user_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'actor_name'     => ['type' => 'VARCHAR', 'constraint' => 128],
            'action_details' => ['type' => 'TEXT', 'null' => true],
            'ip_address'     => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('event_type');
        $this->forge->addKey(['target_type', 'target_id']);
        $this->forge->createTable('workforce_scheduling_audit', true);
    }

    public function down()
    {
        $this->forge->dropTable('workforce_scheduling_audit', true);
        $this->forge->dropTable('wallbook_acknowledgements', true);
        $this->forge->dropTable('wallbook_handover_items', true);
        $this->forge->dropTable('wallbook_handovers', true);
        $this->forge->dropTable('workforce_replacement_proposals', true);
        $this->forge->dropTable('workforce_coverage_requirements', true);
        $this->forge->dropTable('workforce_agent_availability', true);
        $this->forge->dropTable('workforce_duty_assignments', true);
        $this->forge->dropTable('workforce_rosters', true);
        $this->forge->dropTable('workforce_shifts', true);
        $this->forge->dropTable('workforce_schedule_policies', true);
    }
}
