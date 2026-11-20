<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProjectManagementAndDeliverySchema extends Migration
{
    public function up()
    {
        // 1. Project Clients (Minimal Customer/Client Profile)
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'client_code'  => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'company_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'industry'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status'       => ['type' => 'ENUM', 'constraint' => ['prospect', 'active', 'inactive', 'archived'], 'default' => 'active'],
            'address'      => ['type' => 'TEXT', 'null' => true],
            'website'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'notes'        => ['type' => 'TEXT', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->createTable('project_clients', true);

        // 2. Project Client Contacts
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'client_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'full_name'  => ['type' => 'VARCHAR', 'constraint' => 150],
            'job_title'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'phone'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'is_primary' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('client_id', 'project_clients', 'id', 'CASCADE', 'CASCADE', 'fk_pcc_client');
        $this->forge->createTable('project_client_contacts', true);

        // 3. Projects Core Table
        $this->forge->addField([
            'id'                      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'project_uuid'            => ['type' => 'CHAR', 'constraint' => 36, 'unique' => true],
            'project_code'            => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'                    => ['type' => 'VARCHAR', 'constraint' => 255],
            'project_type'            => ['type' => 'ENUM', 'constraint' => ['customer_project', 'internal_initiative'], 'default' => 'customer_project'],
            'category'                => [
                'type'       => 'ENUM',
                'constraint' => ['web_app', 'ai_automation', 'digital_infra', 'consulting', 'internal_platform', 'branding_marketing', 'rnd', 'process_improvement'],
                'default'    => 'web_app'
            ],
            'description'             => ['type' => 'TEXT', 'null' => true],
            'objectives'              => ['type' => 'TEXT', 'null' => true],
            'client_id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'unit_id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'project_owner_user_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'project_manager_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'project_manager_agent_id'=> ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'delivery_lead_agent_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'sponsoring_authority'    => ['type' => 'ENUM', 'constraint' => ['human_owner', 'continuous_executive_board', 'division_head'], 'default' => 'continuous_executive_board'],
            'sponsoring_direction_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'sponsoring_decision_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'channel_id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'lifecycle_stage'         => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'planning'
            ],
            'status'                  => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'active', 'on_hold', 'completed', 'cancelled', 'archived'],
                'default'    => 'active'
            ],
            'priority'                => [
                'type'       => 'ENUM',
                'constraint' => ['low', 'medium', 'high', 'urgent', 'critical'],
                'default'    => 'medium'
            ],
            'confidentiality_level'   => [
                'type'       => 'ENUM',
                'constraint' => ['public', 'internal', 'confidential', 'restricted'],
                'default'    => 'internal'
            ],
            'start_date'              => ['type' => 'DATE', 'null' => true],
            'target_completion_date'  => ['type' => 'DATE', 'null' => true],
            'actual_completion_date'  => ['type' => 'DATE', 'null' => true],
            'budget_currency'         => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'IDR'],
            'proposed_budget'         => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'approved_budget_limit'   => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'actual_cost_incurred'    => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'progress_percent'        => ['type' => 'INT', 'constraint' => 3, 'default' => 0],
            'health_status'           => ['type' => 'ENUM', 'constraint' => ['healthy', 'at_risk', 'critical'], 'default' => 'healthy'],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
            'updated_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('project_type');
        $this->forge->addKey('status');
        $this->forge->addKey('lifecycle_stage');
        $this->forge->addForeignKey('client_id', 'project_clients', 'id', 'SET NULL', 'CASCADE', 'fk_prj_client');
        $this->forge->addForeignKey('unit_id', 'organization_units', 'id', 'SET NULL', 'CASCADE', 'fk_prj_unit');
        $this->forge->addForeignKey('project_owner_user_id', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_prj_owner_user');
        $this->forge->addForeignKey('delivery_lead_agent_id', 'ai_agent_registry', 'id', 'SET NULL', 'CASCADE', 'fk_prj_lead_agent');
        $this->forge->addForeignKey('sponsoring_direction_id', 'owner_directions', 'id', 'SET NULL', 'CASCADE', 'fk_prj_direction');
        $this->forge->addForeignKey('sponsoring_decision_id', 'executive_decisions', 'id', 'SET NULL', 'CASCADE', 'fk_prj_decision');
        $this->forge->createTable('projects', true);

        // 4. Project Team Members
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'project_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'member_type'           => ['type' => 'ENUM', 'constraint' => ['human', 'agent'], 'default' => 'agent'],
            'user_id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'agent_id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'project_role'          => [
                'type'       => 'ENUM',
                'constraint' => ['project_sponsor', 'project_owner', 'project_manager', 'delivery_lead', 'contributor', 'reviewer', 'client_contact', 'specialist'],
                'default'    => 'contributor'
            ],
            'allocation_percentage' => ['type' => 'INT', 'constraint' => 3, 'default' => 100],
            'status'                => ['type' => 'ENUM', 'constraint' => ['active', 'released'], 'default' => 'active'],
            'joined_at'             => ['type' => 'DATETIME'],
            'released_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE', 'fk_ptm_project');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_ptm_user');
        $this->forge->addForeignKey('agent_id', 'ai_agent_registry', 'id', 'SET NULL', 'CASCADE', 'fk_ptm_agent');
        $this->forge->createTable('project_team_members', true);

        // 5. Project Briefs & Requirements
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'project_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'unique' => true],
            'background'          => ['type' => 'TEXT', 'null' => true],
            'business_objectives' => ['type' => 'TEXT', 'null' => true],
            'expected_outcomes'   => ['type' => 'TEXT', 'null' => true],
            'constraints'         => ['type' => 'TEXT', 'null' => true],
            'assumptions'         => ['type' => 'TEXT', 'null' => true],
            'dependencies'        => ['type' => 'TEXT', 'null' => true],
            'success_criteria'    => ['type' => 'TEXT', 'null' => true],
            'scope_boundaries'    => ['type' => 'TEXT', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE', 'fk_pb_project');
        $this->forge->createTable('project_briefs', true);

        // 6. Project Scope & Baseline Versions
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'project_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'version_number'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '1.0'],
            'scope_summary'        => ['type' => 'TEXT'],
            'in_scope_items_json'  => ['type' => 'TEXT', 'null' => true],
            'out_of_scope_json'    => ['type' => 'TEXT', 'null' => true],
            'is_baseline'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'approved_by_user_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'approved_at'          => ['type' => 'DATETIME', 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE', 'fk_ps_project');
        $this->forge->createTable('project_scopes', true);

        // 7. Project Milestones
        $this->forge->addField([
            'id'                     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'project_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'milestone_code'         => ['type' => 'VARCHAR', 'constraint' => 50],
            'title'                  => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'            => ['type' => 'TEXT', 'null' => true],
            'target_date'            => ['type' => 'DATE'],
            'actual_completion_date' => ['type' => 'DATE', 'null' => true],
            'is_critical_path'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status'                 => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'in_progress', 'achieved', 'delayed', 'missed'],
                'default'    => 'pending'
            ],
            'order_index'            => ['type' => 'INT', 'default' => 1],
            'created_at'             => ['type' => 'DATETIME', 'null' => true],
            'updated_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE', 'fk_pml_project');
        $this->forge->createTable('project_milestones', true);

        // 8. Project Deliverables
        $this->forge->addField([
            'id'                     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'project_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'milestone_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deliverable_code'       => ['type' => 'VARCHAR', 'constraint' => 50],
            'title'                  => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'            => ['type' => 'TEXT', 'null' => true],
            'responsible_agent_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'responsible_user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'due_date'               => ['type' => 'DATE', 'null' => true],
            'acceptance_criteria'    => ['type' => 'TEXT', 'null' => true],
            'supporting_evidence_url'=> ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'review_status'          => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'submitted', 'under_review', 'accepted', 'revision_required', 'rejected'],
                'default'    => 'draft'
            ],
            'reviewed_by_user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'reviewed_at'            => ['type' => 'DATETIME', 'null' => true],
            'client_accepted'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'client_accepted_at'     => ['type' => 'DATETIME', 'null' => true],
            'created_at'             => ['type' => 'DATETIME', 'null' => true],
            'updated_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE', 'fk_pdl_project');
        $this->forge->addForeignKey('milestone_id', 'project_milestones', 'id', 'SET NULL', 'CASCADE', 'fk_pdl_milestone');
        $this->forge->addForeignKey('responsible_agent_id', 'ai_agent_registry', 'id', 'SET NULL', 'CASCADE', 'fk_pdl_agent');
        $this->forge->createTable('project_deliverables', true);

        // 9. Project Risks
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'project_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'risk_code'        => ['type' => 'VARCHAR', 'constraint' => 50],
            'title'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'      => ['type' => 'TEXT'],
            'probability'      => ['type' => 'ENUM', 'constraint' => ['low', 'medium', 'high'], 'default' => 'medium'],
            'impact'           => ['type' => 'ENUM', 'constraint' => ['low', 'medium', 'high', 'critical'], 'default' => 'medium'],
            'risk_level'       => ['type' => 'ENUM', 'constraint' => ['low', 'medium', 'high', 'critical'], 'default' => 'medium'],
            'mitigation_plan'  => ['type' => 'TEXT', 'null' => true],
            'contingency_plan' => ['type' => 'TEXT', 'null' => true],
            'owner_user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'owner_agent_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'status'           => ['type' => 'ENUM', 'constraint' => ['identified', 'mitigated', 'occurred', 'closed'], 'default' => 'identified'],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE', 'fk_prk_project');
        $this->forge->createTable('project_risks', true);

        // 10. Project Issues
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'project_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'issue_code'           => ['type' => 'VARCHAR', 'constraint' => 50],
            'title'                => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'          => ['type' => 'TEXT'],
            'severity'             => ['type' => 'ENUM', 'constraint' => ['minor', 'moderate', 'major', 'blocker'], 'default' => 'moderate'],
            'priority'             => ['type' => 'ENUM', 'constraint' => ['low', 'medium', 'high', 'urgent'], 'default' => 'medium'],
            'resolution_plan'      => ['type' => 'TEXT', 'null' => true],
            'responsible_user_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'responsible_agent_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'status'               => ['type' => 'ENUM', 'constraint' => ['open', 'investigating', 'resolved', 'closed'], 'default' => 'open'],
            'resolved_at'          => ['type' => 'DATETIME', 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE', 'fk_pi_project');
        $this->forge->createTable('project_issues', true);

        // 11. Project Change Requests
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'project_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'change_code'           => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'requester_type'        => ['type' => 'ENUM', 'constraint' => ['human', 'agent', 'client'], 'default' => 'human'],
            'requester_user_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'requester_agent_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'title'                 => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'           => ['type' => 'TEXT'],
            'business_justification'=> ['type' => 'TEXT', 'null' => true],
            'scope_impact'          => ['type' => 'TEXT', 'null' => true],
            'schedule_impact_days'  => ['type' => 'INT', 'default' => 0],
            'budget_impact_amount'  => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'risk_assessment'       => ['type' => 'TEXT', 'null' => true],
            'approval_level'        => ['type' => 'ENUM', 'constraint' => ['A', 'B', 'C'], 'default' => 'B'],
            'status'                => [
                'type'       => 'ENUM',
                'constraint' => ['requested', 'under_review', 'approved', 'rejected', 'implemented'],
                'default'    => 'requested'
            ],
            'approved_by_user_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'approved_at'           => ['type' => 'DATETIME', 'null' => true],
            'created_at'            => ['type' => 'DATETIME', 'null' => true],
            'updated_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE', 'fk_pcr_project');
        $this->forge->createTable('project_change_requests', true);

        // 12. Project Resource Allocations
        $this->forge->addField([
            'id'                      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'project_id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'resource_type'           => [
                'type'       => 'ENUM',
                'constraint' => ['human_hours', 'ai_agent_runtime', 'compute_infrastructure', 'external_service'],
                'default'    => 'ai_agent_runtime'
            ],
            'resource_name'           => ['type' => 'VARCHAR', 'constraint' => 150],
            'allocated_quantity'      => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'utilized_quantity'       => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'unit_cost_idr'           => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'total_allocated_cost_idr'=> ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'notes'                   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE', 'fk_pra_project');
        $this->forge->createTable('project_resource_allocations', true);

        // 13. Project Status & Lifecycle History
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'project_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'from_stage'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'to_stage'            => ['type' => 'VARCHAR', 'constraint' => 50],
            'from_status'         => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'to_status'           => ['type' => 'VARCHAR', 'constraint' => 50],
            'changed_by_user_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'changed_by_agent_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'reason'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'notes'               => ['type' => 'TEXT', 'null' => true],
            'created_at'          => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE', 'fk_psh_project');
        $this->forge->createTable('project_status_history', true);

        // 14. Project Templates
        $this->forge->addField([
            'id'                        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'template_code'             => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'                      => ['type' => 'VARCHAR', 'constraint' => 150],
            'project_type'              => ['type' => 'ENUM', 'constraint' => ['customer_project', 'internal_initiative'], 'default' => 'customer_project'],
            'category'                  => ['type' => 'VARCHAR', 'constraint' => 50],
            'description'               => ['type' => 'TEXT', 'null' => true],
            'default_milestones_json'   => ['type' => 'TEXT', 'null' => true],
            'default_deliverables_json' => ['type' => 'TEXT', 'null' => true],
            'default_roles_json'        => ['type' => 'TEXT', 'null' => true],
            'is_active'                 => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'                => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('project_templates', true);

        // 15. Project Audit Events
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'event_type'    => ['type' => 'VARCHAR', 'constraint' => 100],
            'actor_type'    => ['type' => 'ENUM', 'constraint' => ['user', 'agent', 'system'], 'default' => 'user'],
            'actor_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'actor_name'    => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'project_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'metadata_json' => ['type' => 'TEXT', 'null' => true],
            'ip_address'    => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at'    => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('event_type');
        $this->forge->addKey('project_id');
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'SET NULL', 'CASCADE', 'fk_pae_project');
        $this->forge->createTable('project_audit_events', true);

        // 16. Extend workforce_tasks with optional project_id and milestone_id columns
        // Check if column already exists before adding
        $db = \Config\Database::connect();
        if (!$db->fieldExists('project_id', 'workforce_tasks')) {
            $this->forge->addColumn('workforce_tasks', [
                'project_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'unit_id'],
                'milestone_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'project_id'],
            ]);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        if ($db->fieldExists('project_id', 'workforce_tasks')) {
            $this->forge->dropColumn('workforce_tasks', 'milestone_id');
            $this->forge->dropColumn('workforce_tasks', 'project_id');
        }

        $this->forge->dropTable('project_audit_events', true);
        $this->forge->dropTable('project_templates', true);
        $this->forge->dropTable('project_status_history', true);
        $this->forge->dropTable('project_resource_allocations', true);
        $this->forge->dropTable('project_change_requests', true);
        $this->forge->dropTable('project_issues', true);
        $this->forge->dropTable('project_risks', true);
        $this->forge->dropTable('project_deliverables', true);
        $this->forge->dropTable('project_milestones', true);
        $this->forge->dropTable('project_scopes', true);
        $this->forge->dropTable('project_briefs', true);
        $this->forge->dropTable('project_team_members', true);
        $this->forge->dropTable('projects', true);
        $this->forge->dropTable('project_client_contacts', true);
        $this->forge->dropTable('project_clients', true);
    }
}
