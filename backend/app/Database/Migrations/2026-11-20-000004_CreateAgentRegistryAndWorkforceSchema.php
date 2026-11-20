<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAgentRegistryAndWorkforceSchema extends Migration
{
    public function up()
    {
        // 1. AI Agent Registry (Permanent Identity & Lifecycle)
        $this->forge->addField([
            'id'                         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'agent_uuid'                 => ['type' => 'VARCHAR', 'constraint' => 36, 'unique' => true],
            'agent_code'                 => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'                       => ['type' => 'VARCHAR', 'constraint' => 150],
            'display_name'               => ['type' => 'VARCHAR', 'constraint' => 150],
            'classification'             => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'operational', // executive, division_lead, department_lead, specialist, operational, support, research, coordination
            ],
            'lifecycle_state'            => [
                'type'       => 'ENUM',
                'constraint' => [
                    'draft', 'proposed', 'under_review', 'approved',
                    'provisioning_pending', 'active', 'temporarily_suspended',
                    'under_reconfiguration', 'retired', 'archived'
                ],
                'default'    => 'draft',
            ],
            'operational_status'         => [
                'type'       => 'ENUM',
                'constraint' => ['idle', 'working', 'on_leave', 'handover_in_progress', 'offline'],
                'default'    => 'idle',
            ],
            'primary_unit_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'primary_position_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'legacy_agent_key'           => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true, 'null' => true],
            'roster_schedule_type'       => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => '5_2_staggered'],
            'welfare_tier'               => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'tier_standard'],
            'designated_replacement_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_by_user_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'approved_by_user_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'approved_at'                => ['type' => 'DATETIME', 'null' => true],
            'activated_at'               => ['type' => 'DATETIME', 'null' => true],
            'suspended_at'               => ['type' => 'DATETIME', 'null' => true],
            'retired_at'                 => ['type' => 'DATETIME', 'null' => true],
            'created_at'                 => ['type' => 'DATETIME', 'null' => true],
            'updated_at'                 => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'                 => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('lifecycle_state');
        $this->forge->addKey('operational_status');
        $this->forge->addKey('primary_unit_id');
        $this->forge->addKey('primary_position_id');
        $this->forge->addForeignKey('primary_unit_id', 'organization_units', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('primary_position_id', 'organization_positions', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('ai_agent_registry', true);

        // 2. AI Agent Profiles
        $this->forge->addField([
            'id'                                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'agent_id'                          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'unique' => true],
            'description'                       => ['type' => 'TEXT', 'null' => true],
            'organizational_purpose'            => ['type' => 'TEXT', 'null' => true],
            'primary_responsibilities_summary'  => ['type' => 'TEXT', 'null' => true],
            'secondary_responsibilities_summary'=> ['type' => 'TEXT', 'null' => true],
            'core_capabilities_summary'         => ['type' => 'TEXT', 'null' => true],
            'communication_preferences'         => ['type' => 'TEXT', 'null' => true],
            'profile_version'                   => ['type' => 'INT', 'default' => 1],
            'created_at'                        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'                        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ai_agent_profiles', true);

        // 3. AI Agent Organizational Assignments (Historical tracking)
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'agent_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'unit_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'position_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'assignment_type'  => [
                'type'       => 'ENUM',
                'constraint' => ['primary', 'secondary', 'project', 'interim', 'acting'],
                'default'    => 'primary',
            ],
            'start_date'       => ['type' => 'DATETIME'],
            'end_date'         => ['type' => 'DATETIME', 'null' => true],
            'status'           => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'concluded', 'transferred', 'revoked'],
                'default'    => 'active',
            ],
            'assigned_by'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'approval_ref'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'reason_for_change'=> ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['agent_id', 'status']);
        $this->forge->addForeignKey('agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('unit_id', 'organization_units', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('position_id', 'organization_positions', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('ai_agent_assignments', true);

        // 4. AI Agent Structured Responsibilities
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'agent_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'title'                => ['type' => 'VARCHAR', 'constraint' => 150],
            'description'          => ['type' => 'TEXT'],
            'category'             => [
                'type'       => 'ENUM',
                'constraint' => ['primary', 'secondary', 'temporary', 'project_based', 'compliance'],
                'default'    => 'primary',
            ],
            'scope_unit_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'priority'             => [
                'type'       => 'ENUM',
                'constraint' => ['critical', 'high', 'medium', 'low'],
                'default'    => 'medium',
            ],
            'status'               => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'deprecated', 'transferred', 'concluded'],
                'default'    => 'active',
            ],
            'effective_start_date' => ['type' => 'DATE'],
            'effective_end_date'   => ['type' => 'DATE', 'null' => true],
            'approval_ref'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['agent_id', 'status']);
        $this->forge->addForeignKey('agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('scope_unit_id', 'organization_units', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('ai_agent_responsibilities', true);

        // 5. Skill Catalogue (Reusable Competency Master)
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'code'        => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 150],
            'category'    => ['type' => 'VARCHAR', 'constraint' => 50],
            'description' => ['type' => 'TEXT', 'null' => true],
            'version'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '1.0'],
            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('category');
        $this->forge->createTable('skill_catalogue', true);

        // 6. AI Agent Skills & Qualifications
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'agent_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'skill_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'proficiency_level'  => [
                'type'       => 'ENUM',
                'constraint' => ['beginner', 'competent', 'proficient', 'expert', 'master'],
                'default'    => 'competent',
            ],
            'verification_status'=> [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'verified', 'endorsed', 'rejected'],
                'default'    => 'pending',
            ],
            'verified_by'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'evidence_reference' => ['type' => 'TEXT', 'null' => true],
            'last_assessed_at'   => ['type' => 'DATETIME', 'null' => true],
            'is_active'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['agent_id', 'skill_id'], 'uk_agent_skill');
        $this->forge->addForeignKey('agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('skill_id', 'skill_catalogue', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ai_agent_skills', true);

        // 7. AI Agent Runtime Mappings (Hermes / 9Router Integration Boundary)
        $this->forge->addField([
            'id'                        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'agent_id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'provider'                  => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'hermes_docker'],
            'runtime_profile_reference' => ['type' => 'VARCHAR', 'constraint' => 150],
            'runtime_instance_id'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'model_gateway_preference'  => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => '9router'],
            'configuration_version'     => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'v1.0.0'],
            'integration_status'        => [
                'type'       => 'ENUM',
                'constraint' => ['unconfigured', 'mapped', 'provisioned', 'ready', 'error', 'disabled'],
                'default'    => 'unconfigured',
            ],
            'effective_start_date'      => ['type' => 'DATETIME'],
            'effective_end_date'        => ['type' => 'DATETIME', 'null' => true],
            'notes'                     => ['type' => 'TEXT', 'null' => true],
            'created_at'                => ['type' => 'DATETIME', 'null' => true],
            'updated_at'                => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['agent_id', 'integration_status']);
        $this->forge->addForeignKey('agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ai_agent_runtime_mappings', true);

        // 8. Individual Agent Performance History (Immutable timeline attached to Agent UUID)
        $this->forge->addField([
            'id'                  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'agent_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'agent_uuid'          => ['type' => 'VARCHAR', 'constraint' => 36],
            'category'            => [
                'type'       => 'ENUM',
                'constraint' => [
                    'assignment_change', 'lifecycle_transition', 'task_completion',
                    'activity', 'output_delivery', 'evaluation', 'recognition',
                    'audit_event', 'policy_event'
                ],
                'default'    => 'activity',
            ],
            'title'               => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'         => ['type' => 'TEXT'],
            'task_ref'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'output_ref'          => ['type' => 'TEXT', 'null' => true],
            'evidence_ref'        => ['type' => 'TEXT', 'null' => true],
            'evidence_status'     => [
                'type'       => 'ENUM',
                'constraint' => ['submitted', 'under_review', 'verified', 'rejected', 'disputed'],
                'default'    => 'submitted',
            ],
            'evaluator_identity'  => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'evaluation_notes'    => ['type' => 'TEXT', 'null' => true],
            'unit_id_at_time'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'position_id_at_time' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'superseded_by_id'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'created_at'          => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('agent_id');
        $this->forge->addKey('agent_uuid');
        $this->forge->addKey('category');
        $this->forge->addKey('evidence_status');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ai_agent_performance_history', true);

        // 9. AI Agent Lifecycle Events (Audit of state transitions)
        $this->forge->addField([
            'id'                   => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'agent_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'from_state'           => ['type' => 'VARCHAR', 'constraint' => 50],
            'to_state'             => ['type' => 'VARCHAR', 'constraint' => 50],
            'initiated_by_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'initiated_by_name'    => ['type' => 'VARCHAR', 'constraint' => 150],
            'approval_ref'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'reason'               => ['type' => 'TEXT', 'null' => true],
            'created_at'           => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('agent_id');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('agent_id', 'ai_agent_registry', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ai_agent_lifecycle_events', true);
    }

    public function down()
    {
        $this->forge->dropTable('ai_agent_lifecycle_events', true);
        $this->forge->dropTable('ai_agent_performance_history', true);
        $this->forge->dropTable('ai_agent_runtime_mappings', true);
        $this->forge->dropTable('ai_agent_skills', true);
        $this->forge->dropTable('skill_catalogue', true);
        $this->forge->dropTable('ai_agent_responsibilities', true);
        $this->forge->dropTable('ai_agent_assignments', true);
        $this->forge->dropTable('ai_agent_profiles', true);
        $this->forge->dropTable('ai_agent_registry', true);
    }
}
