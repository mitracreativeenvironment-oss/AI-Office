<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePerformanceManagementSchema extends Migration
{
    public function up()
    {
        // ---------------------------------------------------------------------
        // 1. KPI Categories
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'category_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'display_order' => [
                'type'       => 'INT',
                'default'    => 0,
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
        $this->forge->createTable('kpi_categories', true);

        // ---------------------------------------------------------------------
        // 2. KPI Definitions
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'kpi_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'category_id' => [
                'type'       => 'INT',
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'measurement_objective' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'performance_level' => [
                'type'       => 'ENUM',
                'constraint' => ['corporate', 'executive', 'organizational', 'project', 'workforce'],
                'default'    => 'corporate',
            ],
            'formula_expression' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'formula_version' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'v1.0',
            ],
            'unit_of_measure' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'percentage',
            ],
            'data_source' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'workforce_tasks',
            ],
            'measurement_frequency' => [
                'type'       => 'ENUM',
                'constraint' => ['daily', 'weekly', 'monthly', 'quarterly', 'annual'],
                'default'    => 'monthly',
            ],
            'target_value' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,4',
                'null'       => true,
            ],
            'warning_threshold' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,4',
                'null'       => true,
            ],
            'critical_threshold' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,4',
                'null'       => true,
            ],
            'default_weight' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 1.00,
            ],
            'responsible_unit_id' => [
                'type'       => 'INT',
                'null'       => true,
            ],
            'responsible_role' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'verification_requirement' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'approval_authority' => [
                'type'       => 'ENUM',
                'constraint' => ['human_owner', 'continuous_executive_board', 'division_head'],
                'default'    => 'continuous_executive_board',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'pending_owner_approval', 'active', 'deprecated'],
                'default'    => 'active',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_by_user_id' => [
                'type' => 'INT',
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
        $this->forge->addKey('category_id');
        $this->forge->addKey('performance_level');
        $this->forge->createTable('kpi_definitions', true);

        // ---------------------------------------------------------------------
        // 3. KPI Results (Recorded & Computed Metrics)
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
            ],
            'kpi_id' => [
                'type'       => 'INT',
            ],
            'period_type' => [
                'type'       => 'ENUM',
                'constraint' => ['daily', 'weekly', 'monthly', 'quarterly', 'annual'],
                'default'    => 'monthly',
            ],
            'period_start' => [
                'type' => 'DATE',
            ],
            'period_end' => [
                'type' => 'DATE',
            ],
            'entity_type' => [
                'type'       => 'ENUM',
                'constraint' => ['company', 'executive', 'division', 'unit', 'project', 'agent'],
                'default'    => 'company',
            ],
            'entity_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'actual_value' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,4',
                'null'       => true,
            ],
            'target_value' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,4',
                'null'       => true,
            ],
            'variance' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,4',
                'null'       => true,
            ],
            'variance_pct' => [
                'type'       => 'DECIMAL',
                'constraint' => '8,2',
                'null'       => true,
            ],
            'value_type' => [
                'type'       => 'ENUM',
                'constraint' => ['actual', 'target', 'forecast', 'benchmark', 'estimated'],
                'default'    => 'actual',
            ],
            'data_status' => [
                'type'       => 'ENUM',
                'constraint' => ['verified', 'partially_verified', 'estimated', 'incomplete', 'unavailable'],
                'default'    => 'verified',
            ],
            'is_double_count_safe' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'calculation_version' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'v1.0',
            ],
            'calculation_meta_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'calculated_by' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'MetricCalculationService',
            ],
            'calculated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['kpi_id', 'period_start', 'period_end']);
        $this->forge->addKey(['entity_type', 'entity_id']);
        $this->forge->createTable('kpi_results', true);

        // ---------------------------------------------------------------------
        // 4. Performance Evaluation Cycles
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'cycle_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'cycle_type' => [
                'type'       => 'ENUM',
                'constraint' => ['monthly', 'quarterly', 'annual', 'project_end'],
                'default'    => 'monthly',
            ],
            'period_start' => [
                'type' => 'DATE',
            ],
            'period_end' => [
                'type' => 'DATE',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'open', 'data_collection', 'evidence_aggregation', 'kpi_calculation', 'automated_validation', 'reviewer_assessment', 'review', 'approval_pending', 'finalized', 'archived'],
                'default'    => 'open',
            ],
            'opened_by_user_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'finalized_by_user_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'finalized_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'total_agents_evaluated' => [
                'type'    => 'INT',
                'default' => 0,
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
        $this->forge->createTable('performance_evaluation_cycles', true);

        // ---------------------------------------------------------------------
        // 5. Agent Performance Evaluations
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
            ],
            'evaluation_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'cycle_id' => [
                'type' => 'INT',
            ],
            'agent_id' => [
                'type' => 'INT',
            ],
            'evaluator_user_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'reviewer_user_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'composite_score' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
            ],
            'quality_score' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
            ],
            'delivery_score' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
            ],
            'collaboration_score' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
            ],
            'core_values_score' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
            ],
            'complexity_factor' => [
                'type'       => 'DECIMAL',
                'constraint' => '3,2',
                'default'    => 1.00,
            ],
            'score_band' => [
                'type'       => 'ENUM',
                'constraint' => ['exceptional', 'exceeds_expectations', 'meets_expectations', 'needs_improvement', 'unsatisfactory'],
                'default'    => 'meets_expectations',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'submitted', 'under_review', 'approved', 'finalized', 'disputed', 'revised'],
                'default'    => 'draft',
            ],
            'evaluation_notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'strengths' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'improvement_recommendations' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'reviewer_feedback' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_finalized' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'finalized_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'audit_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
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
        $this->forge->addKey(['cycle_id', 'agent_id']);
        $this->forge->addKey('score_band');
        $this->forge->createTable('agent_performance_evaluations', true);

        // ---------------------------------------------------------------------
        // 6. Agent Evaluation Evidence
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
            ],
            'evaluation_id' => [
                'type' => 'BIGINT',
            ],
            'evidence_type' => [
                'type'       => 'ENUM',
                'constraint' => ['task_deliverable', 'peer_review', 'wallbook_handover', 'knowledge_contribution', 'incident_resolution', 'schedule_adherence', 'project_milestone'],
            ],
            'source_table' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'source_id' => [
                'type' => 'BIGINT',
            ],
            'reference_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'summary' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'evidence_weight' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 1.00,
            ],
            'is_verified' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'verified_by' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'verified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['evaluation_id', 'evidence_type']);
        $this->forge->createTable('agent_evaluation_evidence', true);

        // ---------------------------------------------------------------------
        // 7. Core Values Assessments
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
            ],
            'evaluation_id' => [
                'type' => 'BIGINT',
                'null' => true,
            ],
            'cycle_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'agent_id' => [
                'type' => 'INT',
            ],
            'core_value' => [
                'type'       => 'ENUM',
                'constraint' => ['integrity', 'innovation', 'collaboration', 'excellence', 'adaptability', 'customer_centric', 'sustainability'],
            ],
            'rating_score' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 80.00,
            ],
            'performance_level' => [
                'type'       => 'ENUM',
                'constraint' => ['exemplary', 'proficient', 'developing', 'unsatisfactory'],
                'default'    => 'proficient',
            ],
            'behavioral_evidence' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'justification' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'evaluator_identity' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'Continuous Executive Board',
            ],
            'assessed_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addKey(['agent_id', 'core_value']);
        $this->forge->createTable('core_values_assessments', true);

        // ---------------------------------------------------------------------
        // 8. Performance Improvement Plans (Reusing workforce_tasks)
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'plan_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'target_type' => [
                'type'       => 'ENUM',
                'constraint' => ['agent', 'division', 'unit', 'project'],
                'default'    => 'agent',
            ],
            'target_id' => [
                'type' => 'INT',
            ],
            'evaluation_id' => [
                'type' => 'BIGINT',
                'null' => true,
            ],
            'objective' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'identified_gap' => [
                'type' => 'TEXT',
            ],
            'recommended_action' => [
                'type' => 'TEXT',
            ],
            'bound_task_id' => [
                'type' => 'INT',
                'null' => true, // Bound to workforce_tasks.id
            ],
            'responsible_agent_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'responsible_user_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'target_completion_date' => [
                'type' => 'DATE',
            ],
            'success_criteria' => [
                'type' => 'TEXT',
            ],
            'progress_percent' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['proposed', 'approved', 'in_progress', 'under_review', 'completed', 'cancelled'],
                'default'    => 'proposed',
            ],
            'resolution_evidence' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'reviewed_by_user_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addKey(['target_type', 'target_id']);
        $this->forge->createTable('performance_improvement_plans', true);

        // ---------------------------------------------------------------------
        // 9. Welfare Bonus References (Financial Governance & DoA Integration)
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
            ],
            'reference_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'evaluation_id' => [
                'type' => 'BIGINT',
            ],
            'agent_id' => [
                'type' => 'INT',
            ],
            'bonus_category' => [
                'type'       => 'ENUM',
                'constraint' => ['high_complexity_delivery', 'exceptional_quality', 'cross_division_innovation', 'critical_incident_mitigation'],
                'default'    => 'high_complexity_delivery',
            ],
            'recommended_ticket_bonus' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ],
            'justification' => [
                'type' => 'TEXT',
            ],
            'financial_approval_level' => [
                'type'       => 'ENUM',
                'constraint' => ['level_a', 'level_b'],
                'default'    => 'level_b',
            ],
            'financial_impact_idr' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,2',
                'default'    => 0.00,
            ],
            'approval_status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected', 'disbursed'],
                'default'    => 'pending',
            ],
            'approved_by_user_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'approved_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'welfare_period_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'disbursed_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addKey(['evaluation_id', 'agent_id']);
        $this->forge->createTable('welfare_bonus_references', true);

        // ---------------------------------------------------------------------
        // 10. Executive Reports
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'report_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'report_type' => [
                'type'       => 'ENUM',
                'constraint' => ['corporate_overview', 'executive_operations', 'organizational_unit', 'ai_workforce', 'project_portfolio'],
                'default'    => 'corporate_overview',
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'period_start' => [
                'type' => 'DATE',
            ],
            'period_end' => [
                'type' => 'DATE',
            ],
            'scope_filter_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'all',
            ],
            'scope_filter_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'executive_summary' => [
                'type' => 'TEXT',
            ],
            'highlights_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'risks_and_issues_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'kpi_summary_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'recommendations_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'author_identity' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'Director AI',
            ],
            'approval_status' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'published', 'ratified', 'archived'],
                'default'    => 'published',
            ],
            'generated_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addKey(['report_type', 'period_start', 'period_end']);
        $this->forge->createTable('executive_reports', true);

        // ---------------------------------------------------------------------
        // 11. Performance Audit Trail
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
            ],
            'event_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
            ],
            'target_entity' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
            ],
            'target_id' => [
                'type' => 'BIGINT',
            ],
            'actor_type' => [
                'type'       => 'ENUM',
                'constraint' => ['human', 'agent', 'system'],
                'default'    => 'system',
            ],
            'actor_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'action_summary' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'pre_state_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'post_state_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['target_entity', 'target_id']);
        $this->forge->createTable('performance_audit_trail', true);
    }

    public function down()
    {
        $this->forge->dropTable('performance_audit_trail', true);
        $this->forge->dropTable('executive_reports', true);
        $this->forge->dropTable('welfare_bonus_references', true);
        $this->forge->dropTable('performance_improvement_plans', true);
        $this->forge->dropTable('core_values_assessments', true);
        $this->forge->dropTable('agent_evaluation_evidence', true);
        $this->forge->dropTable('agent_performance_evaluations', true);
        $this->forge->dropTable('performance_evaluation_cycles', true);
        $this->forge->dropTable('kpi_results', true);
        $this->forge->dropTable('kpi_definitions', true);
        $this->forge->dropTable('kpi_categories', true);
    }
}
