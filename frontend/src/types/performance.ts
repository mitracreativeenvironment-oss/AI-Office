/**
 * MCE AI Office — Operational Performance Management, KPI Framework, Agent Evaluation & Executive Reporting Types (Prompt 29)
 */

export type ScoreBand =
  | 'exceptional'
  | 'exceeds_expectations'
  | 'meets_expectations'
  | 'needs_improvement'
  | 'unsatisfactory';

export type EvaluationStatus =
  | 'draft'
  | 'submitted'
  | 'under_review'
  | 'approved'
  | 'finalized'
  | 'disputed'
  | 'revised';

export type CycleStatus =
  | 'draft'
  | 'open'
  | 'data_collection'
  | 'evidence_aggregation'
  | 'kpi_calculation'
  | 'automated_validation'
  | 'reviewer_assessment'
  | 'review'
  | 'approval_pending'
  | 'finalized'
  | 'archived';

export type CoreValueKey =
  | 'integrity'
  | 'innovation'
  | 'collaboration'
  | 'excellence'
  | 'adaptability'
  | 'customer_centric'
  | 'sustainability';

export type PipStatus =
  | 'proposed'
  | 'approved'
  | 'in_progress'
  | 'under_review'
  | 'completed'
  | 'cancelled';

export type BonusApprovalStatus =
  | 'pending'
  | 'approved'
  | 'rejected'
  | 'disbursed';

export type ExecutiveReportType =
  | 'corporate_overview'
  | 'executive_operations'
  | 'organizational_unit'
  | 'ai_workforce'
  | 'project_portfolio';

export interface KpiCategory {
  id: number;
  category_code: string;
  name: string;
  description?: string;
  display_order: number;
  is_active: number;
  created_at?: string;
  updated_at?: string;
}

export interface KpiDefinition {
  id: number;
  kpi_code: string;
  category_id: number;
  category_code?: string;
  category_name?: string;
  name: string;
  description: string;
  measurement_objective?: string;
  performance_level: 'corporate' | 'executive' | 'organizational' | 'workforce' | 'project';
  formula_expression: string;
  formula_version: string;
  unit_of_measure: 'percentage' | 'currency_idr' | 'hours' | 'days' | 'count' | 'ratio' | 'score_100';
  data_source: string;
  measurement_frequency: 'realtime' | 'daily' | 'weekly' | 'monthly' | 'quarterly' | 'annual';
  target_value: number;
  warning_threshold?: number;
  critical_threshold?: number;
  default_weight: number;
  responsible_unit_id?: number;
  responsible_role?: string;
  verification_requirement?: string;
  approval_authority?: string;
  status: 'draft' | 'active' | 'under_review' | 'deprecated' | 'archived';
  is_active: number;
  created_at?: string;
  updated_at?: string;
}

export interface KpiResult {
  id: number;
  kpi_id: number;
  period_type: string;
  period_start: string;
  period_end: string;
  entity_type: string;
  entity_id?: number;
  actual_value: number;
  target_value: number;
  variance: number;
  variance_pct: number;
  value_type: 'actual' | 'target' | 'forecast' | 'benchmark' | 'estimated';
  data_status: 'verified' | 'partially_verified' | 'estimated' | 'incomplete' | 'unavailable';
  is_double_count_safe: number;
  calculation_version: string;
  calculation_meta_json?: string;
  calculated_by?: string;
  calculated_at?: string;
  created_at?: string;
}

export interface CorporateScorecardMetric {
  kpi_id: number;
  kpi_code: string;
  kpi_name: string;
  category_code: string;
  category_name: string;
  performance_level: string;
  actual_value: number;
  target_value: number;
  variance: number;
  unit_of_measure: string;
  attainment_pct: number;
  status: 'on_target' | 'warning' | 'critical';
  weight: number;
}

