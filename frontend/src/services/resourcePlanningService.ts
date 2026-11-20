import { apiClient } from './apiClient';
import type {
  ResourceItem,
  AgentCapacity,
  AgentTimelineEntry,
  AgentWorkloadHeatmapItem,
  WorkloadSummary,
  DivisionalCapacityComparison,
  OrganizationOverview,
  SkillMatrixItem,
  SkillBottleneck,
  SingleAgentDependency,
  CapacityReservation,
  ResourceRequest,
  ResourceAllocation,
  ResourceConflict,
  PortfolioPriority,
  ForecastSnapshot,
  GapAnalysis,
} from '../types/resourcePlanning';

class ResourcePlanningService {
  private basePath = '/resource-planning';

  private buildQueryString(params?: Record<string, any>): string {
    if (!params) return '';
    const filtered = Object.entries(params)
      .filter(([_, v]) => v !== undefined && v !== null && v !== '')
      .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(String(v))}`);
    return filtered.length > 0 ? `?${filtered.join('&')}` : '';
  }

  private async get<T>(path: string, params?: Record<string, any>): Promise<T> {
    const qs = this.buildQueryString(params);
    const res = await apiClient.request<T>(`${path}${qs}`, { method: 'GET' });
    return res.data;
  }

  private async post<T>(path: string, body?: any): Promise<T> {
    const res = await apiClient.request<T>(path, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });
    return res.data;
  }

  private async put<T>(path: string, body?: any): Promise<T> {
    const res = await apiClient.request<T>(path, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });
    return res.data;
  }

  private async delete<T>(path: string): Promise<T> {
    const res = await apiClient.request<T>(path, { method: 'DELETE' });
    return res.data;
  }

  // 1. Organizational Overview & Comparison
  async getOrganizationOverview(): Promise<OrganizationOverview> {
    return this.get<OrganizationOverview>(`${this.basePath}/overview`);
  }

  async getDivisionalComparison(): Promise<DivisionalCapacityComparison[]> {
    return this.get<DivisionalCapacityComparison[]>(`${this.basePath}/divisional-comparison`);
  }

  // 2. Resource Inventory & Registry
  async getResources(filters?: { category?: string; status?: string; owner_unit_id?: number; search?: string }): Promise<ResourceItem[]> {
    return this.get<ResourceItem[]>(`${this.basePath}/resources`, filters);
  }

  async getResource(id: number): Promise<ResourceItem> {
    return this.get<ResourceItem>(`${this.basePath}/resources/${id}`);
  }

  async createResource(data: Partial<ResourceItem>): Promise<ResourceItem> {
    return this.post<ResourceItem>(`${this.basePath}/resources`, data);
  }

  async updateResource(id: number, data: Partial<ResourceItem>): Promise<ResourceItem> {
    return this.put<ResourceItem>(`${this.basePath}/resources/${id}`, data);
  }

  async deleteResource(id: number): Promise<void> {
    await this.delete(`${this.basePath}/resources/${id}`);
  }

  // 3. Workforce Capacity & Workload
  async getAllAgentsCapacity(unitId?: number): Promise<AgentCapacity[]> {
    const params = unitId ? { unit_id: unitId } : undefined;
    return this.get<AgentCapacity[]>(`${this.basePath}/capacity/agents`, params);
  }

  async getAgentCapacity(id: number, startDate?: string, endDate?: string): Promise<AgentCapacity> {
    const params = { start_date: startDate, end_date: endDate };
    return this.get<AgentCapacity>(`${this.basePath}/capacity/agents/${id}`, params);
  }

  async getAgentTimeline(id: number, weeks: number = 4): Promise<AgentTimelineEntry[]> {
    return this.get<AgentTimelineEntry[]>(`${this.basePath}/capacity/agents/${id}/timeline`, { weeks });
  }

  async getWorkloadHeatmap(weeks: number = 4, unitId?: number): Promise<AgentWorkloadHeatmapItem[]> {
    const params: Record<string, any> = { weeks };
    if (unitId) params.unit_id = unitId;
    return this.get<AgentWorkloadHeatmapItem[]>(`${this.basePath}/workload/heatmap`, params);
  }

  async getWorkloadSummary(): Promise<WorkloadSummary> {
    return this.get<WorkloadSummary>(`${this.basePath}/workload/summary`);
  }

  // 4. Skill Coverage Matrix & Bottlenecks
  async getSkillCoverageMatrix(unitId?: number): Promise<SkillMatrixItem[]> {
    const params = unitId ? { unit_id: unitId } : undefined;
    return this.get<SkillMatrixItem[]>(`${this.basePath}/skills/matrix`, params);
  }

  async getSkillBottlenecks(): Promise<SkillBottleneck[]> {
    return this.get<SkillBottleneck[]>(`${this.basePath}/skills/bottlenecks`);
  }

  async getSingleAgentDependencies(): Promise<SingleAgentDependency[]> {
    return this.get<SingleAgentDependency[]>(`${this.basePath}/skills/spof`);
  }

  // 5. Capacity Reservations (Internal Ops)
  async getReservations(filters?: { status?: string; reservation_type?: string; scope_unit_id?: number }): Promise<CapacityReservation[]> {
    return this.get<CapacityReservation[]>(`${this.basePath}/reservations`, filters);
  }

  async getReservation(id: number): Promise<CapacityReservation> {
    return this.get<CapacityReservation>(`${this.basePath}/reservations/${id}`);
  }

  async createReservation(data: Partial<CapacityReservation>): Promise<CapacityReservation> {
    return this.post<CapacityReservation>(`${this.basePath}/reservations`, data);
  }

  async updateReservation(id: number, data: Partial<CapacityReservation>): Promise<CapacityReservation> {
    return this.put<CapacityReservation>(`${this.basePath}/reservations/${id}`, data);
  }

  async deactivateReservation(id: number): Promise<void> {
    await this.post(`${this.basePath}/reservations/${id}/deactivate`, {});
  }

  // 6. Resource Requests & Workflow
  async getRequests(filters?: { lifecycle_status?: string; project_id?: number; resource_category?: string; priority?: string }): Promise<ResourceRequest[]> {
    return this.get<ResourceRequest[]>(`${this.basePath}/requests`, filters);
  }

  async getRequest(id: number): Promise<ResourceRequest> {
    return this.get<ResourceRequest>(`${this.basePath}/requests/${id}`);
  }

  async createRequest(data: Partial<ResourceRequest>): Promise<ResourceRequest> {
    return this.post<ResourceRequest>(`${this.basePath}/requests`, data);
  }

  async submitRequest(id: number): Promise<ResourceRequest> {
    return this.post<ResourceRequest>(`${this.basePath}/requests/${id}/submit`, {});
  }

  async assessRequestCapacity(id: number, notes?: string): Promise<ResourceRequest> {
    return this.post<ResourceRequest>(`${this.basePath}/requests/${id}/assess-capacity`, { notes });
  }

  async approveRequest(id: number): Promise<ResourceRequest> {
    return this.post<ResourceRequest>(`${this.basePath}/requests/${id}/approve`, {});
  }

  async rejectRequest(id: number, reason: string): Promise<ResourceRequest> {
    return this.post<ResourceRequest>(`${this.basePath}/requests/${id}/reject`, { reason });
  }

  async allocateRequest(id: number, allocationData: Record<string, any>): Promise<ResourceRequest> {
    return this.post<ResourceRequest>(`${this.basePath}/requests/${id}/allocate`, allocationData);
  }

  // 7. Allocations Master
  async getAllocations(filters?: { status?: string; project_id?: number; agent_id?: number }): Promise<ResourceAllocation[]> {
    return this.get<ResourceAllocation[]>(`${this.basePath}/allocations`, filters);
  }

  // 8. Resource Conflicts
  async getConflicts(filters?: { conflict_type?: string; severity?: string; resolution_status?: string; agent_id?: number }): Promise<ResourceConflict[]> {
    return this.get<ResourceConflict[]>(`${this.basePath}/conflicts`, filters);
  }

  async getConflict(id: number): Promise<ResourceConflict> {
    return this.get<ResourceConflict>(`${this.basePath}/conflicts/${id}`);
  }

  async detectConflicts(): Promise<ResourceConflict[]> {
    return this.post<ResourceConflict[]>(`${this.basePath}/conflicts/detect`, {});
  }

  async resolveConflict(id: number, notes: string): Promise<ResourceConflict> {
    return this.post<ResourceConflict>(`${this.basePath}/conflicts/${id}/resolve`, { notes });
  }

  async dismissConflict(id: number, reason: string): Promise<ResourceConflict> {
    return this.post<ResourceConflict>(`${this.basePath}/conflicts/${id}/dismiss`, { reason });
  }

  // 9. Portfolio Prioritization
  async getPortfolioPriorities(period?: string): Promise<PortfolioPriority[]> {
    const params = period ? { period } : undefined;
    return this.get<PortfolioPriority[]>(`${this.basePath}/priorities`, params);
  }

  async getProjectPriority(projectId: number): Promise<PortfolioPriority> {
    return this.get<PortfolioPriority>(`${this.basePath}/priorities/project/${projectId}`);
  }

  async evaluateProjectPriority(data: Record<string, any>): Promise<PortfolioPriority> {
    return this.post<PortfolioPriority>(`${this.basePath}/priorities/evaluate`, data);
  }

  async overridePriority(projectId: number, newRank: number, priorityTier: string, reason: string): Promise<PortfolioPriority> {
    return this.post<PortfolioPriority>(`${this.basePath}/priorities/override`, {
      project_id: projectId,
      new_rank: newRank,
      priority_tier: priorityTier,
      reason,
    });
  }

  // 10. Forecasting & Gap Analysis
  async getForecastSnapshots(periodType: string = 'weekly'): Promise<ForecastSnapshot[]> {
    return this.get<ForecastSnapshot[]>(`${this.basePath}/forecasts`, { period_type: periodType });
  }

  async generateForecastSnapshot(startDate?: string, endDate?: string, unitId?: number): Promise<ForecastSnapshot> {
    return this.post<ForecastSnapshot>(`${this.basePath}/forecasts/generate`, {
      start_date: startDate,
      end_date: endDate,
      unit_id: unitId,
    });
  }

  async getGapAnalyses(filters?: { status?: string; severity?: string; gap_category?: string }): Promise<GapAnalysis[]> {
    return this.get<GapAnalysis[]>(`${this.basePath}/gaps`, filters);
  }

  async createGapAnalysis(data: Record<string, any>): Promise<GapAnalysis> {
    return this.post<GapAnalysis>(`${this.basePath}/gaps`, data);
  }

  async resolveGapAnalysis(id: number, actionNotes: string): Promise<GapAnalysis> {
    return this.post<GapAnalysis>(`${this.basePath}/gaps/${id}/resolve`, { action_notes: actionNotes });
  }
}

export const resourcePlanningService = new ResourcePlanningService();
