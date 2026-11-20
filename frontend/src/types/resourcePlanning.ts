export type UtilizationClassification =
  | 'underutilized'
  | 'balanced'
  | 'high_utilization'
  | 'overallocated'
  | 'unavailable';

export type ResourceCategory =
  | 'ai_model_service'
  | 'compute_infrastructure'
  | 'development_environment'
  | 'software_tool'
  | 'shared_facility'
  | 'external_service';

export type ReservationType =
  | 'internal_operations'
  | 'bizdev_growth'
  | 'platform_rnd'
  | 'system_maintenance'
  | 'agent_welfare_buffer'
  | 'executive_strategic';

export type RequestLifecycleStatus =
  | 'draft'
  | 'submitted'
  | 'under_review'
  | 'capacity_assessed'
  | 'approved'
  | 'rejected'
  | 'allocated'
  | 'active'
  | 'released'
  | 'cancelled';

export type ConflictType =
  | 'overallocation'
  | 'rest_day_conflict'
  | 'leave_overlap'
  | 'skill_mismatch'
  | 'replacement_missing'
  | 'resource_maintenance'
  | 'budget_ceiling_breach';

export type ConflictSeverity = 'info' | 'warning' | 'critical' | 'blocker';

export type ConflictResolutionStatus =
  | 'detected'
  | 'acknowledged'
  | 'resolving'
  | 'resolved'
  | 'ignored';

export type PriorityTier =
  | 'tier_1_critical'
  | 'tier_2_high'
  | 'tier_3_medium'
  | 'tier_4_deferrable';

export interface ResourceItem {
  id: number;
  resource_uuid: string;
  resource_code: string;
  name: string;
  category: ResourceCategory;
  description: string | null;
  unit_of_measure: 'hours' | 'tokens' | 'seats' | 'instances' | 'units';
  total_capacity: number;
  allocated_capacity: number;
  available_capacity: number;
  utilization_percent: number;
  unit_cost_idr: number;
  owner_unit_id: number | null;
  owner_unit_name?: string | null;
  owner_unit_code?: string | null;
  status: 'active' | 'maintenance' | 'deprecated' | 'retired';
  specifications: Record<string, any>;
  created_at: string | null;
  updated_at: string | null;
}

export interface AgentSkillItem {
  id: number;
  agent_id: number;
  skill_id: number;
  skill_code: string;
  skill_name: string;
  skill_category: string;
  proficiency_level: 'beginner' | 'competent' | 'proficient' | 'expert' | 'master';
  verification_status: 'pending' | 'verified' | 'endorsed' | 'rejected';
  verified_by?: string | null;
}

export interface AgentCapacity {
  agent_id: number;
  agent_uuid: string;
  agent_code: string;
  name: string;
  display_name: string;
  classification: string;
  operational_status: string;
  unit_id: number;
  unit_name?: string | null;
  unit_code?: string | null;
  position_title?: string | null;
  roster_schedule_type: string;
  gross_capacity_hours: number;
  scheduled_capacity_hours: number;
  allocated_capacity_hours: number;
  reserved_capacity_hours: number;
  unavailable_capacity_hours: number;
  available_capacity_hours: number;
  utilization_percent: number;
  utilization_classification: UtilizationClassification;
  skills_count: number;
  skills: AgentSkillItem[];
}

export interface AgentTimelineEntry {
  week_number: number;
  year: number;
  start_date: string;
  end_date: string;
  scheduled_capacity_hours: number;
  allocated_capacity_hours: number;
  reserved_capacity_hours: number;
  available_capacity_hours: number;
  utilization_percent: number;
  utilization_classification: UtilizationClassification;
}

export interface AgentWorkloadHeatmapItem {
  agent_id: number;
  agent_code: string;
  name: string;
  unit_code?: string | null;
  position_title?: string | null;
  current_utilization_pct: number;
  current_classification: UtilizationClassification;
  timeline: AgentTimelineEntry[];
}

export interface WorkloadSummary {
  total_agents_evaluated: number;
  counts_by_tier: {
    underutilized: number;
    balanced: number;
    high_utilization: number;
    overallocated: number;
    unavailable: number;
  };
  total_scheduled_hours: number;
  total_allocated_hours: number;
  total_reserved_hours: number;
  overall_utilization_pct: number;
  status: 'healthy' | 'action_required';
}

