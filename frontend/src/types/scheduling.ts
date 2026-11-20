// Workforce Scheduling, Shift Coverage, Replacement & Wallbook Handover Types

export interface SchedulePolicy {
  id: number;
  policy_code: string;
  name: string;
  description?: string | null;
  cycle_days: number;
  working_days_count: number;
  rest_days_count: number;
  min_rest_interval_hours: number;
  applicable_unit_id?: number | null;
  is_default: number;
  is_active: number;
  effective_from?: string | null;
  effective_to?: string | null;
}

export interface WorkforceShift {
  id: number;
  shift_code: string;
  name: string;
  start_time: string;
  end_time: string;
  timezone: string;
  duration_hours: number;
  crosses_midnight: number;
  applicable_unit_id?: number | null;
  coverage_category: 'continuous_24_7' | 'business_hours' | 'extended_hours' | 'on_call';
  is_active: number;
}

export interface DutyAssignment {
  id: number;
  roster_id: number;
  agent_id: number;
  shift_id?: number | null;
  assignment_date: string;
  duty_type: 'working' | 'rest' | 'standby' | 'on_call';
  status: 'scheduled' | 'in_progress' | 'completed' | 'missed' | 'reassigned';
  is_actual_completed: number;
  completed_at?: string | null;
  replacement_proposal_id?: number | null;
  notes?: string | null;
  agent_name?: string;
  agent_code?: string;
  classification?: string;
  shift_name?: string;
  shift_code?: string;
  start_time?: string;
  end_time?: string;
}

export interface WorkforceRoster {
  id: number;
  roster_code: string;
  title: string;
  policy_id: number;
  policy_name?: string;
  working_days_count?: number;
  rest_days_count?: number;
  start_date: string;
  end_date: string;
  unit_id?: number | null;
  unit_name?: string | null;
  status: 'draft' | 'published' | 'archived';
  published_by_user_id?: number | null;
  published_at?: string | null;
  created_at: string;
  assignments_count?: number;
  assignments?: DutyAssignment[];
}

export interface CoverageRequirement {
  id: number;
  requirement_code: string;
  name: string;
  unit_id: number;
  unit_name?: string;
  unit_code?: string;
  shift_id?: number | null;
  shift_name?: string;
  shift_code?: string;
  day_of_week: string;
  min_agents_required: number;
  required_skill_id?: number | null;
  skill_name?: string;
  skill_code?: string;
  min_proficiency: string;
  criticality: 'critical' | 'high' | 'medium' | 'low';
  is_continuous_24_7: number;
  is_active: number;
}

export interface CoverageEvaluationItem {
  requirement_id: number;
  requirement_code: string;
  requirement_name: string;
  unit_id: number;
  unit_name: string;
  shift_id?: number | null;
  shift_name: string;
  criticality: 'critical' | 'high' | 'medium' | 'low';
  min_agents_required: number;
  actual_agents_count: number;
  status: 'covered' | 'partially_covered' | 'uncovered' | 'coverage_at_risk' | 'pending_replacement';
  qualified_agents: Array<{
    agent_id: number;
    agent_name: string;
    agent_code: string;
    classification: string;
  }>;
  gap: number;
}

export interface CoverageEvaluationResult {
  date: string;
  summary: {
    total_requirements: number;
    covered: number;
    partially_covered: number;
    uncovered: number;
    coverage_at_risk: number;
    pending_replacement: number;
  };
  evaluations: CoverageEvaluationItem[];
}

export interface ReplacementCandidate {
  candidate_agent_id: number;
  agent_code: string;
  name: string;
  classification: string;
  primary_unit_id?: number | null;
  is_same_unit: boolean;
  compatibility_score: number;
  matching_skills: string[];
  total_skills_count: number;
  is_available: boolean;
}

export interface ReplacementProposal {
  id: number;
  proposal_code: string;
  outgoing_agent_id: number;
  outgoing_agent_name?: string;
  outgoing_agent_code?: string;
  incoming_agent_id: number;
  incoming_agent_name?: string;
  incoming_agent_code?: string;
  duty_assignment_id: number;
  assignment_date?: string;
  original_responsibility?: string | null;
  start_time: string;
  end_time: string;
  reason: string;
  qualification_notes?: string | null;
  status: 'proposed' | 'accepted' | 'rejected' | 'approved' | 'activated' | 'cancelled';
  proposed_by_user_id?: number | null;
  accepted_at?: string | null;
  accepted_by_agent_identity?: string | null;
  approved_by_user_id?: number | null;
  approved_at?: string | null;
  activated_at?: string | null;
  created_at: string;
}

export interface WallbookHandoverItem {
  id?: number;
  handover_id?: number;
  section_category:
    | 'responsibilities'
    | 'active_tasks'
    | 'task_progress'
    | 'completed_work'
    | 'pending_work'
    | 'important_decisions'
    | 'outstanding_approvals'
    | 'operational_risks'
    | 'known_issues'
    | 'required_follow_up'
    | 'relevant_documents'
    | 'relevant_evidence'
    | 'context_instructions';
  item_order: number;
  title: string;
  content: string;
  item_status: 'pending' | 'in_progress' | 'transferred' | 'resolved';
  reference_type?: string | null;
  reference_id?: string | null;
}

export interface WallbookAcknowledgement {
  id: number;
  handover_id: number;
  agent_id: number;
  agent_name?: string;
  agent_code?: string;
  action: 'acknowledge' | 'request_clarification' | 'accept' | 'reject';
  comments?: string | null;
  clarification_questions?: string | null;
  created_at: string;
}

export interface WallbookHandover {
  id: number;
  handover_code: string;
  title: string;
  outgoing_agent_id: number;
  outgoing_agent_name?: string;
  outgoing_agent_code?: string;
  incoming_agent_id: number;
  incoming_agent_name?: string;
  incoming_agent_code?: string;
  unit_id: number;
  unit_name?: string;
  duty_assignment_id?: number | null;
  replacement_proposal_id?: number | null;
  shift_id?: number | null;
  shift_name?: string;
  shift_code?: string;
  deadline_at: string;
  status:
    | 'draft'
    | 'submitted'
    | 'under_review'
    | 'awaiting_acknowledgement'
    | 'acknowledged'
    | 'clarification_required'
    | 'accepted'
    | 'rejected'
    | 'escalated'
    | 'completed';
  submitted_at?: string | null;
  acknowledged_at?: string | null;
  accepted_at?: string | null;
  escalated_at?: string | null;
  escalation_reason?: string | null;
  created_at: string;
  items_count?: number;
  items?: WallbookHandoverItem[];
  acknowledgements?: WallbookAcknowledgement[];
}
