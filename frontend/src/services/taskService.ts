import { apiClient } from './apiClient';
import type {
  WorkforceTask,
  TaskMetrics,
  TaskAssignment,
  TaskDelegation,
  TaskCollaborationGroup,
  TaskDependency,
  TaskDeliverable,
  TaskReview,
  TaskBlocker,
} from '../types/tasks';

export interface TaskFilterParams {
  unit_id?: number;
  status?: string;
  priority?: string;
  category?: string;
  accountable_agent_id?: number;
  search?: string;
  limit?: number;
  offset?: number;
}

export const taskService = {
  // Tasks & Metrics
  async listTasks(params: TaskFilterParams = {}): Promise<{ items: WorkforceTask[]; total: number }> {
    const query = new URLSearchParams();
    if (params.unit_id) query.append('unit_id', params.unit_id.toString());
    if (params.status) query.append('status', params.status);
    if (params.priority) query.append('priority', params.priority);
    if (params.category) query.append('category', params.category);
    if (params.accountable_agent_id) query.append('accountable_agent_id', params.accountable_agent_id.toString());
    if (params.search) query.append('search', params.search);
    if (params.limit) query.append('limit', params.limit.toString());
    if (params.offset) query.append('offset', params.offset.toString());

    const qs = query.toString();
    const url = qs ? `/workforce/tasks?${qs}` : '/workforce/tasks';
    const res = await apiClient.request<WorkforceTask[]>(url);
    return {
      items: res.data,
      total: (res.meta?.total as number) ?? res.data.length,
    };
  },

  async getMetrics(): Promise<TaskMetrics> {
    const res = await apiClient.request<TaskMetrics>('/workforce/tasks/metrics');
    return res.data;
  },

  async getTask(id: number): Promise<WorkforceTask> {
    const res = await apiClient.request<WorkforceTask>(`/workforce/tasks/${id}`);
    return res.data;
  },

  async createTask(data: Partial<WorkforceTask>): Promise<WorkforceTask> {
    const res = await apiClient.request<WorkforceTask>('/workforce/tasks', {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return res.data;
  },

  async transitionTask(id: number, targetStatus: string, reason?: string): Promise<WorkforceTask> {
    const res = await apiClient.request<WorkforceTask>(`/workforce/tasks/${id}/transition`, {
      method: 'POST',
      body: JSON.stringify({ target_status: targetStatus, reason }),
    });
    return res.data;
  },

  async assignAgent(taskId: number, data: { agent_id: number; assignment_role?: string; responsibility_scope?: string }): Promise<TaskAssignment> {
    const res = await apiClient.request<TaskAssignment>(`/workforce/tasks/${taskId}/assign`, {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return res.data;
  },

  // Delegations
  async listDelegations(filters: { task_id?: number; status?: string } = {}): Promise<TaskDelegation[]> {
    const query = new URLSearchParams();
    if (filters.task_id) query.append('task_id', filters.task_id.toString());
    if (filters.status) query.append('status', filters.status);
    const qs = query.toString();
    const url = qs ? `/workforce/tasks/delegations?${qs}` : '/workforce/tasks/delegations';
    const res = await apiClient.request<TaskDelegation[]>(url);
    return res.data;
  },

  async createDelegation(data: {
    task_id: number;
    delegating_agent_id: number;
    delegated_agent_id: number;
    delegated_scope: string;
    expected_output?: string;
    authority_limit?: string;
    deadline?: string;
  }): Promise<TaskDelegation> {
    const res = await apiClient.request<TaskDelegation>('/workforce/tasks/delegations', {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return res.data;
  },

  async acceptDelegation(id: number, agentIdentity?: string): Promise<TaskDelegation> {
    const res = await apiClient.request<TaskDelegation>(`/workforce/tasks/delegations/${id}/accept`, {
      method: 'POST',
      body: JSON.stringify({ agent_identity: agentIdentity }),
    });
    return res.data;
  },

  // Collaborations
  async listCollaborations(taskId?: number): Promise<TaskCollaborationGroup[]> {
    const url = taskId ? `/workforce/tasks/collaborations?task_id=${taskId}` : '/workforce/tasks/collaborations';
    const res = await apiClient.request<TaskCollaborationGroup[]>(url);
    return res.data;
  },

  async createCollaboration(taskId: number, data: { group_code: string; name: string; lead_agent_id: number; shared_objective: string }): Promise<TaskCollaborationGroup> {
    const res = await apiClient.request<TaskCollaborationGroup>(`/workforce/tasks/${taskId}/collaborations`, {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return res.data;
  },

  async addCollaborationMember(groupId: number, data: { agent_id: number; role?: string; assigned_responsibilities?: string }): Promise<TaskCollaborationGroup> {
    const res = await apiClient.request<TaskCollaborationGroup>(`/workforce/tasks/collaborations/${groupId}/members`, {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return res.data;
  },

  // Dependencies
  async listDependencies(taskId: number): Promise<TaskDependency[]> {
    const res = await apiClient.request<TaskDependency[]>(`/workforce/tasks/${taskId}/dependencies`);
    return res.data;
  },

  async addDependency(taskId: number, dependsOnTaskId: number, dependencyType = 'finish_to_start'): Promise<TaskDependency> {
    const res = await apiClient.request<TaskDependency>(`/workforce/tasks/${taskId}/dependencies`, {
      method: 'POST',
      body: JSON.stringify({ depends_on_task_id: dependsOnTaskId, dependency_type: dependencyType }),
    });
    return res.data;
  },

  // Deliverables & Reviews
  async listDeliverables(taskId: number): Promise<TaskDeliverable[]> {
    const res = await apiClient.request<TaskDeliverable[]>(`/workforce/tasks/${taskId}/deliverables`);
    return res.data;
  },

  async submitDeliverable(taskId: number, data: {
    title: string;
    submitting_agent_id: number;
    deliverable_type?: string;
    description?: string;
    artifact_uri?: string;
  }): Promise<TaskDeliverable> {
    const res = await apiClient.request<TaskDeliverable>(`/workforce/tasks/${taskId}/deliverables`, {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return res.data;
  },

  async submitReview(taskId: number, deliverableId: number, data: {
    reviewer_agent_id?: number;
    reviewer_user_id?: number;
    review_outcome: 'accepted' | 'revision_required' | 'rejected';
    evaluation_comments: string;
    is_verified_contribution?: number;
  }): Promise<TaskReview> {
    const res = await apiClient.request<TaskReview>(`/workforce/tasks/${taskId}/deliverables/${deliverableId}/review`, {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return res.data;
  },

  // Blockers
  async reportBlocker(taskId: number, data: {
    category: string;
    description: string;
    severity?: string;
    reported_by_agent_id?: number;
  }): Promise<TaskBlocker> {
    const res = await apiClient.request<TaskBlocker>(`/workforce/tasks/${taskId}/blockers`, {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return res.data;
  },

  async resolveBlocker(blockerId: number, resolutionNotes: string): Promise<TaskBlocker> {
    const res = await apiClient.request<TaskBlocker>(`/workforce/tasks/blockers/${blockerId}/resolve`, {
      method: 'POST',
      body: JSON.stringify({ resolution_notes: resolutionNotes }),
    });
    return res.data;
  },

  // Wallbook Continuity
  async getActiveTasksForAgent(agentId: number): Promise<WorkforceTask[]> {
    const res = await apiClient.request<WorkforceTask[]>(`/workforce/agents/${agentId}/active-tasks`);
    return res.data;
  },
};
