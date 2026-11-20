// Agent Welfare & Welfare Tickets API Client Service (Prompt 24)

import { apiClient } from './apiClient';
import type {
  WelfareOverview,
  WelfarePolicy,
  WelfareCompensationPeriod,
  WelfareTaskBonusEvaluation,
  WelfareSpecialRecognition,
  WelfareTicketTransaction,
  WelfareAgentBalance,
  WelfareFacility,
  WelfareFacilityUsageLog,
} from '../types/welfare';

class WelfareService {
  /**
   * Campus Welfare Overview & Metrics
   */
  public async getOverview(): Promise<WelfareOverview> {
    const res = await apiClient.request<WelfareOverview>('/welfare/overview');
    return res.data;
  }

  /**
   * Welfare Policies
   */
  public async listPolicies(): Promise<WelfarePolicy[]> {
    const res = await apiClient.request<WelfarePolicy[]>('/welfare/policies');
    return res.data;
  }

  public async getActivePolicy(): Promise<WelfarePolicy> {
    const res = await apiClient.request<WelfarePolicy>('/welfare/policies/active');
    return res.data;
  }

  public async createPolicy(payload: Partial<WelfarePolicy>): Promise<WelfarePolicy> {
    const res = await apiClient.request<WelfarePolicy>('/welfare/policies', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async approvePolicy(policyId: number, notes?: string): Promise<WelfarePolicy> {
    const res = await apiClient.request<WelfarePolicy>(`/welfare/policies/${policyId}/approve`, {
      method: 'POST',
      body: JSON.stringify({ notes }),
    });
    return res.data;
  }

  /**
   * Compensation Periods
   */
  public async listPeriods(filters?: { status?: string }): Promise<WelfareCompensationPeriod[]> {
    const params = new URLSearchParams();
    if (filters?.status) params.append('status', filters.status);
    const endpoint = `/welfare/periods${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<WelfareCompensationPeriod[]>(endpoint);
    return res.data;
  }

  public async getPeriodDetail(periodId: number): Promise<WelfareCompensationPeriod> {
    const res = await apiClient.request<WelfareCompensationPeriod>(`/welfare/periods/${periodId}`);
    return res.data;
  }

  public async createPeriod(payload: { period_code?: string; start_date?: string; end_date?: string; notes?: string }): Promise<WelfareCompensationPeriod> {
    const res = await apiClient.request<WelfareCompensationPeriod>('/welfare/periods', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async calculatePeriod(periodId: number): Promise<WelfareCompensationPeriod> {
    const res = await apiClient.request<WelfareCompensationPeriod>(`/welfare/periods/${periodId}/calculate`, {
      method: 'POST',
    });
    return res.data;
  }

  public async approvePeriod(periodId: number): Promise<WelfareCompensationPeriod> {
    const res = await apiClient.request<WelfareCompensationPeriod>(`/welfare/periods/${periodId}/approve`, {
      method: 'POST',
    });
    return res.data;
  }

  public async distributePeriod(periodId: number): Promise<WelfareCompensationPeriod> {
    const res = await apiClient.request<WelfareCompensationPeriod>(`/welfare/periods/${periodId}/distribute`, {
      method: 'POST',
    });
    return res.data;
  }

  /**
   * Task Bonuses
   */
  public async listTaskBonuses(filters?: { agent_id?: number; approval_status?: string }): Promise<WelfareTaskBonusEvaluation[]> {
    const params = new URLSearchParams();
    if (filters?.agent_id) params.append('agent_id', String(filters.agent_id));
    if (filters?.approval_status) params.append('approval_status', filters.approval_status);
    const endpoint = `/welfare/task-bonuses${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<WelfareTaskBonusEvaluation[]>(endpoint);
    return res.data;
  }

  public async evaluateTaskBonus(taskId: number, payload?: { bonus_category?: string; verification_notes?: string }): Promise<WelfareTaskBonusEvaluation> {
    const res = await apiClient.request<WelfareTaskBonusEvaluation>(`/welfare/tasks/${taskId}/evaluate-bonus`, {
      method: 'POST',
      body: JSON.stringify(payload ?? {}),
    });
    return res.data;
  }

  public async approveTaskBonus(evaluationId: number, amount?: number): Promise<WelfareTaskBonusEvaluation> {
    const res = await apiClient.request<WelfareTaskBonusEvaluation>(`/welfare/task-bonuses/${evaluationId}/approve`, {
      method: 'POST',
      body: JSON.stringify({ amount }),
    });
    return res.data;
  }

  /**
   * Special Recognitions
   */
  public async listRecognitions(filters?: { agent_id?: number; approval_status?: string }): Promise<WelfareSpecialRecognition[]> {
    const params = new URLSearchParams();
    if (filters?.agent_id) params.append('agent_id', String(filters.agent_id));
    if (filters?.approval_status) params.append('approval_status', filters.approval_status);
    const endpoint = `/welfare/recognitions${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<WelfareSpecialRecognition[]>(endpoint);
    return res.data;
  }

  public async nominateRecognition(payload: {
    agent_id: number;
    title: string;
    category?: string;
    justification: string;
    awarded_tickets?: number;
    evidence_ref?: string;
  }): Promise<WelfareSpecialRecognition> {
    const res = await apiClient.request<WelfareSpecialRecognition>('/welfare/recognitions', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async approveRecognition(recognitionId: number, amount?: number): Promise<WelfareSpecialRecognition> {
    const res = await apiClient.request<WelfareSpecialRecognition>(`/welfare/recognitions/${recognitionId}/approve`, {
      method: 'POST',
      body: JSON.stringify({ amount }),
    });
    return res.data;
  }

  /**
   * Ledger & Balances
   */
  public async listTransactions(filters?: { agent_id?: number; ticket_category?: string; limit?: number; offset?: number }): Promise<{
    items: WelfareTicketTransaction[];
    total: number;
    limit: number;
    offset: number;
  }> {
    const params = new URLSearchParams();
    if (filters?.agent_id) params.append('agent_id', String(filters.agent_id));
    if (filters?.ticket_category) params.append('ticket_category', filters.ticket_category);
    if (filters?.limit) params.append('limit', String(filters.limit));
    if (filters?.offset) params.append('offset', String(filters.offset));
    const endpoint = `/welfare/ledger${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<{
      items: WelfareTicketTransaction[];
      total: number;
      limit: number;
      offset: number;
    }>(endpoint);
    return res.data;
  }

  public async getAgentBalance(agentId: number): Promise<WelfareAgentBalance> {
    const res = await apiClient.request<WelfareAgentBalance>(`/welfare/agents/${agentId}/balance`);
    return res.data;
  }

  public async reconcileAgentBalance(agentId: number): Promise<WelfareAgentBalance> {
    const res = await apiClient.request<WelfareAgentBalance>(`/welfare/agents/${agentId}/reconcile`, {
      method: 'POST',
    });
    return res.data;
  }

  public async postAdjustment(payload: { agent_id: number; amount: number; direction?: 'credit' | 'debit'; reason: string }): Promise<WelfareTicketTransaction> {
    const res = await apiClient.request<WelfareTicketTransaction>('/welfare/adjustments', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async reverseTransaction(transactionId: number, reason: string): Promise<WelfareTicketTransaction> {
    const res = await apiClient.request<WelfareTicketTransaction>(`/welfare/transactions/${transactionId}/reverse`, {
      method: 'POST',
      body: JSON.stringify({ reason }),
    });
    return res.data;
  }

  /**
   * Facilities
   */
  public async listFacilities(): Promise<WelfareFacility[]> {
    const res = await apiClient.request<WelfareFacility[]>('/welfare/facilities');
    return res.data;
  }

  public async checkInFacility(facilityId: number, payload: { agent_id: number; session_type?: string; workstation_id?: number; notes?: string }): Promise<WelfareFacilityUsageLog> {
    const res = await apiClient.request<WelfareFacilityUsageLog>(`/welfare/facilities/${facilityId}/check-in`, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async checkOutFacility(sessionId: number): Promise<WelfareFacilityUsageLog> {
    const res = await apiClient.request<WelfareFacilityUsageLog>(`/welfare/facility-sessions/${sessionId}/check-out`, {
      method: 'POST',
    });
    return res.data;
  }

  public async listFacilityLogs(filters?: { facility_id?: number; agent_id?: number }): Promise<WelfareFacilityUsageLog[]> {
    const params = new URLSearchParams();
    if (filters?.facility_id) params.append('facility_id', String(filters.facility_id));
    if (filters?.agent_id) params.append('agent_id', String(filters.agent_id));
    const endpoint = `/welfare/facility-logs${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<WelfareFacilityUsageLog[]>(endpoint);
    return res.data;
  }
}

export const welfareService = new WelfareService();
