// Hermes Runtime & 9Router Model Gateway API Client Service (Prompt 22)

import { apiClient } from './apiClient';
import type {
  SystemRuntimeHealth,
  RuntimeEnvironment,
  RuntimeProfile,
  RuntimeTool,
  ExecutionSession,
  ModelRoutingProfile,
  ModelUsageSummary,
  AgentReadinessValidation,
} from '../types/runtime';

class RuntimeService {
  /**
   * Aggregate Infrastructure Health (Hermes Runtime + 9Router Model Gateway)
   */
  public async getHealth(): Promise<SystemRuntimeHealth> {
    const res = await apiClient.request<SystemRuntimeHealth>('/runtime/health');
    return res.data;
  }

  /**
   * Configured Hermes Runtime Environments
   */
  public async getEnvironments(): Promise<RuntimeEnvironment[]> {
    const res = await apiClient.request<RuntimeEnvironment[]>('/runtime/environments');
    return res.data;
  }

  /**
   * Agent Runtime Profiles
   */
  public async getProfiles(): Promise<RuntimeProfile[]> {
    const res = await apiClient.request<RuntimeProfile[]>('/runtime/profiles');
    return res.data;
  }

  /**
   * Controlled Tool Registry
   */
  public async getTools(filters?: { category?: string; risk_level?: string; is_active?: number }): Promise<RuntimeTool[]> {
    const params = new URLSearchParams();
    if (filters?.category) params.append('category', filters.category);
    if (filters?.risk_level) params.append('risk_level', filters.risk_level);
    if (filters?.is_active !== undefined) params.append('is_active', String(filters.is_active));

    const endpoint = `/runtime/tools${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<RuntimeTool[]>(endpoint);
    return res.data;
  }

  /**
   * Register a new tool in the Controlled Tool Registry
   */
  public async createTool(data: Partial<RuntimeTool>): Promise<RuntimeTool> {
    const res = await apiClient.request<RuntimeTool>('/runtime/tools', {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return res.data;
  }

  /**
   * Authorize and invoke a tool in the execution sandbox
   */
  public async invokeTool(sessionId: number, toolId: number, inputPayload: Record<string, unknown>): Promise<unknown> {
    const res = await apiClient.request('/runtime/tools/invoke', {
      method: 'POST',
      body: JSON.stringify({
        session_id: sessionId,
        tool_id: toolId,
        input_payload: inputPayload,
      }),
    });
    return res.data;
  }

  /**
   * List Execution Sessions
   */
  public async listExecutions(filters?: { task_id?: number; agent_id?: number; status?: string; limit?: number; offset?: number }): Promise<{ items: ExecutionSession[]; total: number }> {
    const params = new URLSearchParams();
    if (filters?.task_id) params.append('task_id', String(filters.task_id));
    if (filters?.agent_id) params.append('agent_id', String(filters.agent_id));
    if (filters?.status) params.append('status', filters.status);
    if (filters?.limit) params.append('limit', String(filters.limit));
    if (filters?.offset) params.append('offset', String(filters.offset));

    const endpoint = `/runtime/executions${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<ExecutionSession[]>(endpoint);
    return {
      items: res.data,
      total: (res.meta?.total as number) ?? res.data.length,
    };
  }

  /**
   * Get single execution session with audit events, tool calls, and model requests
   */
  public async getExecution(id: number): Promise<ExecutionSession> {
    const res = await apiClient.request<ExecutionSession>(`/runtime/executions/${id}`);
    return res.data;
  }

  /**
   * Dispatch an authorized task execution to Hermes Runtime
   */
  public async dispatchExecution(taskId: number, agentId: number, options?: Record<string, unknown>): Promise<unknown> {
    const res = await apiClient.request('/runtime/executions', {
      method: 'POST',
      body: JSON.stringify({
        task_id: taskId,
        agent_id: agentId,
        ...options,
      }),
    });
    return res.data;
  }

  /**
   * Cancel an in-progress or queued execution session
   */
  public async cancelExecution(id: number, reason: string): Promise<boolean> {
    const res = await apiClient.request<{ cancelled: boolean }>(`/runtime/executions/${id}/cancel`, {
      method: 'POST',
      body: JSON.stringify({ reason }),
    });
    return res.data.cancelled;
  }

  /**
   * Get Agent Runtime Mapping
   */
  public async getAgentRuntime(agentId: number): Promise<unknown> {
    const res = await apiClient.request(`/runtime/agents/${agentId}`);
    return res.data;
  }

  /**
   * Validate Agent Readiness across all 12 Pre-Dispatch steps
   */
  public async validateAgent(agentId: number): Promise<AgentReadinessValidation> {
    const res = await apiClient.request<AgentReadinessValidation>(`/runtime/agents/${agentId}/validate`, {
      method: 'POST',
    });
    return res.data;
  }

  /**
   * 9Router Gateway Status
   */
  public async getGatewayStatus(): Promise<unknown> {
    const res = await apiClient.request('/model-gateway/status');
    return res.data;
  }

  /**
   * 9Router Routing Profiles
   */
  public async getGatewayRoutes(): Promise<ModelRoutingProfile[]> {
    const res = await apiClient.request<ModelRoutingProfile[]>('/model-gateway/routes');
    return res.data;
  }

  /**
   * 9Router Token & Cost Usage Accounting
   */
  public async getGatewayUsage(): Promise<ModelUsageSummary> {
    const res = await apiClient.request<ModelUsageSummary>('/model-gateway/usage');
    return res.data;
  }

  /**
   * Dispatch Model Completion via 9Router Sole Gateway
   */
  public async requestCompletion(profileCode: string, prompt: string, maxTokens = 500): Promise<unknown> {
    const res = await apiClient.request('/model-gateway/completions', {
      method: 'POST',
      body: JSON.stringify({
        profile_code: profileCode,
        prompt,
        max_tokens: maxTokens,
      }),
    });
    return res.data;
  }
}

export const runtimeService = new RuntimeService();
