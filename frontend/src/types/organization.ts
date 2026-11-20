// Organization Hierarchy & Corporate Governance TypeScript Specifications

export type UnitType =
  | 'root'
  | 'executive_body'
  | 'division'
  | 'department'
  | 'sub_division'
  | 'unit'
  | 'team'
  | 'project_group';

export type UnitStatus = 'active' | 'restructuring' | 'suspended' | 'retired';

export interface OrganizationUnit {
  id: number;
  parent_id: number | null;
  code: string;
  name: string;
  type: UnitType;
  description?: string | null;
  mission?: string | null;
  division_key?: string | null;
  status: UnitStatus;
  sort_order: number;
  positions_count?: number;
  subunits_count?: number;
  children?: OrganizationUnit[];
  parent?: OrganizationUnit | null;
}

export type PositionLevel =
  | 'owner'
  | 'executive'
  | 'director'
  | 'division_head'
  | 'department_head'
  | 'unit_lead'
  | 'specialist'
  | 'operator';

export interface OrganizationPosition {
  id: number;
  unit_id: number;
  unit_name?: string;
  unit_code?: string;
  code: string;
  title: string;
  description?: string | null;
  level: PositionLevel;
  authority_profile_id?: number | null;
  authority_profile_name?: string;
  authority_level?: string;
  is_leadership: number | boolean;
  is_active: number | boolean;
  active_appointment?: OrganizationAppointment | null;
}

export interface OrganizationAppointment {
  id: number;
  position_id: number;
  unit_id: number;
  appointee_type: 'human' | 'agent';
  appointee_id: string;
  appointee_name: string;
  status: 'active' | 'acting' | 'interim' | 'ended' | 'revoked';
  start_date: string;
  end_date?: string | null;
  decision_ref?: string | null;
  notes?: string | null;
  position_title?: string;
  unit_name?: string;
}

export interface ReportingRelationship {
  id: number;
  subordinate_position_id: number;
  supervisor_position_id: number;
  relationship_type: 'direct' | 'functional' | 'executive' | 'advisory';
  description?: string | null;
  subordinate_title?: string;
  supervisor_title?: string;
}

export interface CoreValue {
  id: number;
  code: string;
  name_en: string;
  name_id: string;
  description: string;
  display_order: number;
  is_active: number | boolean;
}

export interface GovernanceBody {
  id: number;
  code: string;
  name: string;
  type: 'owner_authority' | 'executive_board' | 'director_authority' | 'committee' | 'working_group';
  description?: string | null;
  mandate?: string | null;
  decision_rule: 'sole_owner' | 'board_consensus' | 'board_majority' | 'delegated_operational';
  is_active: number | boolean;
  member_count?: number;
  members?: GovernanceMember[];
}

export interface GovernanceMember {
  id: number;
  body_id: number;
  member_type: 'human' | 'agent';
  member_id: string;
  member_name: string;
  governance_role: 'chair' | 'executive_member' | 'secretary' | 'advisor' | 'operational_lead';
  voting_weight: number;
  status: 'active' | 'inactive' | 'observer';
  start_date: string;
}

export interface AuthorityProfile {
  id: number;
  code: string;
  name: string;
  authority_level: 'A' | 'B' | 'C' | 'OPERATIONAL';
  domain: 'financial' | 'strategic' | 'organizational' | 'operational' | 'technical';
  financial_limit: number;
  permitted_actions?: string[];
  approval_conditions?: string | null;
  can_subdelegate: number | boolean;
}

export interface DelegationRecord {
  id: number;
  delegation_code: string;
  authority_profile_id: number;
  profile_name?: string;
  authority_level?: string;
  grantor_type: 'human' | 'governance_body';
  grantor_id: string;
  grantor_name: string;
  grantee_type: 'human' | 'agent' | 'governance_body' | 'position';
  grantee_id: string;
  grantee_name: string;
  scope_unit_id?: number | null;
  unit_name?: string;
  delegated_scope?: string | null;
  financial_limit: number;
  status: 'draft' | 'submitted' | 'approved' | 'active' | 'suspended' | 'expired' | 'revoked' | 'rejected';
  start_date: string;
  end_date?: string | null;
}

export interface OwnerDirection {
  id: number;
  direction_code: string;
  direction_type: 'advisory' | 'binding' | 'formal_approval' | 'strategic_instruction' | 'delegation' | 'clarification';
  subject: string;
  description: string;
  target_body_id?: number | null;
  target_unit_id?: number | null;
  issued_by_id: number;
  issued_by_name: string;
  requires_acknowledgment: number | boolean;
  acknowledged_at?: string | null;
  acknowledged_by?: string | null;
  due_date?: string | null;
  status: 'issued' | 'acknowledged' | 'in_progress' | 'completed' | 'superseded';
  created_at?: string;
}

export interface ExecutiveDecision {
  id: number;
  decision_code: string;
  governance_body_id: number;
  governance_body_name?: string;
  direction_id?: number | null;
  direction_code?: string | null;
  title: string;
  summary: string;
  decision_category: 'operational' | 'business_growth' | 'campaign' | 'internal_policy' | 'resource_allocation' | 'restructuring';
  decision_level: 'A' | 'B' | 'C';
  decided_by: string;
  effective_date: string;
  status: 'draft' | 'ratified' | 'in_execution' | 'executed' | 'archived' | 'rescinded';
  notes?: string | null;
  created_at?: string;
}
