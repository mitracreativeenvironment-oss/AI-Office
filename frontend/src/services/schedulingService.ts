import { apiClient } from './apiClient';
import type {
  SchedulePolicy,
  WorkforceShift,
  WorkforceRoster,
  CoverageRequirement,
  CoverageEvaluationResult,
  ReplacementProposal,
  ReplacementCandidate,
  WallbookHandover,
  WallbookHandoverItem,
  DutyAssignment,
} from '../types/scheduling';

export const schedulingService = {
  // Policies & Shifts
  async listPolicies(): Promise<SchedulePolicy[]> {
    const res = await apiClient.request<SchedulePolicy[]>('/workforce/scheduling/policies');
    return res.data;
  },

  async listShifts(): Promise<WorkforceShift[]> {
    const res = await apiClient.request<WorkforceShift[]>('/workforce/scheduling/shifts');
    return res.data;
  },

  // Rosters
  async listRosters(status?: string): Promise<{ items: WorkforceRoster[]; total: number }> {
    const url = status ? `/workforce/scheduling/rosters?status=${status}` : '/workforce/scheduling/rosters';
    const res = await apiClient.request<WorkforceRoster[]>(url);
    return {
      items: res.data,
      total: (res.meta?.total as number) ?? res.data.length,
    };
  },

  async getRoster(id: number): Promise<WorkforceRoster> {
    const res = await apiClient.request<WorkforceRoster>(`/workforce/scheduling/rosters/${id}`);
    return res.data;
  },

  async publishRoster(id: number): Promise<WorkforceRoster> {
    const res = await apiClient.request<WorkforceRoster>(`/workforce/scheduling/rosters/${id}/publish`, {
      method: 'POST',
    });
    return res.data;
  },

  async completeDutyAssignment(id: number): Promise<DutyAssignment> {
    const res = await apiClient.request<DutyAssignment>(`/workforce/scheduling/duty-assignments/${id}/complete`, {
      method: 'POST',
    });
    return res.data;
  },

  // Coverage
  async listCoverageRequirements(unitId?: number): Promise<CoverageRequirement[]> {
    const url = unitId
      ? `/workforce/scheduling/coverage/requirements?unit_id=${unitId}`
      : '/workforce/scheduling/coverage/requirements';
    const res = await apiClient.request<CoverageRequirement[]>(url);
    return res.data;
  },

  async evaluateCoverage(date?: string, unitId?: number): Promise<CoverageEvaluationResult> {
    const params = new URLSearchParams();
    if (date) params.append('date', date);
    if (unitId) params.append('unit_id', String(unitId));
    const res = await apiClient.request<CoverageEvaluationResult>(`/workforce/scheduling/coverage/status?${params.toString()}`);
    return res.data;
  },

  // Replacements
  async listReplacements(status?: string): Promise<ReplacementProposal[]> {
    const url = status ? `/workforce/replacements?status=${status}` : '/workforce/replacements';
    const res = await apiClient.request<ReplacementProposal[]>(url);
    return res.data;
  },

  async getReplacement(id: number): Promise<ReplacementProposal> {
    const res = await apiClient.request<ReplacementProposal>(`/workforce/replacements/${id}`);
    return res.data;
  },

  async findCandidates(dutyAssignmentId: number): Promise<{ candidates: ReplacementCandidate[] }> {
    const res = await apiClient.request<{ candidates: ReplacementCandidate[] }>(
      `/workforce/replacements/candidates?duty_assignment_id=${dutyAssignmentId}`
    );
    return res.data;
  },

  async createProposal(data: Partial<ReplacementProposal>): Promise<ReplacementProposal> {
    const res = await apiClient.request<ReplacementProposal>('/workforce/replacements', {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return res.data;
  },

  async acceptProposal(id: number, identity: string): Promise<ReplacementProposal> {
    const res = await apiClient.request<ReplacementProposal>(`/workforce/replacements/${id}/accept`, {
      method: 'POST',
      body: JSON.stringify({ agent_identity: identity }),
    });
    return res.data;
  },

  async approveProposal(id: number): Promise<ReplacementProposal> {
    const res = await apiClient.request<ReplacementProposal>(`/workforce/replacements/${id}/approve`, {
      method: 'POST',
    });
    return res.data;
  },

  async activateReplacement(id: number): Promise<ReplacementProposal> {
    const res = await apiClient.request<ReplacementProposal>(`/workforce/replacements/${id}/activate`, {
      method: 'POST',
    });
    return res.data;
  },

  // Wallbook
  async listHandovers(status?: string): Promise<WallbookHandover[]> {
    const url = status ? `/wallbook/handovers?status=${status}` : '/wallbook/handovers';
    const res = await apiClient.request<WallbookHandover[]>(url);
    return res.data;
  },

  async getHandover(id: number): Promise<WallbookHandover> {
    const res = await apiClient.request<WallbookHandover>(`/wallbook/handovers/${id}`);
    return res.data;
  },

  async createHandover(data: Partial<WallbookHandover>): Promise<WallbookHandover> {
    const res = await apiClient.request<WallbookHandover>('/wallbook/handovers', {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return res.data;
  },

  async addHandoverItem(handoverId: number, item: Partial<WallbookHandoverItem>): Promise<WallbookHandoverItem> {
    const res = await apiClient.request<WallbookHandoverItem>(`/wallbook/handovers/${handoverId}/items`, {
      method: 'POST',
      body: JSON.stringify(item),
    });
    return res.data;
  },

  async submitHandover(id: number): Promise<WallbookHandover> {
    const res = await apiClient.request<WallbookHandover>(`/wallbook/handovers/${id}/submit`, {
      method: 'POST',
    });
    return res.data;
  },

  async acknowledgeHandover(id: number, incomingAgentId: number, comments?: string): Promise<WallbookHandover> {
    const res = await apiClient.request<WallbookHandover>(`/wallbook/handovers/${id}/acknowledge`, {
      method: 'POST',
      body: JSON.stringify({ incoming_agent_id: incomingAgentId, comments }),
    });
    return res.data;
  },

  async acceptHandover(id: number, incomingAgentId: number, comments?: string): Promise<WallbookHandover> {
    const res = await apiClient.request<WallbookHandover>(`/wallbook/handovers/${id}/accept`, {
      method: 'POST',
      body: JSON.stringify({ incoming_agent_id: incomingAgentId, comments }),
    });
    return res.data;
  },

  async escalateOverdue(): Promise<{ escalated_count: number; escalated_codes: string[] }> {
    const res = await apiClient.request<{ escalated_count: number; escalated_codes: string[] }>(
      '/wallbook/handovers/escalate-overdue',
      { method: 'POST' }
    );
    return res.data;
  },
};
