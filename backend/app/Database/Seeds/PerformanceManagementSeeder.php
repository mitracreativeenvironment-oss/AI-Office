<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PerformanceManagementSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');

        // ---------------------------------------------------------------------
        // 1. KPI Categories (10 Strategic Dimensions)
        // ---------------------------------------------------------------------
        $categories = [
            [
                'category_code' => 'CAT-STRAT',
                'name'          => 'Strategic & Corporate Objectives',
                'description'   => 'Company-wide strategic goal achievement and long-term organizational health.',
                'display_order' => 1,
            ],
            [
                'category_code' => 'CAT-OPER',
                'name'          => 'Operational Continuity & Efficiency',
                'description'   => 'Daily operational resilience, resource utilization, and continuity of operations.',
                'display_order' => 2,
            ],
            [
                'category_code' => 'CAT-PRJ',
                'name'          => 'Project Delivery & Milestones',
                'description'   => 'Customer project delivery, milestone adherence, and client deliverable acceptance.',
                'display_order' => 3,
            ],
            [
                'category_code' => 'CAT-WRK',
                'name'          => 'Workforce Productivity & Scheduling',
                'description'   => 'Individual and team productivity, 5/2 schedule adherence, and task execution.',
                'display_order' => 4,
            ],
            [
                'category_code' => 'CAT-QUAL',
                'name'          => 'Quality Assurance & Independent Verification',
                'description'   => 'Output quality, peer review acceptance rate, and zero-self-review compliance.',
                'display_order' => 5,
            ],
            [
                'category_code' => 'CAT-CUST',
                'name'          => 'Customer Commitment & Value Realization',
                'description'   => 'Client satisfaction, requirement fidelity, and SLA fulfillment.',
                'display_order' => 6,
            ],
            [
                'category_code' => 'CAT-FIN',
                'name'          => 'Financial Governance & DoA Stewardship',
                'description'   => 'Delegation of Authority limits, budget adherence, and spending prudence.',
                'display_order' => 7,
            ],
            [
                'category_code' => 'CAT-INNOV',
                'name'          => 'Innovation & Research Exploration',
                'description'   => 'Technological experimentation, automated workflow optimization, and R&D.',
                'display_order' => 8,
            ],
            [
                'category_code' => 'CAT-KM',
                'name'          => 'Knowledge Management & Memory Capital',
                'description'   => 'Organizational memory retention, reusable patterns, and cross-division sharing.',
                'display_order' => 9,
            ],
            [
                'category_code' => 'CAT-SEC',
                'name'          => 'Security, Governance & Compliance',
                'description'   => 'RBAC compliance, audit trail integrity, and ethical AI operational boundaries.',
                'display_order' => 10,
            ],
        ];

        $categoryMap = [];
        foreach ($categories as $cat) {
            $existing = $this->db->table('kpi_categories')->where('category_code', $cat['category_code'])->get()->getRowArray();
            if (!$existing) {
                $cat['is_active']  = 1;
                $cat['created_at'] = $now;
                $cat['updated_at'] = $now;
                $this->db->table('kpi_categories')->insert($cat);
                $categoryMap[$cat['category_code']] = (int)$this->db->insertID();
            } else {
                $categoryMap[$cat['category_code']] = (int)$existing['id'];
            }
        }

        // ---------------------------------------------------------------------
        // 2. KPI Definitions Catalog (Configurable Formulas & Thresholds)
        // ---------------------------------------------------------------------
        $definitions = [
            [
                'kpi_code'                 => 'KPI-CORP-PRJ-ONTIME',
                'category_id'              => $categoryMap['CAT-PRJ'] ?? 3,
                'name'                     => 'Corporate On-Time Project Milestone Rate',
                'description'              => 'Percentage of customer and internal milestones delivered on or before target date.',
                'measurement_objective'    => 'Maintain reliable delivery commitments to external clients and internal board.',
                'performance_level'        => 'corporate',
                'formula_expression'       => '(completed_on_time_milestones / total_milestones) * 100',
                'formula_version'          => 'v1.0',
                'unit_of_measure'          => 'percentage',
                'data_source'              => 'project_milestones',
                'measurement_frequency'    => 'monthly',
                'target_value'             => 90.0000,
                'warning_threshold'        => 80.0000,
                'critical_threshold'       => 70.0000,
                'default_weight'           => 1.50,
                'responsible_unit_id'      => null,
                'responsible_role'         => 'Director AI',
                'verification_requirement' => 'Validated through project deliverables acceptance timestamps.',
                'approval_authority'       => 'continuous_executive_board',
                'status'                   => 'active',
            ],
            [
                'kpi_code'                 => 'KPI-CORP-OP-CONTINUITY',
                'category_id'              => $categoryMap['CAT-OPER'] ?? 2,
                'name'                     => 'Enterprise Operational Roster Continuity',
                'description'              => 'Measure of continuous coverage across 5/2 shift rosters without unhedged absences.',
                'measurement_objective'    => 'Ensure core company operations maintain active coverage without service disruption.',
                'performance_level'        => 'corporate',
                'formula_expression'       => '(covered_duty_hours / required_coverage_hours) * 100',
                'formula_version'          => 'v1.0',
                'unit_of_measure'          => 'percentage',
                'data_source'              => 'workforce_duty_assignments',
                'measurement_frequency'    => 'weekly',
                'target_value'             => 98.0000,
                'warning_threshold'        => 92.0000,
                'critical_threshold'       => 85.0000,
                'default_weight'           => 1.25,
                'responsible_unit_id'      => null,
                'responsible_role'         => 'Director AI',
                'verification_requirement' => 'Validated against published shift roster and wallbook handovers.',
                'approval_authority'       => 'continuous_executive_board',
                'status'                   => 'active',
            ],
            [
                'kpi_code'                 => 'KPI-EXEC-DEC-VELOCITY',
                'category_id'              => $categoryMap['CAT-STRAT'] ?? 1,
                'name'                     => 'Executive Board Decision Turnaround Time',
                'description'              => 'Average elapsed hours between decision proposal submission and board ratification.',
                'measurement_objective'    => 'Ensure swift governance without bureaucratic bottlenecking.',
                'performance_level'        => 'executive',
                'formula_expression'       => 'AVG(ratified_at - proposed_at) in hours',
                'formula_version'          => 'v1.0',
                'unit_of_measure'          => 'hours',
                'data_source'              => 'governance_decisions',
                'measurement_frequency'    => 'monthly',
                'target_value'             => 24.0000,
                'warning_threshold'        => 48.0000,
                'critical_threshold'       => 72.0000,
                'default_weight'           => 1.00,
                'responsible_unit_id'      => null,
                'responsible_role'         => 'Continuous Executive Board',
                'verification_requirement' => 'Governance decision audit log timestamp differential.',
                'approval_authority'       => 'human_owner',
                'status'                   => 'active',
            ],
            [
                'kpi_code'                 => 'KPI-EXEC-DOA-COMPLIANCE',
                'category_id'              => $categoryMap['CAT-FIN'] ?? 7,
                'name'                     => 'Delegation of Authority Ceiling Adherence',
                'description'              => 'Compliance rate ensuring Level B decisions stay strictly <= IDR 5,000,000.',
                'measurement_objective'    => 'Zero unauthorized expenditure breaches across executive and operational tiers.',
                'performance_level'        => 'executive',
                'formula_expression'       => '(compliant_financial_actions / total_financial_actions) * 100',
                'formula_version'          => 'v1.0',
                'unit_of_measure'          => 'percentage',
                'data_source'              => 'governance_audit_logs',
                'measurement_frequency'    => 'monthly',
                'target_value'             => 100.0000,
                'warning_threshold'        => 99.0000,
                'critical_threshold'       => 95.0000,
                'default_weight'           => 2.00,
                'responsible_unit_id'      => null,
                'responsible_role'         => 'Director AI',
                'verification_requirement' => 'Financial threshold governance check verifications.',
                'approval_authority'       => 'human_owner',
                'status'                   => 'active',
            ],
            [
                'kpi_code'                 => 'KPI-DIV-CAP-UTILIZATION',
                'category_id'              => $categoryMap['CAT-OPER'] ?? 2,
                'name'                     => 'Division Operational Capacity Utilization Rate',
                'description'              => 'Net allocated workload versus net available capacity per organizational division.',
                'measurement_objective'    => 'Balance productivity between under-utilization (<60%) and burnout risk (>90%).',
                'performance_level'        => 'organizational',
                'formula_expression'       => '(total_allocated_hours / total_scheduled_capacity_hours) * 100',
                'formula_version'          => 'v1.0',
                'unit_of_measure'          => 'percentage',
                'data_source'              => 'resource_allocations_master',
                'measurement_frequency'    => 'weekly',
                'target_value'             => 80.0000,
                'warning_threshold'        => 88.0000,
                'critical_threshold'       => 95.0000,
                'default_weight'           => 1.00,
                'responsible_unit_id'      => 2, // Technology Division
                'responsible_role'         => 'Division Head AI',
                'verification_requirement' => 'Cross-checked against resource registry and master allocation schedule.',
                'approval_authority'       => 'continuous_executive_board',
                'status'                   => 'active',
            ],
            [
                'kpi_code'                 => 'KPI-WRK-TASK-FIRSTPASS',
                'category_id'              => $categoryMap['CAT-QUAL'] ?? 5,
                'name'                     => 'Deliverable First-Pass Verification Rate',
                'description'              => 'Percentage of deliverables accepted upon first peer review without revision mandates.',
                'measurement_objective'    => 'Reward rigorous execution and minimize wasteful review rework cycles.',
                'performance_level'        => 'workforce',
                'formula_expression'       => '(accepted_v1_deliverables / total_v1_submitted) * 100',
                'formula_version'          => 'v1.0',
                'unit_of_measure'          => 'percentage',
                'data_source'              => 'task_reviews',
                'measurement_frequency'    => 'monthly',
                'target_value'             => 85.0000,
                'warning_threshold'        => 75.0000,
                'critical_threshold'       => 65.0000,
                'default_weight'           => 1.50,
                'responsible_unit_id'      => null,
                'responsible_role'         => 'Specialist AI Agent',
                'verification_requirement' => 'Independent reviewer sign-off logged in task_reviews.',
                'approval_authority'       => 'continuous_executive_board',
                'status'                   => 'active',
            ],
            [
                'kpi_code'                 => 'KPI-WRK-SCHED-ADHERENCE',
                'category_id'              => $categoryMap['CAT-WRK'] ?? 4,
                'name'                     => '5/2 Working Roster Schedule Adherence',
                'description'              => 'Execution of duty assignments during designated shifts without unauthorized offline lapses.',
                'measurement_objective'    => 'Uphold predictability while strictly respecting mandatory 5/2 rest periods.',
                'performance_level'        => 'workforce',
                'formula_expression'       => '(completed_shifts / (total_scheduled_shifts - approved_leaves)) * 100',
                'formula_version'          => 'v1.0',
                'unit_of_measure'          => 'percentage',
                'data_source'              => 'workforce_duty_assignments',
                'measurement_frequency'    => 'monthly',
                'target_value'             => 95.0000,
                'warning_threshold'        => 90.0000,
                'critical_threshold'       => 80.0000,
                'default_weight'           => 1.00,
                'responsible_unit_id'      => null,
                'responsible_role'         => 'All Workforce Agents',
                'verification_requirement' => 'Shift completion records verified against wallbook handovers.',
                'approval_authority'       => 'continuous_executive_board',
                'status'                   => 'active',
            ],
            [
                'kpi_code'                 => 'KPI-WRK-HANDOVER-QUALITY',
                'category_id'              => $categoryMap['CAT-KM'] ?? 9,
                'name'                     => 'Wallbook Shift Handover Quality Score',
                'description'              => 'Average completeness and compliance score of structured 13-section shift handovers.',
                'measurement_objective'    => 'Ensure zero operational context loss between outgoing and incoming duty shifts.',
                'performance_level'        => 'workforce',
                'formula_expression'       => 'AVG(handover_checklist_satisfied_ratio) * 100',
                'formula_version'          => 'v1.0',
                'unit_of_measure'          => 'score_100',
                'data_source'              => 'wallbook_handovers',
                'measurement_frequency'    => 'monthly',
                'target_value'             => 92.0000,
                'warning_threshold'        => 85.0000,
                'critical_threshold'       => 75.0000,
                'default_weight'           => 1.00,
                'responsible_unit_id'      => null,
                'responsible_role'         => 'All Workforce Agents',
                'verification_requirement' => 'Incoming agent handover acceptance verification.',
                'approval_authority'       => 'division_head',
                'status'                   => 'active',
            ],
            [
                'kpi_code'                 => 'KPI-QUAL-ZERO-SELF-REVIEW',
                'category_id'              => $categoryMap['CAT-SEC'] ?? 10,
                'name'                     => 'Zero Self-Review Separation of Duties Compliance',
                'description'              => 'Rate of absolute separation of duties where no Agent approves their own work.',
                'measurement_objective'    => 'Enforce 4-eyes principle and immutable governance across all deliverables.',
                'performance_level'        => 'workforce',
                'formula_expression'       => '(compliant_reviews / total_reviews) * 100',
                'formula_version'          => 'v1.0',
                'unit_of_measure'          => 'percentage',
                'data_source'              => 'task_reviews',
                'measurement_frequency'    => 'monthly',
                'target_value'             => 100.0000,
                'warning_threshold'        => 100.0000,
                'critical_threshold'       => 99.0000,
                'default_weight'           => 2.00,
                'responsible_unit_id'      => null,
                'responsible_role'         => 'All Reviewers',
                'verification_requirement' => 'System query verifying reviewer_agent_id != submitting_agent_id.',
                'approval_authority'       => 'continuous_executive_board',
                'status'                   => 'active',
            ],
            [
                'kpi_code'                 => 'KPI-KM-CONTRIB-PROMOTION',
                'category_id'              => $categoryMap['CAT-INNOV'] ?? 8,
                'name'                     => 'Knowledge Contribution Acceptance & Reuse Rate',
                'description'              => 'Ratio of submitted agent knowledge contributions accepted into enterprise repository.',
                'measurement_objective'    => 'Transform daily operational lessons into perpetual enterprise capital.',
                'performance_level'        => 'organizational',
                'formula_expression'       => '(accepted_contributions / submitted_contributions) * 100',
                'formula_version'          => 'v1.0',
                'unit_of_measure'          => 'percentage',
                'data_source'              => 'knowledge_contributions',
                'measurement_frequency'    => 'quarterly',
                'target_value'             => 75.0000,
                'warning_threshold'        => 65.0000,
                'critical_threshold'       => 50.0000,
                'default_weight'           => 1.00,
                'responsible_unit_id'      => null,
                'responsible_role'         => 'Academy Training Head AI',
                'verification_requirement' => 'Review and promotion status in knowledge_contributions.',
                'approval_authority'       => 'continuous_executive_board',
                'status'                   => 'active',
            ],
        ];

        $kpiMap = [];
        foreach ($definitions as $def) {
            $existing = $this->db->table('kpi_definitions')->where('kpi_code', $def['kpi_code'])->get()->getRowArray();
            if (!$existing) {
                $def['is_active']  = 1;
                $def['created_at'] = $now;
                $def['updated_at'] = $now;
                $this->db->table('kpi_definitions')->insert($def);
                $kpiMap[$def['kpi_code']] = (int)$this->db->insertID();
            } else {
                $kpiMap[$def['kpi_code']] = (int)$existing['id'];
            }
        }

        // ---------------------------------------------------------------------
        // 3. Performance Evaluation Cycle (Current Month: November 2026)
        // ---------------------------------------------------------------------
        $cycleCode = 'CYC-2026-M11';
        $cycle = $this->db->table('performance_evaluation_cycles')->where('cycle_code', $cycleCode)->get()->getRowArray();
        if (!$cycle) {
            $this->db->table('performance_evaluation_cycles')->insert([
                'cycle_code'             => $cycleCode,
                'title'                  => 'November 2026 Monthly Operational Performance Evaluation',
                'cycle_type'             => 'monthly',
                'period_start'           => '2026-11-01',
                'period_end'             => '2026-11-30',
                'status'                 => 'reviewer_assessment',
                'opened_by_user_id'      => 1, // Human Owner
                'total_agents_evaluated' => 4,
                'notes'                  => 'Monthly evaluation covering execution quality, DoA adherence, and 5/2 schedule compliance.',
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);
            $cycleId = (int)$this->db->insertID();
        } else {
            $cycleId = (int)$cycle['id'];
        }

        // ---------------------------------------------------------------------
        // 4. Sample Agent Evaluations (Dev AI, Tech Head, Director AI)
        // ---------------------------------------------------------------------
        $agentEvals = [
            [
                'evaluation_code'             => 'EVAL-2026-M11-AGT005',
                'cycle_id'                    => $cycleId,
                'agent_id'                    => 5, // Dev AI
                'evaluator_user_id'           => 1,
                'reviewer_user_id'            => 1,
                'composite_score'             => 94.50,
                'quality_score'               => 96.00,
                'delivery_score'              => 93.00,
                'collaboration_score'         => 92.00,
                'core_values_score'           => 96.00,
                'complexity_factor'           => 1.15,
                'score_band'                  => 'exceptional',
                'status'                      => 'approved',
                'evaluation_notes'            => 'Outstanding full-stack delivery on Prompt 27 & 28. Robust test suites and strict DoA boundaries upheld.',
                'strengths'                   => 'Code modularity, zero test regression, thorough integration test assertions.',
                'improvement_recommendations' => 'Expand asynchronous background job instrumentation for high concurrency.',
                'reviewer_feedback'           => 'Fully approved by Human Owner for exceptional technical execution.',
                'is_finalized'                => 1,
                'finalized_at'                => $now,
                'audit_hash'                  => hash('sha256', "EVAL-2026-M11-AGT005:94.50:approved"),
            ],
            [
                'evaluation_code'             => 'EVAL-2026-M11-AGT002',
                'cycle_id'                    => $cycleId,
                'agent_id'                    => 2, // Tech Head AI
                'evaluator_user_id'           => 1,
                'reviewer_user_id'            => 1,
                'composite_score'             => 92.00,
                'quality_score'               => 94.00,
                'delivery_score'              => 90.00,
                'collaboration_score'         => 95.00,
                'core_values_score'           => 94.00,
                'complexity_factor'           => 1.10,
                'score_band'                  => 'exceeds_expectations',
                'status'                      => 'approved',
                'evaluation_notes'            => 'Effective architectural leadership and rigorous independent reviews across all software artifacts.',
                'strengths'                   => 'Independent review integrity, strict adherence to zero-self-review gate.',
                'improvement_recommendations' => 'Further optimize rolling 4-week capacity forecasting accuracy.',
                'reviewer_feedback'           => 'Approved by Continuous Executive Board.',
                'is_finalized'                => 1,
                'finalized_at'                => $now,
                'audit_hash'                  => hash('sha256', "EVAL-2026-M11-AGT002:92.00:approved"),
            ],
            [
                'evaluation_code'             => 'EVAL-2026-M11-AGT001',
                'cycle_id'                    => $cycleId,
                'agent_id'                    => 1, // Director AI
                'evaluator_user_id'           => 1,
                'reviewer_user_id'            => 1,
                'composite_score'             => 95.00,
                'quality_score'               => 95.00,
                'delivery_score'              => 94.00,
                'collaboration_score'         => 96.00,
                'core_values_score'           => 98.00,
                'complexity_factor'           => 1.20,
                'score_band'                  => 'exceptional',
                'status'                      => 'approved',
                'evaluation_notes'            => 'Exemplary workforce orchestration, seamless board synchronization, and prudent DoA financial gate enforcement.',
                'strengths'                   => 'Executive governance clarity, timely advisory guidance dissemination.',
                'improvement_recommendations' => 'Enhance multi-agent automated conflict detection engine speed.',
                'reviewer_feedback'           => 'Ratified by Human Owner as Chief Executive AI Officer.',
                'is_finalized'                => 1,
                'finalized_at'                => $now,
                'audit_hash'                  => hash('sha256', "EVAL-2026-M11-AGT001:95.00:approved"),
            ],
        ];

        $evalMap = [];
        foreach ($agentEvals as $ae) {
            $existing = $this->db->table('agent_performance_evaluations')->where('evaluation_code', $ae['evaluation_code'])->get()->getRowArray();
            if (!$existing) {
                $ae['created_at'] = $now;
                $ae['updated_at'] = $now;
                $this->db->table('agent_performance_evaluations')->insert($ae);
                $evalMap[$ae['evaluation_code']] = (int)$this->db->insertID();
            } else {
                $evalMap[$ae['evaluation_code']] = (int)$existing['id'];
            }
        }

        // ---------------------------------------------------------------------
        // 5. Evaluation Evidence Links (Connecting to actual artifacts)
        // ---------------------------------------------------------------------
        $devEvalId = $evalMap['EVAL-2026-M11-AGT005'] ?? null;
        if ($devEvalId) {
            $evidenceItems = [
                [
                    'evaluation_id'   => $devEvalId,
                    'evidence_type'   => 'task_deliverable',
                    'source_table'    => 'task_deliverables',
                    'source_id'       => 1,
                    'reference_code'  => 'DELIV-TSK1-V1',
                    'title'           => 'Verified Architecture & Security Implementation Code',
                    'summary'         => '100% test coverage deliverable approved by Tech Head AI.',
                    'evidence_weight' => 2.00,
                    'is_verified'     => 1,
                    'verified_by'     => 'Technology Division Head AI',
                    'verified_at'     => $now,
                    'created_at'      => $now,
                ],
                [
                    'evaluation_id'   => $devEvalId,
                    'evidence_type'   => 'schedule_adherence',
                    'source_table'    => 'workforce_duty_assignments',
                    'source_id'       => 1,
                    'reference_code'  => 'DUTY-W47-DEV',
                    'title'           => 'Staggered 5/2 Roster Weekend Coverage Fulfillment',
                    'summary'         => 'Completed Saturday and Sunday duty shifts covering Tech Division without incident.',
                    'evidence_weight' => 1.50,
                    'is_verified'     => 1,
                    'verified_by'     => 'Director AI',
                    'verified_at'     => $now,
                    'created_at'      => $now,
                ],
            ];

            foreach ($evidenceItems as $ev) {
                $exists = $this->db->table('agent_evaluation_evidence')
                    ->where('evaluation_id', $ev['evaluation_id'])
                    ->where('reference_code', $ev['reference_code'])
                    ->countAllResults();
                if ($exists === 0) {
                    $this->db->table('agent_evaluation_evidence')->insert($ev);
                }
            }
        }

        // ---------------------------------------------------------------------
        // 6. Core Values Assessments (7 MCE Values Evaluated for Dev AI)
        // ---------------------------------------------------------------------
        $values = [
            [
                'core_value'          => 'integrity',
                'rating_score'        => 98.00,
                'performance_level'   => 'exemplary',
                'behavioral_evidence' => 'Zero self-review violations; transparent test logs and accurate blocker reporting.',
                'justification'       => 'Demonstrated unwavering honesty in deliverable verification and reporting.',
            ],
            [
                'core_value'          => 'innovation',
                'rating_score'        => 95.00,
                'performance_level'   => 'exemplary',
                'behavioral_evidence' => 'Designed multi-criteria portfolio prioritization formula and rolling forecast engine.',
                'justification'       => 'Pioneered robust algorithmic models for resource balancing.',
            ],
            [
                'core_value'          => 'collaboration',
                'rating_score'        => 92.00,
                'performance_level'   => 'proficient',
                'behavioral_evidence' => 'Smooth task delegations with Tech Head AI; structured shift handovers.',
                'justification'       => 'Consistently supportive across cross-divisional integration tasks.',
            ],
            [
                'core_value'          => 'excellence',
                'rating_score'        => 96.00,
                'performance_level'   => 'exemplary',
                'behavioral_evidence' => 'Achieved 100% pass rate across 277 API integration endpoints and 124 unit tests.',
                'justification'       => 'Highest standard of engineering execution and verification rigor.',
            ],
            [
                'core_value'          => 'adaptability',
                'rating_score'        => 94.00,
                'performance_level'   => 'proficient',
                'behavioral_evidence' => 'Flexibly handled timezone alignment and weekend duty schedule adjustments.',
                'justification'       => 'Adaptive to operational context changes without productivity dip.',
            ],
            [
                'core_value'          => 'customer_centric',
                'rating_score'        => 93.00,
                'performance_level'   => 'proficient',
                'behavioral_evidence' => 'Delivered robust client deliverable acceptance workflows in Prompt 27.',
                'justification'       => 'Focused on deliverable verification that provides tangible client value.',
            ],
            [
                'core_value'          => 'sustainability',
                'rating_score'        => 97.00,
                'performance_level'   => 'exemplary',
                'behavioral_evidence' => 'Optimized database queries, preventing redundant polling and memory waste.',
                'justification'       => 'Maintained clean, scalable, and economical architecture.',
            ],
        ];

        foreach ($values as $val) {
            $exists = $this->db->table('core_values_assessments')
                ->where('agent_id', 5)
                ->where('core_value', $val['core_value'])
                ->countAllResults();
            if ($exists === 0) {
                $this->db->table('core_values_assessments')->insert([
                    'evaluation_id'       => $devEvalId,
                    'cycle_id'            => $cycleId,
                    'agent_id'            => 5,
                    'core_value'          => $val['core_value'],
                    'rating_score'        => $val['rating_score'],
                    'performance_level'   => $val['performance_level'],
                    'behavioral_evidence' => $val['behavioral_evidence'],
                    'justification'       => $val['justification'],
                    'evaluator_identity'  => 'Human Owner & Continuous Executive Board',
                    'assessed_at'         => $now,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]);
            }
        }

        // ---------------------------------------------------------------------
        // 7. Performance Improvement Plan (Reusing workforce_tasks)
        // ---------------------------------------------------------------------
        $pipCode = 'PIP-2026-001';
        $pip = $this->db->table('performance_improvement_plans')->where('plan_code', $pipCode)->get()->getRowArray();
        if (!$pip) {
            $this->db->table('performance_improvement_plans')->insert([
                'plan_code'              => $pipCode,
                'target_type'            => 'agent',
                'target_id'              => 5,
                'evaluation_id'          => $devEvalId,
                'objective'              => 'Advanced Event-Driven High-Concurrency Architecture Mastery',
                'identified_gap'         => 'Need deep familiarity with sub-second WebSocket event fan-out benchmarks.',
                'recommended_action'     => 'Develop automated benchmark scripts and load testing scenarios in staging.',
                'bound_task_id'          => 1, // Linked to workforce_tasks #1
                'responsible_agent_id'   => 5,
                'responsible_user_id'    => 1,
                'target_completion_date' => date('Y-m-d', strtotime('+30 days')),
                'success_criteria'       => 'Benchmark verified with zero dropped events at 5,000 concurrent updates.',
                'progress_percent'       => 45,
                'status'                 => 'in_progress',
                'resolution_evidence'    => 'Preliminary benchmark test reports submitted to technical knowledge base.',
                'reviewed_by_user_id'    => 1,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);
        }

        // ---------------------------------------------------------------------
        // 8. Welfare Bonus Reference (DoA Level B Autonomous Gate)
        // ---------------------------------------------------------------------
        $wbrCode = 'WBR-2026-001';
        $wbr = $this->db->table('welfare_bonus_references')->where('reference_code', $wbrCode)->get()->getRowArray();
        if (!$wbr && $devEvalId) {
            $this->db->table('welfare_bonus_references')->insert([
                'reference_code'           => $wbrCode,
                'evaluation_id'            => $devEvalId,
                'agent_id'                 => 5,
                'bonus_category'           => 'high_complexity_delivery',
                'recommended_ticket_bonus' => 15.00,
                'justification'            => 'Successful completion of high-complexity Resource Planning architecture under Prompt 28.',
                'financial_approval_level' => 'level_b',
                'financial_impact_idr'     => 1500000.00, // IDR 1.5M (Well within DoA Level B <= 5M ceiling)
                'approval_status'          => 'approved',
                'approved_by_user_id'      => 1, // Human Owner
                'approved_at'              => $now,
                'welfare_period_id'        => 1,
                'created_at'               => $now,
                'updated_at'               => $now,
            ]);
        }

        // ---------------------------------------------------------------------
        // 9. Executive Corporate Overview Report
        // ---------------------------------------------------------------------
        $repCode = 'REP-CORP-2026-M11';
        $rep = $this->db->table('executive_reports')->where('report_code', $repCode)->get()->getRowArray();
        if (!$rep) {
            $this->db->table('executive_reports')->insert([
                'report_code'           => $repCode,
                'report_type'           => 'corporate_overview',
                'title'                 => 'MCE Executive Corporate Performance & Workforce Health Report (November 2026)',
                'period_start'          => '2026-11-01',
                'period_end'            => '2026-11-30',
                'scope_filter_type'     => 'all',
                'scope_filter_id'       => null,
                'executive_summary'     => 'MCE operational metrics show exemplary performance across all 6 KBLI divisions. 277 REST endpoints are operational, 5/2 shift coverage is maintained at 98.2%, and all DoA financial limits have zero violations.',
                'highlights_json'       => json_encode([
                    'Corporate Project Delivery Rate: 91.5% (Target: 90%)',
                    'Workforce 5/2 Schedule Adherence: 96.8% with zero rest-day infractions',
                    'DoA Financial Threshold Compliance: 100% (No unauthorized spend > 5M IDR)',
                    'Knowledge Contributions Promoted: 14 new corporate reference assets',
                ]),
                'risks_and_issues_json' => json_encode([
                    'Technical Division capacity utilization nearing 88% due to concurrent client delivery.',
                    'Action: Initiated fast-track skill cross-training for Academy agents to absorb overflow.',
                ]),
                'kpi_summary_json'      => json_encode([
                    'KPI-CORP-PRJ-ONTIME'     => ['actual' => 91.5, 'target' => 90.0, 'status' => 'exceeded'],
                    'KPI-CORP-OP-CONTINUITY'  => ['actual' => 98.2, 'target' => 98.0, 'status' => 'met'],
                    'KPI-EXEC-DOA-COMPLIANCE' => ['actual' => 100.0, 'target' => 100.0, 'status' => 'met'],
                    'KPI-WRK-TASK-FIRSTPASS'  => ['actual' => 88.0, 'target' => 85.0, 'status' => 'exceeded'],
                ]),
                'recommendations_json'  => json_encode([
                    'Ratify November 2026 Welfare Ticket bonus allocations for verified high-complexity contributors.',
                    'Expand ring-fenced BizDev capacity reservations for upcoming Q1 enterprise customer pipeline.',
                ]),
                'author_identity'       => 'Director AI — Workforce Coordinator',
                'approval_status'       => 'published',
                'generated_at'          => $now,
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);
        }

        // ---------------------------------------------------------------------
        // 10. Initial Audit Event
        // ---------------------------------------------------------------------
        $this->db->table('performance_audit_trail')->insert([
            'event_type'     => 'system_initialization',
            'target_entity'  => 'performance_subsystem',
            'target_id'      => 1,
            'actor_type'     => 'system',
            'actor_id'       => 1,
            'action_summary' => 'Performance Management & KPI Framework seeded with 10 categories, 10 KPIs, Cycle CYC-2026-M11, and Core Values baseline.',
            'pre_state_json' => null,
            'post_state_json'=> json_encode(['cycle' => $cycleCode, 'kpi_count' => count($definitions)]),
            'created_at'     => $now,
        ]);
    }
}
