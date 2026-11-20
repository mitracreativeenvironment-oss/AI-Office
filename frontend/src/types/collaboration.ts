/**
 * MCE AI Office — Internal Communication, Executive Coordination & Meetings Types (Prompt 26)
 */

export type CollaborationDomain =
  | 'executive_coordination'
  | 'corporate_ops'
  | 'division_ops'
  | 'project_collab'
  | 'technical'
  | 'knowledge_sharing'
  | 'announcements'
  | 'operational_alerts';

export type CollaborationClassification =
  | 'public'
  | 'internal'
  | 'confidential'
  | 'restricted'
  | 'highly_restricted';

export type MeetingCategory =
  | 'board_meeting'
  | 'executive_coordination'
  | 'division_sync'
  | 'cross_division'
  | 'project_meeting'
  | 'technical_review'
  | 'operational_planning';

export type MeetingStatus =
  | 'draft'
  | 'scheduled'
  | 'confirmed'
  | 'in_progress'
  | 'completed'
  | 'minutes_finalized'
  | 'cancelled'
  | 'rescheduled';

export type ParticipantRole = 'chair' | 'secretary' | 'participant' | 'observer';

export type InvitationStatus = 'invited' | 'confirmed' | 'declined' | 'tentative';

export type AttendanceStatus = 'unrecorded' | 'attended' | 'absent' | 'excused';

export type DirectiveType = 'strategic_instruction' | 'policy_guidance' | 'advisory_notice' | 'mandate';

export type DirectiveStatus = 'issued' | 'acknowledged' | 'in_progress' | 'completed' | 'suspended';

export type DirectiveAckStatus =
  | 'acknowledged'
  | 'clarification_requested'
  | 'in_execution'
  | 'executed'
  | 'impediment_reported';

export type ActionItemStatus = 'pending' | 'assigned_to_task' | 'in_progress' | 'completed' | 'cancelled';

export interface Channel {
  id: number;
  channel_code: string;
  name: string;
  description?: string | null;
  domain: CollaborationDomain;
  scope_unit_id?: number | null;
  unit_code?: string | null;
  unit_name?: string | null;
  classification: CollaborationClassification;
  is_private: number;
  is_archived: number;
  member_count?: number;
  is_member?: boolean;
  membership_role?: 'admin' | 'moderator' | 'member' | null;
  created_at: string;
  updated_at: string;
  members?: ChannelMember[];
}

export interface ChannelMember {
  id: number;
  channel_id: number;
  member_type: 'human' | 'agent';
  user_id?: number | null;
  agent_id?: number | null;
  user_name?: string | null;
  user_email?: string | null;
  agent_name?: string | null;
  agent_code?: string | null;
  role: 'admin' | 'moderator' | 'member';
  notifications_enabled: number;
  last_read_message_id?: number | null;
  joined_at: string;
}

export interface Conversation {
  id: number;
  conversation_code: string;
  type: 'direct' | 'group' | 'executive_session';
  title?: string | null;
  classification: CollaborationClassification;
  is_active: number;
  last_message_at?: string | null;
  participant_count?: number;
  participants?: ConversationParticipant[];
  created_at: string;
  updated_at: string;
}

export interface ConversationParticipant {
  id: number;
  conversation_id: number;
  participant_type: 'human' | 'agent';
  user_id?: number | null;
  agent_id?: number | null;
  user_name?: string | null;
  agent_name?: string | null;
  agent_code?: string | null;
  role: 'owner' | 'admin' | 'member';
  last_read_message_id?: number | null;
  is_muted: number;
  joined_at: string;
}

export interface MessageReference {
  id?: number;
  message_id?: number;
  reference_type: 'task' | 'document' | 'knowledge_record' | 'wallbook_handover' | 'owner_direction' | 'executive_decision' | 'meeting';
  reference_id: number;
  reference_code?: string | null;
  reference_title?: string | null;
  created_at?: string;
}

export interface Message {
  id: number;
  message_code: string;
  channel_id?: number | null;
  conversation_id?: number | null;
  parent_message_id?: number | null;
  sender_type: 'human' | 'agent' | 'system';
  sender_user_id?: number | null;
  sender_agent_id?: number | null;
  sender_display_name: string;
  sender_user_email?: string | null;
  sender_agent_code?: string | null;
  content: string;
  content_format: 'text' | 'markdown' | 'system_event';
  classification: CollaborationClassification;
  is_pinned: number;
  is_edited: number;
  edited_at?: string | null;
  is_deleted: number;
  created_at: string;
  references?: MessageReference[];
}

export interface Announcement {
  id: number;
  announcement_code: string;
  title: string;
  content: string;
  target_scope: 'all_organization' | 'division' | 'unit' | 'project_team';
  target_unit_id?: number | null;
  priority: 'routine' | 'important' | 'urgent' | 'critical';
  classification: CollaborationClassification;
  published_by_user_id?: number | null;
  published_by_name: string;
  is_published: number;
  published_at?: string | null;
  expires_at?: string | null;
  created_at: string;
  updated_at: string;
}

