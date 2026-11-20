import { apiClient } from './apiClient';
import type {
  KpiCategory,
  KpiDefinition,
  KpiResult,
  CorporateScorecard,
  OperationalPulse,
  ExecutiveOverview,
  DivisionalPerformance,
  ProjectPortfolioPerformance,
  EvaluationCycle,
  AgentPerformanceEvaluation,
  AgentEvaluationEvidence,
  CoreValuesAssessment,
  CoreValuesSummary,
  PerformanceImprovementPlan,
  WelfareBonusReference,
  ExecutiveReport,
} from '../types/performance';

class PerformanceService {
  private basePath = '/performance';

  private buildQueryString(params?: Record<string, any>): string {
    if (!params) return '';
    const filtered = Object.entries(params)
      .filter(([_, v]) => v !== undefined && v !== null && v !== '')
      .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(String(v))}`);
    return filtered.length > 0 ? `?${filtered.join('&')}` : '';
  }

  private async get<T>(path: string, params?: Record<string, any>): Promise<T> {
    const qs = this.buildQueryString(params);
    const res = await apiClient.request<T>(`${this.basePath}${path}${qs}`, { method: 'GET' });
    return res.data;
  }

  private async post<T>(path: string, body?: any): Promise<T> {
    const res = await apiClient.request<T>(`${this.basePath}${path}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });
    return res.data;
  }

  private async put<T>(path: string, body?: any): Promise<T> {
    const res = await apiClient.request<T>(`${this.basePath}${path}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });
    return res.data;
  }

  private async delete<T>(path: string): Promise<T> {
    const res = await apiClient.request<T>(`${this.basePath}${path}`, {
      method: 'DELETE',
    });
    return res.data;
  }

  // 1. KPI Catalog & Categories
  async getCategories(): Promise<KpiCategory[]> {
    return this.get<KpiCategory[]>('/categories');
  }

  async getKpis(filters?: Record<string, any>): Promise<KpiDefinition[]> {
    return this.get<KpiDefinition[]>('/kpis', filters);
  }

  async getKpi(id: number): Promise<KpiDefinition> {
    return this.get<KpiDefinition>(`/kpis/${id}`);
  }

  async createKpi(data: Partial<KpiDefinition>): Promise<KpiDefinition> {
    return this.post<KpiDefinition>('/kpis', data);
  }

  async updateKpi(id: number, data: Partial<KpiDefinition>): Promise<KpiDefinition> {
    return this.put<KpiDefinition>(`/kpis/${id}`, data);
  }

  async deleteKpi(id: number): Promise<{ message: string }> {
    return this.delete<{ message: string }>(`/kpis/${id}`);
  }

  // 2. Metric Calculations
  async calculateKpi(id: number, periodStart?: string, periodEnd?: string): Promise<KpiResult> {
    return this.post<KpiResult>(`/kpis/${id}/calculate`, {
      period_start: periodStart,
      period_end: periodEnd,
    });
  }

  async calculateAllKpis(periodStart?: string, periodEnd?: string): Promise<KpiResult[]> {
    return this.post<KpiResult[]>('/kpis/calculate-all', {
      period_start: periodStart,
      period_end: periodEnd,
    });
  }

  async getKpiResults(id: number, limit?: number): Promise<KpiResult[]> {
    return this.get<KpiResult[]>(`/kpis/${id}/results`, { limit });
  }

  // 3. Scorecards & Dashboards
  async getCorporateScorecard(): Promise<CorporateScorecard> {
    return this.get<CorporateScorecard>('/corporate-scorecard');
  }

  async getOperationalPulse(): Promise<OperationalPulse> {
    return this.get<OperationalPulse>('/operational-pulse');
  }

  async getExecutiveOverview(): Promise<ExecutiveOverview> {
    return this.get<ExecutiveOverview>('/executive-overview');
  }

  async getDivisionalComparison(): Promise<DivisionalPerformance[]> {
    return this.get<DivisionalPerformance[]>('/divisional-comparison');
  }

  async getUnitPerformance(id: number): Promise<any> {
    return this.get<any>(`/units/${id}`);
  }

  async getProjectPortfolio(): Promise<ProjectPortfolioPerformance> {
    return this.get<ProjectPortfolioPerformance>('/project-portfolio');
  }

  // 4. Agent Evaluations
  async getEvaluations(filters?: Record<string, any>): Promise<AgentPerformanceEvaluation[]> {
    return this.get<AgentPerformanceEvaluation[]>('/evaluations', filters);
  }

  async getEvaluation(id: number): Promise<AgentPerformanceEvaluation> {
    return this.get<AgentPerformanceEvaluation>(`/evaluations/${id}`);
  }

  async createEvaluation(data: any): Promise<AgentPerformanceEvaluation> {
    return this.post<AgentPerformanceEvaluation>('/evaluations', data);
  }

  async transitionEvaluation(id: number, status: string, feedback?: string): Promise<AgentPerformanceEvaluation> {
    return this.post<AgentPerformanceEvaluation>(`/evaluations/${id}/transition`, {
      status,
      reviewer_feedback: feedback,
    });
  }

  async getAgentProfile(id: number): Promise<any> {
    return this.get<any>(`/agents/${id}/profile`);
  }

  async addEvidence(id: number, data: any): Promise<AgentEvaluationEvidence> {
    return this.post<AgentEvaluationEvidence>(`/evaluations/${id}/evidence`, data);
  }

  async getEvidence(id: number): Promise<AgentEvaluationEvidence[]> {
    return this.get<AgentEvaluationEvidence[]>(`/evaluations/${id}/evidence`);
  }

  // 5. Core Values Assessments
  async getAgentCoreValues(agentId: number, cycleId?: number): Promise<CoreValuesAssessment[]> {
    return this.get<CoreValuesAssessment[]>(`/agents/${agentId}/core-values`, { cycle_id: cycleId });
  }

  async assessCoreValue(data: Partial<CoreValuesAssessment>): Promise<CoreValuesAssessment> {
    return this.post<CoreValuesAssessment>('/core-values', data);
  }

  async getCoreValuesSummary(cycleId?: number): Promise<CoreValuesSummary> {
    return this.get<CoreValuesSummary>('/core-values/summary', { cycle_id: cycleId });
  }

  // 6. Workflow Cycles
  async getCycles(filters?: Record<string, any>): Promise<EvaluationCycle[]> {
    return this.get<EvaluationCycle[]>('/cycles', filters);
  }

  async getCycle(id: number): Promise<EvaluationCycle> {
    return this.get<EvaluationCycle>(`/cycles/${id}`);
  }

  async createCycle(data: Partial<EvaluationCycle>): Promise<EvaluationCycle> {
    return this.post<EvaluationCycle>('/cycles', data);
  }

  async transitionCycle(id: number, status: string): Promise<EvaluationCycle> {
    return this.post<EvaluationCycle>(`/cycles/${id}/transition`, { status });
  }

  async finalizeEvaluation(id: number, comments?: string): Promise<any> {
    return this.post<any>(`/evaluations/${id}/finalize`, { comments });
  }

  // 7. Improvement Plans (PIPs)
  async getPlans(filters?: Record<string, any>): Promise<PerformanceImprovementPlan[]> {
    return this.get<PerformanceImprovementPlan[]>('/plans', filters);
  }

  async getPlan(id: number): Promise<PerformanceImprovementPlan> {
    return this.get<PerformanceImprovementPlan>(`/plans/${id}`);
  }

  async createPlan(data: Partial<PerformanceImprovementPlan>): Promise<PerformanceImprovementPlan> {
    return this.post<PerformanceImprovementPlan>('/plans', data);
  }

  async updatePlan(id: number, data: Partial<PerformanceImprovementPlan>): Promise<PerformanceImprovementPlan> {
    return this.put<PerformanceImprovementPlan>(`/plans/${id}`, data);
  }

  async transitionPlan(id: number, status: string, resolutionEvidence?: string): Promise<PerformanceImprovementPlan> {
    return this.post<PerformanceImprovementPlan>(`/plans/${id}/transition`, {
      status,
      resolution_evidence: resolutionEvidence,
    });
  }

  async linkPlanTask(id: number, taskId: number): Promise<PerformanceImprovementPlan> {
    return this.post<PerformanceImprovementPlan>(`/plans/${id}/link-task`, { task_id: taskId });
  }

  // 8. Welfare Bonuses & DoA
  async getBonuses(filters?: Record<string, any>): Promise<WelfareBonusReference[]> {
    return this.get<WelfareBonusReference[]>('/bonuses', filters);
  }

  async getBonus(id: number): Promise<WelfareBonusReference> {
    return this.get<WelfareBonusReference>(`/bonuses/${id}`);
  }

  async recommendBonus(data: Partial<WelfareBonusReference>): Promise<WelfareBonusReference> {
    return this.post<WelfareBonusReference>('/bonuses/recommend', data);
  }

  async approveBonus(id: number): Promise<WelfareBonusReference> {
    return this.post<WelfareBonusReference>(`/bonuses/${id}/approve`);
  }

  async rejectBonus(id: number, reason?: string): Promise<WelfareBonusReference> {
    return this.post<WelfareBonusReference>(`/bonuses/${id}/reject`, { reason });
  }

  async disburseBonus(id: number, welfarePeriodId?: number): Promise<WelfareBonusReference> {
    return this.post<WelfareBonusReference>(`/bonuses/${id}/disburse`, { welfare_period_id: welfarePeriodId });
  }

  async getBonusEligibility(agentId: number, cycleId?: number): Promise<any> {
    return this.get<any>(`/agents/${agentId}/bonus-eligibility`, { cycle_id: cycleId });
  }

  // 9. Executive Reporting
  async getReports(filters?: Record<string, any>): Promise<ExecutiveReport[]> {
    return this.get<ExecutiveReport[]>('/reports', filters);
  }

  async getReport(id: number): Promise<ExecutiveReport> {
    return this.get<ExecutiveReport>(`/reports/${id}`);
  }

  async generateReport(data: Partial<ExecutiveReport>): Promise<ExecutiveReport> {
    return this.post<ExecutiveReport>('/reports/generate', data);
  }

  async publishReport(id: number): Promise<ExecutiveReport> {
    return this.post<ExecutiveReport>(`/reports/${id}/publish`);
  }

  async ratifyReport(id: number): Promise<ExecutiveReport> {
    return this.post<ExecutiveReport>(`/reports/${id}/ratify`);
  }
}

export const performanceService = new PerformanceService();
export default performanceService;
