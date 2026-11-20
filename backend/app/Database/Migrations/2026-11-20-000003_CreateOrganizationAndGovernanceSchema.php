<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrganizationAndGovernanceSchema extends Migration
{
    public function up()
    {
        // 1. Organizational Units Table (Dynamic multi-level hierarchy)
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'parent_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'code'                 => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'                 => ['type' => 'VARCHAR', 'constraint' => 150],
            'type'                 => [
                'type'       => 'ENUM',
                'constraint' => ['root', 'executive_body', 'division', 'department', 'sub_division', 'unit', 'team', 'project_group'],
                'default'    => 'division',
            ],
            'description'          => ['type' => 'TEXT', 'null' => true],
            'mission'              => ['type' => 'TEXT', 'null' => true],
            'division_key'         => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'status'               => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'restructuring', 'suspended', 'retired'],
                'default'    => 'active',
            ],
            'sort_order'           => ['type' => 'INT', 'default' => 0],
            'effective_start_date' => ['type' => 'DATE', 'null' => true],
            'effective_end_date'   => ['type' => 'DATE', 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('parent_id');
        $this->forge->addKey('type');
        $this->forge->addKey('status');
        $this->forge->createTable('organization_units', true);

        // 2. Organization Positions Table (Offices/responsibilities distinct from units)
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'unit_id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'code'                 => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'title'                => ['type' => 'VARCHAR', 'constraint' => 150],
            'description'          => ['type' => 'TEXT', 'null' => true],
            'level'                => [
                'type'       => 'ENUM',
                'constraint' => ['owner', 'executive', 'director', 'division_head', 'department_head', 'unit_lead', 'specialist', 'operator'],
                'default'    => 'specialist',
            ],
            'authority_profile_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'is_leadership'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('unit_id');
        $this->forge->addKey('level');
        $this->forge->addForeignKey('unit_id', 'organization_units', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('organization_positions', true);

        // 3. Organization Appointments Table (Assigns human or agent to position historically)
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'position_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'unit_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'appointee_type' => ['type' => 'ENUM', 'constraint' => ['human', 'agent'], 'default' => 'human'],
            'appointee_id'   => ['type' => 'VARCHAR', 'constraint' => 100],
            'appointee_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'status'         => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'acting', 'interim', 'ended', 'revoked'],
                'default'    => 'active',
            ],
            'start_date'     => ['type' => 'DATETIME'],
            'end_date'       => ['type' => 'DATETIME', 'null' => true],
            'decision_ref'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'notes'          => ['type' => 'TEXT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['position_id', 'status']);
        $this->forge->addKey(['appointee_type', 'appointee_id']);
        $this->forge->addForeignKey('position_id', 'organization_positions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('unit_id', 'organization_units', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('organization_appointments', true);

        // 4. Reporting Relationships Table
        $this->forge->addField([
            'id'                      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'subordinate_position_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'supervisor_position_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'relationship_type'       => [
                'type'       => 'ENUM',
                'constraint' => ['direct', 'functional', 'executive', 'advisory'],
                'default'    => 'direct',
            ],
            'description'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['subordinate_position_id', 'supervisor_position_id', 'relationship_type'], 'uk_reporting');
        $this->forge->addForeignKey('subordinate_position_id', 'organization_positions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('supervisor_position_id', 'organization_positions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('reporting_relationships', true);

        // 5. Governance Bodies Table
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'code'          => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 150],
            'type'          => [
                'type'       => 'ENUM',
                'constraint' => ['owner_authority', 'executive_board', 'director_authority', 'committee', 'working_group'],
                'default'    => 'executive_board',
            ],
            'description'   => ['type' => 'TEXT', 'null' => true],
            'mandate'       => ['type' => 'TEXT', 'null' => true],
            'decision_rule' => [
                'type'       => 'ENUM',
                'constraint' => ['sole_owner', 'board_consensus', 'board_majority', 'delegated_operational'],
                'default'    => 'board_consensus',
            ],
            'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('governance_bodies', true);

        // 6. Governance Memberships Table
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'body_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'member_type'     => ['type' => 'ENUM', 'constraint' => ['human', 'agent'], 'default' => 'human'],
            'member_id'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'member_name'     => ['type' => 'VARCHAR', 'constraint' => 150],
            'governance_role' => [
                'type'       => 'ENUM',
                'constraint' => ['chair', 'executive_member', 'secretary', 'advisor', 'operational_lead'],
                'default'    => 'executive_member',
            ],
            'voting_weight'   => ['type' => 'INT', 'default' => 1],
            'status'          => ['type' => 'ENUM', 'constraint' => ['active', 'inactive', 'observer'], 'default' => 'active'],
            'start_date'      => ['type' => 'DATETIME'],
            'end_date'        => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('body_id', 'governance_bodies', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('governance_memberships', true);

        // 7. Authority Profiles Table (DoA Framework)
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'code'                => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'                => ['type' => 'VARCHAR', 'constraint' => 150],
            'authority_level'     => ['type' => 'ENUM', 'constraint' => ['A', 'B', 'C', 'OPERATIONAL'], 'default' => 'C'],
            'domain'              => [
                'type'       => 'ENUM',
                'constraint' => ['financial', 'strategic', 'organizational', 'operational', 'technical'],
                'default'    => 'operational',
            ],
            'financial_limit'     => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'permitted_actions'   => ['type' => 'TEXT', 'null' => true],
            'approval_conditions' => ['type' => 'TEXT', 'null' => true],
            'can_subdelegate'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('authority_profiles', true);

        // 8. Delegation Records Table
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'delegation_code'      => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'authority_profile_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'grantor_type'         => ['type' => 'ENUM', 'constraint' => ['human', 'governance_body'], 'default' => 'human'],
            'grantor_id'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'grantor_name'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'grantee_type'         => ['type' => 'ENUM', 'constraint' => ['human', 'agent', 'governance_body', 'position'], 'default' => 'agent'],
            'grantee_id'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'grantee_name'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'scope_unit_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'delegated_scope'      => ['type' => 'TEXT', 'null' => true],
            'financial_limit'      => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'status'               => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'submitted', 'approved', 'active', 'suspended', 'expired', 'revoked', 'rejected'],
                'default'    => 'active',
            ],
            'start_date'           => ['type' => 'DATETIME'],
            'end_date'             => ['type' => 'DATETIME', 'null' => true],
            'revoked_at'           => ['type' => 'DATETIME', 'null' => true],
            'revoked_by'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'revocation_reason'    => ['type' => 'TEXT', 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('authority_profile_id', 'authority_profiles', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('scope_unit_id', 'organization_units', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('delegation_records', true);

        // 9. Owner Directions Table
        $this->forge->addField([
            'id'                      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'direction_code'          => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'direction_type'          => [
                'type'       => 'ENUM',
                'constraint' => ['advisory', 'binding', 'formal_approval', 'strategic_instruction', 'delegation', 'clarification'],
                'default'    => 'advisory',
            ],
            'subject'                 => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'             => ['type' => 'TEXT'],
            'target_body_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'target_unit_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'issued_by_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'issued_by_name'          => ['type' => 'VARCHAR', 'constraint' => 150],
            'requires_acknowledgment' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'acknowledged_at'         => ['type' => 'DATETIME', 'null' => true],
            'acknowledged_by'         => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'due_date'                => ['type' => 'DATE', 'null' => true],
            'status'                  => [
                'type'       => 'ENUM',
                'constraint' => ['issued', 'acknowledged', 'in_progress', 'completed', 'superseded'],
                'default'    => 'issued',
            ],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
            'updated_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('owner_directions', true);

        // 10. Executive Decisions Table
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'decision_code'      => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'governance_body_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'direction_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'title'              => ['type' => 'VARCHAR', 'constraint' => 255],
            'summary'            => ['type' => 'TEXT'],
            'decision_category'  => [
                'type'       => 'ENUM',
                'constraint' => ['operational', 'business_growth', 'campaign', 'internal_policy', 'resource_allocation', 'restructuring'],
                'default'    => 'operational',
            ],
            'decision_level'     => ['type' => 'ENUM', 'constraint' => ['A', 'B', 'C'], 'default' => 'B'],
            'decided_by'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'effective_date'     => ['type' => 'DATE'],
            'status'             => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'ratified', 'in_execution', 'executed', 'archived', 'rescinded'],
                'default'    => 'ratified',
            ],
            'notes'              => ['type' => 'TEXT', 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('governance_body_id', 'governance_bodies', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('executive_decisions', true);

        // 11. Core Values Table (Controlled Reference Data)
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'code'          => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name_en'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'name_id'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'description'   => ['type' => 'TEXT'],
            'display_order' => ['type' => 'INT', 'default' => 0],
            'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('core_values', true);

        // 12. Governance Audit Logs Table
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 50],
            'entity_id'   => ['type' => 'VARCHAR', 'constraint' => 50],
            'action'      => ['type' => 'VARCHAR', 'constraint' => 50],
            'actor_type'  => ['type' => 'ENUM', 'constraint' => ['user', 'system', 'agent'], 'default' => 'user'],
            'actor_id'    => ['type' => 'VARCHAR', 'constraint' => 100],
            'actor_name'  => ['type' => 'VARCHAR', 'constraint' => 150],
            'old_state'   => ['type' => 'TEXT', 'null' => true],
            'new_state'   => ['type' => 'TEXT', 'null' => true],
            'notes'       => ['type' => 'TEXT', 'null' => true],
            'ip_address'  => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at'  => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['entity_type', 'entity_id']);
        $this->forge->addKey('created_at');
        $this->forge->createTable('governance_audit_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('governance_audit_logs', true);
        $this->forge->dropTable('core_values', true);
        $this->forge->dropTable('executive_decisions', true);
        $this->forge->dropTable('owner_directions', true);
        $this->forge->dropTable('delegation_records', true);
        $this->forge->dropTable('authority_profiles', true);
        $this->forge->dropTable('governance_memberships', true);
        $this->forge->dropTable('governance_bodies', true);
        $this->forge->dropTable('reporting_relationships', true);
        $this->forge->dropTable('organization_appointments', true);
        $this->forge->dropTable('organization_positions', true);
        $this->forge->dropTable('organization_units', true);
    }
}
