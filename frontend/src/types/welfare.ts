/**
 * MCE AI Office — Agent Welfare & Welfare Tickets Types (Prompt 24)
 */

export type WelfareTicketCategory =
  | 'base_compensation'
  | 'task_bonus'
  | 'special_recognition'
  | 'adjustment'
  | 'redemption'
  | 'reversal';

export type CompensationPeriodStatus =
  | 'draft'
  | 'calculated'
  | 'under_review'
  | 'approved'
  | 'distributed'
  | 'cancelled';

export type FacilityType =
  | 'rest_recovery'
  | 'quiet_focus'
  | 'recreation'
  | 'knowledge_enrichment'
  | 'collaboration'
  | 'resource_support';

export interface WelfarePolicy {
  id: number;
  policy_code: string;
  version: string;
  title: string;
  description?: string;
  ticket_unit_name: string;
  ticket_unit_symbol: string;
  base_period: 'weekly' | 'biweekly' | 'monthly' | 'custom';
  base_duty_tickets: number;
  bonus_calculation_method: string;
  max_issuance_limit_per_batch: number;
  requires_human_approval: number;
  approval_status: 'draft' | 'pending_approval' | 'approved' | 'rejected';
  effective_from: string;
  effective_until?: string | null;
  change_reason?: string;
  is_active: number;
  created_at: string;
  updated_at: string;
}

export interface WelfarePeriodCalculation {
  id: number;
  period_id: number;
  agent_id: number;
  agent_name?: string;
  agent_code?: string;
  agent_classification?: string;
  unit_name?: string;
  scheduled_duty_days: number;
  fulfilled_duty_days: number;
  leave_days: number;
  rest_days: number;
  base_tickets_amount: number;
  verified_tasks_count: number;
  task_bonus_amount: number;
  special_recognition_amount: number;
  adjustment_amount: number;
  net_tickets_calculated: number;
  calculation_basis?: string;
  status: 'draft' | 'eligible' | 'ineligible' | 'under_review' | 'approved' | 'issued' | 'rejected';
  ineligibility_reason?: string | null;
}

export interface WelfareCompensationPeriod {
  id: number;
  period_code: string;
  policy_id: number;
  policy_code?: string;
  policy_version?: string;
  period_type: 'weekly' | 'biweekly' | 'monthly' | 'custom';
  start_date: string;
  end_date: string;
  status: CompensationPeriodStatus;
  total_eligible_agents: number;
  total_base_tickets: number;
  total_bonus_tickets: number;
  total_tickets_issued: number;
  notes?: string;
  created_by?: number;
  approved_by?: number;
  calculated_at?: string;
  approved_at?: string;
  distributed_at?: string;
  calculations?: WelfarePeriodCalculation[];
}

export interface WelfareTaskBonusEvaluation {
  id: number;
  task_id: number;
  task_code?: string;
  task_title?: string;
  task_priority?: string;
  agent_id: number;
  agent_name?: string;
  agent_code?: string;
  deliverable_id?: number | null;
  review_id?: number | null;
  bonus_category: string;
  acceptance_criteria_satisfied: number;
  is_verified: number;
  verification_notes?: string;
  calculated_bonus_tickets: number;
  approved_bonus_tickets: number;
  approval_status: 'pending_review' | 'approved' | 'rejected';
  approved_by?: number | null;
  performance_history_id?: number | null;
  issuance_status: 'pending' | 'issued' | 'cancelled';
  created_at: string;
}

export interface WelfareSpecialRecognition {
  id: number;
  recognition_code: string;
  agent_id: number;
  agent_name?: string;
  agent_code?: string;
  agent_classification?: string;
  title: string;
  category: string;
  justification: string;
  evidence_ref?: string;
  awarded_tickets: number;
  approval_status: 'pending_review' | 'approved' | 'rejected';
  doa_level_required: 'A' | 'B' | 'C';
  performance_history_id?: number | null;
  issuance_status: 'pending' | 'issued' | 'cancelled';
  created_at: string;
}

export interface WelfareTicketTransaction {
  id: number;
  transaction_code: string;
  agent_id: number;
  agent_name?: string;
  agent_code?: string;
  ticket_category: WelfareTicketCategory;
  transaction_type: 'credit' | 'debit';
  amount: number;
  balance_after: number;
  source_period_id?: number | null;
  source_task_bonus_id?: number | null;
  source_recognition_id?: number | null;
  reversal_ref_transaction_id?: number | null;
  policy_version: string;
  status: 'pending' | 'completed' | 'reversed' | 'cancelled';
  reason: string;
  created_at: string;
}

export interface WelfareAgentBalance {
  agent_id: number;
  agent_code: string;
  agent_name: string;
  classification?: string;
  unit_name?: string;
  total_credits: number;
  total_debits: number;
  current_balance: number;
  is_reconciled: boolean;
  last_reconciled_at?: string;
}

export interface WelfareFacility {
  id: number;
  facility_code: string;
  building_id: number;
  building_name?: string;
  building_code?: string;
  room_id: number;
  room_name?: string;
  room_code?: string;
  name: string;
  facility_type: FacilityType;
  description?: string;
  capacity: number;
  current_occupancy: number;
  is_full: boolean;
  access_policy: string;
  operating_status: 'available' | 'busy' | 'maintenance' | 'reserved';
  is_active: number;
}

export interface WelfareFacilityUsageLog {
  id: number;
  facility_id: number;
  facility_name?: string;
  facility_code?: string;
  agent_id: number;
  agent_name?: string;
  agent_code?: string;
  workstation_id?: number | null;
  session_type: string;
  start_time: string;
  end_time?: string | null;
  status: 'active' | 'completed' | 'cancelled';
  notes?: string;
}

export interface WelfareOverviewMetrics {
  total_tickets_issued: number;
  total_tickets_deducted: number;
  net_circulation: number;
  by_category: Record<string, number>;
  total_active_agents: number;
  pending_approvals_total: number;
  pending_periods_count: number;
  pending_bonuses_count: number;
  pending_recognitions_count: number;
}

export interface WelfareOverview {
  active_policy: WelfarePolicy | null;
  metrics: WelfareOverviewMetrics;
  agent_roster: WelfareAgentBalance[];
  facilities: WelfareFacility[];
}