export interface DivisionalCapacityComparison {
  division_id: number;
  division_code: string;
  division_name: string;
  headcount: number;
  scheduled_capacity_hours: number;
  allocated_capacity_hours: number;
  reserved_capacity_hours: number;
  available_capacity_hours: number;
  utilization_percent: number;
  classification_counts: Record<string, number>;
}

export interface OrganizationOverview {
  overview: {
    unit_id: number | null;
    unit_name: string;
    unit_code: string;
    headcount: number;
    gross_capacity_hours: number;
    scheduled_capacity_hours: number;
    allocated_capacity_hours: number;
    reserved_capacity_hours: number;
    unavailable_capacity_hours: number;
    available_capacity_hours: number;
    utilization_percent: number;
    classification_counts: Record<string, number>;
    agents: AgentCapacity[];
  };
  divisional_breakdown: DivisionalCapacityComparison[];
  operational_resources: {
    total_resources: number;
    active_resources: number;
    maintenance_count: number;
  };
  generated_at: string;
}

export interface SkillMatrixItem {
  skill_id: number;
  skill_code: string;
  skill_name: string;
  category: string;
  description: string | null;
  total_agents_count: number;
  verified_agents_count: number;
  pending_agents_count: number;
  proficiency_breakdown: Record<string, number>;
  is_single_point_of_failure: boolean;
  assigned_agents: Array<{
    agent_id: number;
    agent_code: string;
    agent_name: string;
    proficiency_level: string;
    verification_status: string;
    unit_code?: string | null;
  }>;
}

export interface SkillBottleneck {
  skill_id: number;
  skill_code: string;
  skill_name: string;
  category: string;
  verified_agents_count: number;
  open_request_count: number;
  demand_hours_per_week: number;
  deficit_hours: number;
  severity: 'warning' | 'critical';
  recommended_action: string;
}

export interface SingleAgentDependency {
  skill_id: number;
  skill_code: string;
  skill_name: string;
  category: string;
  sole_agent: {
    agent_id: number;
    agent_code: string;
    agent_name: string;
    proficiency_level: string;
    verification_status: string;
  } | null;
  severity: 'high';
  recommendation: string;
}

