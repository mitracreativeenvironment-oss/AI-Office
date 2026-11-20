<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTaskManagementAndCollaborationSchema extends Migration
{
    public function up()
    {
        // 1. Workforce Tasks Table
        $this->forge->addField([
            'id'                     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'task_uuid'              => ['type' => 'CHAR', 'constraint' => 36, 'unique' => true],
            'task_code'              => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'title'                  => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'            => ['type' => 'TEXT', 'null' => true],
            'objective'              => ['type' => 'TEXT', 'null' => true],
            'expected_outcome'       => ['type' => 'TEXT', 'null' => true],
            'parent_task_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'unit_id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'category'               => [
                'type'       => 'ENUM',
                'constraint' => [
                    'executive', 'administrative', 'operational', 'research',
                    'development', 'creative', 'marketing', 'customer_project',
                    'business_development', 'internal_improvement', 'maintenance',
                    'incident_response', 'compliance', 'cross_division'
                ],
                'default'    => 'operational'
            ],
            'priority'               => [
                'type'       => 'ENUM',
                'constraint' => ['urgent', 'critical', 'high', 'medium', 'low'],
                'default'    => 'medium'
            ],
            'status'                 => [
                'type'       => 'ENUM',
                'constraint' => [
                    'draft', 'proposed', 'awaiting_approval', 'approved', 'ready',
                    'assigned', 'awaiting_acceptance', 'accepted', 'in_progress', 'blocked',
                    'under_review', 'revision_required', 'verified', 'completed',
                    'suspended', 'cancelled', 'reopened'
                ],
                'default'    => 'draft'
            ],
            'origin_type'            => [
                'type'       => 'ENUM',
                'constraint' => [
                    'owner_instruction', 'board_decision', 'director_directive',
                    'division_initiative', 'customer_project', 'recurring_operational',
                    'system_incident'
                ],
                'default'    => 'board_decision'
            ],
            'origin_reference_id'    => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'is_binding_instruction' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'accountable_agent_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'creator_user_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'creator_agent_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'required_skill_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'min_skill_proficiency'  => [
                'type'       => 'ENUM',
                'constraint' => ['beginner', 'intermediate', 'advanced', 'expert', 'master'],
                'default'    => 'intermediate'
            ],
            'approval_level'         => [
                'type'       => 'ENUM',
                'constraint' => ['A', 'B', 'C', 'routine'],
                'default'    => 'routine'
            ],
            'financial_amount'       => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'approved_by_user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'approved_at'            => ['type' => 'DATETIME', 'null' => true],
            'start_date'             => ['type' => 'DATE', 'null' => true],
            'due_date'               => ['type' => 'DATE', 'null' => true],
            'completed_at'           => ['type' => 'DATETIME', 'null' => true],
            'progress_percent'       => ['type' => 'INT', 'constraint' => 3, 'default' => 0],
            'confidentiality_level'  => [
                'type'       => 'ENUM',
                'constraint' => ['public', 'internal', 'restricted', 'confidential'],
                'default'    => 'internal'
            ],
            'created_at'             => ['type' => 'DATETIME', 'null' => true],
            'updated_at'             => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->addKey('priority');
        $this->forge->addKey('unit_id');
        $this->forge->addKey('accountable_agent_id');
        $this->forge->addForeignKey('unit_id', 'organization_units', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('accountable_agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('required_skill_id', 'skill_catalogue', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('workforce_tasks', true);

        // 2. Task Assignments
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'task_id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'agent_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'assignment_role'      => [
                'type'       => 'ENUM',
                'constraint' => ['primary_assignee', 'lead_agent', 'collaborator', 'reviewer', 'specialist', 'observer'],
                'default'    => 'primary_assignee'
            ],
            'responsibility_scope' => ['type' => 'TEXT', 'null' => true],
            'assigned_by_user_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'assigned_by_agent_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'acceptance_status'    => [
                'type'       => 'ENUM',
                'constraint' => ['awaiting_acceptance', 'accepted', 'rejected', 'clarification_requested'],
                'default'    => 'awaiting_acceptance'
            ],
            'rejection_reason'     => ['type' => 'TEXT', 'null' => true],
            'accepted_at'          => ['type' => 'DATETIME', 'null' => true],
            'status'               => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'completed', 'reassigned', 'revoked'],
                'default'    => 'active'
            ],
            'duty_assignment_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['task_id', 'agent_id']);
        $this->forge->addForeignKey('task_id', 'workforce_tasks', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('task_assignments', true);

        // 3. Task Delegations
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'task_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'delegating_agent_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'delegated_agent_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'delegated_scope'     => ['type' => 'TEXT'],
            'expected_output'     => ['type' => 'TEXT', 'null' => true],
            'authority_limit'     => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'routine'],
            'deadline'            => ['type' => 'DATETIME', 'null' => true],
            'status'              => [
                'type'       => 'ENUM',
                'constraint' => ['proposed', 'accepted', 'rejected', 'revoked', 'completed'],
                'default'    => 'proposed'
            ],
            'rejection_reason'    => ['type' => 'TEXT', 'null' => true],
            'accepted_at'         => ['type' => 'DATETIME', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('task_id', 'workforce_tasks', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('delegating_agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('delegated_agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('task_delegations', true);

        // 4. Task Collaboration Groups
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'collaboration_code' => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'task_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'lead_agent_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'title'              => ['type' => 'VARCHAR', 'constraint' => 160],
            'shared_objective'   => ['type' => 'TEXT'],
            'status'             => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'completed', 'disbanded'],
                'default'    => 'active'
            ],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('task_id', 'workforce_tasks', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('lead_agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('task_collaboration_groups', true);

        // 5. Task Collaboration Members
        $this->forge->addField([
            'id'                        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'group_id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'agent_id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'role'                      => [
                'type'       => 'ENUM',
                'constraint' => ['lead', 'researcher', 'analyst', 'developer', 'reviewer', 'specialist', 'coordinator', 'supporter'],
                'default'    => 'developer'
            ],
            'assigned_responsibilities' => ['type' => 'TEXT'],
            'status'                    => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'withdrawn', 'completed'],
                'default'    => 'active'
            ],
            'created_at'                => ['type' => 'DATETIME', 'null' => true],
            'updated_at'                => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('group_id', 'task_collaboration_groups', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('task_collaboration_members', true);

        // 6. Task Dependencies
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'task_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true], // Downstream task
            'depends_on_task_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true], // Upstream requirement
            'dependency_type'    => [
                'type'       => 'ENUM',
                'constraint' => ['finish_to_start', 'start_to_start', 'finish_to_finish', 'blocking_dependency', 'approval_dependency'],
                'default'    => 'finish_to_start'
            ],
            'is_satisfied'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'satisfied_at'       => ['type' => 'DATETIME', 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['task_id', 'depends_on_task_id']);
        $this->forge->addForeignKey('task_id', 'workforce_tasks', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('depends_on_task_id', 'workforce_tasks', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('task_dependencies', true);

        // 7. Task Execution Attempts (Runtime boundary tracking)
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'task_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'agent_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'attempt_number'    => ['type' => 'INT', 'default' => 1],
            'status'            => [
                'type'       => 'ENUM',
                'constraint' => ['started', 'running', 'paused', 'interrupted', 'failed', 'submitted', 'completed'],
                'default'    => 'started'
            ],
            'context_summary'   => ['type' => 'TEXT', 'null' => true],
            'execution_adapter' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'hermes_simulation'],
            'model_route_key'   => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'error_details'     => ['type' => 'TEXT', 'null' => true],
            'started_at'        => ['type' => 'DATETIME'],
            'ended_at'          => ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('task_id', 'workforce_tasks', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('task_execution_attempts', true);

        // 8. Task Deliverables & Outputs
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'task_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'submitting_agent_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'deliverable_type'    => [
                'type'       => 'ENUM',
                'constraint' => ['document', 'report', 'source_code', 'design_asset', 'research_result', 'dataset', 'configuration', 'audit_evidence'],
                'default'    => 'report'
            ],
            'title'               => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'         => ['type' => 'TEXT', 'null' => true],
            'artifact_uri'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'version'             => ['type' => 'INT', 'default' => 1],
            'review_status'       => [
                'type'       => 'ENUM',
                'constraint' => ['pending_review', 'revision_required', 'accepted', 'rejected'],
                'default'    => 'pending_review'
            ],
            'is_current_accepted' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('task_id', 'workforce_tasks', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('submitting_agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('task_deliverables', true);

        // 9. Task Reviews & Quality Verification
        $this->forge->addField([
            'id'                       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'task_id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'deliverable_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'reviewer_user_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'reviewer_agent_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'review_outcome'           => [
                'type'       => 'ENUM',
                'constraint' => ['accepted', 'accepted_with_conditions', 'revision_required', 'rejected', 'escalated']
            ],
            'evaluation_comments'      => ['type' => 'TEXT'],
            'criteria_checklist'       => ['type' => 'TEXT', 'null' => true],
            'is_verified_contribution' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'               => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('task_id', 'workforce_tasks', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('deliverable_id', 'task_deliverables', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('task_reviews', true);

        // 10. Task Blockers & Exceptions
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'task_id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'category'             => [
                'type'       => 'ENUM',
                'constraint' => [
                    'missing_information', 'missing_approval', 'dependency_unsatisfied',
                    'agent_unavailable', 'runtime_failure', 'insufficient_permission',
                    'missing_competency', 'external_blocker', 'deadline_risk', 'quality_failure'
                ]
            ],
            'description'          => ['type' => 'TEXT'],
            'severity'             => [
                'type'       => 'ENUM',
                'constraint' => ['low', 'medium', 'high', 'critical'],
                'default'    => 'medium'
            ],
            'reported_by_agent_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'reported_by_user_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'status'               => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'in_resolution', 'resolved', 'escalated'],
                'default'    => 'active'
            ],
            'resolution_notes'     => ['type' => 'TEXT', 'null' => true],
            'resolved_at'          => ['type' => 'DATETIME', 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('task_id', 'workforce_tasks', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('task_blockers', true);

        // 11. Task Events Audit Trail
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'task_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'event_type'  => ['type' => 'VARCHAR', 'constraint' => 64],
            'from_status' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'to_status'   => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'actor_type'  => ['type' => 'ENUM', 'constraint' => ['human', 'agent', 'system'], 'default' => 'agent'],
            'actor_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'actor_name'  => ['type' => 'VARCHAR', 'constraint' => 128],
            'details'     => ['type' => 'TEXT', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('task_id');
        $this->forge->addKey('event_type');
        $this->forge->addForeignKey('task_id', 'workforce_tasks', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('task_events_audit', true);
    }

    public function down()
    {
        $this->forge->dropTable('task_events_audit', true);
        $this->forge->dropTable('task_blockers', true);
        $this->forge->dropTable('task_reviews', true);
        $this->forge->dropTable('task_deliverables', true);
        $this->forge->dropTable('task_execution_attempts', true);
        $this->forge->dropTable('task_dependencies', true);
        $this->forge->dropTable('task_collaboration_members', true);
        $this->forge->dropTable('task_collaboration_groups', true);
        $this->forge->dropTable('task_delegations', true);
        $this->forge->dropTable('task_assignments', true);
        $this->forge->dropTable('workforce_tasks', true);
    }
}