export interface CorporateScorecard {
  period: string;
  corporate_composite_index: number;
  health_rating: 'exemplary' | 'healthy' | 'attention_required';
  evaluation_period: string;
  total_active_agents: number;
  metrics_count: number;
  scorecard_categories: CorporateScorecardMetric[];
  operational_pulse: {
    total_tasks: number;
    completed_tasks: number;
    task_completion_rate: number;
    total_deliverables: number;
    accepted_deliverables: number;
    deliverable_acceptance: number;
    total_projects: number;
    active_projects: number;
  };
  governance_compliance: {
    doa_ceiling_adherence_pct: number;
    zero_self_review_pct: number;
    rest_day_violations: number;
  };
  generated_at: string;
}

export interface OperationalPulse {
  overall_health_pct: number;
  operational_status: string;
  active_agent_count: number;
  task_completion_rate: number;
  deliverable_acceptance: number;
  active_projects: number;
  generated_at: string;
}

export interface ExecutiveMandate {
  title: string;
  mandate: string;
  status: string;
  total_directives_issued?: number;
  acknowledgement_rate_pct?: number;
  total_decisions_proposed?: number;
  ratification_rate_pct?: number;
  active_workforce_managed?: number;
  tasks_orchestrated?: number;
}

export interface ExecutiveOverview {
  mandates: {
    human_owner: ExecutiveMandate;
    executive_board: ExecutiveMandate;
    director_ai: ExecutiveMandate;
  };
  doa_governance: {
    level_a_threshold_idr: number;
    level_b_threshold_idr: number;
    total_financial_actions: number;
    violations_count: number;
    audit_status: string;
  };
  evaluation_period: string;
  generated_at: string;
}

export interface DivisionalPerformance {
  division_id: number;
  unit_code: string;
  name: string;
  division_name?: string;
  division_head?: string;
  active_headcount: number;
  agent_count?: number;
  total_tasks: number;
  completed_tasks: number;
  completion_rate: number;
  deliverables_count: number;
  quality_rate: number;
  performance_score: number;
  efficiency_score?: number;
  health_status: 'high_performing' | 'satisfactory' | 'needs_attention';
}

export interface ProjectPortfolioSummary {
  total_projects: number;
  customer_projects: number;
  internal_initiatives: number;
  completed_milestones: number;
  total_milestones: number;
  accepted_deliverables: number;
  total_deliverables: number;
  average_progress_pct: number;
  milestone_attainment_rate: number;
  deliverable_acceptance_rate: number;
  avg_acceptance_rate_pct?: number;
}

export interface ProjectPortfolioItem {
  project_id: number;
  project_code: string;
  name: string;
  project_type: string;
  lifecycle_stage: string;
  client_name: string;
  progress_percent: number;
  health_status: string;
  milestones_completed: number;
  milestones_total: number;
  deliverables_accepted: number;
  deliverables_total: number;
}

export interface ProjectPortfolioPerformance {
  portfolio_summary: ProjectPortfolioSummary;
  projects: ProjectPortfolioItem[];
  evaluated_at?: string;
}

export interface EvaluationCycle {
  id: number;
  cycle_code: string;
  title: string;
  cycle_type: 'monthly' | 'quarterly' | 'annual' | 'project_end';
  period_start: string;
  period_end: string;
  status: CycleStatus;
  opened_by_user_id?: number;
  finalized_by_user_id?: number;
  finalized_at?: string;
  total_agents_evaluated: number;
  notes?: string;
  created_at?: string;
  updated_at?: string;
  evaluations?: AgentPerformanceEvaluation[];
}

