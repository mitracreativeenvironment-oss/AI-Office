import { apiClient } from './apiClient';
import type {
  AiAgent,
  AgentProfile,
  AgentAssignment,
  AgentResponsibility,
  Skill,
  AgentSkill,
  RuntimeMapping,
  PerformanceHistoryRecord,
  LifecycleEvent,
  WorkforceMetrics,
} from '../types/workforce';

export const workforceService = {
  // 1. Workforce Metrics
  async getMetrics(): Promise<WorkforceMetrics> {
    const res = await apiClient.request<WorkforceMetrics>('/workforce/metrics');
    return res.data;
  },

  // 2. Agent Registry Management
  async listAgents(filters: Record<string, any> = {}, limit = 20, offset = 0): Promise<{ items: AiAgent[]; total: number }> {
    const params = new URLSearchParams();
    if (filters.lifecycle_state) params.append('lifecycle_state', filters.lifecycle_state);
    if (filters.operational_status) params.append('operational_status', filters.operational_status);
    if (filters.classification) params.append('classification', filters.classification);
    if (filters.unit_id) params.append('unit_id', String(filters.unit_id));
    if (filters.search) params.append('search', filters.search);
    params.append('limit', String(limit));
    params.append('offset', String(offset));

    const res = await apiClient.request<AiAgent[]>(`/workforce/agents?${params.toString()}`);
    return {
      items: res.data,
      total: (res.meta?.total as number) ?? res.data.length,
    };
  },

  async getAgent(id: number): Promise<AiAgent> {
    const res = await apiClient.request<AiAgent>(`/workforce/agents/${id}`);
    return res.data;
  },

  async createAgent(data: Partial<AiAgent>): Promise<AiAgent> {
    const res = await apiClient.request<AiAgent>('/workforce/agents', {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return res.data;
  },

  async updateAgent(id: number, data: Partial<AiAgent>): Promise<AiAgent> {
    const res = await apiClient.request<AiAgent>(`/workforce/agents/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
    return res.data;
  },

  async updateProfile(id: number, data: Partial<AgentProfile>): Promise<AgentProfile> {
    const res = await apiClient.request<AgentProfile>(`/workforce/agents/${id}/profile`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
    return res.data;
  },

  // 3. Lifecycle State Transitions
  async transitionState(id: number, toState: string, reason?: string, approvalRef?: string): Promise<AiAgent> {
    const res = await apiClient.request<AiAgent>(`/workforce/agents/${id}/transition`, {
      method: 'POST',
      body: JSON.stringify({
        to_state: toState,
        reason,
        approval_ref: approvalRef,
      }),
    });
    return res.data;
  },

  async getLifecycleHistory(id: number): Promise<LifecycleEvent[]> {
    const res = await apiClient.request<LifecycleEvent[]>(`/workforce/agents/${id}/lifecycle`);
    return res.data;
  },

  // 4. Organizational Placement & Historical Assignments
  async getAssignments(id: number): Promise<AgentAssignment[]> {
    const res = await apiClient.request<AgentAssignment[]>(`/workforce/agents/${id}/assignments`);
    return res.data;
  },

  async assignAgent(id: number, unitId: number, positionId?: number, type = 'primary', reason?: string, approvalRef?: string): Promise<AgentAssignment> {
    const res = await apiClient.request<AgentAssignment>(`/workforce/agents/${id}/assignments`, {
      method: 'POST',
      body: JSON.stringify({
        unit_id: unitId,
        position_id: positionId,
        assignment_type: type,
        reason,
        approval_ref: approvalRef,
      }),
    });
    return res.data;
  },

  async endAssignment(id: number, reason: string): Promise<AgentAssignment> {
    const res = await apiClient.request<AgentAssignment>(`/workforce/assignments/${id}/end`, {
      method: 'POST',
      body: JSON.stringify({ reason }),
    });
    return res.data;
  },

  // 5. Skills & Competencies Catalogue
  async getSkillCatalogue(category?: string): Promise<Skill[]> {
    const query = category ? `?category=${encodeURIComponent(category)}` : '';
    const res = await apiClient.request<Skill[]>(`/workforce/skills${query}`);
    return res.data;
  },

  async getAgentSkills(id: number): Promise<AgentSkill[]> {
    const res = await apiClient.request<AgentSkill[]>(`/workforce/agents/${id}/skills`);
    return res.data;
  },

  async assignSkill(id: number, skillId: number, proficiency = 'competent', evidence?: string): Promise<AgentSkill> {
    const res = await apiClient.request<AgentSkill>(`/workforce/agents/${id}/skills`, {
      method: 'POST',
      body: JSON.stringify({
        skill_id: skillId,
        proficiency_level: proficiency,
        evidence_reference: evidence,
      }),
    });
    return res.data;
  },

  async verifySkill(agentSkillId: number, status = 'verified', verifiedBy?: string): Promise<AgentSkill> {
    const res = await apiClient.request<AgentSkill>(`/workforce/skills/${agentSkillId}/verify`, {
      method: 'POST',
      body: JSON.stringify({
        verification_status: status,
        verified_by: verifiedBy,
      }),
    });
    return res.data;
  },

  // 6. Structured Responsibilities
  async getResponsibilities(id: number): Promise<AgentResponsibility[]> {
    const res = await apiClient.request<AgentResponsibility[]>(`/workforce/agents/${id}/responsibilities`);
    return res.data;
  },

  async addResponsibility(id: number, data: Partial<AgentResponsibility>): Promise<AgentResponsibility> {
    const res = await apiClient.request<AgentResponsibility>(`/workforce/agents/${id}/responsibilities`, {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return res.data;
  },

  // 7. Individual Agent Performance History
  async getPerformanceHistory(id: number, category?: string, limit = 50, offset = 0): Promise<{ records: PerformanceHistoryRecord[]; total: number }> {
    const params = new URLSearchParams();
    if (category) params.append('category', category);
    params.append('limit', String(limit));
    params.append('offset', String(offset));

    const res = await apiClient.request<PerformanceHistoryRecord[]>(`/workforce/agents/${id}/performance-history?${params.toString()}`);
    return {
      records: res.data,
      total: (res.meta?.total as number) ?? res.data.length,
    };
  },

  async recordPerformance(id: number, data: Partial<PerformanceHistoryRecord>): Promise<PerformanceHistoryRecord> {
    const res = await apiClient.request<PerformanceHistoryRecord>(`/workforce/agents/${id}/performance-history`, {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return res.data;
  },

  async verifyPerformanceEvidence(id: number, status: string, evaluator: string, notes?: string): Promise<PerformanceHistoryRecord> {
    const res = await apiClient.request<PerformanceHistoryRecord>(`/workforce/performance-history/${id}/verify`, {
      method: 'POST',
      body: JSON.stringify({
        evidence_status: status,
        evaluator_identity: evaluator,
        evaluation_notes: notes,
      }),
    });
    return res.data;
  },

  // 8. Runtime Identity Mappings
  async getRuntimeMappings(id: number): Promise<RuntimeMapping[]> {
    const res = await apiClient.request<RuntimeMapping[]>(`/workforce/agents/${id}/runtime-mappings`);
    return res.data;
  },

  async setRuntimeMapping(id: number, data: Partial<RuntimeMapping>): Promise<RuntimeMapping> {
    const res = await apiClient.request<RuntimeMapping>(`/workforce/agents/${id}/runtime-mappings`, {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return res.data;
  },
};
