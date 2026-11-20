/**
 * MCE AI Office — Project Management, Client Engagement & Delivery Operations Types (Prompt 27)
 */

export type ProjectType = 'customer_project' | 'internal_initiative';

export type ProjectCategory =
  | 'web_app'
  | 'ai_automation'
  | 'digital_infra'
  | 'consulting'
  | 'internal_platform'
  | 'branding_marketing'
  | 'rnd'
  | 'process_improvement';

export type ProjectStatus = 'draft' | 'active' | 'on_hold' | 'completed' | 'cancelled' | 'archived';

export type ProjectHealth = 'healthy' | 'at_risk' | 'critical';

export type ProjectPriority = 'low' | 'medium' | 'high' | 'urgent' | 'critical';

export type ProjectClassification = 'public' | 'internal' | 'confidential' | 'restricted';

export type CustomerLifecycleStage =
  | 'lead'
  | 'qualification'
  | 'proposal'
  | 'contracted'
  | 'planning'
  | 'execution'
  | 'review_acceptance'
  | 'delivery_closure'
  | 'warranty_support';

export type InternalLifecycleStage =
  | 'idea_proposal'
  | 'evaluation'
  | 'authorized'
  | 'planning'
  | 'execution'
  | 'review'
  | 'completed_archived';

export type ProjectRole =
  | 'project_sponsor'
  | 'project_owner'
  | 'project_manager'
  | 'delivery_lead'
  | 'contributor'
  | 'reviewer'
  | 'client_contact'
  | 'specialist';

export type DeliverableStatus =
  | 'draft'
  | 'submitted'
  | 'under_review'
  | 'accepted'
  | 'revision_required'
  | 'rejected';

export type MilestoneStatus = 'pending' | 'in_progress' | 'achieved' | 'delayed' | 'missed';

export type RiskSeverity = 'low' | 'medium' | 'high' | 'critical';
export type RiskStatus = 'identified' | 'mitigated' | 'occurred' | 'closed';

export type IssueSeverity = 'minor' | 'moderate' | 'major' | 'blocker';
export type IssueStatus = 'open' | 'investigating' | 'resolved' | 'closed';

export type ChangeRequestStatus = 'requested' | 'under_review' | 'approved' | 'rejected' | 'implemented';

export interface ProjectClient {
  id: number;
  client_code: string;
  company_name: string;
  industry?: string;
  status: 'prospect' | 'active' | 'inactive' | 'archived';
  address?: string;
  website?: string;
  notes?: string;
  contact_count?: number;
  project_count?: number;
  active_project_count?: number;
  created_at?: string;
  updated_at?: string;
}

export interface ProjectClientContact {
  id: number;
  client_id: number;
  full_name: string;
  job_title?: string;
  email?: string;
  phone?: string;
  is_primary: number;
  created_at?: string;
  updated_at?: string;
}

export interface ProjectBrief {
  id?: number;
  project_id: number;
  background?: string;
  business_objectives?: string;
  expected_outcomes?: string;
  constraints?: string;
  assumptions?: string;
  dependencies?: string;
  success_criteria?: string;
  scope_boundaries?: string;
  created_at?: string;
  updated_at?: string;
}

export interface ProjectScopeBaseline {
  id: number;
  project_id: number;
  version_number: string;
  scope_summary: string;
  in_scope_items_json?: string;
  out_of_scope_json?: string;
  is_baseline: number;
  approved_by_user_id?: number;
  approver_name?: string;
  approved_at?: string;
  created_at?: string;
}

export interface ProjectMilestone {
  id: number;
  project_id: number;
  milestone_code: string;
  title: string;
  description?: string;
  target_date: string;
  actual_completion_date?: string;
  is_critical_path: number;
  status: MilestoneStatus;
  order_index: number;
  created_at?: string;
  updated_at?: string;
}

export interface ProjectDeliverable {
  id: number;
  project_id: number;
  milestone_id?: number;
  milestone_title?: string;
  deliverable_code: string;
  title: string;
  description?: string;
  responsible_agent_id?: number;
  responsible_agent_name?: string;
  responsible_user_id?: number;
  responsible_user_name?: string;
  due_date?: string;
  acceptance_criteria?: string;
  supporting_evidence_url?: string;
  review_status: DeliverableStatus;
  reviewed_by_user_id?: number;
  reviewed_at?: string;
  client_accepted: number;
  client_accepted_at?: string;
  created_at?: string;
  updated_at?: string;
}

