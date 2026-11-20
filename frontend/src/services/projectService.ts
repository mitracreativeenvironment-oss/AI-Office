// Project Management, Client Engagement, Project Lifecycle & Delivery Operations Service (Prompt 27)

import { apiClient } from './apiClient';
import type {
  ProjectItem,
  ProjectDetail,
  PortfolioMetrics,
  ProjectClient,
  ProjectClientContact,
  ProjectBrief,
  ProjectScopeBaseline,
  ProjectMilestone,
  ProjectDeliverable,
  ProjectRisk,
  ProjectIssue,
  ProjectChangeRequest,
  ProjectTeamMember,
  ProjectResourceAllocation,
  ProjectBudgetSummary,
} from '../types/project';

class ProjectService {
  // ==========================================
  // 1. Projects & Portfolio Metrics
  // ==========================================

  public async getPortfolioMetrics(): Promise<PortfolioMetrics> {
    const res = await apiClient.request<PortfolioMetrics>('/projects/portfolio-metrics');
    return res.data;
  }

  public async listProjects(filters?: {
    project_type?: string;
    category?: string;
    status?: string;
    lifecycle_stage?: string;
    health_status?: string;
    client_id?: number;
    unit_id?: number;
    search?: string;
  }): Promise<ProjectItem[]> {
    const params = new URLSearchParams();
    if (filters?.project_type) params.append('project_type', filters.project_type);
    if (filters?.category) params.append('category', filters.category);
    if (filters?.status) params.append('status', filters.status);
    if (filters?.lifecycle_stage) params.append('lifecycle_stage', filters.lifecycle_stage);
    if (filters?.health_status) params.append('health_status', filters.health_status);
    if (filters?.client_id) params.append('client_id', String(filters.client_id));
    if (filters?.unit_id) params.append('unit_id', String(filters.unit_id));
    if (filters?.search) params.append('search', filters.search);

    const endpoint = `/projects${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<ProjectItem[]>(endpoint);
    return res.data;
  }

  public async getProject(id: number): Promise<ProjectDetail> {
    const res = await apiClient.request<ProjectDetail>(`/projects/${id}`);
    return res.data;
  }

  public async createProject(payload: Partial<ProjectItem>): Promise<ProjectDetail> {
    const res = await apiClient.request<ProjectDetail>('/projects', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async updateProject(id: number, payload: Partial<ProjectItem>): Promise<ProjectDetail> {
    const res = await apiClient.request<ProjectDetail>(`/projects/${id}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async deleteProject(id: number): Promise<{ id: number }> {
    const res = await apiClient.request<{ id: number }>(`/projects/${id}`, {
      method: 'DELETE',
    });
    return res.data;
  }

  // ==========================================
  // 2. Lifecycle Transitions & History
  // ==========================================

  public async transitionStage(
    id: number,
    targetStage: string,
    reason: string,
    options?: Record<string, unknown>
  ): Promise<ProjectDetail> {
    const res = await apiClient.request<ProjectDetail>(`/projects/${id}/transition`, {
      method: 'POST',
      body: JSON.stringify({ target_stage: targetStage, reason, options }),
    });
    return res.data;
  }

  public async getStatusHistory(id: number): Promise<any[]> {
    const res = await apiClient.request<any[]>(`/projects/${id}/status-history`);
    return res.data;
  }

  // ==========================================
  // 3. Task Integration
  // ==========================================

  public async getLinkedTasks(id: number): Promise<any[]> {
    const res = await apiClient.request<any[]>(`/projects/${id}/tasks`);
    return res.data;
  }

  public async linkTask(id: number, taskId: number, milestoneId?: number): Promise<any> {
    const res = await apiClient.request<any>(`/projects/${id}/tasks/link`, {
      method: 'POST',
      body: JSON.stringify({ task_id: taskId, milestone_id: milestoneId }),
    });
    return res.data;
  }

  public async unlinkTask(id: number, taskId: number): Promise<any> {
    const res = await apiClient.request<any>(`/projects/${id}/tasks/unlink`, {
      method: 'POST',
      body: JSON.stringify({ task_id: taskId }),
    });
    return res.data;
  }

  public async recalculateProgress(id: number): Promise<{ progress_percent: number }> {
    const res = await apiClient.request<{ progress_percent: number }>(`/projects/${id}/recalculate-progress`, {
      method: 'POST',
    });
    return res.data;
  }

  // ==========================================
  // 4. Clients & Contacts
  // ==========================================

  public async listClients(filters?: { status?: string; search?: string }): Promise<ProjectClient[]> {
    const params = new URLSearchParams();
    if (filters?.status) params.append('status', filters.status);
    if (filters?.search) params.append('search', filters.search);
    const endpoint = `/projects/clients${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<ProjectClient[]>(endpoint);
    return res.data;
  }

  public async getClient(id: number): Promise<ProjectClient & { contacts: ProjectClientContact[]; projects: ProjectItem[] }> {
    const res = await apiClient.request<ProjectClient & { contacts: ProjectClientContact[]; projects: ProjectItem[] }>(`/projects/clients/${id}`);
    return res.data;
  }

  public async createClient(payload: Partial<ProjectClient>): Promise<ProjectClient> {
    const res = await apiClient.request<ProjectClient>('/projects/clients', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async updateClient(id: number, payload: Partial<ProjectClient>): Promise<ProjectClient> {
    const res = await apiClient.request<ProjectClient>(`/projects/clients/${id}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async listContacts(clientId: number): Promise<ProjectClientContact[]> {
    const res = await apiClient.request<ProjectClientContact[]>(`/projects/clients/${clientId}/contacts`);
    return res.data;
  }

  public async createContact(clientId: number, payload: Partial<ProjectClientContact>): Promise<ProjectClientContact> {
    const res = await apiClient.request<ProjectClientContact>(`/projects/clients/${clientId}/contacts`, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async deleteContact(contactId: number): Promise<{ id: number }> {
    const res = await apiClient.request<{ id: number }>(`/projects/clients/contacts/${contactId}`, {
      method: 'DELETE',
    });
    return res.data;
  }

  // ==========================================
  // 5. Briefs & Scopes
  // ==========================================

  public async getBrief(projectId: number): Promise<ProjectBrief | null> {
    const res = await apiClient.request<ProjectBrief | null>(`/projects/${projectId}/brief`);
    return res.data;
  }

  public async upsertBrief(projectId: number, payload: Partial<ProjectBrief>): Promise<ProjectBrief> {
    const res = await apiClient.request<ProjectBrief>(`/projects/${projectId}/brief`, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async getScopes(projectId: number): Promise<ProjectScopeBaseline[]> {
    const res = await apiClient.request<ProjectScopeBaseline[]>(`/projects/${projectId}/scopes`);
    return res.data;
  }

  public async createScopeBaseline(projectId: number, payload: Partial<ProjectScopeBaseline>): Promise<ProjectScopeBaseline> {
    const res = await apiClient.request<ProjectScopeBaseline>(`/projects/${projectId}/scopes/baseline`, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  // ==========================================
  // 6. Milestones & Deliverables
  // ==========================================

  public async getMilestones(projectId: number): Promise<ProjectMilestone[]> {
    const res = await apiClient.request<ProjectMilestone[]>(`/projects/${projectId}/milestones`);
    return res.data;
  }

  public async createMilestone(projectId: number, payload: Partial<ProjectMilestone>): Promise<ProjectMilestone> {
    const res = await apiClient.request<ProjectMilestone>(`/projects/${projectId}/milestones`, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async updateMilestone(milestoneId: number, payload: Partial<ProjectMilestone>): Promise<ProjectMilestone> {
    const res = await apiClient.request<ProjectMilestone>(`/projects/milestones/${milestoneId}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async deleteMilestone(milestoneId: number): Promise<{ id: number }> {
    const res = await apiClient.request<{ id: number }>(`/projects/milestones/${milestoneId}`, {
      method: 'DELETE',
    });
    return res.data;
  }

  public async getDeliverables(projectId: number, milestoneId?: number): Promise<ProjectDeliverable[]> {
    const endpoint = milestoneId
      ? `/projects/${projectId}/deliverables?milestone_id=${milestoneId}`
      : `/projects/${projectId}/deliverables`;
    const res = await apiClient.request<ProjectDeliverable[]>(endpoint);
    return res.data;
  }

  public async createDeliverable(projectId: number, payload: Partial<ProjectDeliverable>): Promise<ProjectDeliverable> {
    const res = await apiClient.request<ProjectDeliverable>(`/projects/${projectId}/deliverables`, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async submitDeliverable(deliverableId: number, supportingEvidenceUrl: string): Promise<ProjectDeliverable> {
    const res = await apiClient.request<ProjectDeliverable>(`/projects/deliverables/${deliverableId}/submit`, {
      method: 'POST',
      body: JSON.stringify({ supporting_evidence_url: supportingEvidenceUrl }),
    });
    return res.data;
  }

  public async reviewDeliverable(deliverableId: number, reviewStatus: string, feedback?: string): Promise<ProjectDeliverable> {
    const res = await apiClient.request<ProjectDeliverable>(`/projects/deliverables/${deliverableId}/review`, {
      method: 'POST',
      body: JSON.stringify({ review_status: reviewStatus, feedback }),
    });
    return res.data;
  }

  public async clientAcceptDeliverable(deliverableId: number): Promise<ProjectDeliverable> {
    const res = await apiClient.request<ProjectDeliverable>(`/projects/deliverables/${deliverableId}/client-accept`, {
      method: 'POST',
    });
    return res.data;
  }

  // ==========================================
  // 7. Risks & Issues & Change Requests
  // ==========================================

  public async getRisks(projectId: number): Promise<ProjectRisk[]> {
    const res = await apiClient.request<ProjectRisk[]>(`/projects/${projectId}/risks`);
    return res.data;
  }

  public async createRisk(projectId: number, payload: Partial<ProjectRisk>): Promise<ProjectRisk> {
    const res = await apiClient.request<ProjectRisk>(`/projects/${projectId}/risks`, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async updateRisk(riskId: number, payload: Partial<ProjectRisk>): Promise<ProjectRisk> {
    const res = await apiClient.request<ProjectRisk>(`/projects/risks/${riskId}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async getIssues(projectId: number): Promise<ProjectIssue[]> {
    const res = await apiClient.request<ProjectIssue[]>(`/projects/${projectId}/issues`);
    return res.data;
  }

  public async createIssue(projectId: number, payload: Partial<ProjectIssue>): Promise<ProjectIssue> {
    const res = await apiClient.request<ProjectIssue>(`/projects/${projectId}/issues`, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async resolveIssue(issueId: number, resolutionPlan: string): Promise<ProjectIssue> {
    const res = await apiClient.request<ProjectIssue>(`/projects/issues/${issueId}/resolve`, {
      method: 'POST',
      body: JSON.stringify({ resolution_plan: resolutionPlan }),
    });
    return res.data;
  }

  public async getChangeRequests(projectId: number): Promise<ProjectChangeRequest[]> {
    const res = await apiClient.request<ProjectChangeRequest[]>(`/projects/${projectId}/change-requests`);
    return res.data;
  }

  public async createChangeRequest(projectId: number, payload: Partial<ProjectChangeRequest>): Promise<ProjectChangeRequest> {
    const res = await apiClient.request<ProjectChangeRequest>(`/projects/${projectId}/change-requests`, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async evaluateChangeRequest(crId: number, decision: string, rationale?: string): Promise<ProjectChangeRequest> {
    const res = await apiClient.request<ProjectChangeRequest>(`/projects/change-requests/${crId}/evaluate`, {
      method: 'POST',
      body: JSON.stringify({ decision, rationale }),
    });
    return res.data;
  }

  // ==========================================
  // 8. Team, Resources & Budget
  // ==========================================

  public async getTeam(projectId: number): Promise<ProjectTeamMember[]> {
    const res = await apiClient.request<ProjectTeamMember[]>(`/projects/${projectId}/team`);
    return res.data;
  }

  public async addTeamMember(projectId: number, payload: Partial<ProjectTeamMember>): Promise<ProjectTeamMember> {
    const res = await apiClient.request<ProjectTeamMember>(`/projects/${projectId}/team`, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async removeTeamMember(memberId: number): Promise<{ id: number }> {
    const res = await apiClient.request<{ id: number }>(`/projects/team/${memberId}`, {
      method: 'DELETE',
    });
    return res.data;
  }

  public async getResources(projectId: number): Promise<ProjectResourceAllocation[]> {
    const res = await apiClient.request<ProjectResourceAllocation[]>(`/projects/${projectId}/resources`);
    return res.data;
  }

  public async addResource(projectId: number, payload: Partial<ProjectResourceAllocation>): Promise<ProjectResourceAllocation> {
    const res = await apiClient.request<ProjectResourceAllocation>(`/projects/${projectId}/resources`, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async getBudget(projectId: number): Promise<ProjectBudgetSummary> {
    const res = await apiClient.request<ProjectBudgetSummary>(`/projects/${projectId}/budget`);
    return res.data;
  }

  public async updateBudget(projectId: number, proposedBudget: number, approvedBudget: number): Promise<ProjectBudgetSummary> {
    const res = await apiClient.request<ProjectBudgetSummary>(`/projects/${projectId}/budget`, {
      method: 'PUT',
      body: JSON.stringify({ proposed_budget: proposedBudget, approved_budget_limit: approvedBudget }),
    });
    return res.data;
  }
}

export const projectService = new ProjectService();
