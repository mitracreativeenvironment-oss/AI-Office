import React, { useState, useEffect, useCallback } from 'react';
import { taskService, TaskFilterParams } from '../services/taskService';
import type {
  WorkforceTask,
  TaskMetrics,
  TaskDelegation,
  TaskCollaborationGroup,
  TaskDeliverable,
  TaskBlocker,
  TaskStatus,
} from '../types/tasks';

interface TaskManagementViewProps {
  onNotify?: (message: string, type?: 'info' | 'success' | 'warning' | 'error') => void;
}

const STATUS_COLORS: Record<string, string> = {
  draft: 'bg-gray-800 text-gray-300 border-gray-600',
  proposed: 'bg-purple-950/50 text-purple-300 border-purple-800',
  awaiting_approval: 'bg-amber-950/50 text-amber-300 border-amber-800',
  approved: 'bg-emerald-950/50 text-emerald-300 border-emerald-800',
  ready: 'bg-blue-950/50 text-blue-300 border-blue-800',
  assigned: 'bg-cyan-950/50 text-cyan-300 border-cyan-800',
  awaiting_acceptance: 'bg-indigo-950/50 text-indigo-300 border-indigo-800',
  accepted: 'bg-teal-950/50 text-teal-300 border-teal-800',
  in_progress: 'bg-green-950/60 text-green-300 border-green-700 font-semibold',
  blocked: 'bg-red-950/80 text-red-300 border-red-700 animate-pulse font-semibold',
  under_review: 'bg-amber-950/60 text-amber-300 border-amber-700',
  revision_required: 'bg-orange-950/60 text-orange-300 border-orange-700',
  verified: 'bg-emerald-950/60 text-emerald-300 border-emerald-600',
  completed: 'bg-emerald-900/40 text-emerald-400 border-emerald-700',
  suspended: 'bg-gray-800 text-gray-400 border-gray-700',
  cancelled: 'bg-red-950/30 text-red-400 border-red-900',
  reopened: 'bg-blue-950/60 text-blue-300 border-blue-700',
};

const PRIORITY_BADGES: Record<string, string> = {
  low: 'text-gray-400 bg-gray-900 border-gray-700',
  medium: 'text-blue-400 bg-blue-950/40 border-blue-800',
  high: 'text-amber-400 bg-amber-950/40 border-amber-800',
  critical: 'text-rose-400 bg-rose-950/60 border-rose-800 font-bold',
  strategic: 'text-purple-400 bg-purple-950/60 border-purple-800 font-bold',
};

const VERIFIED_AGENTS = [
  { id: 1, code: 'AGT-DIR-001', name: 'Director AI' },
  { id: 2, code: 'AGT-TECH-001', name: 'Tech Head AI' },
  { id: 3, code: 'AGT-SRV-001', name: 'Services Head AI' },
  { id: 4, code: 'AGT-ACAD-001', name: 'Academy Head AI' },
  { id: 5, code: 'AGT-HW-001', name: 'Hardware Head AI' },
  { id: 6, code: 'AGT-DEV-001', name: 'Dev AI' },
];

