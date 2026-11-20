<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateResourceAndCapacityPlanningSchema extends Migration
{
    public function up()
    {
        // 1. Resource Registry (Non-workforce operational assets & tools)
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'resource_uuid'       => ['type' => 'CHAR', 'constraint' => 36, 'unique' => true],
            'resource_code'       => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'                => ['type' => 'VARCHAR', 'constraint' => 150],
            'category'            => [
                'type'       => 'ENUM',
                'constraint' => ['ai_model_service', 'compute_infrastructure', 'development_environment', 'software_tool', 'shared_facility', 'external_service'],
                'default'    => 'software_tool'
            ],
            'description'         => ['type' => 'TEXT', 'null' => true],
            'unit_of_measure'     => [
                'type'       => 'ENUM',
                'constraint' => ['hours', 'tokens', 'seats', 'instances', 'units'],
                'default'    => 'hours'
            ],
            'total_capacity'      => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 100.00],
            'allocated_capacity'  => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'unit_cost_idr'       => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'owner_unit_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'status'              => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'maintenance', 'deprecated', 'retired'],
                'default'    => 'active'
            ],
            'specifications_json' => ['type' => 'TEXT', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('category');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('owner_unit_id', 'organization_units', 'id', 'SET NULL', 'CASCADE', 'fk_rr_unit');
        $this->forge->createTable('resource_registry', true);

        // 2. Resource Capacity Reservations (Internal operations ring-fencing)
        $this->forge->addField([
            'id'                      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'reservation_code'        => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'title'                   => ['type' => 'VARCHAR', 'constraint' => 255],
            'reservation_type'        => [
                'type'       => 'ENUM',
                'constraint' => ['internal_operations', 'bizdev_growth', 'platform_rnd', 'system_maintenance', 'agent_welfare_buffer', 'executive_strategic'],
                'default'    => 'internal_operations'
            ],
            'scope_unit_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'target_agent_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'target_resource_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'reserved_percentage'     => ['type' => 'INT', 'constraint' => 3, 'default' => 20],
            'reserved_hours_per_week' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'default' => 8.00],
            'start_date'              => ['type' => 'DATE'],
            'end_date'                => ['type' => 'DATE', 'null' => true],
            'sponsoring_authority'    => [
                'type'       => 'ENUM',
                'constraint' => ['continuous_executive_board', 'human_owner', 'division_head'],
                'default'    => 'continuous_executive_board'
            ],
            'sponsoring_decision_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'sponsoring_direction_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'status'                  => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'active', 'suspended', 'concluded'],
                'default'    => 'active'
            ],
            'justification'           => ['type' => 'TEXT', 'null' => true],
            'created_by_user_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
            'updated_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('reservation_type');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('scope_unit_id', 'organization_units', 'id', 'SET NULL', 'CASCADE', 'fk_rcr_unit');
        $this->forge->addForeignKey('target_agent_id', 'ai_agent_registry', 'id', 'SET NULL', 'CASCADE', 'fk_rcr_agent');
        $this->forge->addForeignKey('target_resource_id', 'resource_registry', 'id', 'SET NULL', 'CASCADE', 'fk_rcr_resource');
        $this->forge->addForeignKey('sponsoring_decision_id', 'executive_decisions', 'id', 'SET NULL', 'CASCADE', 'fk_rcr_decision');
        $this->forge->addForeignKey('sponsoring_direction_id', 'owner_directions', 'id', 'SET NULL', 'CASCADE', 'fk_rcr_direction');
        $this->forge->addForeignKey('created_by_user_id', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_rcr_user');
        $this->forge->createTable('resource_capacity_reservations', true);

        // 3. Resource Requests (Workforce and asset acquisition workflow)
        $this->forge->addField([
            'id'                        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'request_code'              => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'project_id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'requesting_unit_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'requester_user_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'requester_agent_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'resource_category'         => [
                'type'       => 'ENUM',
                'constraint' => ['workforce_agent', 'operational_tool', 'compute_infra', 'ai_model_service', 'multi_resource'],
                'default'    => 'workforce_agent'
            ],
            'title'                     => ['type' => 'VARCHAR', 'constraint' => 255],
            'purpose'                   => ['type' => 'TEXT'],
            'required_skill_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'required_proficiency'      => [
                'type'       => 'ENUM',
                'constraint' => ['beginner', 'competent', 'proficient', 'expert', 'master'],
                'null'       => true
            ],
            'quantity_requested'        => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 1.00],
            'hours_per_week'            => ['type' => 'DECIMAL', 'constraint' => '6,2', 'default' => 20.00],
            'start_date'                => ['type' => 'DATE'],
            'end_date'                  => ['type' => 'DATE', 'null' => true],
            'priority'                  => [
                'type'       => 'ENUM',
                'constraint' => ['low', 'medium', 'high', 'urgent', 'critical'],
                'default'    => 'medium'
            ],
            'estimated_cost_idr'        => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'lifecycle_status'          => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'submitted', 'under_review', 'capacity_assessed', 'approved', 'rejected', 'allocated', 'active', 'released', 'cancelled'],
                'default'    => 'draft'
            ],
            'capacity_assessment_notes' => ['type' => 'TEXT', 'null' => true],
            'is_capacity_sufficient'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'approval_authority_level'  => [
                'type'       => 'ENUM',
                'constraint' => ['A', 'B', 'C'],
                'default'    => 'B'
            ],
            'approved_by_user_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'approved_at'               => ['type' => 'DATETIME', 'null' => true],
            'rejection_reason'          => ['type' => 'TEXT', 'null' => true],
            'created_at'                => ['type' => 'DATETIME', 'null' => true],
            'updated_at'                => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('lifecycle_status');
        $this->forge->addKey('priority');
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'SET NULL', 'CASCADE', 'fk_rq_project');
        $this->forge->addForeignKey('requesting_unit_id', 'organization_units', 'id', 'SET NULL', 'CASCADE', 'fk_rq_unit');
        $this->forge->addForeignKey('requester_user_id', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_rq_user');
        $this->forge->addForeignKey('requester_agent_id', 'ai_agent_registry', 'id', 'SET NULL', 'CASCADE', 'fk_rq_agent');
        $this->forge->addForeignKey('required_skill_id', 'skill_catalogue', 'id', 'SET NULL', 'CASCADE', 'fk_rq_skill');
        $this->forge->addForeignKey('approved_by_user_id', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_rq_approver');
        $this->forge->createTable('resource_requests', true);

        // 4. Resource Allocations Master (Master schedule binding work & assets)
        $this->forge->addField([
            'id'                       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'allocation_code'          => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'request_id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'project_id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'allocation_type'          => [
                'type'       => 'ENUM',
                'constraint' => ['workforce_agent', 'workforce_human', 'operational_resource'],
                'default'    => 'workforce_agent'
            ],
            'agent_id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'user_id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'resource_id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'unit_id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'allocated_percentage'     => ['type' => 'INT', 'constraint' => 3, 'default' => 50],
            'allocated_hours_per_week' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'default' => 20.00],
            'start_date'               => ['type' => 'DATE'],
            'end_date'                 => ['type' => 'DATE', 'null' => true],
            'status'                   => [
                'type'       => 'ENUM',
                'constraint' => ['tentative', 'confirmed', 'active', 'completed', 'released', 'cancelled'],
                'default'    => 'confirmed'
            ],
            'unit_cost_idr'            => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'total_cost_idr'           => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'notes'                    => ['type' => 'TEXT', 'null' => true],
            'created_at'               => ['type' => 'DATETIME', 'null' => true],
            'updated_at'               => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->addForeignKey('request_id', 'resource_requests', 'id', 'SET NULL', 'CASCADE', 'fk_ram_request');
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'SET NULL', 'CASCADE', 'fk_ram_project');
        $this->forge->addForeignKey('agent_id', 'ai_agent_registry', 'id', 'SET NULL', 'CASCADE', 'fk_ram_agent');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_ram_user');
        $this->forge->addForeignKey('resource_id', 'resource_registry', 'id', 'SET NULL', 'CASCADE', 'fk_ram_resource');
        $this->forge->addForeignKey('unit_id', 'organization_units', 'id', 'SET NULL', 'CASCADE', 'fk_ram_unit');
        $this->forge->createTable('resource_allocations_master', true);

        // 5. Resource Conflicts (Automated collision detection)
        $this->forge->addField([
            'id'                     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'conflict_code'          => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'conflict_type'          => [
                'type'       => 'ENUM',
                'constraint' => ['overallocation', 'rest_day_conflict', 'leave_overlap', 'skill_mismatch', 'replacement_missing', 'resource_maintenance', 'budget_ceiling_breach'],
                'default'    => 'overallocation'
            ],
            'severity'               => [
                'type'       => 'ENUM',
                'constraint' => ['info', 'warning', 'critical', 'blocker'],
                'default'    => 'warning'
            ],
            'entity_type'            => [
                'type'       => 'ENUM',
                'constraint' => ['agent', 'resource', 'project', 'unit'],
                'default'    => 'agent'
            ],
            'agent_id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'resource_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'project_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'allocation_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'title'                  => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'            => ['type' => 'TEXT'],
            'impact_summary'         => ['type' => 'TEXT', 'null' => true],
            'recommended_resolution' => ['type' => 'TEXT', 'null' => true],
            'resolution_status'      => [
                'type'       => 'ENUM',
                'constraint' => ['detected', 'acknowledged', 'resolving', 'resolved', 'ignored'],
                'default'    => 'detected'
            ],
            'resolved_by_user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'resolution_notes'       => ['type' => 'TEXT', 'null' => true],
            'resolved_at'            => ['type' => 'DATETIME', 'null' => true],
            'detected_at'            => ['type' => 'DATETIME'],
            'created_at'             => ['type' => 'DATETIME', 'null' => true],
            'updated_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('conflict_type');
        $this->forge->addKey('severity');
        $this->forge->addKey('resolution_status');
        $this->forge->addForeignKey('agent_id', 'ai_agent_registry', 'id', 'SET NULL', 'CASCADE', 'fk_rc_agent');
        $this->forge->addForeignKey('resource_id', 'resource_registry', 'id', 'SET NULL', 'CASCADE', 'fk_rc_resource');
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'SET NULL', 'CASCADE', 'fk_rc_project');
        $this->forge->addForeignKey('allocation_id', 'resource_allocations_master', 'id', 'SET NULL', 'CASCADE', 'fk_rc_allocation');
        $this->forge->addForeignKey('resolved_by_user_id', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_rc_resolver');
        $this->forge->createTable('resource_conflicts', true);

        // 6. Portfolio Priorities (Strategic multi-criteria scoring & ranking)
        $this->forge->addField([
            'id'                        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'project_id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'unique' => true],
            'evaluation_period'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '2026-Q4'],
            'strategic_alignment_score' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 50.00],
            'customer_commitment_score' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 50.00],
            'business_value_score'      => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 50.00],
            'urgency_score'             => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 50.00],
            'feasibility_score'         => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 50.00],
            'risk_penalty_score'        => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 20.00],
            'composite_priority_score'  => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 50.00],
            'calculated_rank'           => ['type' => 'INT', 'default' => 1],
            'priority_tier'             => [
                'type'       => 'ENUM',
                'constraint' => ['tier_1_critical', 'tier_2_high', 'tier_3_medium', 'tier_4_deferrable'],
                'default'    => 'tier_2_high'
            ],
            'evaluation_rationale'      => ['type' => 'TEXT', 'null' => true],
            'evaluated_by_user_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'evaluated_at'              => ['type' => 'DATETIME', 'null' => true],
            'is_overridden'             => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'override_reason'           => ['type' => 'TEXT', 'null' => true],
            'created_at'                => ['type' => 'DATETIME', 'null' => true],
            'updated_at'                => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('priority_tier');
        $this->forge->addKey('calculated_rank');
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE', 'fk_pp_project');
        $this->forge->addForeignKey('evaluated_by_user_id', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_pp_evaluator');
        $this->forge->createTable('portfolio_priorities', true);

        // 7. Capacity Forecast Snapshots (Forward demand vs supply projections)
        $this->forge->addField([
            'id'                            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'snapshot_code'                 => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'period_type'                   => [
                'type'       => 'ENUM',
                'constraint' => ['weekly', 'monthly', 'quarterly'],
                'default'    => 'weekly'
            ],
            'period_start_date'             => ['type' => 'DATE'],
            'period_end_date'               => ['type' => 'DATE'],
            'unit_id'                       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'total_agents_headcount'        => ['type' => 'INT', 'default' => 0],
            'gross_capacity_hours'          => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'scheduled_capacity_hours'      => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'reserved_capacity_hours'       => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'projected_demand_hours'        => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'net_available_capacity_hours'  => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'utilization_projected_percent' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0.00],
            'status'                        => [
                'type'       => 'ENUM',
                'constraint' => ['balanced', 'surplus', 'deficit', 'critical_shortage'],
                'default'    => 'balanced'
            ],
            'forecast_assumptions_json'     => ['type' => 'TEXT', 'null' => true],
            'is_forecast_data'              => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'                    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('period_start_date');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('unit_id', 'organization_units', 'id', 'SET NULL', 'CASCADE', 'fk_cfs_unit');
        $this->forge->createTable('capacity_forecast_snapshots', true);

        // 8. Capacity Gap Analyses (Workforce & competency deficit resolution)
        $this->forge->addField([
            'id'                       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'gap_code'                 => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'unit_id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'skill_id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'gap_category'             => [
                'type'       => 'ENUM',
                'constraint' => ['skill_shortage', 'headcount_deficit', 'concurrency_bottleneck', 'coverage_gap'],
                'default'    => 'skill_shortage'
            ],
            'severity'                 => [
                'type'       => 'ENUM',
                'constraint' => ['low', 'medium', 'high', 'critical'],
                'default'    => 'medium'
            ],
            'required_capacity_hours'  => ['type' => 'DECIMAL', 'constraint' => '8,2', 'default' => 0.00],
            'available_capacity_hours' => ['type' => 'DECIMAL', 'constraint' => '8,2', 'default' => 0.00],
            'deficit_hours'            => ['type' => 'DECIMAL', 'constraint' => '8,2', 'default' => 0.00],
            'impacted_projects_json'   => ['type' => 'TEXT', 'null' => true],
            'recommended_action'       => [
                'type'       => 'ENUM',
                'constraint' => ['train_existing_agent', 'shift_reallocation', 'provision_new_agent', 'temporary_standby_activation'],
                'default'    => 'train_existing_agent'
            ],
            'action_plan_description'  => ['type' => 'TEXT', 'null' => true],
            'status'                   => [
                'type'       => 'ENUM',
                'constraint' => ['identified', 'under_review', 'action_initiated', 'resolved'],
                'default'    => 'identified'
            ],
            'created_at'               => ['type' => 'DATETIME', 'null' => true],
            'updated_at'               => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('gap_category');
        $this->forge->addKey('severity');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('unit_id', 'organization_units', 'id', 'SET NULL', 'CASCADE', 'fk_cga_unit');
        $this->forge->addForeignKey('skill_id', 'skill_catalogue', 'id', 'SET NULL', 'CASCADE', 'fk_cga_skill');
        $this->forge->createTable('capacity_gap_analyses', true);

        // 9. Resource Audit Events (Audit trail for allocations, approvals & overrides)
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'event_type'    => ['type' => 'VARCHAR', 'constraint' => 100],
            'actor_type'    => ['type' => 'ENUM', 'constraint' => ['user', 'agent', 'system'], 'default' => 'user'],
            'actor_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'actor_name'    => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'target_type'   => [
                'type'       => 'ENUM',
                'constraint' => ['request', 'allocation', 'reservation', 'conflict', 'priority', 'resource'],
                'default'    => 'allocation'
            ],
            'target_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'metadata_json' => ['type' => 'TEXT', 'null' => true],
            'ip_address'    => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at'    => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('event_type');
        $this->forge->addKey('target_type');
        $this->forge->createTable('resource_audit_events', true);
    }

    public function down()
    {
        $this->forge->dropTable('resource_audit_events', true);
        $this->forge->dropTable('capacity_gap_analyses', true);
        $this->forge->dropTable('capacity_forecast_snapshots', true);
        $this->forge->dropTable('portfolio_priorities', true);
        $this->forge->dropTable('resource_conflicts', true);
        $this->forge->dropTable('resource_allocations_master', true);
        $this->forge->dropTable('resource_requests', true);
        $this->forge->dropTable('resource_capacity_reservations', true);
        $this->forge->dropTable('resource_registry', true);
    }
}