export interface MeetingAgenda {
  id: number;
  meeting_id: number;
  item_order: number;
  title: string;
  description?: string | null;
  allocated_minutes: number;
  presenter_name?: string | null;
  status: 'pending' | 'in_discussion' | 'concluded' | 'tabled';
}

export interface MeetingMinutes {
  id?: number;
  meeting_id: number;
  prepared_by_user_id?: number | null;
  prepared_by_agent_id?: number | null;
  approved_by_user_id?: number | null;
  attendance_summary?: string | null;
  discussion_notes: string;
  formal_decisions?: string | null;
  is_approved: number;
  approved_at?: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface MeetingActionItem {
  id: number;
  meeting_id: number;
  meeting_code?: string;
  meeting_title?: string;
  agenda_item_id?: number | null;
  task_id?: number | null;
  linked_task_code?: string | null;
  linked_task_title?: string | null;
  linked_task_status?: string | null;
  description: string;
  assignee_type: 'human' | 'agent';
  assignee_user_id?: number | null;
  assignee_agent_id?: number | null;
  assignee_user_name?: string | null;
  assignee_agent_name?: string | null;
  assignee_agent_code?: string | null;
  due_date?: string | null;
  status: ActionItemStatus;
  created_at: string;
  updated_at: string;
}

export interface MeetingParticipant {
  id: number;
  meeting_id: number;
  participant_type: 'human' | 'agent';
  user_id?: number | null;
  agent_id?: number | null;
  user_name?: string | null;
  user_email?: string | null;
  agent_name?: string | null;
  agent_code?: string | null;
  role: ParticipantRole;
  invitation_status: InvitationStatus;
  attendance_status: AttendanceStatus;
  notes?: string | null;
}

export interface Meeting {
  id: number;
  meeting_code: string;
  title: string;
  category: MeetingCategory;
  status: MeetingStatus;
  classification: CollaborationClassification;
  room_id?: number | null;
  room_code?: string | null;
  room_name?: string | null;
  room_category?: string | null;
  organizer_user_id?: number | null;
  organizer_agent_id?: number | null;
  scheduled_start_time: string;
  scheduled_end_time: string;
  actual_start_time?: string | null;
  actual_end_time?: string | null;
  description?: string | null;
  participant_count?: number;
  agenda_count?: number;
  created_at: string;
  updated_at: string;
  participants?: MeetingParticipant[];
  agendas?: MeetingAgenda[];
  minutes?: MeetingMinutes | null;
  action_items?: MeetingActionItem[];
}

export interface OwnerDirective {
  id: number;
  direction_code: string;
  direction_type: DirectiveType;
  subject: string;
  description: string;
  target_body_id?: number | null;
  target_unit_id?: number | null;
  issued_by_id: number;
  issued_by_name: string;
  requires_acknowledgment: number;
  acknowledged_at?: string | null;
  acknowledged_by?: string | null;
  due_date?: string | null;
  status: DirectiveStatus;
  ack_id?: number | null;
  ack_status?: DirectiveAckStatus | null;
  ack_notes?: string | null;
  ack_time?: string | null;
  acknowledging_agent_id?: number | null;
  acknowledging_user_id?: number | null;
  acknowledging_agent?: { id: number; name: string; agent_code: string } | null;
  created_at: string;
  updated_at: string;
}

export interface ExecutiveDecision {
  id: number;
  decision_code: string;
  governance_body_id: number;
  governance_body_name?: string | null;
  direction_id?: number | null;
  direction_code?: string | null;
  direction_subject?: string | null;
  title: string;
  summary: string;
  decision_category: string;
  decision_level: 'A' | 'B' | 'C' | 'D';
  decided_by: string;
  effective_date: string;
  status: 'draft' | 'proposed' | 'deliberation' | 'ratified' | 'rejected' | 'superseded';
  notes?: string | null;
  created_at: string;
  updated_at: string;
}

export interface ExecutiveOverview {
  metrics: {
    total_directives: number;
    active_directives: number;
    total_decisions: number;
    ratified_decisions: number;
    pending_acknowledgments: number;
    active_board_meetings: number;
  };
  recent_directives: OwnerDirective[];
  recent_decisions: ExecutiveDecision[];
  doa_profiles: Array<{
    id: number;
    code: string;
    name: string;
    authority_level: string;
    domain: string;
    financial_limit: number;
    permitted_actions: string;
  }>;
}

export interface CollaborationNotification {
  id: number;
  recipient_type: 'human' | 'agent';
  user_id?: number | null;
  agent_id?: number | null;
  title: string;
  message: string;
  type: 'message' | 'mention' | 'meeting_invite' | 'meeting_reminder' | 'decision_published' | 'directive_issued' | 'approval_requested' | 'action_item_assigned' | 'announcement' | 'alert';
  priority: 'low' | 'medium' | 'high' | 'urgent';
  resource_uri?: string | null;
  is_read: number;
  read_at?: string | null;
  created_at: string;
}

export interface CollaborationSearchResult {
  query: string;
  total_results: number;
  channels: Channel[];
  messages: Message[];
  meetings: Meeting[];
  announcements: Announcement[];
  action_items: MeetingActionItem[];
}
