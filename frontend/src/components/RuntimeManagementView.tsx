import React, { useState, useEffect, useCallback } from 'react';
import { runtimeService } from '../services/runtimeService';
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

interface RuntimeManagementViewProps {
  onNotify?: (message: string, type?: 'info' | 'success' | 'warning' | 'error') => void;
}

const EXEC_STATUS_COLORS: Record<string, string> = {
  initialized: 'bg-gray-800 text-gray-300 border-gray-600',
  dispatched: 'bg-blue-950/60 text-blue-300 border-blue-700',
  running: 'bg-amber-950/60 text-amber-300 border-amber-600 animate-pulse font-semibold',
  completed: 'bg-emerald-950/60 text-emerald-300 border-emerald-600 font-semibold',
  failed: 'bg-red-950/80 text-red-300 border-red-700 font-semibold',
  cancelled: 'bg-gray-900 text-gray-400 border-gray-700',
  timed_out: 'bg-purple-950/60 text-purple-300 border-purple-700',
};

const RISK_BADGES: Record<string, string> = {
  low: 'text-emerald-400 bg-emerald-950/40 border-emerald-800',
  medium: 'text-blue-400 bg-blue-950/40 border-blue-800',
  high: 'text-amber-400 bg-amber-950/40 border-amber-800 font-bold',
  critical: 'text-rose-400 bg-rose-950/60 border-rose-800 font-bold animate-pulse',
};

const VERIFIED_AGENTS = [
  { id: 1, code: 'AGT-DIR-001', name: 'Director AI' },
  { id: 2, code: 'AGT-TECH-001', name: 'Tech Head AI' },
  { id: 3, code: 'AGT-SRV-001', name: 'Services Head AI' },
  { id: 4, code: 'AGT-ACAD-001', name: 'Academy Head AI' },
  { id: 5, code: 'AGT-HW-001', name: 'Hardware Head AI' },
  { id: 6, code: 'AGT-DEV-001', name: 'Dev AI' },
];