export interface CapacityReservation {
  id: number;
  reservation_code: string;
  title: string;
  reservation_type: ReservationType;
  scope_unit_id: number | null;
  unit_name?: string | null;
  unit_code?: string | null;
  target_agent_id: number | null;
  agent_name?: string | null;
  agent_code?: string | null;
  target_resource_id: number | null;
  resource_name?: string | null;
  resource_code?: string | null;
  reserved_percentage: number;
  reserved_hours_per_week: number;
  start_date: string;
  end_date: string | null;
  sponsoring_authority: string;
  status: 'draft' | 'active' | 'suspended' | 'concluded';
  justification: string | null;
  creator_username?: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface ResourceRequest {
  id: number;
  request_code: string;
  project_id: number | null;
  project_name?: string | null;
  project_code?: string | null;
  requesting_unit_id: number | null;
  unit_name?: string | null;
  unit_code?: string | null;
  requester_user_id: number | null;
  requester_username?: string | null;
  requester_agent_id: number | null;
  resource_category: 'workforce_agent' | 'operational_tool' | 'compute_infra' | 'ai_model_service' | 'multi_resource';
  title: string;
  purpose: string;
  required_skill_id: number | null;
  required_skill_name?: string | null;
  required_skill_code?: string | null;
  required_proficiency: string | null;
  quantity_requested: number;
  hours_per_week: number;
  start_date: string;
  end_date: string | null;
  priority: 'low' | 'medium' | 'high' | 'urgent' | 'critical';
  estimated_cost_idr: number;
  lifecycle_status: RequestLifecycleStatus;
  capacity_assessment_notes: string | null;
  is_capacity_sufficient: number;
  approval_authority_level: 'A' | 'B' | 'C';
  approved_by_user_id: number | null;
  approver_username?: string | null;
  approved_at: string | null;
  rejection_reason: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface ResourceAllocation {
  id: number;
  allocation_code: string;
  request_id: number | null;
  project_id: number | null;
  project_name?: string | null;
  project_code?: string | null;
  allocation_type: 'workforce_agent' | 'workforce_human' | 'operational_resource';
  agent_id: number | null;
  agent_name?: string | null;
  agent_code?: string | null;
  user_id: number | null;
  username?: string | null;
  resource_id: number | null;
  resource_name?: string | null;
  resource_code?: string | null;
  unit_id: number | null;
  unit_name?: string | null;
  unit_code?: string | null;
  allocated_percentage: number;
  allocated_hours_per_week: number;
  start_date: string;
  end_date: string | null;
  status: 'tentative' | 'confirmed' | 'active' | 'completed' | 'released' | 'cancelled';
  unit_cost_idr: number;
  total_cost_idr: number;
  notes: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface ResourceConflict {
  id: number;
  conflict_code: string;
  conflict_type: ConflictType;
  severity: ConflictSeverity;
  entity_type: 'agent' | 'resource' | 'project' | 'unit';
  agent_id: number | null;
  agent_name?: string | null;
  agent_code?: string | null;
  resource_id: number | null;
  resource_name?: string | null;
  resource_code?: string | null;
  project_id: number | null;
  project_name?: string | null;
  project_code?: string | null;
  allocation_id: number | null;
  title: string;
  description: string;
  impact_summary: string | null;
  recommended_resolution: string | null;
  resolution_status: ConflictResolutionStatus;
  resolved_by_user_id: number | null;
  resolver_username?: string | null;
  resolution_notes: string | null;
  resolved_at: string | null;
  detected_at: string;
  created_at: string | null;
  updated_at: string | null;
}

export interface PortfolioPriority {
  id: number;
  project_id: number;
  project_name?: string | null;
  project_code?: string | null;
  project_type?: 'customer_project' | 'internal_initiative';
  project_status?: string | null;
  lifecycle_stage?: string | null;
  declared_priority?: string | null;
  health_status?: string | null;
  evaluation_period: string;
  strategic_alignment_score: number;
  customer_commitment_score: number;
  business_value_score: number;
  urgency_score: number;
  feasibility_score: number;
  risk_penalty_score: number;
  composite_priority_score: number;
  calculated_rank: number;
  priority_tier: PriorityTier;
  evaluation_rationale: string | null;
  evaluated_by_user_id: number | null;
  evaluator_username?: string | null;
  evaluated_at: string | null;
  is_overridden: number;
  override_reason: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface ForecastSnapshot {
  id: number;
  snapshot_code: string;
  period_type: 'weekly' | 'monthly' | 'quarterly';
  period_start_date: string;
  period_end_date: string;
  unit_id: number | null;
  unit_name?: string | null;
  unit_code?: string | null;
  total_agents_headcount: number;
  gross_capacity_hours: number;
  scheduled_capacity_hours: number;
  reserved_capacity_hours: number;
  projected_demand_hours: number;
  net_available_capacity_hours: number;
  utilization_projected_percent: number;
  status: 'balanced' | 'surplus' | 'deficit' | 'critical_shortage';
  forecast_assumptions: Record<string, any>;
  is_forecast_data: number;
  created_at: string | null;
}

export interface GapAnalysis {
  id: number;
  gap_code: string;
  unit_id: number | null;
  unit_name?: string | null;
  unit_code?: string | null;
  skill_id: number | null;
  skill_name?: string | null;
  skill_code?: string | null;
  gap_category: 'skill_shortage' | 'headcount_deficit' | 'concurrency_bottleneck' | 'coverage_gap';
  severity: 'low' | 'medium' | 'high' | 'critical';
  required_capacity_hours: number;
  available_capacity_hours: number;
  deficit_hours: number;
  impacted_projects: string[];
  recommended_action: 'train_existing_agent' | 'shift_reallocation' | 'provision_new_agent' | 'temporary_standby_activation';
  action_plan_description: string | null;
  status: 'identified' | 'under_review' | 'action_initiated' | 'resolved';
  created_at: string | null;
  updated_at: string | null;
}