export interface ProjectRisk {
  id: number;
  project_id: number;
  risk_code: string;
  title: string;
  description: string;
  probability: 'low' | 'medium' | 'high';
  impact: 'low' | 'medium' | 'high' | 'critical';
  risk_level: 'low' | 'medium' | 'high' | 'critical';
  mitigation_plan?: string;
  contingency_plan?: string;
  owner_user_id?: number;
  owner_user_name?: string;
  owner_agent_id?: number;
  owner_agent_name?: string;
  status: RiskStatus;
  created_at?: string;
  updated_at?: string;
}

export interface ProjectIssue {
  id: number;
  project_id: number;
  issue_code: string;
  title: string;
  description: string;
  severity: IssueSeverity;
  priority: 'low' | 'medium' | 'high' | 'urgent';
  resolution_plan?: string;
  responsible_user_id?: number;
  responsible_user_name?: string;
  responsible_agent_id?: number;
  responsible_agent_name?: string;
  status: IssueStatus;
  resolved_at?: string;
  created_at?: string;
  updated_at?: string;
}

export interface ProjectChangeRequest {
  id: number;
  project_id: number;
  change_code: string;
  requester_type: 'human' | 'agent' | 'client';
  requester_user_id?: number;
  requester_user_name?: string;
  title: string;
  description: string;
  business_justification?: string;
  scope_impact?: string;
  schedule_impact_days: number;
  budget_impact_amount: number;
  risk_assessment?: string;
  approval_level: 'A' | 'B' | 'C';
  status: ChangeRequestStatus;
  approved_by_user_id?: number;
  approved_at?: string;
  created_at?: string;
  updated_at?: string;
}

export interface ProjectTeamMember {
  id: number;
  project_id: number;
  member_type: 'human' | 'agent';
  user_id?: number;
  username?: string;
  user_full_name?: string;
  agent_id?: number;
  agent_name?: string;
  agent_code?: string;
  agent_classification?: string;
  project_role: ProjectRole;
  allocation_percentage: number;
  status: 'active' | 'released';
  joined_at: string;
  released_at?: string;
}

export interface ProjectResourceAllocation {
  id: number;
  project_id: number;
  resource_type: 'human_hours' | 'ai_agent_runtime' | 'compute_infrastructure' | 'external_service';
  resource_name: string;
  allocated_quantity: number;
  utilized_quantity: number;
  unit_cost_idr: number;
  total_allocated_cost_idr: number;
  notes?: string;
  created_at?: string;
}

export interface ProjectBudgetSummary {
  currency: string;
  proposed_budget: number;
  approved_budget: number;
  actual_cost_spent: number;
  remaining_budget: number;
  utilization_percent: number;
  is_over_budget: boolean;
  breakdown: Record<string, {
    allocated_cost: number;
    utilized_cost: number;
    count: number;
  }>;
}

export interface ProjectItem {
  id: number;
  project_uuid: string;
  project_code: string;
  name: string;
  project_type: ProjectType;
  category: ProjectCategory;
  description?: string;
  objectives?: string;
  client_id?: number;
  client_name?: string;
  client_code?: string;
  unit_id?: number;
  unit_name?: string;
  project_owner_user_id?: number;
  owner_full_name?: string;
  project_manager_user_id?: number;
  project_manager_agent_id?: number;
  pm_agent_name?: string;
  delivery_lead_agent_id?: number;
  dl_agent_name?: string;
  sponsoring_authority: 'human_owner' | 'continuous_executive_board' | 'division_head';
  channel_id?: number;
  lifecycle_stage: string;
  status: ProjectStatus;
  priority: ProjectPriority;
  confidentiality_level: ProjectClassification;
  start_date?: string;
  target_completion_date?: string;
  actual_completion_date?: string;
  budget_currency: string;
  proposed_budget: number;
  approved_budget_limit: number;
  actual_cost_incurred: number;
  progress_percent: number;
  health_status: ProjectHealth;
  created_at?: string;
  updated_at?: string;
}

export interface ProjectDetail extends ProjectItem {
  brief?: ProjectBrief;
  active_scope?: ProjectScopeBaseline;
  milestones: ProjectMilestone[];
  deliverables: ProjectDeliverable[];
  team_members: ProjectTeamMember[];
  counts: {
    milestones: number;
    deliverables: number;
    team_members: number;
    risks: number;
    open_issues: number;
    change_reqs: number;
    linked_tasks: number;
  };
}

export interface PortfolioMetrics {
  total_projects: number;
  customer_projects: number;
  internal_initiatives: number;
  active_projects: number;
  average_progress: number;
  health_distribution: {
    healthy: number;
    at_risk: number;
    critical: number;
  };
  financials: {
    currency: string;
    total_proposed_budget: number;
    total_approved_budget: number;
    total_actual_cost: number;
    budget_utilization_rate: number;
  };
  stage_distribution: Record<string, number>;
}
