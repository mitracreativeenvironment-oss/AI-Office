// AI Agent Registry & Workforce Lifecycle TypeScript Specifications

export type AgentLifecycleState =
  | 'draft'
  | 'proposed'
  | 'under_review'
  | 'approved'
  | 'provisioning_pending'
  | 'active'
  | 'temporarily_suspended'
  | 'under_reconfiguration'
  | 'retired'
  | 'archived';

export type AgentOperationalStatus =
  | 'idle'
  | 'working'
  | 'on_leave'
  | 'handover_in_progress'
  | 'offline';

export type EvidenceStatus =
  | 'submitted'
  | 'under_review'
  | 'verified'
  | 'rejected'
  | 'disputed';

export interface AiAgent {
  id: number;
  agent_uuid: string;
  agent_code: string;
  name: string;
  display_name: string;
  classification: string;
  lifecycle_state: AgentLifecycleState;
  operational_status: AgentOperationalStatus;
  primary_unit_id: number | null;
  primary_position_id: number | null;
  unit_name?: string | null;
  unit_code?: string | null;
  position_title?: string | null;
  position_code?: string | null;
  legacy_agent_key?: string | null;
  roster_schedule_type: string;
  welfare_tier: string;
  designated_replacement_id?: number | null;
  created_by_user_id?: number | null;
  approved_by_user_id?: number | null;
  approved_at?: string | null;
  activated_at?: string | null;
  suspended_at?: string | null;
  retired_at?: string | null;
  created_at: string;
  updated_at: string;
  profile?: AgentProfile | null;
  skills?: AgentSkill[];
  responsibilities?: AgentResponsibility[];
  active_assignment?: AgentAssignment | null;
  runtime_mapping?: RuntimeMapping | null;
  skills_count?: number;
  responsibilities_count?: number;
  runtime_status?: string;
  runtime_provider?: string | null;
  description?: string | null;
  organizational_purpose?: string | null;
}

export interface AgentProfile {
  id: number;
  agent_id: number;
  description?: string | null;
  organizational_purpose?: string | null;
  primary_responsibilities_summary?: string | null;
  secondary_responsibilities_summary?: string | null;
  core_capabilities_summary?: string | null;
  communication_preferences?: Record<string, any> | string | null;
  profile_version: number;
  created_at: string;
  updated_at: string;
}

export interface AgentAssignment {
  id: number;
  agent_id: number;
  unit_id: number;
  position_id?: number | null;
  unit_name?: string;
  unit_code?: string;
  position_title?: string | null;
  position_code?: string | null;
  assignment_type: 'primary' | 'secondary' | 'project' | 'interim' | 'acting';
  start_date: string;
  end_date?: string | null;
  status: 'active' | 'concluded' | 'transferred' | 'revoked';
  assigned_by: string;
  approval_ref?: string | null;
  reason_for_change?: string | null;
  created_at: string;
}

export interface AgentResponsibility {
  id: number;
  agent_id: number;
  title: string;
  description: string;
  category: 'primary' | 'secondary' | 'temporary' | 'project_based' | 'compliance';
  scope_unit_id?: number | null;
  priority: 'critical' | 'high' | 'medium' | 'low';
  status: 'active' | 'deprecated' | 'transferred' | 'concluded';
  effective_start_date: string;
  effective_end_date?: string | null;
  approval_ref?: string | null;
}

export interface Skill {
  id: number;
  code: string;
  name: string;
  category: string;
  description?: string | null;
  version: string;
  is_active: number | boolean;
}

export interface AgentSkill {
  id: number;
  agent_id: number;
  skill_id: number;
  skill_code?: string;
  skill_name?: string;
  skill_category?: string;
  skill_description?: string | null;
  proficiency_level: 'beginner' | 'competent' | 'proficient' | 'expert' | 'master';
  verification_status: 'pending' | 'verified' | 'endorsed' | 'rejected';
  verified_by?: string | null;
  evidence_reference?: string | null;
  last_assessed_at?: string | null;
  is_active: number | boolean;
}

export interface RuntimeMapping {
  id: number;
  agent_id: number;
  provider: string;
  runtime_profile_reference: string;
  runtime_instance_id?: string | null;
  model_gateway_preference: string;
  configuration_version: string;
  integration_status: 'unconfigured' | 'mapped' | 'provisioned' | 'ready' | 'error' | 'disabled';
  effective_start_date: string;
  effective_end_date?: string | null;
  notes?: string | null;
}

export interface PerformanceHistoryRecord {
  id: number;
  agent_id: number;
  agent_uuid: string;
  category:
    | 'assignment_change'
    | 'lifecycle_transition'
    | 'task_completion'
    | 'activity'
    | 'output_delivery'
    | 'evaluation'
    | 'recognition'
    | 'audit_event'
    | 'policy_event';
  title: string;
  description: string;
  task_ref?: string | null;
  output_ref?: string | null;
  evidence_ref?: string | null;
  evidence_status: EvidenceStatus;
  evaluator_identity?: string | null;
  evaluation_notes?: string | null;
  unit_id_at_time?: number | null;
  position_id_at_time?: number | null;
  superseded_by_id?: number | null;
  created_at: string;
}

export interface LifecycleEvent {
  id: number;
  agent_id: number;
  from_state: string;
  to_state: string;
  initiated_by_user_id?: number | null;
  initiated_by_name: string;
  approval_ref?: string | null;
  reason?: string | null;
  created_at: string;
}

export interface WorkforceMetrics {
  total_registered_agents: number;
  active_agents: number;
  suspended_agents: number;
  under_review_agents: number;
  retired_agents: number;
  by_lifecycle_state: Record<string, number>;
  by_operational_status: Record<string, number>;
  by_classification: Record<string, number>;
}