export const TaskManagementView: React.FC<TaskManagementViewProps> = ({ onNotify }) => {
  const [activeSubTab, setActiveSubTab] = useState<'registry' | 'kanban' | 'delegations' | 'collaborations'>('registry');
  const [tasks, setTasks] = useState<WorkforceTask[]>([]);
  const [metrics, setMetrics] = useState<TaskMetrics | null>(null);
  const [delegations, setDelegations] = useState<TaskDelegation[]>([]);
  const [collaborations, setCollaborations] = useState<TaskCollaborationGroup[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [filters, setFilters] = useState<TaskFilterParams>({ limit: 50, offset: 0 });

  // Inspector Modal State
  const [selectedTask, setSelectedTask] = useState<WorkforceTask | null>(null);
  const [loadingDetail, setLoadingDetail] = useState<boolean>(false);
  const [detailTab, setDetailTab] = useState<'overview' | 'deliverables' | 'review' | 'blockers' | 'dependencies'>('overview');

  // Modals
  const [showCreateTaskModal, setShowCreateTaskModal] = useState<boolean>(false);
  const [showDelegationModal, setShowDelegationModal] = useState<boolean>(false);
  const [showDeliverableModal, setShowDeliverableModal] = useState<boolean>(false);
  const [showBlockerModal, setShowBlockerModal] = useState<boolean>(false);

  // Forms
  const [taskForm, setTaskForm] = useState({
    task_code: '',
    title: '',
    objective: '',
    unit_id: 2,
    category: 'development',
    priority: 'high',
    origin_type: 'division_head',
    accountable_agent_id: 2,
    approval_level: 'routine',
    financial_amount: 0,
    due_date: new Date(Date.now() + 14 * 86400000).toISOString().split('T')[0],
  });

  const [delegationForm, setDelegationForm] = useState({
    task_id: 0,
    delegating_agent_id: 2,
    delegated_agent_id: 6,
    delegated_scope: '',
    authority_limit: 'routine',
    deadline: '',
  });

  const [deliverableForm, setDeliverableForm] = useState({
    title: '',
    deliverable_type: 'code_patch',
    submitting_agent_id: 6,
    description: '',
    artifact_uri: '',
  });

  const [reviewForm, setReviewForm] = useState({
    reviewer_agent_id: 2,
    review_outcome: 'accepted' as 'accepted' | 'revision_required' | 'rejected',
    evaluation_comments: '',
    is_verified_contribution: 1,
  });

  const [blockerForm, setBlockerForm] = useState({
    category: 'system_environment',
    description: '',
    severity: 'high',
    reported_by_agent_id: 2,
  });

  const [resolveBlockerNotes, setResolveBlockerNotes] = useState<string>('');

  const loadData = useCallback(async () => {
    setLoading(true);
    try {
      const [tasksRes, metricsRes, delegRes, collabRes] = await Promise.all([
        taskService.listTasks(filters),
        taskService.getMetrics(),
        taskService.listDelegations(),
        taskService.listCollaborations(),
      ]);
      setTasks(tasksRes.items);
      setMetrics(metricsRes);
      setDelegations(delegRes);
      setCollaborations(collabRes);
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Failed to load task operations data.';
      onNotify?.(msg, 'error');
    } finally {
      setLoading(false);
    }
  }, [filters, onNotify]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  const openTaskInspector = async (taskId: number) => {
    setLoadingDetail(true);
    setDetailTab('overview');
    try {
      const fullTask = await taskService.getTask(taskId);
      setSelectedTask(fullTask);
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Failed to load task details.';
      onNotify?.(msg, 'error');
    } finally {
      setLoadingDetail(false);
    }
  };

  const handleTransition = async (targetStatus: string) => {
    if (!selectedTask) return;
    try {
      const updated = await taskService.transitionTask(selectedTask.id, targetStatus);
      setSelectedTask(updated);
      onNotify?.(`Task ${updated.task_code} moved to ${targetStatus}.`, 'success');
      loadData();
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Lifecycle transition rejected.';
      onNotify?.(msg, 'error');
    }
  };

  const handleCreateTask = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const created = await taskService.createTask({
        ...taskForm,
        unit_id: Number(taskForm.unit_id),
        accountable_agent_id: Number(taskForm.accountable_agent_id),
        financial_amount: Number(taskForm.financial_amount) || null,
      } as Partial<WorkforceTask>);
      onNotify?.(`Task ${created.task_code} created successfully!`, 'success');
      setShowCreateTaskModal(false);
      loadData();
      openTaskInspector(created.id);
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Failed to create task.';
      onNotify?.(msg, 'error');
    }
  };

  const handleCreateDelegation = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const deleg = await taskService.createDelegation({
        ...delegationForm,
        task_id: Number(delegationForm.task_id),
        delegating_agent_id: Number(delegationForm.delegating_agent_id),
        delegated_agent_id: Number(delegatedAgentIdCheck(delegationForm.delegated_agent_id)),
      });
      onNotify?.(`Delegation proposed successfully to Agent #${deleg.delegated_agent_id}.`, 'success');
      setShowDelegationModal(false);
      loadData();
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Delegation creation rejected.';
      onNotify?.(msg, 'error');
    }
  };

  const delegatedAgentIdCheck = (id: number | string) => Number(id);

  const handleAcceptDelegation = async (delegId: number) => {
    try {
      await taskService.acceptDelegation(delegId, 'AGT-DEV-001');
      onNotify?.(`Delegation #${delegId} accepted and scope transferred.`, 'success');
      loadData();
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Failed to accept delegation.';
      onNotify?.(msg, 'error');
    }
  };

  const handleSubmitDeliverable = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedTask) return;
    try {
      await taskService.submitDeliverable(selectedTask.id, {
        ...deliverableForm,
        submitting_agent_id: Number(deliverableForm.submitting_agent_id),
      });
      onNotify?.('Deliverable submitted successfully for independent verification.', 'success');
      setShowDeliverableModal(false);
      openTaskInspector(selectedTask.id);
      loadData();
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Failed to submit deliverable.';
      onNotify?.(msg, 'error');
    }
  };

  const handleSubmitReview = async (deliverableId: number) => {
    if (!selectedTask) return;
    try {
      await taskService.submitReview(selectedTask.id, deliverableId, {
        reviewer_agent_id: Number(reviewForm.reviewer_agent_id),
        review_outcome: reviewForm.review_outcome,
        evaluation_comments: reviewForm.evaluation_comments,
        is_verified_contribution: reviewForm.is_verified_contribution,
      });
      onNotify?.('Quality verification recorded and performance history integrated!', 'success');
      openTaskInspector(selectedTask.id);
      loadData();
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Review submission rejected.';
      onNotify?.(msg, 'error');
    }
  };

  const handleReportBlocker = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedTask) return;
    try {
      await taskService.reportBlocker(selectedTask.id, {
        ...blockerForm,
        reported_by_agent_id: Number(blockerForm.reported_by_agent_id),
      });
      onNotify?.('Blocker reported. Task state automatically transitioned to blocked.', 'warning');
      setShowBlockerModal(false);
      openTaskInspector(selectedTask.id);
      loadData();
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Failed to report blocker.';
      onNotify?.(msg, 'error');
    }
  };

  const handleResolveBlocker = async (blockerId: number) => {
    if (!selectedTask || !resolveBlockerNotes.trim()) {
      onNotify?.('Please provide resolution notes before unblocking.', 'warning');
      return;
    }
    try {
      await taskService.resolveBlocker(blockerId, resolveBlockerNotes);
      onNotify?.('Blocker resolved. Task automatically restored to in_progress!', 'success');
      setResolveBlockerNotes('');
      openTaskInspector(selectedTask.id);
      loadData();
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Failed to resolve blocker.';
      onNotify?.(msg, 'error');
    }
  };

  return (
    <div className="space-y-6">
      {/* 1. Header & Quick Controls */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-900/80 p-5 rounded-xl border border-slate-800 backdrop-blur-sm">
        <div>
          <div className="flex items-center gap-3">
            <span className="p-2.5 rounded-lg bg-blue-500/10 border border-blue-500/30 text-blue-400 text-xl font-bold">
              📋
            </span>
            <div>
              <h2 className="text-xl font-bold text-white tracking-wide">
                AI Workforce Task Control Plane
              </h2>
              <p className="text-xs text-slate-400 mt-0.5">
                17-State Lifecycle State Machine • Delegation Engine • Multi-Agent Collaboration • Independent Review Verification
              </p>
            </div>
          </div>
        </div>

        <div className="flex items-center gap-3">
          <button
            onClick={() => {
              setTaskForm(prev => ({
                ...prev,
                task_code: `TSK-${new Date().getFullYear()}-${Math.floor(100 + Math.random() * 900)}`,
              }));
              setShowCreateTaskModal(true);
            }}
            className="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg font-medium text-xs shadow-lg shadow-blue-500/20 transition-all flex items-center gap-2"
          >
            <span>➕</span> Create Workforce Task
          </button>
          <button
            onClick={() => {
              if (tasks.length > 0) {
                setDelegationForm(prev => ({ ...prev, task_id: tasks[0].id }));
              }
              setShowDelegationModal(true);
            }}
            className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg font-medium text-xs border border-slate-700 transition-all flex items-center gap-2"
          >
            <span>🤝</span> Propose Delegation
          </button>
          <button
            onClick={loadData}
            className="px-3 py-2 bg-slate-800/80 hover:bg-slate-700 text-slate-300 rounded-lg text-xs border border-slate-700 transition-all"
            title="Refresh Data"
          >
            🔄
          </button>
        </div>
      </div>

      {/* 2. Operations Metrics Aggregate Bar */}
      {metrics && (
        <div className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
          <div className="bg-slate-900/60 p-3.5 rounded-xl border border-slate-800/80">
            <div className="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Total Tasks</div>
            <div className="text-2xl font-bold text-white mt-1">{metrics.total}</div>
            <div className="text-[10px] text-slate-500 mt-0.5">In Task Control Plane</div>
          </div>
          <div className="bg-slate-900/60 p-3.5 rounded-xl border border-blue-900/30">
            <div className="text-[11px] font-medium text-blue-400 uppercase tracking-wider">Ready / Assigned</div>
            <div className="text-2xl font-bold text-blue-300 mt-1">{metrics.ready + metrics.assigned}</div>
            <div className="text-[10px] text-slate-500 mt-0.5">Ready for Execution</div>
          </div>
          <div className="bg-slate-900/60 p-3.5 rounded-xl border border-green-900/30">
            <div className="text-[11px] font-medium text-green-400 uppercase tracking-wider">In Progress</div>
            <div className="text-2xl font-bold text-green-400 mt-1">{metrics.in_progress}</div>
            <div className="text-[10px] text-slate-500 mt-0.5">Active Agent Execution</div>
          </div>
          <div className="bg-slate-900/60 p-3.5 rounded-xl border border-amber-900/30">
            <div className="text-[11px] font-medium text-amber-400 uppercase tracking-wider">Under Review</div>
            <div className="text-2xl font-bold text-amber-400 mt-1">{metrics.under_review}</div>
            <div className="text-[10px] text-slate-500 mt-0.5">Awaiting QA & Signoff</div>
          </div>
          <div className={`p-3.5 rounded-xl border ${metrics.blocked > 0 ? 'bg-red-950/40 border-red-800/60' : 'bg-slate-900/60 border-slate-800/80'}`}>
            <div className={`text-[11px] font-medium uppercase tracking-wider ${metrics.blocked > 0 ? 'text-red-400' : 'text-slate-400'}`}>
              Blocked Tasks
            </div>
            <div className={`text-2xl font-bold mt-1 ${metrics.blocked > 0 ? 'text-red-400 animate-pulse' : 'text-slate-300'}`}>
              {metrics.blocked}
            </div>
            <div className="text-[10px] text-slate-500 mt-0.5">Impeded Execution</div>
          </div>
          <div className="bg-slate-900/60 p-3.5 rounded-xl border border-emerald-900/30">
            <div className="text-[11px] font-medium text-emerald-400 uppercase tracking-wider">Completed</div>
            <div className="text-2xl font-bold text-emerald-400 mt-1">{metrics.completed}</div>
            <div className="text-[10px] text-slate-500 mt-0.5">Verified Outputs</div>
          </div>
          <div className="bg-slate-900/60 p-3.5 rounded-xl border border-slate-800/80">
            <div className="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Avg Progress</div>
            <div className="text-2xl font-bold text-cyan-400 mt-1">{metrics.avg_progress}%</div>
            <div className="w-full bg-slate-800 h-1 rounded-full mt-2 overflow-hidden">
              <div className="bg-cyan-500 h-full rounded-full transition-all" style={{ width: `${metrics.avg_progress}%` }} />
            </div>
          </div>
        </div>
      )}

      {/* 3. Sub-Navigation Tabs */}
      <div className="flex border-b border-slate-800 gap-1 text-sm font-medium">
        <button
          onClick={() => setActiveSubTab('registry')}
          className={`px-4 py-2.5 rounded-t-lg transition-all flex items-center gap-2 ${
            activeSubTab === 'registry'
              ? 'bg-slate-800/90 text-blue-400 border-t-2 border-blue-500 font-semibold'
              : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40'
          }`}
        >
          <span>📋</span> Task Registry & Work Packages ({tasks.length})
        </button>
        <button
          onClick={() => setActiveSubTab('kanban')}
          className={`px-4 py-2.5 rounded-t-lg transition-all flex items-center gap-2 ${
            activeSubTab === 'kanban'
              ? 'bg-slate-800/90 text-blue-400 border-t-2 border-blue-500 font-semibold'
              : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40'
          }`}
        >
          <span>📊</span> Visual Kanban State Machine
        </button>
        <button
          onClick={() => setActiveSubTab('delegations')}
          className={`px-4 py-2.5 rounded-t-lg transition-all flex items-center gap-2 ${
            activeSubTab === 'delegations'
              ? 'bg-slate-800/90 text-blue-400 border-t-2 border-blue-500 font-semibold'
              : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40'
          }`}
        >
          <span>🤝</span> Delegation Register ({delegations.length})
        </button>
        <button
          onClick={() => setActiveSubTab('collaborations')}
          className={`px-4 py-2.5 rounded-t-lg transition-all flex items-center gap-2 ${
            activeSubTab === 'collaborations'
              ? 'bg-slate-800/90 text-blue-400 border-t-2 border-blue-500 font-semibold'
              : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40'
          }`}
        >
          <span>👥</span> Multi-Agent Collaboration Groups ({collaborations.length})
        </button>
      </div>

      {/* 4. Tab Contents */}
      {loading ? (
        <div className="flex items-center justify-center py-16 text-slate-400 gap-3">
          <span className="animate-spin text-xl">⏳</span> Loading Task Control Plane...
        </div>
      ) : (
        <>
          {/* TAB 1: Task Registry Table */}
          {activeSubTab === 'registry' && (
            <div className="space-y-4">
              {/* Filter controls */}
              <div className="flex flex-wrap items-center gap-3 bg-slate-900/50 p-3 rounded-lg border border-slate-800 text-xs">
                <input
                  type="text"
                  placeholder="Search code, title, objective..."
                  value={filters.search ?? ''}
                  onChange={e => setFilters(prev => ({ ...prev, search: e.target.value }))}
                  className="bg-slate-950 border border-slate-700 px-3 py-1.5 rounded-md text-slate-200 placeholder-slate-500 focus:outline-none focus:border-blue-500"
                />
                <select
                  value={filters.status ?? ''}
                  onChange={e => setFilters(prev => ({ ...prev, status: e.target.value || undefined }))}
                  className="bg-slate-950 border border-slate-700 px-3 py-1.5 rounded-md text-slate-200 focus:outline-none focus:border-blue-500"
                >
                  <option value="">All Statuses (17 States)</option>
                  <option value="draft">Draft</option>
                  <option value="ready">Ready</option>
                  <option value="assigned">Assigned</option>
                  <option value="in_progress">In Progress</option>
                  <option value="under_review">Under Review</option>
                  <option value="blocked">Blocked</option>
                  <option value="verified">Verified</option>
                  <option value="completed">Completed</option>
                </select>
                <select
                  value={filters.priority ?? ''}
                  onChange={e => setFilters(prev => ({ ...prev, priority: e.target.value || undefined }))}
                  className="bg-slate-950 border border-slate-700 px-3 py-1.5 rounded-md text-slate-200 focus:outline-none focus:border-blue-500"
                >
                  <option value="">All Priorities</option>
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="critical">Critical</option>
                  <option value="strategic">Strategic</option>
                </select>
                <select
                  value={filters.category ?? ''}
                  onChange={e => setFilters(prev => ({ ...prev, category: e.target.value || undefined }))}
                  className="bg-slate-950 border border-slate-700 px-3 py-1.5 rounded-md text-slate-200 focus:outline-none focus:border-blue-500"
                >
                  <option value="">All Categories</option>
                  <option value="development">Development</option>
                  <option value="governance">Governance</option>
                  <option value="operations">Operations</option>
                  <option value="research">Research</option>
                  <option value="hardware">Hardware</option>
                </select>
                <button
                  onClick={() => setFilters({ limit: 50, offset: 0 })}
                  className="px-3 py-1.5 bg-slate-800 text-slate-300 rounded hover:bg-slate-700 ml-auto"
                >
                  Reset Filters
                </button>
              </div>

              {/* Table */}
              <div className="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900/60">
                <table className="w-full text-left text-xs text-slate-300">
                  <thead className="bg-slate-950/80 text-slate-400 font-semibold border-b border-slate-800 uppercase tracking-wider text-[11px]">
                    <tr>
                      <th className="py-3 px-4">Code / Title</th>
                      <th className="py-3 px-4">Division / Unit</th>
                      <th className="py-3 px-4">Accountable Agent</th>
                      <th className="py-3 px-4">Category</th>
                      <th className="py-3 px-4">Priority</th>
                      <th className="py-3 px-4">Status (17-State)</th>
                      <th className="py-3 px-4">Progress</th>
                      <th className="py-3 px-4 text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-800/60 font-mono text-xs">
                    {tasks.map(task => (
                      <tr key={task.id} className="hover:bg-slate-800/40 transition-colors">
                        <td className="py-3 px-4 font-sans">
                          <div className="flex items-center gap-2">
                            <span className="font-mono font-bold text-blue-400">{task.task_code}</span>
                            {task.parent_task_id && (
                              <span className="text-[10px] bg-slate-800 px-1.5 py-0.5 rounded text-slate-400 border border-slate-700" title={`Subtask of ${task.parent_task_code}`}>
                                ↳ Subtask
                              </span>
                            )}
                            {task.is_binding_instruction === 1 && (
                              <span className="text-[10px] bg-purple-950/80 px-1.5 py-0.5 rounded text-purple-300 border border-purple-800 font-bold" title="Continuous Board Binding Instruction">
                                ⚖️ Binding
                              </span>
                            )}
                          </div>
                          <div className="font-medium text-slate-200 mt-0.5">{task.title}</div>
                        </td>
                        <td className="py-3 px-4 font-sans text-slate-300">
                          {task.unit_name ?? `Unit #${task.unit_id}`}
                        </td>
                        <td className="py-3 px-4 font-sans">
                          <span className="text-cyan-300 font-medium">
                            {task.accountable_agent_name ?? `Agent #${task.accountable_agent_id}`}
                          </span>
                        </td>
                        <td className="py-3 px-4 font-sans capitalize text-slate-300">
                          {task.category}
                        </td>
                        <td className="py-3 px-4 font-sans">
                          <span className={`px-2 py-0.5 rounded-full border text-[10px] capitalize ${PRIORITY_BADGES[task.priority] ?? 'text-slate-400'}`}>
                            {task.priority}
                          </span>
                        </td>
                        <td className="py-3 px-4 font-sans">
                          <span className={`px-2 py-0.5 rounded border text-[11px] uppercase tracking-wider ${STATUS_COLORS[task.status] ?? 'bg-slate-800 text-slate-400'}`}>
                            {task.status.replace('_', ' ')}
                          </span>
                        </td>
                        <td className="py-3 px-4 font-sans">
                          <div className="flex items-center gap-2">
                            <span className="font-mono text-[11px] text-slate-300">{task.progress_percent}%</span>
                            <div className="w-16 bg-slate-800 h-1.5 rounded-full overflow-hidden">
                              <div
                                className="bg-blue-500 h-full rounded-full"
                                style={{ width: `${task.progress_percent}%` }}
                              />
                            </div>
                          </div>
                        </td>
                        <td className="py-3 px-4 text-right font-sans">
                          <button
                            onClick={() => openTaskInspector(task.id)}
                            className="px-2.5 py-1 bg-blue-600/20 hover:bg-blue-600/40 text-blue-300 rounded border border-blue-500/30 text-xs transition-colors"
                          >
                            Inspect 🔍
                          </button>
                        </td>
                      </tr>
                    ))}
                    {tasks.length === 0 && (
                      <tr>
                        <td colSpan={8} className="py-12 text-center text-slate-500 font-sans">
                          No workforce tasks match the specified filters.
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* TAB 2: Visual Kanban State Machine */}
          {activeSubTab === 'kanban' && (
            <div className="grid grid-cols-1 md:grid-cols-5 gap-3">
              {/* Column 1: Ready / Assigned */}
              <div className="bg-slate-900/60 p-3 rounded-xl border border-slate-800 flex flex-col h-[650px]">
                <div className="flex items-center justify-between pb-2 border-b border-slate-800 mb-3">
                  <span className="text-xs font-bold text-blue-400 uppercase tracking-wider">Ready / Assigned</span>
                  <span className="text-[11px] bg-blue-950 px-2 py-0.5 rounded-full text-blue-300 border border-blue-800">
                    {tasks.filter(t => ['ready', 'assigned', 'draft', 'proposed', 'accepted'].includes(t.status)).length}
                  </span>
                </div>
                <div className="space-y-2.5 overflow-y-auto flex-1 pr-1">
                  {tasks
                    .filter(t => ['ready', 'assigned', 'draft', 'proposed', 'accepted'].includes(t.status))
                    .map(t => (
                      <div
                        key={t.id}
                        onClick={() => openTaskInspector(t.id)}
                        className="bg-slate-950/80 p-3 rounded-lg border border-slate-800 hover:border-blue-500/50 cursor-pointer transition-all space-y-2 shadow-sm"
                      >
                        <div className="flex items-center justify-between">
                          <span className="font-mono text-xs font-bold text-blue-400">{t.task_code}</span>
                          <span className={`px-1.5 py-0.5 rounded text-[10px] uppercase border ${STATUS_COLORS[t.status]}`}>
                            {t.status}
                          </span>
                        </div>
                        <div className="text-xs font-medium text-slate-200 line-clamp-2">{t.title}</div>
                        <div className="flex items-center justify-between text-[11px] text-slate-400 pt-1 border-t border-slate-800/80">
                          <span>👤 {t.accountable_agent_name ?? 'Lead'}</span>
                          <span className={`px-1 rounded text-[10px] ${PRIORITY_BADGES[t.priority]}`}>{t.priority}</span>
                        </div>
                      </div>
                    ))}
                </div>
              </div>

              {/* Column 2: In Progress */}
              <div className="bg-slate-900/60 p-3 rounded-xl border border-green-900/40 flex flex-col h-[650px]">
                <div className="flex items-center justify-between pb-2 border-b border-slate-800 mb-3">
                  <span className="text-xs font-bold text-green-400 uppercase tracking-wider">In Progress</span>
                  <span className="text-[11px] bg-green-950 px-2 py-0.5 rounded-full text-green-300 border border-green-800">
                    {tasks.filter(t => t.status === 'in_progress').length}
                  </span>
                </div>
                <div className="space-y-2.5 overflow-y-auto flex-1 pr-1">
                  {tasks
                    .filter(t => t.status === 'in_progress')
                    .map(t => (
                      <div
                        key={t.id}
                        onClick={() => openTaskInspector(t.id)}
                        className="bg-slate-950/80 p-3 rounded-lg border border-green-900/40 hover:border-green-500/50 cursor-pointer transition-all space-y-2 shadow-sm"
                      >
                        <div className="flex items-center justify-between">
                          <span className="font-mono text-xs font-bold text-green-400">{t.task_code}</span>
                          <span className="text-[10px] font-mono text-slate-400">{t.progress_percent}%</span>
                        </div>
                        <div className="text-xs font-medium text-slate-200 line-clamp-2">{t.title}</div>
                        <div className="w-full bg-slate-800 h-1 rounded-full overflow-hidden">
                          <div className="bg-green-500 h-full rounded-full" style={{ width: `${t.progress_percent}%` }} />
                        </div>
                        <div className="flex items-center justify-between text-[11px] text-slate-400 pt-1 border-t border-slate-800/80">
                          <span>👤 {t.accountable_agent_name ?? 'Lead'}</span>
                          <span className={`px-1 rounded text-[10px] ${PRIORITY_BADGES[t.priority]}`}>{t.priority}</span>
                        </div>
                      </div>
                    ))}
                </div>
              </div>

              {/* Column 3: Under Review */}
              <div className="bg-slate-900/60 p-3 rounded-xl border border-amber-900/40 flex flex-col h-[650px]">
                <div className="flex items-center justify-between pb-2 border-b border-slate-800 mb-3">
                  <span className="text-xs font-bold text-amber-400 uppercase tracking-wider">Under Review</span>
                  <span className="text-[11px] bg-amber-950 px-2 py-0.5 rounded-full text-amber-300 border border-amber-800">
                    {tasks.filter(t => ['under_review', 'revision_required'].includes(t.status)).length}
                  </span>
                </div>
                <div className="space-y-2.5 overflow-y-auto flex-1 pr-1">
                  {tasks
                    .filter(t => ['under_review', 'revision_required'].includes(t.status))
                    .map(t => (
                      <div
                        key={t.id}
                        onClick={() => openTaskInspector(t.id)}
                        className="bg-slate-950/80 p-3 rounded-lg border border-amber-900/40 hover:border-amber-500/50 cursor-pointer transition-all space-y-2 shadow-sm"
                      >
                        <div className="flex items-center justify-between">
                          <span className="font-mono text-xs font-bold text-amber-400">{t.task_code}</span>
                          <span className="text-[10px] text-amber-300 font-bold">🔍 QA Pending</span>
                        </div>
                        <div className="text-xs font-medium text-slate-200 line-clamp-2">{t.title}</div>
                        <div className="flex items-center justify-between text-[11px] text-slate-400 pt-1 border-t border-slate-800/80">
                          <span>👤 {t.accountable_agent_name ?? 'Lead'}</span>
                          <span className="text-[10px] text-slate-500">{t.deliverables?.length ?? 1} deliverable(s)</span>
                        </div>
                      </div>
                    ))}
                </div>
              </div>

              {/* Column 4: Blocked (Alert Column) */}
              <div className="bg-red-950/20 p-3 rounded-xl border border-red-900/60 flex flex-col h-[650px]">
                <div className="flex items-center justify-between pb-2 border-b border-red-900/40 mb-3">
                  <span className="text-xs font-bold text-red-400 uppercase tracking-wider flex items-center gap-1.5">
                    <span className="animate-pulse">⚠️</span> Blocked
                  </span>
                  <span className="text-[11px] bg-red-950 px-2 py-0.5 rounded-full text-red-300 border border-red-800 font-bold">
                    {tasks.filter(t => t.status === 'blocked').length}
                  </span>
                </div>
                <div className="space-y-2.5 overflow-y-auto flex-1 pr-1">
                  {tasks
                    .filter(t => t.status === 'blocked')
                    .map(t => (
                      <div
                        key={t.id}
                        onClick={() => openTaskInspector(t.id)}
                        className="bg-slate-950/90 p-3 rounded-lg border border-red-800 hover:border-red-500 cursor-pointer transition-all space-y-2 shadow-sm"
                      >
                        <div className="flex items-center justify-between">
                          <span className="font-mono text-xs font-bold text-red-400">{t.task_code}</span>
                          <span className="text-[10px] text-red-300 bg-red-950 px-1 py-0.5 rounded border border-red-800">
                            Impeded
                          </span>
                        </div>
                        <div className="text-xs font-medium text-slate-200 line-clamp-2">{t.title}</div>
                        <div className="text-[11px] text-red-400/90 bg-red-950/40 p-1.5 rounded border border-red-900/40 line-clamp-2">
                          ⚠️ Awaiting operational resolution
                        </div>
                      </div>
                    ))}
                  {tasks.filter(t => t.status === 'blocked').length === 0 && (
                    <div className="py-12 text-center text-slate-600 text-xs">
                      No blocked tasks. Operations clear.
                    </div>
                  )}
                </div>
              </div>

              {/* Column 5: Completed / Verified */}
              <div className="bg-slate-900/60 p-3 rounded-xl border border-emerald-900/40 flex flex-col h-[650px]">
                <div className="flex items-center justify-between pb-2 border-b border-slate-800 mb-3">
                  <span className="text-xs font-bold text-emerald-400 uppercase tracking-wider">Completed</span>
                  <span className="text-[11px] bg-emerald-950 px-2 py-0.5 rounded-full text-emerald-300 border border-emerald-800">
                    {tasks.filter(t => ['verified', 'completed'].includes(t.status)).length}
                  </span>
                </div>
                <div className="space-y-2.5 overflow-y-auto flex-1 pr-1">
                  {tasks
                    .filter(t => ['verified', 'completed'].includes(t.status))
                    .map(t => (
                      <div
                        key={t.id}
                        onClick={() => openTaskInspector(t.id)}
                        className="bg-slate-950/80 p-3 rounded-lg border border-emerald-900/40 hover:border-emerald-500/50 cursor-pointer transition-all space-y-2 shadow-sm"
                      >
                        <div className="flex items-center justify-between">
                          <span className="font-mono text-xs font-bold text-emerald-400">{t.task_code}</span>
                          <span className="text-[10px] text-emerald-400 font-bold">100% ✓</span>
                        </div>
                        <div className="text-xs font-medium text-slate-200 line-clamp-2">{t.title}</div>
                        <div className="flex items-center justify-between text-[11px] text-slate-400 pt-1 border-t border-slate-800/80">
                          <span>👤 {t.accountable_agent_name ?? 'Lead'}</span>
                          <span className="text-[10px] text-emerald-500">Verified</span>
                        </div>
                      </div>
                    ))}
                </div>
              </div>
            </div>
          )}

          {/* TAB 3: Delegations Register */}
          {activeSubTab === 'delegations' && (
            <div className="space-y-4">
              <div className="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900/60">
                <table className="w-full text-left text-xs text-slate-300">
                  <thead className="bg-slate-950/80 text-slate-400 font-semibold border-b border-slate-800 uppercase tracking-wider text-[11px]">
                    <tr>
                      <th className="py-3 px-4">Task</th>
                      <th className="py-3 px-4">Delegating Agent (Authority)</th>
                      <th className="py-3 px-4">Delegated Agent</th>
                      <th className="py-3 px-4">Delegated Scope</th>
                      <th className="py-3 px-4">Authority Limit</th>
                      <th className="py-3 px-4">Status</th>
                      <th className="py-3 px-4 text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-800/60 font-sans text-xs">
                    {delegations.map(d => (
                      <tr key={d.id} className="hover:bg-slate-800/40 transition-colors">
                        <td className="py-3 px-4">
                          <div className="font-mono font-bold text-blue-400">{d.task_code}</div>
                          <div className="text-slate-300 text-[11px]">{d.task_title}</div>
                        </td>
                        <td className="py-3 px-4 font-semibold text-purple-300">
                          {d.delegating_agent_name ?? `Agent #${d.delegating_agent_id}`}
                        </td>
                        <td className="py-3 px-4 font-semibold text-cyan-300">
                          {d.delegated_agent_name ?? `Agent #${d.delegated_agent_id}`}
                        </td>
                        <td className="py-3 px-4 text-slate-200 max-w-xs line-clamp-2">
                          {d.delegated_scope}
                        </td>
                        <td className="py-3 px-4 uppercase text-[10px] font-mono text-amber-400">
                          {d.authority_limit}
                        </td>
                        <td className="py-3 px-4">
                          <span className={`px-2 py-0.5 rounded text-[10px] uppercase font-bold ${
                            d.status === 'accepted'
                              ? 'bg-emerald-950 text-emerald-300 border border-emerald-800'
                              : d.status === 'proposed'
                              ? 'bg-amber-950 text-amber-300 border border-amber-800'
                              : 'bg-slate-800 text-slate-400'
                          }`}>
                            {d.status}
                          </span>
                        </td>
                        <td className="py-3 px-4 text-right">
                          {d.status === 'proposed' && (
                            <button
                              onClick={() => handleAcceptDelegation(d.id)}
                              className="px-2.5 py-1 bg-emerald-600/20 hover:bg-emerald-600/40 text-emerald-300 rounded border border-emerald-500/30 text-xs transition-colors"
                            >
                              Accept Delegation ✓
                            </button>
                          )}
                        </td>
                      </tr>
                    ))}
                    {delegations.length === 0 && (
                      <tr>
                        <td colSpan={7} className="py-12 text-center text-slate-500">
                          No active delegations recorded.
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* TAB 4: Multi-Agent Collaboration Groups */}
          {activeSubTab === 'collaborations' && (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {collaborations.map(g => (
                <div key={g.id} className="bg-slate-900/70 p-4 rounded-xl border border-slate-800 space-y-3">
                  <div className="flex items-center justify-between">
                    <div>
                      <span className="font-mono text-xs font-bold text-blue-400 bg-blue-950/60 px-2 py-0.5 rounded border border-blue-800">
                        {g.group_code}
                      </span>
                      <h3 className="text-sm font-bold text-white mt-1">{g.name}</h3>
                    </div>
                    <span className="text-[11px] bg-emerald-950 text-emerald-300 px-2 py-0.5 rounded border border-emerald-800 uppercase font-semibold">
                      {g.status}
                    </span>
                  </div>

                  <p className="text-xs text-slate-300 bg-slate-950/60 p-2.5 rounded border border-slate-800">
                    <span className="text-slate-500 font-bold uppercase text-[10px] block">Shared Objective:</span>
                    {g.shared_objective}
                  </p>

                  <div className="space-y-2 pt-2 border-t border-slate-800">
                    <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-between">
                      <span>Collaborating Agents ({g.members?.length ?? g.members_count ?? 0})</span>
                      <span className="text-cyan-400">Lead: {g.lead_agent_name ?? 'Tech Head AI'}</span>
                    </div>

                    <div className="space-y-1.5">
                      {g.members?.map(m => (
                        <div key={m.id} className="flex items-center justify-between bg-slate-950/40 px-2.5 py-1.5 rounded text-xs border border-slate-800/60">
                          <div className="flex items-center gap-2">
                            <span className="font-semibold text-slate-200">{m.agent_name}</span>
                            <span className="text-[10px] font-mono text-slate-500">({m.agent_code})</span>
                          </div>
                          <span className="text-[10px] bg-slate-800 px-2 py-0.5 rounded text-cyan-300 capitalize font-medium">
                            {m.role}
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
              ))}
              {collaborations.length === 0 && (
                <div className="col-span-2 py-12 text-center text-slate-500">
                  No multi-agent collaboration groups registered.
                </div>
              )}
            </div>
          )}
        </>
      )}

      {/* 5. TASK INSPECTOR SLIDE-OVER / MODAL */}
      {selectedTask && (
        <div className="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex justify-end transition-opacity">
          <div className="w-full max-w-2xl bg-slate-900 border-l border-slate-800 h-full overflow-y-auto p-6 space-y-6 flex flex-col justify-between shadow-2xl">
            {/* Modal Header */}
            <div>
              <div className="flex items-center justify-between border-b border-slate-800 pb-4">
                <div>
                  <div className="flex items-center gap-2">
                    <span className="font-mono text-sm font-bold text-blue-400 bg-blue-950 px-2 py-0.5 rounded border border-blue-800">
                      {selectedTask.task_code}
                    </span>
                    <span className={`px-2 py-0.5 rounded text-xs uppercase font-bold border ${STATUS_COLORS[selectedTask.status]}`}>
                      {selectedTask.status.replace('_', ' ')}
                    </span>
                  </div>
                  <h2 className="text-lg font-bold text-white mt-1.5">{selectedTask.title}</h2>
                </div>
                <button
                  onClick={() => setSelectedTask(null)}
                  className="text-slate-400 hover:text-white text-xl p-1 rounded hover:bg-slate-800"
                >
                  ✕
                </button>
              </div>

              {/* 17-State Visual Stepper */}
              <div className="py-4 border-b border-slate-800">
                <div className="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                  17-State Lifecycle Progression:
                </div>
                <div className="flex items-center gap-1 overflow-x-auto py-1 text-[10px] font-mono">
                  {['draft', 'ready', 'assigned', 'in_progress', 'under_review', 'verified', 'completed'].map((st, idx) => {
                    const isCurrent = selectedTask.status === st;
                    const isPassed = ['completed', 'verified'].includes(selectedTask.status) || idx < 3;
                    return (
                      <React.Fragment key={st}>
                        <span
                          className={`px-2 py-1 rounded text-center whitespace-nowrap ${
                            isCurrent
                              ? 'bg-blue-600 text-white font-bold ring-2 ring-blue-400'
                              : isPassed
                              ? 'bg-slate-800 text-slate-300'
                              : 'bg-slate-950 text-slate-600'
                          }`}
                        >
                          {st}
                        </span>
                        {idx < 6 && <span className="text-slate-600">→</span>}
                      </React.Fragment>
                    );
                  })}
                </div>
              </div>

              {/* Lifecycle Actions Bar */}
              <div className="py-3 border-b border-slate-800">
                <div className="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                  Allowed Lifecycle Transitions:
                </div>
                <div className="flex flex-wrap gap-2">
                  {selectedTask.status === 'draft' && (
                    <button
                      onClick={() => handleTransition('ready')}
                      className="px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-medium rounded shadow transition-all"
                    >
                      Advance to Ready ➔
                    </button>
                  )}
                  {selectedTask.status === 'ready' && (
                    <button
                      onClick={() => handleTransition('assigned')}
                      className="px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-medium rounded shadow transition-all"
                    >
                      Confirm Assignment ➔
                    </button>
                  )}
                  {selectedTask.status === 'assigned' && (
                    <button
                      onClick={() => handleTransition('accepted')}
                      className="px-3 py-1.5 bg-teal-600 hover:bg-teal-500 text-white text-xs font-medium rounded shadow transition-all"
                    >
                      Agent Accepts Task ➔
                    </button>
                  )}
                  {['accepted', 'ready'].includes(selectedTask.status) && (
                    <button
                      onClick={() => handleTransition('in_progress')}
                      className="px-3 py-1.5 bg-green-600 hover:bg-green-500 text-white text-xs font-medium rounded shadow transition-all"
                    >
                      Start Execution (in_progress) ⚡
                    </button>
                  )}
                  {selectedTask.status === 'in_progress' && (
                    <>
                      <button
                        onClick={() => setShowDeliverableModal(true)}
                        className="px-3 py-1.5 bg-amber-600 hover:bg-amber-500 text-white text-xs font-medium rounded shadow transition-all"
                      >
                        Submit Deliverable 📦
                      </button>
                      <button
                        onClick={() => setShowBlockerModal(true)}
                        className="px-3 py-1.5 bg-red-600 hover:bg-red-500 text-white text-xs font-medium rounded shadow transition-all"
                      >
                        Report Blocker ⚠️
                      </button>
                    </>
                  )}
                  {selectedTask.status === 'under_review' && (
                    <button
                      onClick={() => setDetailTab('review')}
                      className="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-medium rounded shadow transition-all"
                    >
                      Conduct Independent Review 🔍
                    </button>
                  )}
                  {selectedTask.status === 'verified' && (
                    <button
                      onClick={() => handleTransition('completed')}
                      className="px-3 py-1.5 bg-emerald-700 hover:bg-emerald-600 text-white text-xs font-medium rounded shadow transition-all"
                    >
                      Formal Task Completion (Sign-off) ✅
                    </button>
                  )}
                </div>
              </div>

              {/* Inspector Sub-Tabs */}
              <div className="flex border-b border-slate-800 gap-1 text-xs font-medium pt-3">
                <button
                  onClick={() => setDetailTab('overview')}
                  className={`px-3 py-2 rounded-t transition-all ${
                    detailTab === 'overview' ? 'bg-slate-800 text-blue-400 font-bold border-b-2 border-blue-500' : 'text-slate-400 hover:text-white'
                  }`}
                >
                  Overview & Subtasks
                </button>
                <button
                  onClick={() => setDetailTab('deliverables')}
                  className={`px-3 py-2 rounded-t transition-all ${
                    detailTab === 'deliverables' ? 'bg-slate-800 text-blue-400 font-bold border-b-2 border-blue-500' : 'text-slate-400 hover:text-white'
                  }`}
                >
                  Deliverables ({selectedTask.deliverables?.length ?? 0})
                </button>
                <button
                  onClick={() => setDetailTab('review')}
                  className={`px-3 py-2 rounded-t transition-all ${
                    detailTab === 'review' ? 'bg-slate-800 text-blue-400 font-bold border-b-2 border-blue-500' : 'text-slate-400 hover:text-white'
                  }`}
                >
                  Quality Verification
                </button>
                <button
                  onClick={() => setDetailTab('blockers')}
                  className={`px-3 py-2 rounded-t transition-all ${
                    detailTab === 'blockers' ? 'bg-slate-800 text-red-400 font-bold border-b-2 border-red-500' : 'text-slate-400 hover:text-white'
                  }`}
                >
                  Blockers ({selectedTask.blockers?.filter(b => b.status === 'active').length ?? 0})
                </button>
              </div>

              {/* Inspector Body */}
              <div className="pt-4 space-y-4">
                {detailTab === 'overview' && (
                  <div className="space-y-4 text-xs">
                    <div className="bg-slate-950 p-3 rounded-lg border border-slate-800 space-y-2">
                      <div className="text-[10px] font-bold text-slate-500 uppercase">Objective</div>
                      <div className="text-slate-200 leading-relaxed">
                        {selectedTask.objective ?? 'No detailed objective provided.'}
                      </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                      <div className="bg-slate-950 p-2.5 rounded border border-slate-800">
                        <span className="text-slate-500 text-[10px] block uppercase font-bold">Accountable Agent</span>
                        <span className="font-semibold text-cyan-400 mt-0.5 block">
                          {selectedTask.accountable_agent_name ?? `Agent #${selectedTask.accountable_agent_id}`}
                        </span>
                      </div>
                      <div className="bg-slate-950 p-2.5 rounded border border-slate-800">
                        <span className="text-slate-500 text-[10px] block uppercase font-bold">Origin Type</span>
                        <span className="font-semibold text-slate-200 capitalize mt-0.5 block">
                          {selectedTask.origin_type.replace('_', ' ')}
                        </span>
                      </div>
                      <div className="bg-slate-950 p-2.5 rounded border border-slate-800">
                        <span className="text-slate-500 text-[10px] block uppercase font-bold">DoA Level</span>
                        <span className="font-semibold text-amber-400 uppercase mt-0.5 block">
                          {selectedTask.approval_level}
                        </span>
                      </div>
                      <div className="bg-slate-950 p-2.5 rounded border border-slate-800">
                        <span className="text-slate-500 text-[10px] block uppercase font-bold">Target Due Date</span>
                        <span className="font-semibold text-slate-300 font-mono mt-0.5 block">
                          {selectedTask.due_date ?? 'Not set'}
                        </span>
                      </div>
                    </div>

                    {/* Subtasks */}
                    <div className="pt-2">
                      <div className="text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                        Subtasks & Work Packages ({selectedTask.subtasks?.length ?? 0})
                      </div>
                      <div className="space-y-2">
                        {selectedTask.subtasks?.map(sub => (
                          <div key={sub.id} className="bg-slate-950 p-2.5 rounded border border-slate-800 flex items-center justify-between">
                            <div>
                              <span className="font-mono text-xs font-bold text-blue-400">{sub.task_code}</span>
                              <div className="text-slate-200 mt-0.5">{sub.title}</div>
                            </div>
                            <span className={`px-2 py-0.5 rounded text-[10px] uppercase border ${STATUS_COLORS[sub.status]}`}>
                              {sub.status}
                            </span>
                          </div>
                        ))}
                        {(!selectedTask.subtasks || selectedTask.subtasks.length === 0) && (
                          <div className="text-slate-500 italic text-[11px]">No subtasks attached to this task.</div>
                        )}
                      </div>
                    </div>
                  </div>
                )}

                {detailTab === 'deliverables' && (
                  <div className="space-y-3 text-xs">
                    <div className="flex justify-between items-center mb-2">
                      <span className="font-bold text-slate-300 uppercase">Submitted Deliverables</span>
                      <button
                        onClick={() => setShowDeliverableModal(true)}
                        className="px-2.5 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded text-[11px] font-medium"
                      >
                        + Submit Artifact
                      </button>
                    </div>

                    {selectedTask.deliverables?.map(del => (
                      <div key={del.id} className="bg-slate-950 p-3 rounded-lg border border-slate-800 space-y-2">
                        <div className="flex items-center justify-between">
                          <div className="flex items-center gap-2">
                            <span className="font-bold text-white text-sm">{del.title}</span>
                            <span className="font-mono text-[10px] bg-slate-800 px-1.5 py-0.5 rounded text-cyan-400 border border-slate-700">
                              v{del.version}
                            </span>
                          </div>
                          <span className={`px-2 py-0.5 rounded text-[10px] uppercase font-bold ${
                            del.review_status === 'accepted' ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-amber-950 text-amber-300 border border-amber-800'
                          }`}>
                            {del.review_status}
                          </span>
                        </div>

                        {del.artifact_uri && (
                          <div className="font-mono text-[11px] text-blue-400 bg-slate-900 p-1.5 rounded truncate">
                            URI: {del.artifact_uri}
                          </div>
                        )}

                        <div className="text-slate-400 text-[11px]">
                          Submitted by: <span className="text-slate-200">{del.submitting_agent_name ?? `Agent #${del.submitting_agent_id}`}</span>
                        </div>

                        {del.reviews && del.reviews.length > 0 && (
                          <div className="pt-2 border-t border-slate-800/80 space-y-1">
                            <span className="text-[10px] font-bold text-slate-500 uppercase">Review Feedback:</span>
                            {del.reviews.map(r => (
                              <div key={r.id} className="bg-slate-900/60 p-2 rounded text-[11px] text-slate-300">
                                <span className="font-bold text-cyan-400">{r.reviewer_name ?? 'Reviewer'}:</span> {r.evaluation_comments}
                              </div>
                            ))}
                          </div>
                        )}
                      </div>
                    ))}
                    {(!selectedTask.deliverables || selectedTask.deliverables.length === 0) && (
                      <div className="py-8 text-center text-slate-500">No deliverables submitted yet.</div>
                    )}
                  </div>
                )}

                {detailTab === 'review' && (
                  <div className="space-y-4 text-xs">
                    <div className="bg-slate-950 p-4 rounded-lg border border-slate-800 space-y-3">
                      <h4 className="font-bold text-slate-200 uppercase tracking-wide">
                        Independent Quality Review Rule
                      </h4>
                      <p className="text-slate-400 text-[11px] leading-relaxed">
                        Strict Zero-Self-Review Enforcement: The executing agent cannot verify their own deliverables. Accepted reviews automatically register the contribution in the agent&apos;s performance history.
                      </p>

                      {selectedTask.deliverables && selectedTask.deliverables.length > 0 ? (
                        <div className="space-y-3 pt-2">
                          <div>
                            <label className="block text-slate-400 mb-1">Select Reviewer Agent</label>
                            <select
                              value={reviewForm.reviewer_agent_id}
                              onChange={e => setReviewForm(prev => ({ ...prev, reviewer_agent_id: Number(e.target.value) }))}
                              className="w-full bg-slate-900 border border-slate-700 rounded p-2 text-white"
                            >
                              {VERIFIED_AGENTS.map(ag => (
                                <option key={ag.id} value={ag.id}>{ag.name} ({ag.code})</option>
                              ))}
                            </select>
                          </div>

                          <div>
                            <label className="block text-slate-400 mb-1">Outcome</label>
                            <select
                              value={reviewForm.review_outcome}
                              onChange={e => setReviewForm(prev => ({ ...prev, review_outcome: e.target.value as 'accepted' | 'revision_required' | 'rejected' }))}
                              className="w-full bg-slate-900 border border-slate-700 rounded p-2 text-white"
                            >
                              <option value="accepted">Accepted (Quality Standard Met)</option>
                              <option value="revision_required">Revision Required</option>
                              <option value="rejected">Rejected</option>
                            </select>
                          </div>

                          <div>
                            <label className="block text-slate-400 mb-1">Evaluation Comments</label>
                            <textarea
                              rows={3}
                              placeholder="Thorough audit feedback matching architectural standard..."
                              value={reviewForm.evaluation_comments}
                              onChange={e => setReviewForm(prev => ({ ...prev, evaluation_comments: e.target.value }))}
                              className="w-full bg-slate-900 border border-slate-700 rounded p-2 text-white"
                            />
                          </div>

                          <div className="flex items-center gap-2 pt-1">
                            <input
                              type="checkbox"
                              id="verifiedContrib"
                              checked={reviewForm.is_verified_contribution === 1}
                              onChange={e => setReviewForm(prev => ({ ...prev, is_verified_contribution: e.target.checked ? 1 : 0 }))}
                              className="rounded bg-slate-900 border-slate-700 text-blue-600"
                            />
                            <label htmlFor="verifiedContrib" className="text-slate-300 font-medium">
                              Link as Verified Contribution to Agent Performance History
                            </label>
                          </div>

                          <button
                            onClick={() => handleSubmitReview(selectedTask.deliverables![0].id)}
                            className="w-full py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-medium rounded shadow transition-all mt-2"
                          >
                            Submit Independent Review & Update Performance History ➔
                          </button>
                        </div>
                      ) : (
                        <div className="py-6 text-center text-slate-500 italic">
                          A deliverable must be submitted before quality review can take place.
                        </div>
                      )}
                    </div>
                  </div>
                )}

                {detailTab === 'blockers' && (
                  <div className="space-y-3 text-xs">
                    <div className="flex justify-between items-center mb-2">
                      <span className="font-bold text-slate-300 uppercase">Operational Blockers</span>
                      <button
                        onClick={() => setShowBlockerModal(true)}
                        className="px-2.5 py-1 bg-red-600 hover:bg-red-500 text-white rounded text-[11px] font-medium"
                      >
                        + Report Blocker
                      </button>
                    </div>

                    {selectedTask.blockers?.map(b => (
                      <div key={b.id} className="bg-slate-950 p-3 rounded-lg border border-red-900/60 space-y-2">
                        <div className="flex items-center justify-between">
                          <span className="font-bold text-red-400 capitalize">{b.category.replace('_', ' ')} Blocker</span>
                          <span className={`px-2 py-0.5 rounded text-[10px] uppercase font-bold ${
                            b.status === 'resolved' ? 'bg-emerald-950 text-emerald-300' : 'bg-red-950 text-red-300'
                          }`}>
                            {b.status}
                          </span>
                        </div>
                        <p className="text-slate-300 text-[11px]">{b.description}</p>
                        <div className="text-[10px] text-slate-500">Reported at: {b.reported_at}</div>

                        {b.status === 'active' && (
                          <div className="pt-2 border-t border-slate-800 space-y-2">
                            <input
                              type="text"
                              placeholder="Resolution notes..."
                              value={resolveBlockerNotes}
                              onChange={e => setResolveBlockerNotes(e.target.value)}
                              className="w-full bg-slate-900 border border-slate-700 px-2.5 py-1 rounded text-white text-xs"
                            />
                            <button
                              onClick={() => handleResolveBlocker(b.id)}
                              className="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-[11px] font-medium"
                            >
                              Resolve & Resume Execution
                            </button>
                          </div>
                        )}
                      </div>
                    ))}
                    {(!selectedTask.blockers || selectedTask.blockers.length === 0) && (
                      <div className="py-8 text-center text-slate-500">No active blockers for this task.</div>
                    )}
                  </div>
                )}
              </div>
            </div>

            {/* Modal Footer */}
            <div className="border-t border-slate-800 pt-4 flex justify-end">
              <button
                onClick={() => setSelectedTask(null)}
                className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded text-xs"
              >
                Close Inspector
              </button>
            </div>
          </div>
        </div>
      )}

      {/* 6. CREATE TASK MODAL */}
      {showCreateTaskModal && (
        <div className="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-6 space-y-4 shadow-2xl">
            <div className="flex items-center justify-between border-b border-slate-800 pb-3">
              <h3 className="text-base font-bold text-white">Create New Workforce Task</h3>
              <button onClick={() => setShowCreateTaskModal(false)} className="text-slate-400 hover:text-white">✕</button>
            </div>
            <form onSubmit={handleCreateTask} className="space-y-3 text-xs">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-slate-400 mb-1">Task Code</label>
                  <input
                    type="text"
                    required
                    value={taskForm.task_code}
                    onChange={e => setTaskForm(prev => ({ ...prev, task_code: e.target.value }))}
                    className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white font-mono"
                  />
                </div>
                <div>
                  <label className="block text-slate-400 mb-1">Priority</label>
                  <select
                    value={taskForm.priority}
                    onChange={e => setTaskForm(prev => ({ ...prev, priority: e.target.value }))}
                    className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                  >
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                    <option value="strategic">Strategic</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-slate-400 mb-1">Task Title</label>
                <input
                  type="text"
                  required
                  placeholder="e.g. Implement Hermes Execution Adapter"
                  value={taskForm.title}
                  onChange={e => setTaskForm(prev => ({ ...prev, title: e.target.value }))}
                  className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                />
              </div>

              <div>
                <label className="block text-slate-400 mb-1">Objective</label>
                <textarea
                  rows={2}
                  placeholder="Clear operational target, completion criteria, and expected deliverable..."
                  value={taskForm.objective}
                  onChange={e => setTaskForm(prev => ({ ...prev, objective: e.target.value }))}
                  className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-slate-400 mb-1">Category</label>
                  <select
                    value={taskForm.category}
                    onChange={e => setTaskForm(prev => ({ ...prev, category: e.target.value }))}
                    className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                  >
                    <option value="development">Development</option>
                    <option value="governance">Governance</option>
                    <option value="operations">Operations</option>
                    <option value="research">Research</option>
                    <option value="hardware">Hardware</option>
                  </select>
                </div>
                <div>
                  <label className="block text-slate-400 mb-1">Accountable Agent</label>
                  <select
                    value={taskForm.accountable_agent_id}
                    onChange={e => setTaskForm(prev => ({ ...prev, accountable_agent_id: Number(e.target.value) }))}
                    className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                  >
                    {VERIFIED_AGENTS.map(a => (
                      <option key={a.id} value={a.id}>{a.name}</option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-800">
                <button
                  type="button"
                  onClick={() => setShowCreateTaskModal(false)}
                  className="px-4 py-2 bg-slate-800 text-slate-300 rounded hover:bg-slate-700"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded font-medium shadow"
                >
                  Create Task
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* 7. PROPOSE DELEGATION MODAL */}
      {showDelegationModal && (
        <div className="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-6 space-y-4 shadow-2xl">
            <div className="flex items-center justify-between border-b border-slate-800 pb-3">
              <h3 className="text-base font-bold text-white">Propose Formal Task Delegation</h3>
              <button onClick={() => setShowDelegationModal(false)} className="text-slate-400 hover:text-white">✕</button>
            </div>
            <form onSubmit={handleCreateDelegation} className="space-y-3 text-xs">
              <div>
                <label className="block text-slate-400 mb-1">Select Task</label>
                <select
                  value={delegationForm.task_id}
                  onChange={e => setDelegationForm(prev => ({ ...prev, task_id: Number(e.target.value) }))}
                  className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                >
                  {tasks.map(t => (
                    <option key={t.id} value={t.id}>{t.task_code} - {t.title}</option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-slate-400 mb-1">Delegating Agent (Authority)</label>
                  <select
                    value={delegationForm.delegating_agent_id}
                    onChange={e => setDelegationForm(prev => ({ ...prev, delegating_agent_id: Number(e.target.value) }))}
                    className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                  >
                    {VERIFIED_AGENTS.map(a => (
                      <option key={a.id} value={a.id}>{a.name}</option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="block text-slate-400 mb-1">Delegated Agent (Assignee)</label>
                  <select
                    value={delegationForm.delegated_agent_id}
                    onChange={e => setDelegationForm(prev => ({ ...prev, delegated_agent_id: Number(e.target.value) }))}
                    className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                  >
                    {VERIFIED_AGENTS.map(a => (
                      <option key={a.id} value={a.id}>{a.name}</option>
                    ))}
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-slate-400 mb-1">Delegated Scope</label>
                <textarea
                  rows={2}
                  required
                  placeholder="Specific bounded responsibilities handed over..."
                  value={delegationForm.delegated_scope}
                  onChange={e => setDelegationForm(prev => ({ ...prev, delegated_scope: e.target.value }))}
                  className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-800">
                <button
                  type="button"
                  onClick={() => setShowDelegationModal(false)}
                  className="px-4 py-2 bg-slate-800 text-slate-300 rounded hover:bg-slate-700"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded font-medium shadow"
                >
                  Submit Delegation
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* 8. SUBMIT DELIVERABLE MODAL */}
      {showDeliverableModal && selectedTask && (
        <div className="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-6 space-y-4 shadow-2xl">
            <div className="flex items-center justify-between border-b border-slate-800 pb-3">
              <h3 className="text-base font-bold text-white">Submit Task Deliverable Artifact</h3>
              <button onClick={() => setShowDeliverableModal(false)} className="text-slate-400 hover:text-white">✕</button>
            </div>
            <form onSubmit={handleSubmitDeliverable} className="space-y-3 text-xs">
              <div>
                <label className="block text-slate-400 mb-1">Deliverable Title</label>
                <input
                  type="text"
                  required
                  placeholder="e.g. Core Algorithm Implementation v1"
                  value={deliverableForm.title}
                  onChange={e => setDeliverableForm(prev => ({ ...prev, title: e.target.value }))}
                  className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-slate-400 mb-1">Deliverable Type</label>
                  <select
                    value={deliverableForm.deliverable_type}
                    onChange={e => setDeliverableForm(prev => ({ ...prev, deliverable_type: e.target.value }))}
                    className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                  >
                    <option value="code_patch">Code Patch / PR</option>
                    <option value="report">Operational Report</option>
                    <option value="architecture_doc">Architecture Doc</option>
                    <option value="config">Configuration Specification</option>
                    <option value="data_model">Database Model / Migration</option>
                  </select>
                </div>
                <div>
                  <label className="block text-slate-400 mb-1">Submitting Agent</label>
                  <select
                    value={deliverableForm.submitting_agent_id}
                    onChange={e => setDeliverableForm(prev => ({ ...prev, submitting_agent_id: Number(e.target.value) }))}
                    className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                  >
                    {VERIFIED_AGENTS.map(a => (
                      <option key={a.id} value={a.id}>{a.name}</option>
                    ))}
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-slate-400 mb-1">Artifact URI / Evidence Link</label>
                <input
                  type="text"
                  placeholder="file://backend/app/Services/... or https://..."
                  value={deliverableForm.artifact_uri}
                  onChange={e => setDeliverableForm(prev => ({ ...prev, artifact_uri: e.target.value }))}
                  className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white font-mono"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-800">
                <button
                  type="button"
                  onClick={() => setShowDeliverableModal(false)}
                  className="px-4 py-2 bg-slate-800 text-slate-300 rounded hover:bg-slate-700"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded font-medium shadow"
                >
                  Submit for Quality Verification
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* 9. REPORT BLOCKER MODAL */}
      {showBlockerModal && selectedTask && (
        <div className="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-6 space-y-4 shadow-2xl">
            <div className="flex items-center justify-between border-b border-slate-800 pb-3">
              <h3 className="text-base font-bold text-red-400 flex items-center gap-2">
                <span>⚠️</span> Report Operational Blocker
              </h3>
              <button onClick={() => setShowBlockerModal(false)} className="text-slate-400 hover:text-white">✕</button>
            </div>
            <form onSubmit={handleReportBlocker} className="space-y-3 text-xs">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-slate-400 mb-1">Category</label>
                  <select
                    value={blockerForm.category}
                    onChange={e => setBlockerForm(prev => ({ ...prev, category: e.target.value }))}
                    className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                  >
                    <option value="system_environment">System Environment</option>
                    <option value="dependency">Task Dependency</option>
                    <option value="missing_information">Missing Information</option>
                    <option value="authority_approval">Authority / Approval Gate</option>
                    <option value="cross_agent">Cross-Agent Synchronization</option>
                  </select>
                </div>
                <div>
                  <label className="block text-slate-400 mb-1">Severity</label>
                  <select
                    value={blockerForm.severity}
                    onChange={e => setBlockerForm(prev => ({ ...prev, severity: e.target.value }))}
                    className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white font-bold"
                  >
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-slate-400 mb-1">Description</label>
                <textarea
                  rows={3}
                  required
                  placeholder="Detail the impediment and what unblocking action is required..."
                  value={blockerForm.description}
                  onChange={e => setBlockerForm(prev => ({ ...prev, description: e.target.value }))}
                  className="w-full bg-slate-950 border border-slate-700 rounded p-2 text-white"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-800">
                <button
                  type="button"
                  onClick={() => setShowBlockerModal(false)}
                  className="px-4 py-2 bg-slate-800 text-slate-300 rounded hover:bg-slate-700"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-red-600 hover:bg-red-500 text-white rounded font-medium shadow"
                >
                  Confirm & Halt Task
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
