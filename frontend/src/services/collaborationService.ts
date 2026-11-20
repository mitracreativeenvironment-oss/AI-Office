// Internal Communication, Executive Coordination & Meetings API Client Service (Prompt 26)

import { apiClient } from './apiClient';
import type {
  ExecutiveOverview,
  OwnerDirective,
  ExecutiveDecision,
  Channel,
  Conversation,
  Message,
  Announcement,
  Meeting,
  MeetingActionItem,
  MeetingMinutes,
  MeetingParticipant,
  MeetingAgenda,
  CollaborationNotification,
  CollaborationSearchResult,
} from '../types/collaboration';

class CollaborationService {
  // ==========================================
  // 1. Executive Coordination
  // ==========================================

  public async getExecutiveOverview(): Promise<ExecutiveOverview> {
    const res = await apiClient.request<ExecutiveOverview>('/collaboration/executive/overview');
    return res.data;
  }

  public async listDirectives(filters?: { direction_type?: string; status?: string; search?: string }): Promise<OwnerDirective[]> {
    const params = new URLSearchParams();
    if (filters?.direction_type) params.append('direction_type', filters.direction_type);
    if (filters?.status) params.append('status', filters.status);
    if (filters?.search) params.append('search', filters.search);
    const endpoint = `/collaboration/executive/directives${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<OwnerDirective[]>(endpoint);
    return res.data;
  }

  public async getDirective(id: number): Promise<OwnerDirective> {
    const res = await apiClient.request<OwnerDirective>(`/collaboration/executive/directives/${id}`);
    return res.data;
  }

  public async createDirective(payload: Partial<OwnerDirective>): Promise<OwnerDirective> {
    const res = await apiClient.request<OwnerDirective>('/collaboration/executive/directives', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async acknowledgeDirective(id: number, payload: { status: string; notes?: string; actor_type?: string; agent_id?: number }): Promise<any> {
    const res = await apiClient.request<any>(`/collaboration/executive/directives/${id}/acknowledge`, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async listDecisions(filters?: { status?: string; decision_level?: string; search?: string }): Promise<ExecutiveDecision[]> {
    const params = new URLSearchParams();
    if (filters?.status) params.append('status', filters.status);
    if (filters?.decision_level) params.append('decision_level', filters.decision_level);
    if (filters?.search) params.append('search', filters.search);
    const endpoint = `/collaboration/executive/decisions${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<ExecutiveDecision[]>(endpoint);
    return res.data;
  }

  public async createDecisionProposal(payload: Partial<ExecutiveDecision>): Promise<ExecutiveDecision> {
    const res = await apiClient.request<ExecutiveDecision>('/collaboration/executive/decisions', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  // ==========================================
  // 2. Channels
  // ==========================================

  public async listChannels(filters?: { domain?: string; is_private?: number; search?: string }): Promise<Channel[]> {
    const params = new URLSearchParams();
    if (filters?.domain) params.append('domain', filters.domain);
    if (filters?.is_private !== undefined) params.append('is_private', String(filters.is_private));
    if (filters?.search) params.append('search', filters.search);
    const endpoint = `/collaboration/channels${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<Channel[]>(endpoint);
    return res.data;
  }

  public async getChannel(id: number): Promise<Channel> {
    const res = await apiClient.request<Channel>(`/collaboration/channels/${id}`);
    return res.data;
  }

  public async createChannel(payload: Partial<Channel>): Promise<Channel> {
    const res = await apiClient.request<Channel>('/collaboration/channels', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async joinChannel(id: number, memberType = 'human', agentId?: number, role = 'member'): Promise<any> {
    const res = await apiClient.request<any>(`/collaboration/channels/${id}/join`, {
      method: 'POST',
      body: JSON.stringify({ member_type: memberType, agent_id: agentId, role }),
    });
    return res.data;
  }

  public async leaveChannel(id: number, memberType = 'human', agentId?: number): Promise<any> {
    const res = await apiClient.request<any>(`/collaboration/channels/${id}/leave`, {
      method: 'POST',
      body: JSON.stringify({ member_type: memberType, agent_id: agentId }),
    });
    return res.data;
  }

  public async getChannelMessages(channelId: number, filters?: { parent_message_id?: string; limit?: number }): Promise<Message[]> {
    const params = new URLSearchParams();
    if (filters?.parent_message_id) params.append('parent_message_id', filters.parent_message_id);
    if (filters?.limit) params.append('limit', String(filters.limit));
    const endpoint = `/collaboration/channels/${channelId}/messages${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<Message[]>(endpoint);
    return res.data;
  }

  public async sendChannelMessage(channelId: number, payload: Partial<Message>): Promise<Message> {
    const res = await apiClient.request<Message>(`/collaboration/channels/${channelId}/messages`, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  // ==========================================
  // 3. Conversations
  // ==========================================

  public async listConversations(): Promise<Conversation[]> {
    const res = await apiClient.request<Conversation[]>('/collaboration/conversations');
    return res.data;
  }

  public async getConversation(id: number): Promise<Conversation> {
    const res = await apiClient.request<Conversation>(`/collaboration/conversations/${id}`);
    return res.data;
  }

  public async createConversation(payload: Partial<Conversation> & { participants?: any[] }): Promise<Conversation> {
    const res = await apiClient.request<Conversation>('/collaboration/conversations', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async getConversationMessages(conversationId: number, filters?: { parent_message_id?: string; limit?: number }): Promise<Message[]> {
    const params = new URLSearchParams();
    if (filters?.parent_message_id) params.append('parent_message_id', filters.parent_message_id);
    if (filters?.limit) params.append('limit', String(filters.limit));
    const endpoint = `/collaboration/conversations/${conversationId}/messages${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<Message[]>(endpoint);
    return res.data;
  }

  public async sendConversationMessage(conversationId: number, payload: Partial<Message>): Promise<Message> {
    const res = await apiClient.request<Message>(`/collaboration/conversations/${conversationId}/messages`, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async pinMessage(id: number, isPinned: boolean): Promise<any> {
    const res = await apiClient.request<any>(`/collaboration/messages/${id}/pin`, {
      method: 'POST',
      body: JSON.stringify({ is_pinned: isPinned }),
    });
    return res.data;
  }

  public async markMessagesRead(targetType: 'channel' | 'conversation', targetId: number, lastMessageId: number): Promise<any> {
    const res = await apiClient.request<any>('/collaboration/messages/read', {
      method: 'POST',
      body: JSON.stringify({ target_type: targetType, target_id: targetId, last_message_id: lastMessageId }),
    });
    return res.data;
  }

  // ==========================================
  // 4. Announcements
  // ==========================================

  public async listAnnouncements(filters?: { target_scope?: string; priority?: string; search?: string }): Promise<Announcement[]> {
    const params = new URLSearchParams();
    if (filters?.target_scope) params.append('target_scope', filters.target_scope);
    if (filters?.priority) params.append('priority', filters.priority);
    if (filters?.search) params.append('search', filters.search);
    const endpoint = `/collaboration/announcements${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<Announcement[]>(endpoint);
    return res.data;
  }

  public async getAnnouncement(id: number): Promise<Announcement> {
    const res = await apiClient.request<Announcement>(`/collaboration/announcements/${id}`);
    return res.data;
  }

  public async createAnnouncement(payload: Partial<Announcement>): Promise<Announcement> {
    const res = await apiClient.request<Announcement>('/collaboration/announcements', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async updateAnnouncement(id: number, payload: Partial<Announcement>): Promise<Announcement> {
    const res = await apiClient.request<Announcement>(`/collaboration/announcements/${id}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async deleteAnnouncement(id: number): Promise<any> {
    const res = await apiClient.request<any>(`/collaboration/announcements/${id}`, {
      method: 'DELETE',
    });
    return res.data;
  }

  // ==========================================
  // 5. Meetings & Governance
  // ==========================================

  public async listMeetings(filters?: { status?: string; category?: string; room_id?: number; search?: string }): Promise<Meeting[]> {
    const params = new URLSearchParams();
    if (filters?.status) params.append('status', filters.status);
    if (filters?.category) params.append('category', filters.category);
    if (filters?.room_id) params.append('room_id', String(filters.room_id));
    if (filters?.search) params.append('search', filters.search);
    const endpoint = `/collaboration/meetings${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<Meeting[]>(endpoint);
    return res.data;
  }

  public async getMeeting(id: number): Promise<Meeting> {
    const res = await apiClient.request<Meeting>(`/collaboration/meetings/${id}`);
    return res.data;
  }

  public async createMeeting(payload: Partial<Meeting>): Promise<Meeting> {
    const res = await apiClient.request<Meeting>('/collaboration/meetings', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async updateMeetingStatus(id: number, status: string): Promise<Meeting> {
    const res = await apiClient.request<Meeting>(`/collaboration/meetings/${id}/status`, {
      method: 'PATCH',
      body: JSON.stringify({ status }),
    });
    return res.data;
  }

  public async addParticipant(meetingId: number, payload: Partial<MeetingParticipant>): Promise<MeetingParticipant> {
    const res = await apiClient.request<MeetingParticipant>(`/collaboration/meetings/${meetingId}/participants`, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async updateParticipantAttendance(meetingId: number, participantId: number, status: string, notes?: string): Promise<any> {
    const res = await apiClient.request<any>(`/collaboration/meetings/${meetingId}/participants/${participantId}`, {
      method: 'PATCH',
      body: JSON.stringify({ attendance_status: status, notes }),
    });
    return res.data;
  }

  public async addAgendaItem(meetingId: number, payload: Partial<MeetingAgenda>): Promise<MeetingAgenda> {
    const res = await apiClient.request<MeetingAgenda>(`/collaboration/meetings/${meetingId}/agendas`, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async getMeetingMinutes(meetingId: number): Promise<MeetingMinutes | null> {
    const res = await apiClient.request<MeetingMinutes | null>(`/collaboration/meetings/${meetingId}/minutes`);
    return res.data;
  }

  public async saveMeetingMinutes(meetingId: number, payload: Partial<MeetingMinutes>): Promise<MeetingMinutes> {
    const res = await apiClient.request<MeetingMinutes>(`/collaboration/meetings/${meetingId}/minutes`, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async approveMeetingMinutes(meetingId: number): Promise<any> {
    const res = await apiClient.request<any>(`/collaboration/meetings/${meetingId}/minutes/approve`, {
      method: 'POST',
    });
    return res.data;
  }

  public async listActionItems(filters?: { meeting_id?: number; status?: string }): Promise<MeetingActionItem[]> {
    const params = new URLSearchParams();
    if (filters?.meeting_id) params.append('meeting_id', String(filters.meeting_id));
    if (filters?.status) params.append('status', filters.status);
    const endpoint = `/collaboration/action-items${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<MeetingActionItem[]>(endpoint);
    return res.data;
  }

  public async createActionItem(meetingId: number, payload: Partial<MeetingActionItem> & { sync_to_task?: boolean }): Promise<MeetingActionItem> {
    const res = await apiClient.request<MeetingActionItem>(`/collaboration/meetings/${meetingId}/action-items`, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async updateActionItemStatus(id: number, status: string, taskId?: number): Promise<any> {
    const res = await apiClient.request<any>(`/collaboration/action-items/${id}`, {
      method: 'PATCH',
      body: JSON.stringify({ status, task_id: taskId }),
    });
    return res.data;
  }

  // ==========================================
  // 6. Notifications & Search
  // ==========================================

  public async listNotifications(filters?: { is_read?: number; type?: string; limit?: number }): Promise<CollaborationNotification[]> {
    const params = new URLSearchParams();
    if (filters?.is_read !== undefined) params.append('is_read', String(filters.is_read));
    if (filters?.type) params.append('type', filters.type);
    if (filters?.limit) params.append('limit', String(filters.limit));
    const endpoint = `/collaboration/notifications${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<CollaborationNotification[]>(endpoint);
    return res.data;
  }

  public async getUnreadCount(): Promise<number> {
    const res = await apiClient.request<{ unread_count: number }>('/collaboration/notifications/unread-count');
    return res.data.unread_count;
  }

  public async markNotificationRead(id: number): Promise<any> {
    const res = await apiClient.request<any>(`/collaboration/notifications/${id}/read`, {
      method: 'POST',
    });
    return res.data;
  }

  public async markAllNotificationsRead(): Promise<any> {
    const res = await apiClient.request<any>('/collaboration/notifications/read-all', {
      method: 'POST',
    });
    return res.data;
  }

  public async searchCollaboration(query: string): Promise<CollaborationSearchResult> {
    const res = await apiClient.request<CollaborationSearchResult>(`/collaboration/search?q=${encodeURIComponent(query)}`);
    return res.data;
  }
}

export const collaborationService = new CollaborationService();
export default collaborationService;