export interface AgentPerformanceEvaluation {
  id: number;
  evaluation_code: string;
  cycle_id: number;
  cycle_title?: string;
  cycle_code?: string;
  agent_id: number;
  agent_code?: string;
  agent_name?: string;
  classification?: string;
  unit_name?: string;
  evaluator_user_id?: number;
  reviewer_user_id?: number;
  composite_score: number;
  quality_score: number;
  delivery_score: number;
  collaboration_score: number;
  core_values_score: number;
  complexity_factor: number;
  score_band: ScoreBand;
  status: EvaluationStatus;
  evaluation_notes?: string;
  strengths?: string;
  improvement_recommendations?: string;
  reviewer_feedback?: string;
  is_finalized: number;
  finalized_at?: string;
  audit_hash?: string;
  created_at?: string;
  updated_at?: string;
  evidence?: AgentEvaluationEvidence[];
  core_values?: CoreValuesAssessment[];
}

export interface AgentEvaluationEvidence {
  id: number;
  evaluation_id: number;
  evidence_type: 'task_deliverable' | 'peer_review' | 'wallbook_handover' | 'knowledge_contribution' | 'incident_resolution' | 'schedule_adherence' | 'project_milestone';
  source_table: string;
  source_id: number;
  reference_code?: string;
  title: string;
  summary?: string;
  evidence_weight: number;
  is_verified: number;
  verified_by?: string;
  verified_at?: string;
  created_at?: string;
}

export interface CoreValuesAssessment {
  id: number;
  evaluation_id?: number;
  cycle_id?: number;
  agent_id: number;
  agent_name?: string;
  agent_code?: string;
  core_value: CoreValueKey;
  rating_score: number;
  performance_level: 'exemplary' | 'proficient' | 'developing' | 'unsatisfactory';
  behavioral_evidence?: string;
  justification?: string;
  evaluator_identity?: string;
  assessed_at?: string;
  created_at?: string;
  updated_at?: string;
}

export interface CoreValuesSummary {
  total_assessments_logged: number;
  dimensions: Record<CoreValueKey, { count: number; avg_score: number }>;
  calculated_at: string;
}

export interface PerformanceImprovementPlan {
  id: number;
  plan_code: string;
  target_type: 'agent' | 'division' | 'unit' | 'project';
  target_id: number;
  evaluation_id?: number;
  evaluation_code?: string;
  composite_score?: number;
  objective: string;
  identified_gap: string;
  recommended_action: string;
  bound_task_id?: number;
  bound_task_title?: string;
  bound_task_status?: string;
  responsible_agent_id?: number;
  agent_name?: string;
  agent_code?: string;
  responsible_user_id?: number;
  target_completion_date: string;
  success_criteria: string;
  progress_percent: number;
  status: PipStatus;
  resolution_evidence?: string;
  reviewed_by_user_id?: number;
  completed_at?: string;
  created_at?: string;
  updated_at?: string;
}

export interface WelfareBonusReference {
  id: number;
  reference_code: string;
  evaluation_id: number;
  evaluation_code?: string;
  composite_score?: number;
  score_band?: string;
  agent_id: number;
  agent_name?: string;
  agent_code?: string;
  bonus_category: 'high_complexity_delivery' | 'exceptional_quality' | 'cross_division_innovation' | 'critical_incident_mitigation';
  recommended_ticket_bonus: number;
  justification: string;
  financial_approval_level: 'level_a' | 'level_b';
  financial_impact_idr: number;
  approval_status: BonusApprovalStatus;
  approved_by_user_id?: number;
  approved_at?: string;
  welfare_period_id?: number;
  period_code?: string;
  disbursed_at?: string;
  created_at?: string;
  updated_at?: string;
}

export interface ExecutiveReport {
  id: number;
  report_code: string;
  report_type: ExecutiveReportType;
  title: string;
  period_start: string;
  period_end: string;
  scope_filter_type: string;
  scope_filter_id?: number;
  executive_summary: string;
  highlights: string[];
  risks_and_issues: string[];
  kpi_summary: any;
  recommendations: string[];
  author_identity: string;
  approval_status: 'draft' | 'published' | 'ratified' | 'archived';
  generated_at?: string;
  created_at?: string;
  updated_at?: string;
}
