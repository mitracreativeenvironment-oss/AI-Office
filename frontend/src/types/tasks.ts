// Workforce Task Management, Delegation, Collaboration & Quality Review Types

export type TaskStatus =
  | 'draft'
  | 'proposed'
  | 'awaiting_approval'
  | 'approved'
  | 'ready'
  | 'assigned'
  | 'awaiting_acceptance'
  | 'accepted'
  | 'in_progress'
  | 'blocked'
  | 'under_review'
  | 'revision_required'
  | 'verified'
  | 'completed'
  | 'suspended'
  | 'cancelled'
  | 'reopened';

export type TaskPriority = 'low' | 'medium' | 'high' | 'critical' | 'strategic';

export type TaskCategory =
  | 'development'
  | 'governance'
  | 'operations'
  | 'research'
  | 'customer_support'
  | 'hardware'
  | 'security'
  | 'financial';

export type TaskOrigin =
  | 'owner_advisory'
  | 'owner_binding'
  | 'board_decision'
  | 'division_head'
  | 'customer_project'
  | 'recurring_operational'
  | 'delegation'
  | 'incident';

export interface WorkforceTask {
  id: number;
  uuid: string;
  task_code: string;
  title: string;
  objective?: string | null;
  parent_task_id?: number | null;
  parent_task_code?: string | null;
  parent_task_title?: string | null;
  unit_id: number;
  unit_code?: string;
  unit_name?: string;
  category: TaskCategory;
  priority: TaskPriority;
  status: TaskStatus;
  origin_type: TaskOrigin;
  is_binding_instruction: number;
  accountable_agent_id: number;
  accountable_agent_name?: string;
  accountable_agent_code?: string;
  required_skill_id?: number | null;
  min_skill_proficiency?: string;
  approval_level: 'routine' | 'level_c' | 'level_b' | 'level_a';
  financial_amount?: number | null;
  start_date?: string | null;
  due_date?: string | null;
  completed_at?: string | null;
  progress_percent: number;
  confidentiality_level: 'public' | 'internal' | 'confidential' | 'restricted';
  created_at?: string;
  updated_at?: string;
  subtasks?: WorkforceTask[];
  assignments?: TaskAssignment[];
  deliverables?: TaskDeliverable[];
  blockers?: TaskBlocker[];
  dependencies?: TaskDependency[];
}

export interface TaskAssignment {
  id: number;
  task_id: number;
  agent_id: number;
  agent_name?: string;
  agent_code?: string;
  assignment_role: 'lead_agent' | 'collaborator' | 'specialist' | 'reviewer' | 'escalation_contact';
  responsibility_scope: string;
  acceptance_status: 'proposed' | 'accepted' | 'rejected';
  accepted_at?: string | null;
  status: 'active' | 'completed' | 'withdrawn';
  created_at?: string;
}

export interface TaskDelegation {
  id: number;
  task_id: number;
  task_code?: string;
  task_title?: string;
  delegating_agent_id: number;
  delegating_agent_name?: string;
  delegating_agent_code?: string;
  delegated_agent_id: number;
  delegated_agent_name?: string;
  delegated_agent_code?: string;
  delegated_scope: string;
  expected_output?: string | null;
  authority_limit: 'routine' | 'level_c' | 'level_b' | 'full';
  deadline?: string | null;
  status: 'proposed' | 'accepted' | 'rejected' | 'revoked' | 'completed';
  rejection_reason?: string | null;
  accepted_at?: string | null;
  created_at?: string;
}

export interface TaskCollaborationGroup {
  id: number;
  task_id: number;
  task_code?: string;
  task_title?: string;
  group_code: string;
  name: string;
  lead_agent_id: number;
  lead_agent_name?: string;
  shared_objective: string;
  status: 'active' | 'paused' | 'disbanded' | 'completed';
  members_count?: number;
  members?: TaskCollaborationMember[];
  created_at?: string;
}

export interface TaskCollaborationMember {
  id: number;
  group_id: number;
  agent_id: number;
  agent_name?: string;
  agent_code?: string;
  role: 'lead' | 'architect' | 'developer' | 'qa_verifier' | 'specialist' | 'domain_expert';
  assigned_responsibilities: string;
  status: 'active' | 'inactive';
  joined_at?: string;
}

export interface TaskDependency {
  id: number;
  task_id: number;
  depends_on_task_id: number;
  depends_on_task_code?: string;
  depends_on_task_title?: string;
  depends_on_task_status?: TaskStatus;
  dependency_type: 'finish_to_start' | 'start_to_start' | 'finish_to_finish';
  is_satisfied: number;
  created_at?: string;
}

export interface TaskDeliverable {
  id: number;
  task_id: number;
  submitting_agent_id: number;
  submitting_agent_name?: string;
  deliverable_type: 'code_patch' | 'report' | 'architecture_doc' | 'config' | 'audit_log' | 'data_model';
  title: string;
  description?: string | null;
  artifact_uri?: string | null;
  version: number;
  review_status: 'pending_review' | 'accepted' | 'revision_required' | 'rejected';
  is_current_accepted: number;
  reviews?: TaskReview[];
  created_at?: string;
}

export interface TaskReview {
  id: number;
  task_id: number;
  deliverable_id: number;
  reviewer_user_id?: number | null;
  reviewer_agent_id?: number | null;
  reviewer_name?: string;
  review_outcome: 'accepted' | 'revision_required' | 'rejected';
  evaluation_comments: string;
  checklist_results?: string | null;
  is_verified_contribution: number;
  performance_history_id?: number | null;
  created_at?: string;
}

export interface TaskBlocker {
  id: number;
  task_id: number;
  category: 'dependency' | 'missing_information' | 'authority_approval' | 'system_environment' | 'cross_agent';
  description: string;
  severity: 'low' | 'medium' | 'high' | 'critical';
  reported_by_agent_id?: number | null;
  reporter_name?: string;
  reported_at: string;
  status: 'active' | 'resolved';
  resolved_at?: string | null;
  resolution_notes?: string | null;
}

export interface TaskMetrics {
  total: number;
  draft: number;
  ready: number;
  assigned: number;
  in_progress: number;
  blocked: number;
  under_review: number;
  completed: number;
  avg_progress: number;
  high_priority_count: number;
}
