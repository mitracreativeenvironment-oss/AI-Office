// Hermes AI Agent Runtime & 9Router Model Gateway TypeScript Definitions (Prompt 22)

export type RuntimeEnvironmentType = 'hermes_local' | 'hermes_distributed' | 'hermes_simulation';
export type IntegrationStatus = 'ready' | 'pending_provision' | 'reconfiguring' | 'suspended' | 'disconnected';
export type ExecutionStatus = 'initialized' | 'dispatched' | 'running' | 'completed' | 'failed' | 'cancelled' | 'timed_out';
export type ToolRiskLevel = 'low' | 'medium' | 'high' | 'critical';

export interface RuntimeEnvironment {
  id: number;
  environment_code: string;
  name: string;
  runtime_type: RuntimeEnvironmentType;
  endpoint_url: string;
  heartbeat_url?: string | null;
  status: 'connected' | 'disconnected' | 'simulated' | 'degraded';
  concurrency_limit: number;
  active_sessions_count: number;
  latency_ms: number;
  last_heartbeat_at?: string | null;
  created_at: string;
  updated_at: string;
}

export interface ModelRoutingProfile {
  id: number;
  profile_code: string;
  name: string;
  description?: string | null;
  gateway_endpoint: string;
  primary_model: string;
  fallback_model?: string | null;
  context_window_limit: number;
  max_output_tokens: number;
  temperature: number;
  top_p: number;
  cost_per_1k_input: number;
  cost_per_1k_output: number;
  is_active: number;
  routing_strategy: 'round_robin' | 'cost_optimized' | 'speed_optimized' | 'deterministic';
  created_at: string;
  updated_at: string;
}

export interface RuntimeProfile {
  id: number;
  profile_code: string;
  name: string;
  model_routing_profile_id: number;
  routing_profile_code?: string;
  primary_model?: string;
  max_execution_duration_sec: number;
  max_tool_invocations: number;
  requires_human_oversight: number;
  tool_policy_json: string | Record<string, unknown>;
  memory_policy_json: string | Record<string, unknown>;
  sandbox_filesystem_policy: 'isolated' | 'ephemeral' | 'shared_readonly';
  network_access_policy: 'disabled' | 'whitelisted' | 'unrestricted';
  is_active: number;
  created_at: string;
  updated_at: string;
}

export interface RuntimeTool {
  id: number;
  tool_code: string;
  name: string;
  description: string;
  category: 'code' | 'audit' | 'data' | 'system' | 'ai_hardware';
  schema_definition_json: string | Record<string, unknown>;
  required_permission_code?: string | null;
  risk_level: ToolRiskLevel;
  requires_human_approval: number;
  is_active: number;
  created_at: string;
  updated_at: string;
}

export interface ExecutionSession {
  id: number;
  session_uuid: string;
  task_id: number;
  task_code?: string;
  task_title?: string;
  agent_id: number;
  agent_name?: string;
  agent_code?: string;
  runtime_environment_id: number;
  runtime_profile_id: number;
  model_routing_profile_id: number;
  status: ExecutionStatus;
  execution_mode: 'live' | 'simulated';
  tokens_used: number;
  estimated_cost_idr: number;
  idempotency_key: string;
  result_summary?: string | null;
  error_message?: string | null;
  dispatched_at: string;
  started_at?: string | null;
  completed_at?: string | null;
  created_at: string;
  updated_at: string;
  events?: RuntimeExecutionEvent[];
  tool_invocations?: RuntimeToolInvocation[];
  model_requests?: ModelRequestsUsage[];
}

export interface RuntimeExecutionEvent {
  id: number;
  session_id: number;
  event_type: string;
  event_level: 'info' | 'warning' | 'error' | 'critical';
  event_payload_json?: string | null;
  recorded_at: string;
}

export interface RuntimeToolInvocation {
  id: number;
  session_id: number;
  tool_id: number;
  tool_code: string;
  status: 'pending' | 'completed' | 'rejected' | 'failed';
  input_payload_json?: string | null;
  output_payload_json?: string | null;
  execution_duration_ms: number;
  is_authorized: number;
  rejection_reason?: string | null;
  created_at: string;
}

export interface ModelRequestsUsage {
  id: number;
  session_id?: number | null;
  task_id?: number | null;
  agent_id: number;
  correlation_id: string;
  gateway_endpoint: string;
  model_route: string;
  provider: string;
  prompt_tokens: number;
  completion_tokens: number;
  total_tokens: number;
  latency_ms: number;
  estimated_cost_idr: number;
  status: string;
  error_details?: string | null;
  created_at: string;
}

export interface ModelUsageSummary {
  total_requests: number;
  total_tokens: number;
  prompt_tokens: number;
  completion_tokens: number;
  total_cost_idr: number;
  successful_requests: number;
  failed_requests: number;
  by_route: Record<string, { requests: number; tokens: number; cost_idr: number }>;
}

export interface SystemRuntimeHealth {
  overall_status: 'healthy' | 'simulated_ready' | 'degraded' | 'critical';
  hermes_runtime: {
    component: string;
    status: 'connected' | 'simulated' | 'disconnected';
    is_live: boolean;
    endpoint: string;
    latency_ms: number;
    active_environments_count: number;
    mode: string;
    last_checked_at: string;
  };
  nine_router_gateway: {
    component: string;
    status: 'connected' | 'simulated' | 'disconnected';
    is_live: boolean;
    endpoint: string;
    latency_ms: number;
    active_routes_count: number;
    sole_gateway_enforced: boolean;
    direct_bypass_policy: string;
    mode: string;
    last_checked_at: string;
  };
  ready_agents_count: number;
  sessions: {
    active: number;
    completed: number;
    failed: number;
    total: number;
  };
  model_usage: ModelUsageSummary;
  sole_gateway_enforced: boolean;
  direct_bypass_policy: string;
  checked_at: string;
}

export interface AgentReadinessValidation {
  ready: boolean;
  agent_id: number;
  agent_code: string;
  runtime_instance_id: string;
  profile_reference: string;
  gateway: string;
  status: string;
  reason?: string;
  checklist?: Record<string, boolean>;
}