export const RuntimeManagementView: React.FC<RuntimeManagementViewProps> = ({ onNotify }) => {
  const [activeTab, setActiveTab] = useState<'overview' | 'executions' | 'profiles' | 'gateway' | 'tools' | 'dispatch'>('overview');
  const [loading, setLoading] = useState<boolean>(true);
  const [refreshing, setRefreshing] = useState<boolean>(false);

  // Data states
  const [health, setHealth] = useState<SystemRuntimeHealth | null>(null);
  const [environments, setEnvironments] = useState<RuntimeEnvironment[]>([]);
  const [profiles, setProfiles] = useState<RuntimeProfile[]>([]);
  const [tools, setTools] = useState<RuntimeTool[]>([]);
  const [executions, setExecutions] = useState<ExecutionSession[]>([]);
  const [routes, setRoutes] = useState<ModelRoutingProfile[]>([]);
  const [usage, setUsage] = useState<ModelUsageSummary | null>(null);

  // Modal / Detail states
  const [selectedSession, setSelectedSession] = useState<ExecutionSession | null>(null);
  const [loadingSessionDetail, setLoadingSessionDetail] = useState<boolean>(false);
  const [isNewToolModalOpen, setIsNewToolModalOpen] = useState<boolean>(false);

  // Tool Form state
  const [newTool, setNewTool] = useState({
    tool_code: '',
    name: '',
    description: '',
    category: 'code',
    risk_level: 'low',
    requires_human_approval: 0,
    schema_definition_json: '{}',
  });

  // Gateway test console state
  const [testProfileCode, setTestProfileCode] = useState<string>('ROUTE-FAST-REASONING');
  const [testPrompt, setTestPrompt] = useState<string>('Verify 9Router model routing response and latency.');
  const [testResult, setTestResult] = useState<unknown>(null);
  const [testingModel, setTestingModel] = useState<boolean>(false);

  // Execution dispatch state
  const [dispatchTaskId, setDispatchTaskId] = useState<number>(2);
  const [dispatchAgentId, setDispatchAgentId] = useState<number>(6);
  const [dispatching, setDispatching] = useState<boolean>(false);
  const [validationResult, setValidationResult] = useState<AgentReadinessValidation | null>(null);
  const [validating, setValidating] = useState<boolean>(false);

  const notify = (msg: string, type: 'info' | 'success' | 'warning' | 'error' = 'info') => {
    if (onNotify) {
      onNotify(msg, type);
    } else {
      console.log(`[${type.toUpperCase()}] ${msg}`);
    }
  };

  const loadData = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    else setLoading(true);

    try {
      const [hRes, envRes, profRes, toolRes, execRes, routeRes, useRes] = await Promise.all([
        runtimeService.getHealth().catch(() => null),
        runtimeService.getEnvironments().catch(() => []),
        runtimeService.getProfiles().catch(() => []),
        runtimeService.getTools().catch(() => []),
        runtimeService.listExecutions().catch(() => ({ items: [], total: 0 })),
        runtimeService.getGatewayRoutes().catch(() => []),
        runtimeService.getGatewayUsage().catch(() => null),
      ]);

      if (hRes) setHealth(hRes);
      setEnvironments(envRes);
      setProfiles(profRes);
      setTools(toolRes);
      setExecutions(execRes.items);
      setRoutes(routeRes);
      if (useRes) setUsage(useRes);

      if (isRefresh) notify('Runtime & 9Router state refreshed successfully.', 'success');
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Failed to load runtime infrastructure data.';
      notify(msg, 'error');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    loadData();
  }, [loadData]);

  const handleInspectSession = async (session: ExecutionSession) => {
    setLoadingSessionDetail(true);
    setSelectedSession(session);
    try {
      const detail = await runtimeService.getExecution(session.id);
      setSelectedSession(detail);
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Failed to fetch session detail audit.';
      notify(msg, 'error');
    } finally {
      setLoadingSessionDetail(false);
    }
  };

  const handleCreateTool = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newTool.tool_code || !newTool.name) {
      notify('Tool code and name are required.', 'warning');
      return;
    }

    try {
      await runtimeService.createTool({
        ...newTool,
        category: newTool.category as any,
        risk_level: newTool.risk_level as any,
      });
      notify(`Tool '${newTool.tool_code}' registered in Controlled Tool Registry.`, 'success');
      setIsNewToolModalOpen(false);
      setNewTool({
        tool_code: '',
        name: '',
        description: '',
        category: 'code',
        risk_level: 'low',
        requires_human_approval: 0,
        schema_definition_json: '{}',
      });
      loadData(true);
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Failed to register tool.';
      notify(msg, 'error');
    }
  };

  const handleTestModel = async () => {
    if (!testPrompt) return;
    setTestingModel(true);
    setTestResult(null);
    try {
      const res = await runtimeService.requestCompletion(testProfileCode, testPrompt, 400);
      setTestResult(res);
      notify('Model completion routed successfully via 9Router Sole Gateway.', 'success');
      // Refresh usage stats
      const updatedUsage = await runtimeService.getGatewayUsage().catch(() => null);
      if (updatedUsage) setUsage(updatedUsage);
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Model gateway request failed.';
      notify(msg, 'error');
    } finally {
      setTestingModel(false);
    }
  };

  const handleValidateReadiness = async () => {
    setValidating(true);
    setValidationResult(null);
    try {
      const res = await runtimeService.validateAgent(dispatchAgentId);
      setValidationResult(res);
      if (res.ready) {
        notify(`Agent #${dispatchAgentId} verified ready for execution across 12 checks.`, 'success');
      } else {
        notify(`Agent #${dispatchAgentId} readiness rejected: ${res.reason}`, 'warning');
      }
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Validation request failed.';
      notify(msg, 'error');
    } finally {
      setValidating(false);
    }
  };

  const handleDispatchExecution = async () => {
    setDispatching(true);
    try {
      const res = await runtimeService.dispatchExecution(dispatchTaskId, dispatchAgentId, {
        nonce: `ui_dispatch_${Date.now()}`,
      });
      notify(`Task #${dispatchTaskId} execution session dispatched to Hermes runtime!`, 'success');
      loadData(true);
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Dispatch failed: Execution preconditions not satisfied.';
      notify(msg, 'error');
    } finally {
      setDispatching(false);
    }
  };

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[400px] text-gray-400">
        <div className="w-10 h-10 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin mb-4" />
        <p className="text-sm font-medium">Probing Hermes AI Agent Runtime & 9Router Gateway...</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* 1. Header & Quick Actions */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-800 pb-4">
        <div>
          <div className="flex items-center gap-3">
            <h1 className="text-xl font-bold text-white tracking-wide flex items-center gap-2">
              <span>⚡</span> Hermes AI Agent Runtime & 9Router Model Gateway
            </h1>
            <span
              className={`px-2.5 py-0.5 text-xs font-semibold rounded-full border ${
                health?.overall_status === 'healthy'
                  ? 'bg-emerald-950/60 text-emerald-400 border-emerald-700'
                  : 'bg-amber-950/60 text-amber-400 border-amber-700'
              }`}
            >
              {health?.overall_status === 'healthy' ? '● RUNTIME HEALTHY' : '● SIMULATED FALLBACK ACTIVE'}
            </span>
          </div>
          <p className="text-xs text-gray-400 mt-1">
            MCE Business Control Plane (CI4) authoritative orchestrator • Hermes Sandbox Execution • 9Router Sole Model Gateway
          </p>
        </div>

        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={() => loadData(true)}
            disabled={refreshing}
            className="px-3 py-1.5 text-xs font-medium bg-gray-800 hover:bg-gray-700 text-gray-200 border border-gray-700 rounded-lg flex items-center gap-1.5 transition-colors disabled:opacity-50"
          >
            <span className={refreshing ? 'animate-spin' : ''}>↻</span>
            {refreshing ? 'Refreshing...' : 'Refresh Health'}
          </button>
        </div>
      </div>

      {/* 2. Infrastructure Health & Security Banner */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        {/* Hermes Status */}
        <div className="bg-gray-900/80 border border-gray-800 rounded-xl p-4 flex flex-col justify-between">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider">Hermes Runtime</span>
            <span
              className={`w-2 h-2 rounded-full ${
                health?.hermes_runtime.status === 'connected' ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400'
              }`}
            />
          </div>
          <div className="mt-2">
            <div className="text-lg font-bold text-white flex items-center gap-2">
              {health?.hermes_runtime.status === 'connected' ? 'LIVE DAEMON' : 'SIMULATED ADAPTER'}
            </div>
            <p className="text-xs text-gray-400 mt-1 font-mono truncate">{health?.hermes_runtime.endpoint}</p>
          </div>
          <div className="mt-3 pt-2 border-t border-gray-800/60 flex justify-between text-[11px] text-gray-400">
            <span>Latency: {health?.hermes_runtime.latency_ms} ms</span>
            <span>Envs: {health?.hermes_runtime.active_environments_count}</span>
          </div>
        </div>

        {/* 9Router Status */}
        <div className="bg-gray-900/80 border border-gray-800 rounded-xl p-4 flex flex-col justify-between">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider">9Router Gateway</span>
            <span
              className={`w-2 h-2 rounded-full ${
                health?.nine_router_gateway.status === 'connected' ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400'
              }`}
            />
          </div>
          <div className="mt-2">
            <div className="text-lg font-bold text-white flex items-center gap-2">
              {health?.nine_router_gateway.status === 'connected' ? 'LIVE GATEWAY' : 'SIMULATED MULTIPLEXER'}
            </div>
            <p className="text-xs text-gray-400 mt-1 font-mono truncate">{health?.nine_router_gateway.endpoint}</p>
          </div>
          <div className="mt-3 pt-2 border-t border-gray-800/60 flex justify-between text-[11px] text-gray-400">
            <span>Active Routes: {health?.nine_router_gateway.active_routes_count}</span>
            <span className="text-emerald-400 font-semibold">SOLE GATEWAY</span>
          </div>
        </div>

        {/* Sessions Count */}
        <div className="bg-gray-900/80 border border-gray-800 rounded-xl p-4 flex flex-col justify-between">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider">Execution Sessions</span>
            <span className="text-xs text-cyan-400 font-mono">Total: {health?.sessions.total ?? 0}</span>
          </div>
          <div className="mt-2 flex items-baseline gap-3">
            <div className="text-2xl font-bold text-emerald-400">{health?.sessions.completed ?? 0}</div>
            <span className="text-xs text-gray-400">Completed</span>
            {health?.sessions.active ? (
              <span className="text-xs font-bold text-amber-400 bg-amber-950/40 px-2 py-0.5 rounded border border-amber-800">
                {health.sessions.active} Active
              </span>
            ) : null}
          </div>
          <div className="mt-3 pt-2 border-t border-gray-800/60 flex justify-between text-[11px] text-gray-400">
            <span>Failed: {health?.sessions.failed ?? 0}</span>
            <span>Ready Agents: {health?.ready_agents_count ?? 0}</span>
          </div>
        </div>

        {/* Model Usage Cost */}
        <div className="bg-gray-900/80 border border-gray-800 rounded-xl p-4 flex flex-col justify-between">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider">9Router Usage (IDR)</span>
            <span className="text-xs text-purple-400 font-mono">Tokens: {(usage?.total_tokens ?? 0).toLocaleString()}</span>
          </div>
          <div className="mt-2">
            <div className="text-xl font-bold text-white font-mono">
              Rp {(usage?.total_cost_idr ?? 0).toLocaleString('id-ID', { minimumFractionDigits: 2 })}
            </div>
            <p className="text-xs text-gray-400 mt-1">Requests: {usage?.total_requests ?? 0} total</p>
          </div>
          <div className="mt-3 pt-2 border-t border-gray-800/60 flex justify-between text-[11px] text-emerald-400">
            <span>Bypass Policy: BLOCKED</span>
            <span>100% Routed</span>
          </div>
        </div>
      </div>

      {/* 3. Sub-Navigation Tabs */}
      <div className="flex flex-wrap items-center gap-2 border-b border-gray-800 pb-2">
        <button
          type="button"
          onClick={() => setActiveTab('overview')}
          className={`px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ${
            activeTab === 'overview' ? 'bg-emerald-600 text-white font-semibold' : 'text-gray-400 hover:text-white hover:bg-gray-800'
          }`}
        >
          🌐 Infrastructure & Environments ({environments.length})
        </button>
        <button
          type="button"
          onClick={() => setActiveTab('executions')}
          className={`px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ${
            activeTab === 'executions' ? 'bg-emerald-600 text-white font-semibold' : 'text-gray-400 hover:text-white hover:bg-gray-800'
          }`}
        >
          🚀 Execution Sessions Monitor ({executions.length})
        </button>
        <button
          type="button"
          onClick={() => setActiveTab('profiles')}
          className={`px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ${
            activeTab === 'profiles' ? 'bg-emerald-600 text-white font-semibold' : 'text-gray-400 hover:text-white hover:bg-gray-800'
          }`}
        >
          🎭 Agent Runtime Profiles ({profiles.length})
        </button>
        <button
          type="button"
          onClick={() => setActiveTab('gateway')}
          className={`px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ${
            activeTab === 'gateway' ? 'bg-emerald-600 text-white font-semibold' : 'text-gray-400 hover:text-white hover:bg-gray-800'
          }`}
        >
          🛡️ 9Router Model Gateway & Console ({routes.length} Routes)
        </button>
        <button
          type="button"
          onClick={() => setActiveTab('tools')}
          className={`px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ${
            activeTab === 'tools' ? 'bg-emerald-600 text-white font-semibold' : 'text-gray-400 hover:text-white hover:bg-gray-800'
          }`}
        >
          🔧 Controlled Tool Registry ({tools.length})
        </button>
        <button
          type="button"
          onClick={() => setActiveTab('dispatch')}
          className={`px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ${
            activeTab === 'dispatch' ? 'bg-emerald-600 text-white font-semibold' : 'text-gray-400 hover:text-white hover:bg-gray-800'
          }`}
        >
          🎯 Dispatch & Precondition Verification
        </button>
      </div>

      {/* 4. Tab Contents */}

      {/* Tab: Overview & Environments */}
      {activeTab === 'overview' && (
        <div className="space-y-6">
          <div className="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <div className="px-5 py-3 border-b border-gray-800 flex justify-between items-center bg-gray-950/50">
              <h2 className="text-sm font-semibold text-white">Configured Hermes Runtime Environments</h2>
              <span className="text-xs text-gray-400">Authoritative node topology for workforce agent isolation</span>
            </div>
            <div className="divide-y divide-gray-800">
              {environments.map((env) => (
                <div key={env.id} className="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-gray-800/30">
                  <div className="space-y-1">
                    <div className="flex items-center gap-2">
                      <span className="text-xs font-mono font-bold text-emerald-400 bg-emerald-950/40 px-2 py-0.5 rounded border border-emerald-800">
                        {env.environment_code}
                      </span>
                      <span className="text-sm font-bold text-white">{env.name}</span>
                      <span className="text-xs text-gray-400">({env.runtime_type})</span>
                    </div>
                    <p className="text-xs font-mono text-gray-400">{env.endpoint_url}</p>
                  </div>

                  <div className="flex items-center gap-4 text-xs">
                    <div className="text-right">
                      <div className="text-gray-400">Concurrency Limit</div>
                      <div className="text-white font-mono font-semibold">{env.concurrency_limit} concurrent</div>
                    </div>
                    <div className="text-right">
                      <div className="text-gray-400">Latency</div>
                      <div className="text-emerald-400 font-mono font-semibold">{env.latency_ms} ms</div>
                    </div>
                    <span
                      className={`px-2.5 py-1 text-xs font-semibold rounded-lg border ${
                        env.status === 'connected'
                          ? 'bg-emerald-950/60 text-emerald-300 border-emerald-700'
                          : 'bg-amber-950/60 text-amber-300 border-amber-700'
                      }`}
                    >
                      {env.status.toUpperCase()}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Architectural Constraints Reminder */}
          <div className="bg-blue-950/20 border border-blue-900/60 rounded-xl p-4 text-xs text-blue-300 space-y-1">
            <div className="font-bold flex items-center gap-2">
              <span>ℹ️</span> Architectural Invariant: Sole Model Gateway Enforcement
            </div>
            <p>
              9Router operates as the sole model gateway for all AI Agents at MCE AI Office. Direct model provider connections, alternate gateways,
              and unsanctioned token egress are strictly forbidden and blocked by policy. All agent tool execution occurs within bounded Hermes runtime sandboxes.
            </p>
          </div>
        </div>
      )}

      {/* Tab: Executions */}
      {activeTab === 'executions' && (
        <div className="space-y-4">
          <div className="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <div className="px-5 py-3 border-b border-gray-800 flex justify-between items-center bg-gray-950/50">
              <h2 className="text-sm font-semibold text-white">Execution Sessions Log ({executions.length})</h2>
              <span className="text-xs text-gray-400">Click any execution session to inspect full event & tool invocation audit</span>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-gray-950/60 text-gray-400 border-b border-gray-800">
                  <tr>
                    <th className="py-2.5 px-4 font-semibold">UUID / Session</th>
                    <th className="py-2.5 px-4 font-semibold">Task</th>
                    <th className="py-2.5 px-4 font-semibold">Executing Agent</th>
                    <th className="py-2.5 px-4 font-semibold">Status</th>
                    <th className="py-2.5 px-4 font-semibold">Mode</th>
                    <th className="py-2.5 px-4 font-semibold">Tokens</th>
                    <th className="py-2.5 px-4 font-semibold">Cost (IDR)</th>
                    <th className="py-2.5 px-4 font-semibold">Dispatched At</th>
                    <th className="py-2.5 px-4 font-semibold text-right">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-800">
                  {executions.length === 0 ? (
                    <tr>
                      <td colSpan={9} className="py-8 text-center text-gray-500">
                        No execution sessions found. Dispatch an execution from the Dispatch tab.
                      </td>
                    </tr>
                  ) : (
                    executions.map((s) => (
                      <tr key={s.id} className="hover:bg-gray-800/40 transition-colors">
                        <td className="py-3 px-4 font-mono font-medium text-white">
                          <span className="text-emerald-400">#{s.id}</span>
                          <span className="text-gray-500 text-[10px] block">{s.session_uuid.slice(0, 16)}...</span>
                        </td>
                        <td className="py-3 px-4">
                          <span className="font-semibold text-gray-200">{s.task_code || `Task #${s.task_id}`}</span>
                          <span className="text-gray-500 block text-[11px] truncate max-w-[180px]">{s.task_title}</span>
                        </td>
                        <td className="py-3 px-4">
                          <span className="text-cyan-300 font-semibold">{s.agent_name || `Agent #${s.agent_id}`}</span>
                          <span className="text-gray-500 block text-[10px]">{s.agent_code}</span>
                        </td>
                        <td className="py-3 px-4">
                          <span className={`px-2 py-0.5 rounded text-[11px] border ${EXEC_STATUS_COLORS[s.status] || 'bg-gray-800 text-gray-300'}`}>
                            {s.status}
                          </span>
                        </td>
                        <td className="py-3 px-4">
                          <span className="text-gray-400 uppercase font-mono">{s.execution_mode}</span>
                        </td>
                        <td className="py-3 px-4 font-mono text-gray-300">{(s.tokens_used || 0).toLocaleString()}</td>
                        <td className="py-3 px-4 font-mono text-emerald-400">Rp {(s.estimated_cost_idr || 0).toLocaleString('id-ID')}</td>
                        <td className="py-3 px-4 text-gray-400 font-mono text-[11px]">{s.dispatched_at?.split(' ')[1] || s.dispatched_at}</td>
                        <td className="py-3 px-4 text-right">
                          <button
                            type="button"
                            onClick={() => handleInspectSession(s)}
                            className="px-2.5 py-1 text-xs font-semibold bg-gray-800 hover:bg-emerald-600 hover:text-white text-gray-200 border border-gray-700 rounded transition-colors"
                          >
                            Inspect Audit
                          </button>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>

          {/* Session Detail Drawer / Modal */}
          {selectedSession && (
            <div className="bg-gray-900 border border-gray-800 rounded-xl p-5 space-y-4">
              <div className="flex justify-between items-start border-b border-gray-800 pb-3">
                <div>
                  <h3 className="text-sm font-bold text-white flex items-center gap-2">
                    <span>🔍</span> Execution Session Audit: {selectedSession.session_uuid}
                  </h3>
                  <p className="text-xs text-gray-400 mt-0.5">
                    Task: {selectedSession.task_code || selectedSession.task_id} • Executing Agent: {selectedSession.agent_name || selectedSession.agent_id}
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() => setSelectedSession(null)}
                  className="text-gray-400 hover:text-white text-sm font-bold px-2 py-1 bg-gray-800 rounded"
                >
                  ✕ Close
                </button>
              </div>

              {loadingSessionDetail ? (
                <div className="py-8 text-center text-gray-400">Loading chronological execution events...</div>
              ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {/* Events Timeline */}
                  <div className="bg-gray-950/60 border border-gray-800/80 rounded-lg p-3 space-y-2">
                    <h4 className="text-xs font-bold text-gray-300 uppercase tracking-wider">Chronological Execution Events</h4>
                    <div className="space-y-2 max-h-[300px] overflow-y-auto pr-1">
                      {(selectedSession.events || []).length === 0 ? (
                        <div className="text-xs text-gray-500 py-3 text-center">No recorded events for this session.</div>
                      ) : (
                        selectedSession.events?.map((ev) => (
                          <div key={ev.id} className="text-xs p-2 bg-gray-900/60 rounded border border-gray-800/60 space-y-1">
                            <div className="flex justify-between items-center">
                              <span className="font-mono font-semibold text-emerald-400">{ev.event_type}</span>
                              <span className="text-[10px] text-gray-500 font-mono">{ev.recorded_at}</span>
                            </div>
                            <span className="text-[11px] text-gray-400 block font-mono break-all">{ev.event_payload_json}</span>
                          </div>
                        ))
                      )}
                    </div>
                  </div>

                  {/* Sandbox Tool Invocations */}
                  <div className="bg-gray-950/60 border border-gray-800/80 rounded-lg p-3 space-y-2">
                    <h4 className="text-xs font-bold text-gray-300 uppercase tracking-wider">Sandbox Tool Invocations</h4>
                    <div className="space-y-2 max-h-[300px] overflow-y-auto pr-1">
                      {(selectedSession.tool_invocations || []).length === 0 ? (
                        <div className="text-xs text-gray-500 py-3 text-center">No tool invocations recorded.</div>
                      ) : (
                        selectedSession.tool_invocations?.map((ti) => (
                          <div key={ti.id} className="text-xs p-2 bg-gray-900/60 rounded border border-gray-800/60 space-y-1">
                            <div className="flex justify-between items-center">
                              <span className="font-mono font-bold text-cyan-400">{ti.tool_code}</span>
                              <span className="text-[10px] text-gray-400 font-mono">{ti.execution_duration_ms} ms</span>
                            </div>
                            <div className="flex justify-between items-center text-[11px]">
                              <span className="text-gray-400">Status: {ti.status}</span>
                              <span className="text-emerald-400 font-semibold">{ti.is_authorized ? '✓ Authorized' : '✗ Blocked'}</span>
                            </div>
                            {ti.output_payload_json && (
                              <pre className="text-[10px] bg-black/40 text-gray-300 p-1.5 rounded overflow-x-auto">
                                {ti.output_payload_json}
                              </pre>
                            )}
                          </div>
                        ))
                      )}
                    </div>
                  </div>
                </div>
              )}
            </div>
          )}
        </div>
      )}

      {/* Tab: Runtime Profiles */}
      {activeTab === 'profiles' && (
        <div className="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
          <div className="px-5 py-3 border-b border-gray-800 flex justify-between items-center bg-gray-950/50">
            <h2 className="text-sm font-semibold text-white">Agent Runtime Profiles ({profiles.length})</h2>
            <span className="text-xs text-gray-400">Isolation, tool limits, and model routing bindings per workforce role</span>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
            {profiles.map((p) => (
              <div key={p.id} className="bg-gray-950 border border-gray-800 rounded-lg p-4 space-y-3 flex flex-col justify-between">
                <div>
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-mono font-bold text-purple-400 bg-purple-950/40 px-2 py-0.5 rounded border border-purple-800">
                      {p.profile_code}
                    </span>
                    <span className="text-xs text-gray-400">Max {p.max_execution_duration_sec}s</span>
                  </div>
                  <h3 className="text-sm font-bold text-white mt-2">{p.name}</h3>
                  <div className="mt-2 text-xs text-gray-400 space-y-1">
                    <div>
                      Routing: <span className="text-emerald-400 font-mono">{p.routing_profile_code}</span>
                    </div>
                    <div>
                      Primary Model: <span className="text-cyan-400 font-mono">{p.primary_model}</span>
                    </div>
                    <div>
                      Filesystem Sandbox: <span className="text-gray-300 uppercase font-mono">{p.sandbox_filesystem_policy}</span>
                    </div>
                    <div>
                      Network Policy: <span className="text-gray-300 uppercase font-mono">{p.network_access_policy}</span>
                    </div>
                  </div>
                </div>

                <div className="pt-2 border-t border-gray-800/80 flex justify-between items-center text-xs">
                  <span className="text-gray-500">Max Tool Calls: {p.max_tool_invocations}</span>
                  <span className={p.requires_human_oversight ? 'text-amber-400 font-semibold' : 'text-emerald-400'}>
                    {p.requires_human_oversight ? '⚠️ Human Oversight' : '✓ Autonomous'}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Tab: 9Router Model Gateway & Console */}
      {activeTab === 'gateway' && (
        <div className="space-y-6">
          {/* Routing Profiles Table */}
          <div className="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <div className="px-5 py-3 border-b border-gray-800 flex justify-between items-center bg-gray-950/50">
              <h2 className="text-sm font-semibold text-white">9Router Model Routing Profiles ({routes.length})</h2>
              <span className="text-xs text-emerald-400 font-semibold">Sole Model Gateway Enforced</span>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-gray-950/60 text-gray-400 border-b border-gray-800">
                  <tr>
                    <th className="py-2.5 px-4 font-semibold">Route Code</th>
                    <th className="py-2.5 px-4 font-semibold">Name</th>
                    <th className="py-2.5 px-4 font-semibold">Primary Model</th>
                    <th className="py-2.5 px-4 font-semibold">Fallback Model</th>
                    <th className="py-2.5 px-4 font-semibold">Context Window</th>
                    <th className="py-2.5 px-4 font-semibold">Strategy</th>
                    <th className="py-2.5 px-4 font-semibold">Cost / 1k In/Out (IDR)</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-800">
                  {routes.map((r) => (
                    <tr key={r.id} className="hover:bg-gray-800/40">
                      <td className="py-3 px-4 font-mono font-bold text-emerald-400">{r.profile_code}</td>
                      <td className="py-3 px-4 text-white font-medium">{r.name}</td>
                      <td className="py-3 px-4 font-mono text-cyan-300">{r.primary_model}</td>
                      <td className="py-3 px-4 font-mono text-gray-400">{r.fallback_model || '—'}</td>
                      <td className="py-3 px-4 font-mono text-gray-300">{r.context_window_limit.toLocaleString()}</td>
                      <td className="py-3 px-4 font-mono uppercase text-gray-400">{r.routing_strategy}</td>
                      <td className="py-3 px-4 font-mono text-gray-300">
                        Rp {r.cost_per_1k_input} / Rp {r.cost_per_1k_output}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          {/* Interactive Gateway Test Console */}
          <div className="bg-gray-900 border border-gray-800 rounded-xl p-5 space-y-4">
            <h3 className="text-sm font-bold text-white flex items-center gap-2">
              <span>🧪</span> 9Router Live Model Completion Test Console
            </h3>
            <p className="text-xs text-gray-400">
              Verify prompt routing and token accounting via 9Router. Requests are strictly routed through the gateway; direct provider bypass is blocked.
            </p>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label className="block text-xs font-semibold text-gray-300 mb-1">Target Model Route</label>
                <select
                  value={testProfileCode}
                  onChange={(e) => setTestProfileCode(e.target.value)}
                  className="w-full bg-gray-950 border border-gray-700 rounded-lg px-3 py-2 text-xs text-white"
                >
                  {routes.map((r) => (
                    <option key={r.id} value={r.profile_code}>
                      {r.profile_code} — {r.primary_model}
                    </option>
                  ))}
                </select>
              </div>

              <div className="md:col-span-2">
                <label className="block text-xs font-semibold text-gray-300 mb-1">Prompt</label>
                <input
                  type="text"
                  value={testPrompt}
                  onChange={(e) => setTestPrompt(e.target.value)}
                  placeholder="Enter test prompt..."
                  className="w-full bg-gray-950 border border-gray-700 rounded-lg px-3 py-2 text-xs text-white"
                />
              </div>
            </div>

            <div className="flex justify-end">
              <button
                type="button"
                onClick={handleTestModel}
                disabled={testingModel}
                className="px-4 py-2 text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition-colors disabled:opacity-50"
              >
                {testingModel ? 'Routing via 9Router...' : 'Dispatch Completion Request'}
              </button>
            </div>

            {testResult !== null && (
              <div className="bg-black/60 border border-gray-800 rounded-lg p-3 space-y-1">
                <span className="text-[11px] font-bold text-emerald-400 uppercase tracking-wider">9Router Gateway Response:</span>
                <pre className="text-xs font-mono text-gray-300 overflow-x-auto p-2 bg-gray-950 rounded">
                  {JSON.stringify(testResult, null, 2)}
                </pre>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Tab: Controlled Tools */}
      {activeTab === 'tools' && (
        <div className="space-y-4">
          <div className="flex justify-between items-center">
            <h2 className="text-sm font-semibold text-white">Controlled Sandbox Tools ({tools.length})</h2>
            <button
              type="button"
              onClick={() => setIsNewToolModalOpen(true)}
              className="px-3 py-1.5 text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition-colors"
            >
              + Register New Tool
            </button>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {tools.map((t) => (
              <div key={t.id} className="bg-gray-900 border border-gray-800 rounded-xl p-4 flex flex-col justify-between space-y-3">
                <div>
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-mono font-bold text-cyan-400 bg-cyan-950/40 px-2 py-0.5 rounded border border-cyan-800">
                      {t.tool_code}
                    </span>
                    <span className={`px-2 py-0.5 rounded text-[10px] font-bold border uppercase ${RISK_BADGES[t.risk_level] || ''}`}>
                      {t.risk_level} RISK
                    </span>
                  </div>
                  <h3 className="text-sm font-bold text-white mt-2">{t.name}</h3>
                  <p className="text-xs text-gray-400 mt-1 line-clamp-2">{t.description}</p>
                </div>

                <div className="pt-2 border-t border-gray-800/80 flex justify-between items-center text-xs">
                  <span className="text-gray-500 uppercase font-mono">{t.category}</span>
                  <span className={t.requires_human_approval ? 'text-rose-400 font-bold' : 'text-emerald-400'}>
                    {t.requires_human_approval ? '🔒 Approval Required' : '✓ Auto-Authorized'}
                  </span>
                </div>
              </div>
            ))}
          </div>

          {/* New Tool Modal */}
          {isNewToolModalOpen && (
            <div className="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
              <div className="bg-gray-900 border border-gray-800 rounded-xl p-6 w-full max-w-md space-y-4">
                <div className="flex justify-between items-center">
                  <h3 className="text-base font-bold text-white">Register Controlled Tool</h3>
                  <button type="button" onClick={() => setIsNewToolModalOpen(false)} className="text-gray-400 hover:text-white">
                    ✕
                  </button>
                </div>

                <form onSubmit={handleCreateTool} className="space-y-3">
                  <div>
                    <label className="block text-xs font-semibold text-gray-300 mb-1">Tool Code</label>
                    <input
                      type="text"
                      value={newTool.tool_code}
                      onChange={(e) => setNewTool({ ...newTool, tool_code: e.target.value })}
                      placeholder="e.g. TOOL-LOG-SCAN"
                      required
                      className="w-full bg-gray-950 border border-gray-700 rounded-lg px-3 py-2 text-xs text-white"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-gray-300 mb-1">Tool Name</label>
                    <input
                      type="text"
                      value={newTool.name}
                      onChange={(e) => setNewTool({ ...newTool, name: e.target.value })}
                      placeholder="e.g. Runtime Log Scanner"
                      required
                      className="w-full bg-gray-950 border border-gray-700 rounded-lg px-3 py-2 text-xs text-white"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-gray-300 mb-1">Description</label>
                    <textarea
                      value={newTool.description}
                      onChange={(e) => setNewTool({ ...newTool, description: e.target.value })}
                      placeholder="Describe tool purpose and capability..."
                      rows={2}
                      className="w-full bg-gray-950 border border-gray-700 rounded-lg px-3 py-2 text-xs text-white"
                    />
                  </div>

                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="block text-xs font-semibold text-gray-300 mb-1">Category</label>
                      <select
                        value={newTool.category}
                        onChange={(e) => setNewTool({ ...newTool, category: e.target.value })}
                        className="w-full bg-gray-950 border border-gray-700 rounded-lg px-3 py-2 text-xs text-white"
                      >
                        <option value="code">Code</option>
                        <option value="audit">Audit</option>
                        <option value="data">Data</option>
                        <option value="system">System</option>
                        <option value="ai_hardware">AI Hardware</option>
                      </select>
                    </div>

                    <div>
                      <label className="block text-xs font-semibold text-gray-300 mb-1">Risk Level</label>
                      <select
                        value={newTool.risk_level}
                        onChange={(e) => setNewTool({ ...newTool, risk_level: e.target.value })}
                        className="w-full bg-gray-950 border border-gray-700 rounded-lg px-3 py-2 text-xs text-white"
                      >
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                      </select>
                    </div>
                  </div>

                  <div className="flex items-center gap-2 pt-2">
                    <input
                      type="checkbox"
                      id="approvalReq"
                      checked={newTool.requires_human_approval === 1}
                      onChange={(e) => setNewTool({ ...newTool, requires_human_approval: e.target.checked ? 1 : 0 })}
                      className="rounded border-gray-700 bg-gray-950 text-emerald-500 focus:ring-0"
                    />
                    <label htmlFor="approvalReq" className="text-xs text-gray-300">
                      Requires explicit human approval token before execution
                    </label>
                  </div>

                  <div className="flex justify-end gap-2 pt-3 border-t border-gray-800">
                    <button
                      type="button"
                      onClick={() => setIsNewToolModalOpen(false)}
                      className="px-3 py-1.5 text-xs text-gray-400 hover:text-white"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      className="px-4 py-1.5 text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg"
                    >
                      Save Tool
                    </button>
                  </div>
                </form>
              </div>
            </div>
          )}
        </div>
      )}

      {/* Tab: Dispatch Console */}
      {activeTab === 'dispatch' && (
        <div className="bg-gray-900 border border-gray-800 rounded-xl p-5 space-y-5">
          <div>
            <h2 className="text-sm font-bold text-white flex items-center gap-2">
              <span>🎯</span> Task Execution Dispatcher & Precondition Gate
            </h2>
            <p className="text-xs text-gray-400 mt-1">
              Evaluates all 15 execution preconditions (task executable state, agent active state, assignment & acceptance, availability schedule, DoA boundary, runtime mapping, 9Router profile) before authorizing dispatch to Hermes.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-gray-300 mb-1">Target Task ID</label>
              <input
                type="number"
                value={dispatchTaskId}
                onChange={(e) => setDispatchTaskId(Number(e.target.value))}
                className="w-full bg-gray-950 border border-gray-700 rounded-lg px-3 py-2 text-xs text-white"
              />
              <span className="text-[11px] text-gray-500">e.g. Task #2 (TSK-2026-002) or any active assigned task</span>
            </div>

            <div>
              <label className="block text-xs font-semibold text-gray-300 mb-1">Executing Workforce Agent</label>
              <select
                value={dispatchAgentId}
                onChange={(e) => setDispatchAgentId(Number(e.target.value))}
                className="w-full bg-gray-950 border border-gray-700 rounded-lg px-3 py-2 text-xs text-white"
              >
                {VERIFIED_AGENTS.map((a) => (
                  <option key={a.id} value={a.id}>
                    {a.code} — {a.name}
                  </option>
                ))}
              </select>
              <span className="text-[11px] text-gray-500">Verified workforce agents with provisioned runtime profiles</span>
            </div>
          </div>

          <div className="flex gap-3">
            <button
              type="button"
              onClick={handleValidateReadiness}
              disabled={validating}
              className="px-4 py-2 text-xs font-semibold bg-gray-800 hover:bg-gray-700 text-gray-200 border border-gray-700 rounded-lg transition-colors disabled:opacity-50"
            >
              {validating ? 'Evaluating 12 Pre-Dispatch Checks...' : 'Validate Agent Readiness'}
            </button>
            <button
              type="button"
              onClick={handleDispatchExecution}
              disabled={dispatching}
              className="px-5 py-2 text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition-colors disabled:opacity-50 flex items-center gap-2"
            >
              <span>🚀</span>
              {dispatching ? 'Authorizing & Dispatching...' : 'Dispatch Execution to Hermes'}
            </button>
          </div>

          {validationResult && (
            <div className="bg-black/60 border border-gray-800 rounded-lg p-4 space-y-2">
              <div className="flex items-center justify-between">
                <span className="text-xs font-bold text-white">Agent Pre-Dispatch Verification Result</span>
                <span
                  className={`px-2 py-0.5 rounded text-xs font-bold ${
                    validationResult.ready ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-red-950 text-red-400 border border-red-800'
                  }`}
                >
                  {validationResult.ready ? '✓ 12-STEP READINESS VERIFIED' : '✗ READINESS BLOCKED'}
                </span>
              </div>
              <pre className="text-xs font-mono text-gray-300 p-2 bg-gray-950 rounded overflow-x-auto">
                {JSON.stringify(validationResult, null, 2)}
              </pre>
            </div>
          )}
        </div>
      )}
    </div>
  );
};
