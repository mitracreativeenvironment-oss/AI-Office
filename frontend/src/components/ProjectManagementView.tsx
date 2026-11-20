import React, { useState, useEffect, useCallback } from 'react';
import { projectService } from '../services/projectService';
import type {
  ProjectItem,
  ProjectDetail,
  PortfolioMetrics,
  ProjectClient,
  ProjectMilestone,
  ProjectDeliverable,
  ProjectRisk,
  ProjectIssue,
  ProjectChangeRequest,
  ProjectTeamMember,
  ProjectType,
  ProjectCategory,
  ProjectHealth,
} from '../types/project';

interface ProjectManagementViewProps {
  isAuthenticated?: boolean;
  onOpenLogin?: () => void;
}

export const ProjectManagementView: React.FC<ProjectManagementViewProps> = ({
  isAuthenticated = false,
  onOpenLogin,
}) => {
  // Navigation Tabs
  const [activeTab, setActiveTab] = useState<'portfolio' | 'projects' | 'clients' | 'detail'>('portfolio');

  // Detail Sub-Tab
  const [detailSubTab, setDetailSubTab] = useState<'brief' | 'milestones' | 'team' | 'tasks' | 'risks_issues' | 'budget_cr'>('brief');

  // Main Data States
  const [loading, setLoading] = useState<boolean>(true);
  const [metrics, setMetrics] = useState<PortfolioMetrics | null>(null);
  const [projects, setProjects] = useState<ProjectItem[]>([]);
  const [clients, setClients] = useState<ProjectClient[]>([]);
  const [selectedProjectId, setSelectedProjectId] = useState<number | null>(null);
  const [projectDetail, setProjectDetail] = useState<ProjectDetail | null>(null);
  const [linkedTasks, setLinkedTasks] = useState<any[]>([]);

  // Filter States
  const [typeFilter, setTypeFilter] = useState<string>('');
  const [categoryFilter, setCategoryFilter] = useState<string>('');
  const [statusFilter, setStatusFilter] = useState<string>('');
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [viewMode, setViewMode] = useState<'table' | 'kanban'>('table');

  // Notification Banner
  const [notice, setNotice] = useState<{ text: string; type: 'success' | 'error' } | null>(null);
  const showNotice = (text: string, type: 'success' | 'error' = 'success') => {
    setNotice({ text, type });
    setTimeout(() => setNotice(null), 5000);
  };

  // Modals
  const [showCreateModal, setShowCreateModal] = useState<boolean>(false);
  const [showTransitionModal, setShowTransitionModal] = useState<boolean>(false);
  const [showMilestoneModal, setShowMilestoneModal] = useState<boolean>(false);
  const [showDeliverableModal, setShowDeliverableModal] = useState<boolean>(false);
  const [showRiskModal, setShowRiskModal] = useState<boolean>(false);
  const [showIssueModal, setShowIssueModal] = useState<boolean>(false);
  const [showCrModal, setShowCrModal] = useState<boolean>(false);

  // Forms
  const [createForm, setCreateForm] = useState({
    name: '',
    project_type: 'customer_project' as ProjectType,
    category: 'ai_automation' as ProjectCategory,
    client_id: '',
    description: '',
    objectives: '',
    proposed_budget: 10000000,
    approved_budget_limit: 10000000,
    priority: 'medium',
    confidentiality_level: 'internal',
    start_date: '',
    target_completion_date: '',
  });

  const [transitionForm, setTransitionForm] = useState({
    target_stage: '',
    reason: '',
  });

  const [milestoneForm, setMilestoneForm] = useState({
    title: '',
    description: '',
    target_date: '',
    is_critical_path: 1,
  });

  const [deliverableForm, setDeliverableForm] = useState({
    title: '',
    description: '',
    due_date: '',
    acceptance_criteria: '',
    supporting_evidence_url: '',
  });

  const [riskForm, setRiskForm] = useState({
    title: '',
    description: '',
    probability: 'medium',
    impact: 'medium',
    mitigation_plan: '',
  });

  const [issueForm, setIssueForm] = useState({
    title: '',
    description: '',
    severity: 'moderate',
    priority: 'medium',
    resolution_plan: '',
  });

  const [crForm, setCrForm] = useState({
    title: '',
    description: '',
    business_justification: '',
    scope_impact: '',
    budget_impact_amount: 0,
    schedule_impact_days: 0,
  });

  // ==========================================
  // DATA FETCHING
  // ==========================================

  const loadPortfolioData = useCallback(async () => {
    try {
      const [mRes, pRes, cRes] = await Promise.all([
        projectService.getPortfolioMetrics(),
        projectService.listProjects({
          project_type: typeFilter || undefined,
          category: categoryFilter || undefined,
          status: statusFilter || undefined,
          search: searchQuery || undefined,
        }),
        projectService.listClients(),
      ]);
      setMetrics(mRes);
      setProjects(pRes);
      setClients(cRes);
    } catch (e: any) {
      console.error('Failed to load project data', e);
      showNotice(e.message || 'Failed to load projects', 'error');
    } finally {
      setLoading(false);
    }
  }, [typeFilter, categoryFilter, statusFilter, searchQuery]);

  const loadProjectDetail = useCallback(async (id: number) => {
    try {
      setLoading(true);
      const [detail, tasks] = await Promise.all([
        projectService.getProject(id),
        projectService.getLinkedTasks(id),
      ]);
      setProjectDetail(detail);
      setLinkedTasks(tasks);
    } catch (e: any) {
      console.error('Failed to load project detail', e);
      showNotice(e.message || 'Failed to load project details', 'error');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadPortfolioData();
  }, [loadPortfolioData]);

  useEffect(() => {
    if (selectedProjectId) {
      loadProjectDetail(selectedProjectId);
    }
  }, [selectedProjectId, loadProjectDetail]);

  const openProjectDetail = (id: number) => {
    setSelectedProjectId(id);
    setActiveTab('detail');
    setDetailSubTab('brief');
  };

  // ==========================================
  // ACTIONS
  // ==========================================

  const handleCreateProject = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!isAuthenticated) {
      onOpenLogin?.();
      return;
    }
    try {
      const payload: any = { ...createForm };
      if (createForm.project_type === 'customer_project') {
        payload.client_id = Number(createForm.client_id);
      } else {
        delete payload.client_id;
      }
      payload.proposed_budget = Number(createForm.proposed_budget);
      payload.approved_budget_limit = Number(createForm.approved_budget_limit);

      const created = await projectService.createProject(payload);
      showNotice(`Project ${created.project_code} created successfully!`);
      setShowCreateModal(false);
      loadPortfolioData();
      openProjectDetail(created.id);
    } catch (err: any) {
      showNotice(err.message || 'Failed to create project', 'error');
    }
  };

  const handleTransitionStage = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedProjectId) return;
    try {
      await projectService.transitionStage(
        selectedProjectId,
        transitionForm.target_stage,
        transitionForm.reason || 'Lifecycle transition requested'
      );
      showNotice(`Stage transitioned to ${transitionForm.target_stage}`);
      setShowTransitionModal(false);
      loadProjectDetail(selectedProjectId);
      loadPortfolioData();
    } catch (err: any) {
      showNotice(err.message || 'Failed to transition stage', 'error');
    }
  };

  const handleCreateMilestone = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedProjectId) return;
    try {
      await projectService.createMilestone(selectedProjectId, {
        title: milestoneForm.title,
        description: milestoneForm.description,
        target_date: milestoneForm.target_date,
        is_critical_path: milestoneForm.is_critical_path,
      });
      showNotice('Milestone added successfully');
      setShowMilestoneModal(false);
      loadProjectDetail(selectedProjectId);
    } catch (err: any) {
      showNotice(err.message || 'Failed to add milestone', 'error');
    }
  };

  const handleUpdateMilestoneStatus = async (milestoneId: number, status: any) => {
    if (!selectedProjectId) return;
    try {
      await projectService.updateMilestone(milestoneId, { status });
      showNotice('Milestone status updated');
      loadProjectDetail(selectedProjectId);
    } catch (err: any) {
      showNotice(err.message || 'Failed to update milestone', 'error');
    }
  };

  const handleCreateDeliverable = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedProjectId) return;
    try {
      await projectService.createDeliverable(selectedProjectId, {
        title: deliverableForm.title,
        description: deliverableForm.description,
        due_date: deliverableForm.due_date,
        acceptance_criteria: deliverableForm.acceptance_criteria,
        supporting_evidence_url: deliverableForm.supporting_evidence_url,
      });
      showNotice('Deliverable created successfully');
      setShowDeliverableModal(false);
      loadProjectDetail(selectedProjectId);
    } catch (err: any) {
      showNotice(err.message || 'Failed to create deliverable', 'error');
    }
  };

  const handleReviewDeliverable = async (delId: number, status: string) => {
    if (!selectedProjectId) return;
    try {
      await projectService.reviewDeliverable(delId, status, 'Approved in review');
      showNotice(`Deliverable marked as ${status}`);
      loadProjectDetail(selectedProjectId);
    } catch (err: any) {
      showNotice(err.message || 'Review action failed', 'error');
    }
  };

  const handleClientAcceptDeliverable = async (delId: number) => {
    if (!selectedProjectId) return;
    try {
      await projectService.clientAcceptDeliverable(delId);
      showNotice('Deliverable formally accepted by client');
      loadProjectDetail(selectedProjectId);
    } catch (err: any) {
      showNotice(err.message || 'Client acceptance failed', 'error');
    }
  };

  const handleCreateRisk = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedProjectId) return;
    try {
      await projectService.createRisk(selectedProjectId, riskForm as any);
      showNotice('Risk registered');
      setShowRiskModal(false);
      loadProjectDetail(selectedProjectId);
    } catch (err: any) {
      showNotice(err.message || 'Failed to register risk', 'error');
    }
  };

  const handleCreateIssue = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedProjectId) return;
    try {
      await projectService.createIssue(selectedProjectId, issueForm as any);
      showNotice('Issue logged');
      setShowIssueModal(false);
      loadProjectDetail(selectedProjectId);
    } catch (err: any) {
      showNotice(err.message || 'Failed to log issue', 'error');
    }
  };

  const handleResolveIssue = async (issueId: number) => {
    if (!selectedProjectId) return;
    const plan = prompt('Enter resolution summary:');
    if (!plan) return;
    try {
      await projectService.resolveIssue(issueId, plan);
      showNotice('Issue marked as resolved');
      loadProjectDetail(selectedProjectId);
    } catch (err: any) {
      showNotice(err.message || 'Failed to resolve issue', 'error');
    }
  };

  const handleCreateCr = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedProjectId) return;
    try {
      await projectService.createChangeRequest(selectedProjectId, {
        ...crForm,
        budget_impact_amount: Number(crForm.budget_impact_amount),
        schedule_impact_days: Number(crForm.schedule_impact_days),
      });
      showNotice('Change request submitted');
      setShowCrModal(false);
      loadProjectDetail(selectedProjectId);
    } catch (err: any) {
      showNotice(err.message || 'Failed to submit change request', 'error');
    }
  };

  const handleEvaluateCr = async (crId: number, decision: string) => {
    if (!selectedProjectId) return;
    try {
      await projectService.evaluateChangeRequest(crId, decision, 'Evaluated by authority');
      showNotice(`Change request ${decision}`);
      loadProjectDetail(selectedProjectId);
      loadPortfolioData();
    } catch (err: any) {
      showNotice(err.message || 'Failed to evaluate change request', 'error');
    }
  };

  const handleRecalculateProgress = async () => {
    if (!selectedProjectId) return;
    try {
      const res = await projectService.recalculateProgress(selectedProjectId);
      showNotice(`Progress updated to ${res.progress_percent}%`);
      loadProjectDetail(selectedProjectId);
    } catch (err: any) {
      showNotice(err.message || 'Recalculation failed', 'error');
    }
  };

  // Helper Badge Renderers
  const renderHealthBadge = (health: ProjectHealth) => {
    switch (health) {
      case 'healthy':
        return <span className="px-2 py-0.5 rounded text-xs font-semibold bg-emerald-950/60 text-emerald-400 border border-emerald-800/60">Healthy</span>;
      case 'at_risk':
        return <span className="px-2 py-0.5 rounded text-xs font-semibold bg-amber-950/60 text-amber-400 border border-amber-800/60">At Risk</span>;
      case 'critical':
        return <span className="px-2 py-0.5 rounded text-xs font-semibold bg-rose-950/60 text-rose-400 border border-rose-800/60">Critical</span>;
    }
  };

  const renderTypeBadge = (type: ProjectType) => {
    if (type === 'customer_project') {
      return <span className="px-2 py-0.5 rounded text-xs font-medium bg-blue-950/60 text-blue-300 border border-blue-800/60">Customer Project</span>;
    }
    return <span className="px-2 py-0.5 rounded text-xs font-medium bg-purple-950/60 text-purple-300 border border-purple-800/60">Internal Initiative</span>;
  };

  const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
  };

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 p-6 space-y-6">
      {/* Top Banner Notice */}
      {notice && (
        <div className={`p-4 rounded-lg flex items-center justify-between border ${
          notice.type === 'success' ? 'bg-emerald-950/80 border-emerald-800 text-emerald-200' : 'bg-rose-950/80 border-rose-800 text-rose-200'
        }`}>
          <span>{notice.text}</span>
          <button type="button" onClick={() => setNotice(null)} className="text-slate-400 hover:text-white">✕</button>
        </div>
      )}

      {/* Header & Controls */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800 pb-5">
        <div>
          <div className="flex items-center gap-3">
            <span className="text-3xl">🚀</span>
            <div>
              <h1 className="text-2xl font-bold tracking-tight text-white">Project & Delivery Operations</h1>
              <p className="text-sm text-slate-400">
                Customer Projects, Internal Initiatives, Milestones, Deliverables & Governance (DoA Level A/B/C)
              </p>
            </div>
          </div>
        </div>

        <div className="flex items-center gap-3">
          <button
            type="button"
            onClick={() => {
              if (!isAuthenticated) onOpenLogin?.();
              else setShowCreateModal(true);
            }}
            className="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg font-medium text-sm transition shadow-lg shadow-blue-900/30 flex items-center gap-2"
          >
            <span>+</span> New Project
          </button>
        </div>
      </div>

      {/* Main Navigation Tabs */}
      <div className="flex items-center gap-2 border-b border-slate-800">
        <button
          type="button"
          onClick={() => setActiveTab('portfolio')}
          className={`px-4 py-2 text-sm font-medium border-b-2 transition ${
            activeTab === 'portfolio' ? 'border-blue-500 text-blue-400' : 'border-transparent text-slate-400 hover:text-slate-200'
          }`}
        >
          📊 Portfolio Overview
        </button>
        <button
          type="button"
          onClick={() => setActiveTab('projects')}
          className={`px-4 py-2 text-sm font-medium border-b-2 transition ${
            activeTab === 'projects' ? 'border-blue-500 text-blue-400' : 'border-transparent text-slate-400 hover:text-slate-200'
          }`}
        >
          📂 All Projects ({projects.length})
        </button>
        <button
          type="button"
          onClick={() => setActiveTab('clients')}
          className={`px-4 py-2 text-sm font-medium border-b-2 transition ${
            activeTab === 'clients' ? 'border-blue-500 text-blue-400' : 'border-transparent text-slate-400 hover:text-slate-200'
          }`}
        >
          🏢 Clients & Stakeholders ({clients.length})
        </button>
        {selectedProjectId && (
          <button
            type="button"
            onClick={() => setActiveTab('detail')}
            className={`px-4 py-2 text-sm font-medium border-b-2 transition ${
              activeTab === 'detail' ? 'border-blue-500 text-blue-400' : 'border-transparent text-slate-400 hover:text-slate-200'
            }`}
          >
            🔍 Project: {projectDetail?.project_code || `#${selectedProjectId}`}
          </button>
        )}
      </div>

      {/* ==================================================== */}
      {/* 1. PORTFOLIO OVERVIEW TAB */}
      {/* ==================================================== */}
      {activeTab === 'portfolio' && (
        <div className="space-y-6">
          {/* Key Metric Cards */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div className="bg-slate-900 border border-slate-800 p-5 rounded-xl">
              <span className="text-xs uppercase font-semibold text-slate-400">Total Portfolio</span>
              <div className="text-3xl font-bold mt-2 text-white">{metrics?.total_projects || 0}</div>
              <div className="text-xs text-slate-400 mt-2 flex items-center justify-between">
                <span>Customer: <strong className="text-blue-400">{metrics?.customer_projects || 0}</strong></span>
                <span>Internal: <strong className="text-purple-400">{metrics?.internal_initiatives || 0}</strong></span>
              </div>
            </div>

            <div className="bg-slate-900 border border-slate-800 p-5 rounded-xl">
              <span className="text-xs uppercase font-semibold text-slate-400">Portfolio Health</span>
              <div className="text-3xl font-bold mt-2 text-emerald-400">{metrics?.health_distribution.healthy || 0} <span className="text-sm font-normal text-slate-400">healthy</span></div>
              <div className="text-xs text-slate-400 mt-2 flex items-center gap-3">
                <span className="text-amber-400 font-semibold">{metrics?.health_distribution.at_risk || 0} at risk</span>
                <span className="text-rose-400 font-semibold">{metrics?.health_distribution.critical || 0} critical</span>
              </div>
            </div>

            <div className="bg-slate-900 border border-slate-800 p-5 rounded-xl">
              <span className="text-xs uppercase font-semibold text-slate-400">Delivery Progress</span>
              <div className="text-3xl font-bold mt-2 text-blue-400">{metrics?.average_progress || 0}%</div>
              <div className="w-full bg-slate-800 rounded-full h-2 mt-3 overflow-hidden">
                <div className="bg-blue-500 h-2 rounded-full" style={{ width: `${metrics?.average_progress || 0}%` }}></div>
              </div>
            </div>

            <div className="bg-slate-900 border border-slate-800 p-5 rounded-xl">
              <span className="text-xs uppercase font-semibold text-slate-400">Budget Utilization</span>
              <div className="text-xl font-bold mt-2 text-white">
                {formatCurrency(metrics?.financials.total_actual_cost || 0)}
              </div>
              <div className="text-xs text-slate-400 mt-1">
                Approved: {formatCurrency(metrics?.financials.total_approved_budget || 0)}
              </div>
            </div>
          </div>

          {/* Highlights & Recent Projects */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Customer Projects Spotlight */}
            <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-4">
              <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 className="font-semibold text-slate-200 flex items-center gap-2">
                  <span className="text-blue-400">●</span> External Customer Engagements
                </h3>
                <button
                  type="button"
                  onClick={() => { setTypeFilter('customer_project'); setActiveTab('projects'); }}
                  className="text-xs text-blue-400 hover:underline"
                >
                  View All
                </button>
              </div>

              <div className="space-y-3">
                {projects.filter(p => p.project_type === 'customer_project').slice(0, 3).map(p => (
                  <div
                    key={p.id}
                    onClick={() => openProjectDetail(p.id)}
                    className="p-3 rounded-lg bg-slate-950/60 hover:bg-slate-800/60 border border-slate-800/80 cursor-pointer transition flex items-center justify-between"
                  >
                    <div className="space-y-1">
                      <div className="flex items-center gap-2">
                        <span className="text-xs font-mono text-slate-400">{p.project_code}</span>
                        {renderHealthBadge(p.health_status)}
                      </div>
                      <h4 className="font-medium text-sm text-white hover:text-blue-400">{p.name}</h4>
                      <p className="text-xs text-slate-400">Client: {p.client_name || 'Direct Enterprise'}</p>
                    </div>
                    <div className="text-right">
                      <span className="text-sm font-bold text-slate-200">{p.progress_percent}%</span>
                      <div className="text-xs text-slate-400 capitalize">{p.lifecycle_stage.replace('_', ' ')}</div>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Internal Initiatives Spotlight */}
            <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-4">
              <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 className="font-semibold text-slate-200 flex items-center gap-2">
                  <span className="text-purple-400">●</span> Internal MCE Initiatives (DoA Level B/C)
                </h3>
                <button
                  type="button"
                  onClick={() => { setTypeFilter('internal_initiative'); setActiveTab('projects'); }}
                  className="text-xs text-purple-400 hover:underline"
                >
                  View All
                </button>
              </div>

              <div className="space-y-3">
                {projects.filter(p => p.project_type === 'internal_initiative').slice(0, 3).map(p => (
                  <div
                    key={p.id}
                    onClick={() => openProjectDetail(p.id)}
                    className="p-3 rounded-lg bg-slate-950/60 hover:bg-slate-800/60 border border-slate-800/80 cursor-pointer transition flex items-center justify-between"
                  >
                    <div className="space-y-1">
                      <div className="flex items-center gap-2">
                        <span className="text-xs font-mono text-slate-400">{p.project_code}</span>
                        {renderHealthBadge(p.health_status)}
                      </div>
                      <h4 className="font-medium text-sm text-white hover:text-purple-400">{p.name}</h4>
                      <p className="text-xs text-slate-400">Authority: {p.sponsoring_authority.replace(/_/g, ' ')}</p>
                    </div>
                    <div className="text-right">
                      <span className="text-sm font-bold text-slate-200">{p.progress_percent}%</span>
                      <div className="text-xs text-slate-400 capitalize">{p.lifecycle_stage.replace('_', ' ')}</div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ==================================================== */}
      {/* 2. ALL PROJECTS EXPLORER TAB */}
      {/* ==================================================== */}
      {activeTab === 'projects' && (
        <div className="space-y-4">
          {/* Filters Bar */}
          <div className="flex flex-wrap items-center gap-3 bg-slate-900 p-4 rounded-xl border border-slate-800">
            <input
              type="text"
              placeholder="Search by code, title, description..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="bg-slate-950 border border-slate-800 px-3 py-1.5 rounded-lg text-sm text-slate-200 placeholder-slate-500 w-64 focus:outline-none focus:border-blue-500"
            />

            <select
              value={typeFilter}
              onChange={(e) => setTypeFilter(e.target.value)}
              className="bg-slate-950 border border-slate-800 px-3 py-1.5 rounded-lg text-sm text-slate-200 focus:outline-none focus:border-blue-500"
            >
              <option value="">All Types</option>
              <option value="customer_project">Customer Projects</option>
              <option value="internal_initiative">Internal Initiatives</option>
            </select>

            <select
              value={categoryFilter}
              onChange={(e) => setCategoryFilter(e.target.value)}
              className="bg-slate-950 border border-slate-800 px-3 py-1.5 rounded-lg text-sm text-slate-200 focus:outline-none focus:border-blue-500"
            >
              <option value="">All Categories</option>
              <option value="ai_automation">AI Automation</option>
              <option value="web_app">Web Application</option>
              <option value="internal_platform">Internal Platform</option>
              <option value="rnd">R&D</option>
              <option value="branding_marketing">Branding</option>
            </select>

            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="bg-slate-950 border border-slate-800 px-3 py-1.5 rounded-lg text-sm text-slate-200 focus:outline-none focus:border-blue-500"
            >
              <option value="">All Statuses</option>
              <option value="active">Active</option>
              <option value="draft">Draft</option>
              <option value="on_hold">On Hold</option>
              <option value="completed">Completed</option>
            </select>

            <div className="ml-auto flex items-center gap-2">
              <button
                type="button"
                onClick={() => setViewMode('table')}
                className={`px-3 py-1.5 rounded-lg text-xs font-medium border ${
                  viewMode === 'table' ? 'bg-slate-800 border-slate-600 text-white' : 'border-slate-800 text-slate-400'
                }`}
              >
                Table View
              </button>
              <button
                type="button"
                onClick={() => setViewMode('kanban')}
                className={`px-3 py-1.5 rounded-lg text-xs font-medium border ${
                  viewMode === 'kanban' ? 'bg-slate-800 border-slate-600 text-white' : 'border-slate-800 text-slate-400'
                }`}
              >
                Kanban View
              </button>
            </div>
          </div>

          {/* Projects Table */}
          {viewMode === 'table' ? (
            <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
              <table className="w-full text-left text-sm text-slate-300">
                <thead className="bg-slate-950 text-slate-400 text-xs uppercase font-medium border-b border-slate-800">
                  <tr>
                    <th className="p-4">Code & Project</th>
                    <th className="p-4">Type & Category</th>
                    <th className="p-4">Client / Authority</th>
                    <th className="p-4">Stage</th>
                    <th className="p-4">Health</th>
                    <th className="p-4">Progress</th>
                    <th className="p-4">Budget</th>
                    <th className="p-4 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-800/60">
                  {projects.map((p) => (
                    <tr key={p.id} className="hover:bg-slate-850/50 transition">
                      <td className="p-4">
                        <div className="font-semibold text-white">{p.name}</div>
                        <div className="text-xs font-mono text-slate-400 mt-0.5">{p.project_code}</div>
                      </td>
                      <td className="p-4">
                        <div className="space-y-1">
                          {renderTypeBadge(p.project_type)}
                          <div className="text-xs text-slate-400 capitalize">{p.category.replace('_', ' ')}</div>
                        </div>
                      </td>
                      <td className="p-4 text-xs">
                        {p.project_type === 'customer_project' ? (
                          <div>
                            <span className="font-medium text-slate-200">{p.client_name || 'External'}</span>
                            <div className="text-slate-500">{p.client_code}</div>
                          </div>
                        ) : (
                          <div className="text-slate-400 capitalize">{p.sponsoring_authority.replace(/_/g, ' ')}</div>
                        )}
                      </td>
                      <td className="p-4">
                        <span className="px-2.5 py-1 rounded-md text-xs font-medium bg-slate-800 text-slate-200 border border-slate-700 capitalize">
                          {p.lifecycle_stage.replace('_', ' ')}
                        </span>
                      </td>
                      <td className="p-4">{renderHealthBadge(p.health_status)}</td>
                      <td className="p-4">
                        <div className="flex items-center gap-2">
                          <div className="w-16 bg-slate-800 rounded-full h-1.5 overflow-hidden">
                            <div className="bg-blue-500 h-1.5 rounded-full" style={{ width: `${p.progress_percent}%` }}></div>
                          </div>
                          <span className="text-xs font-semibold">{p.progress_percent}%</span>
                        </div>
                      </td>
                      <td className="p-4 text-xs">
                        <div className="font-medium text-slate-200">{formatCurrency(p.approved_budget_limit)}</div>
                        <div className="text-slate-500">Spent: {formatCurrency(p.actual_cost_incurred)}</div>
                      </td>
                      <td className="p-4 text-right">
                        <button
                          type="button"
                          onClick={() => openProjectDetail(p.id)}
                          className="px-3 py-1.5 bg-blue-900/40 hover:bg-blue-800/60 text-blue-300 rounded text-xs font-medium border border-blue-700/60 transition"
                        >
                          Workspace →
                        </button>
                      </td>
                    </tr>
                  ))}
                  {projects.length === 0 && (
                    <tr>
                      <td colSpan={8} className="p-8 text-center text-slate-500">
                        No projects found matching the selected filters.
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          ) : (
            /* Kanban Board View */
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 overflow-x-auto pb-4">
              {['planning', 'execution', 'review_acceptance', 'delivery_closure'].map((stg) => {
                const stageProjects = projects.filter(p => p.lifecycle_stage === stg || (stg === 'planning' && ['lead', 'qualification', 'proposal', 'idea_proposal', 'evaluation', 'authorized', 'planning'].includes(p.lifecycle_stage)));
                return (
                  <div key={stg} className="bg-slate-900 border border-slate-800 rounded-xl p-4 space-y-3">
                    <div className="flex items-center justify-between border-b border-slate-800 pb-2">
                      <span className="text-xs uppercase font-bold tracking-wider text-slate-400 capitalize">{stg.replace('_', ' ')}</span>
                      <span className="px-2 py-0.5 rounded-full bg-slate-800 text-xs font-semibold text-slate-300">{stageProjects.length}</span>
                    </div>

                    <div className="space-y-2.5">
                      {stageProjects.map(p => (
                        <div
                          key={p.id}
                          onClick={() => openProjectDetail(p.id)}
                          className="bg-slate-950 p-3 rounded-lg border border-slate-800 hover:border-slate-700 cursor-pointer transition space-y-2"
                        >
                          <div className="flex items-center justify-between text-xs">
                            <span className="font-mono text-slate-400">{p.project_code}</span>
                            {renderHealthBadge(p.health_status)}
                          </div>
                          <h5 className="font-medium text-sm text-white">{p.name}</h5>
                          <div className="flex items-center justify-between text-xs text-slate-400 pt-1 border-t border-slate-900">
                            <span>{p.project_type === 'customer_project' ? 'Customer' : 'Internal'}</span>
                            <span className="font-semibold text-blue-400">{p.progress_percent}%</span>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      )}

      {/* ==================================================== */}
      {/* 3. CLIENTS & STAKEHOLDERS TAB */}
      {/* ==================================================== */}
      {activeTab === 'clients' && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {clients.map(c => (
            <div key={c.id} className="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-4">
              <div className="flex items-start justify-between">
                <div>
                  <span className="text-xs font-mono text-slate-400">{c.client_code}</span>
                  <h3 className="font-bold text-lg text-white mt-1">{c.company_name}</h3>
                  <p className="text-xs text-blue-400">{c.industry || 'Enterprise'}</p>
                </div>
                <span className="px-2 py-0.5 rounded text-xs font-semibold bg-emerald-950/60 text-emerald-400 border border-emerald-800/60 capitalize">
                  {c.status}
                </span>
              </div>

              {c.address && <p className="text-xs text-slate-400">{c.address}</p>}

              <div className="grid grid-cols-2 gap-2 pt-3 border-t border-slate-800 text-xs">
                <div>
                  <span className="text-slate-500">Active Projects</span>
                  <div className="text-base font-bold text-slate-200">{c.active_project_count || 0}</div>
                </div>
                <div>
                  <span className="text-slate-500">Key Contacts</span>
                  <div className="text-base font-bold text-slate-200">{c.contact_count || 0}</div>
                </div>
              </div>

              {c.notes && (
                <div className="bg-slate-950 p-2.5 rounded text-xs text-slate-400 italic">
                  "{c.notes}"
                </div>
              )}
            </div>
          ))}
        </div>
      )}

      {/* ==================================================== */}
      {/* 4. PROJECT WORKSPACE / DETAIL VIEW */}
      {/* ==================================================== */}
      {activeTab === 'detail' && projectDetail && (
        <div className="space-y-6">
          {/* Detail Header Banner */}
          <div className="bg-slate-900 border border-slate-800 rounded-xl p-6 space-y-4">
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
              <div className="space-y-1">
                <div className="flex items-center gap-3">
                  <span className="font-mono text-xs bg-slate-800 px-2.5 py-1 rounded text-slate-300 font-semibold">{projectDetail.project_code}</span>
                  {renderTypeBadge(projectDetail.project_type)}
                  {renderHealthBadge(projectDetail.health_status)}
                  <span className="px-2 py-0.5 rounded text-xs font-medium bg-slate-800 text-slate-300 uppercase">
                    Stage: {projectDetail.lifecycle_stage.replace('_', ' ')}
                  </span>
                </div>
                <h2 className="text-2xl font-bold text-white pt-1">{projectDetail.name}</h2>
                <p className="text-xs text-slate-400">{projectDetail.description}</p>
              </div>

              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={() => setShowTransitionModal(true)}
                  className="px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-xs font-semibold transition"
                >
                  ⚡ Advance Stage
                </button>
                <button
                  type="button"
                  onClick={handleRecalculateProgress}
                  className="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs font-medium border border-slate-700 transition"
                >
                  🔄 Sync Progress
                </button>
              </div>
            </div>

            {/* Quick Stats Grid */}
            <div className="grid grid-cols-2 md:grid-cols-5 gap-3 pt-4 border-t border-slate-800 text-xs">
              <div>
                <span className="text-slate-500">Overall Progress</span>
                <div className="text-base font-bold text-blue-400">{projectDetail.progress_percent}%</div>
              </div>
              <div>
                <span className="text-slate-500">Approved Budget</span>
                <div className="text-base font-bold text-white">{formatCurrency(projectDetail.approved_budget_limit)}</div>
              </div>
              <div>
                <span className="text-slate-500">Actual Cost Incurred</span>
                <div className="text-base font-bold text-slate-300">{formatCurrency(projectDetail.actual_cost_incurred)}</div>
              </div>
              <div>
                <span className="text-slate-500">Team Members</span>
                <div className="text-base font-bold text-white">{projectDetail.counts.team_members}</div>
              </div>
              <div>
                <span className="text-slate-500">Open Risks / Issues</span>
                <div className="text-base font-bold text-amber-400">{projectDetail.counts.risks} / {projectDetail.counts.open_issues}</div>
              </div>
            </div>
          </div>

          {/* Sub-Navigation */}
          <div className="flex items-center gap-2 border-b border-slate-800">
            <button
              type="button"
              onClick={() => setDetailSubTab('brief')}
              className={`px-3.5 py-2 text-xs font-semibold border-b-2 transition ${
                detailSubTab === 'brief' ? 'border-blue-500 text-blue-400' : 'border-transparent text-slate-400 hover:text-slate-200'
              }`}
            >
              📄 Brief & Scope
            </button>
            <button
              type="button"
              onClick={() => setDetailSubTab('milestones')}
              className={`px-3.5 py-2 text-xs font-semibold border-b-2 transition ${
                detailSubTab === 'milestones' ? 'border-blue-500 text-blue-400' : 'border-transparent text-slate-400 hover:text-slate-200'
              }`}
            >
              🚩 Milestones & Deliverables ({projectDetail.counts.milestones}/{projectDetail.counts.deliverables})
            </button>
            <button
              type="button"
              onClick={() => setDetailSubTab('team')}
              className={`px-3.5 py-2 text-xs font-semibold border-b-2 transition ${
                detailSubTab === 'team' ? 'border-blue-500 text-blue-400' : 'border-transparent text-slate-400 hover:text-slate-200'
              }`}
            >
              👥 Team & Resource Pool ({projectDetail.counts.team_members})
            </button>
            <button
              type="button"
              onClick={() => setDetailSubTab('tasks')}
              className={`px-3.5 py-2 text-xs font-semibold border-b-2 transition ${
                detailSubTab === 'tasks' ? 'border-blue-500 text-blue-400' : 'border-transparent text-slate-400 hover:text-slate-200'
              }`}
            >
              ⚙️ Linked Workforce Tasks ({linkedTasks.length})
            </button>
            <button
              type="button"
              onClick={() => setDetailSubTab('risks_issues')}
              className={`px-3.5 py-2 text-xs font-semibold border-b-2 transition ${
                detailSubTab === 'risks_issues' ? 'border-blue-500 text-blue-400' : 'border-transparent text-slate-400 hover:text-slate-200'
              }`}
            >
              ⚠️ Risks & Issues ({projectDetail.counts.risks + projectDetail.counts.open_issues})
            </button>
            <button
              type="button"
              onClick={() => setDetailSubTab('budget_cr')}
              className={`px-3.5 py-2 text-xs font-semibold border-b-2 transition ${
                detailSubTab === 'budget_cr' ? 'border-blue-500 text-blue-400' : 'border-transparent text-slate-400 hover:text-slate-200'
              }`}
            >
              💰 Change Requests & Budget ({projectDetail.counts.change_reqs})
            </button>
          </div>

          {/* Sub-Tab 1: Brief & Scope */}
          {detailSubTab === 'brief' && (
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-4">
                <h3 className="font-semibold text-white border-b border-slate-800 pb-2">Project Brief & Strategic Objectives</h3>
                <div className="space-y-3 text-sm">
                  <div>
                    <span className="text-xs uppercase text-slate-500 font-semibold">Background Narrative</span>
                    <p className="text-slate-300 mt-1 leading-relaxed">{projectDetail.brief?.background || 'No background defined yet.'}</p>
                  </div>
                  <div>
                    <span className="text-xs uppercase text-slate-500 font-semibold">Business Objectives</span>
                    <p className="text-slate-300 mt-1 leading-relaxed">{projectDetail.brief?.business_objectives || 'Objectives pending approval.'}</p>
                  </div>
                  <div>
                    <span className="text-xs uppercase text-slate-500 font-semibold">Success Criteria</span>
                    <p className="text-slate-300 mt-1 leading-relaxed">{projectDetail.brief?.success_criteria || projectDetail.objectives || 'Criteria pending baseline.'}</p>
                  </div>
                </div>
              </div>

              <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-4">
                <h3 className="font-semibold text-white border-b border-slate-800 pb-2">Scope Baseline</h3>
                {projectDetail.active_scope ? (
                  <div className="space-y-3 text-sm">
                    <div className="flex items-center justify-between">
                      <span className="text-xs font-mono text-blue-400 font-semibold">Version {projectDetail.active_scope.version_number}</span>
                      <span className="px-2 py-0.5 rounded text-xs bg-emerald-950/60 text-emerald-400 border border-emerald-800/60">Baselined</span>
                    </div>
                    <p className="text-slate-300">{projectDetail.active_scope.scope_summary}</p>
                    {projectDetail.active_scope.in_scope_items_json && (
                      <div>
                        <span className="text-xs uppercase text-slate-500 font-semibold">In Scope</span>
                        <div className="mt-1 space-y-1">
                          {JSON.parse(projectDetail.active_scope.in_scope_items_json || '[]').map((it: string, idx: number) => (
                            <div key={idx} className="flex items-center gap-2 text-xs text-slate-300">
                              <span className="text-emerald-400">✓</span> {it}
                            </div>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                ) : (
                  <p className="text-xs text-slate-500 italic">No formal baseline created yet.</p>
                )}
              </div>
            </div>
          )}

          {/* Sub-Tab 2: Milestones & Deliverables */}
          {detailSubTab === 'milestones' && (
            <div className="space-y-6">
              {/* Milestones Card */}
              <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-4">
                <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                  <h3 className="font-semibold text-white">Project Milestones</h3>
                  <button
                    type="button"
                    onClick={() => setShowMilestoneModal(true)}
                    className="px-3 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded text-xs font-medium"
                  >
                    + Add Milestone
                  </button>
                </div>

                <div className="space-y-3">
                  {projectDetail.milestones.map((m) => (
                    <div key={m.id} className="p-3 bg-slate-950 rounded-lg border border-slate-800/80 flex items-center justify-between">
                      <div className="space-y-1">
                        <div className="flex items-center gap-2">
                          <span className="font-mono text-xs text-slate-400">{m.milestone_code}</span>
                          {m.is_critical_path === 1 && (
                            <span className="px-1.5 py-0.2 rounded text-[10px] bg-amber-950 text-amber-400 border border-amber-800">Critical Path</span>
                          )}
                          <span className={`px-2 py-0.5 rounded text-xs capitalize ${
                            m.status === 'achieved' ? 'bg-emerald-950/60 text-emerald-400' : 'bg-slate-800 text-slate-300'
                          }`}>
                            {m.status.replace('_', ' ')}
                          </span>
                        </div>
                        <h4 className="font-medium text-sm text-white">{m.title}</h4>
                        <p className="text-xs text-slate-400">{m.description}</p>
                      </div>

                      <div className="flex items-center gap-3">
                        <div className="text-right text-xs">
                          <span className="text-slate-500">Target Date</span>
                          <div className="font-medium text-slate-200">{m.target_date}</div>
                        </div>
                        {m.status !== 'achieved' && (
                          <button
                            type="button"
                            onClick={() => handleUpdateMilestoneStatus(m.id, 'achieved')}
                            className="px-2.5 py-1 bg-emerald-900/40 hover:bg-emerald-800/60 text-emerald-300 border border-emerald-700/60 rounded text-xs font-medium"
                          >
                            Mark Achieved
                          </button>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* Deliverables Card */}
              <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-4">
                <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                  <h3 className="font-semibold text-white">Deliverables & Client Acceptance</h3>
                  <button
                    type="button"
                    onClick={() => setShowDeliverableModal(true)}
                    className="px-3 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded text-xs font-medium"
                  >
                    + Add Deliverable
                  </button>
                </div>

                <div className="space-y-3">
                  {projectDetail.deliverables.map((d) => (
                    <div key={d.id} className="p-3 bg-slate-950 rounded-lg border border-slate-800/80 flex items-center justify-between">
                      <div className="space-y-1">
                        <div className="flex items-center gap-2">
                          <span className="font-mono text-xs text-slate-400">{d.deliverable_code}</span>
                          <span className={`px-2 py-0.5 rounded text-xs capitalize ${
                            d.review_status === 'accepted' ? 'bg-emerald-950/60 text-emerald-400' : 'bg-slate-800 text-slate-300'
                          }`}>
                            {d.review_status.replace('_', ' ')}
                          </span>
                          {d.client_accepted === 1 && (
                            <span className="px-2 py-0.5 rounded text-xs bg-blue-950/80 text-blue-300 border border-blue-800">
                              ✓ Client Accepted
                            </span>
                          )}
                        </div>
                        <h4 className="font-medium text-sm text-white">{d.title}</h4>
                        <p className="text-xs text-slate-400">{d.acceptance_criteria}</p>
                      </div>

                      <div className="flex items-center gap-2">
                        {d.review_status !== 'accepted' && (
                          <button
                            type="button"
                            onClick={() => handleReviewDeliverable(d.id, 'accepted')}
                            className="px-2.5 py-1 bg-blue-900/40 hover:bg-blue-800/60 text-blue-300 border border-blue-700/60 rounded text-xs font-medium"
                          >
                            Approve Review
                          </button>
                        )}
                        {d.review_status === 'accepted' && d.client_accepted === 0 && (
                          <button
                            type="button"
                            onClick={() => handleClientAcceptDeliverable(d.id)}
                            className="px-2.5 py-1 bg-emerald-900/40 hover:bg-emerald-800/60 text-emerald-300 border border-emerald-700/60 rounded text-xs font-medium"
                          >
                            Client Acceptance Sign-off
                          </button>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {/* Sub-Tab 3: Workforce Team */}
          {detailSubTab === 'team' && (
            <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-4">
              <h3 className="font-semibold text-white border-b border-slate-800 pb-3">Assigned Workforce & Roles</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {projectDetail.team_members.map((tm) => (
                  <div key={tm.id} className="p-4 bg-slate-950 rounded-lg border border-slate-800 space-y-2">
                    <div className="flex items-center justify-between">
                      <span className="px-2 py-0.5 rounded text-xs bg-slate-800 text-slate-300 uppercase font-mono">
                        {tm.project_role.replace(/_/g, ' ')}
                      </span>
                      <span className="text-xs text-slate-500">{tm.allocation_percentage}% allocation</span>
                    </div>
                    <div className="font-bold text-white">
                      {tm.member_type === 'agent' ? tm.agent_name : tm.user_full_name}
                    </div>
                    <div className="text-xs text-slate-400">
                      {tm.member_type === 'agent' ? `Code: ${tm.agent_code}` : `@${tm.username}`}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Sub-Tab 4: Linked Tasks */}
          {detailSubTab === 'tasks' && (
            <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-4">
              <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                <div>
                  <h3 className="font-semibold text-white">Linked Execution Tasks</h3>
                  <p className="text-xs text-slate-400">Directly integrated with Prompt 21 Workforce Tasks engine</p>
                </div>
              </div>

              <div className="space-y-3">
                {linkedTasks.map((t) => (
                  <div key={t.id} className="p-3 bg-slate-950 rounded-lg border border-slate-800/80 flex items-center justify-between">
                    <div className="space-y-1">
                      <div className="flex items-center gap-2">
                        <span className="font-mono text-xs text-slate-400">{t.task_code}</span>
                        <span className="px-2 py-0.5 rounded text-xs capitalize bg-slate-800 text-slate-300">
                          {t.status}
                        </span>
                        {t.milestone_title && (
                          <span className="text-xs text-blue-400">Milestone: {t.milestone_title}</span>
                        )}
                      </div>
                      <h4 className="font-medium text-sm text-white">{t.title}</h4>
                      <p className="text-xs text-slate-400">Assignee: {t.assignee_agent_name || 'Unassigned'}</p>
                    </div>

                    <div className="text-right text-xs">
                      <span className="text-slate-500">Progress</span>
                      <div className="font-bold text-blue-400 text-sm">{t.progress_percent}%</div>
                    </div>
                  </div>
                ))}
                {linkedTasks.length === 0 && (
                  <p className="text-xs text-slate-500 italic p-4 text-center">No workforce tasks linked to this project yet.</p>
                )}
              </div>
            </div>
          )}

          {/* Sub-Tab 5: Risks & Issues */}
          {detailSubTab === 'risks_issues' && (
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              {/* Risks */}
              <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-4">
                <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                  <h3 className="font-semibold text-white">Risk Matrix</h3>
                  <button
                    type="button"
                    onClick={() => setShowRiskModal(true)}
                    className="px-3 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded text-xs font-medium"
                  >
                    + Log Risk
                  </button>
                </div>

                <div className="space-y-3">
                  {/* Note: Risks are queryable from service */}
                  <p className="text-xs text-slate-400">Active monitoring of project risk factors, probability & mitigation plans.</p>
                </div>
              </div>

              {/* Issues */}
              <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-4">
                <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                  <h3 className="font-semibold text-white">Issues & Blockers</h3>
                  <button
                    type="button"
                    onClick={() => setShowIssueModal(true)}
                    className="px-3 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded text-xs font-medium"
                  >
                    + Log Issue
                  </button>
                </div>

                <div className="space-y-3">
                  <p className="text-xs text-slate-400">Operational blockers requiring prompt escalation or technical resolution.</p>
                </div>
              </div>
            </div>
          )}

          {/* Sub-Tab 6: Change Requests & Budget */}
          {detailSubTab === 'budget_cr' && (
            <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 space-y-4">
              <div className="flex items-center justify-between border-b border-slate-800 pb-3">
                <div>
                  <h3 className="font-semibold text-white">Change Requests (CR) & DoA Approvals</h3>
                  <p className="text-xs text-slate-400">Level A: Human Owner | Level B: Continuous Executive Board | Level C: Division Head</p>
                </div>
                <button
                  type="button"
                  onClick={() => setShowCrModal(true)}
                  className="px-3 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded text-xs font-medium"
                >
                  + Submit CR
                </button>
              </div>

              <div className="space-y-3">
                <p className="text-xs text-slate-400">Strict baseline change control preserving scope integrity and authorized budget ceiling.</p>
              </div>
            </div>
          )}
        </div>
      )}

      {/* ==================================================== */}
      {/* MODAL: CREATE PROJECT */}
      {/* ==================================================== */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50">
          <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-lg w-full p-6 space-y-4">
            <h3 className="text-lg font-bold text-white">Create New Project</h3>
            <form onSubmit={handleCreateProject} className="space-y-3 text-sm">
              <div>
                <label className="block text-xs font-medium text-slate-400 mb-1">Project Name</label>
                <input
                  type="text"
                  required
                  value={createForm.name}
                  onChange={(e) => setCreateForm({ ...createForm, name: e.target.value })}
                  className="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white"
                  placeholder="e.g. Enterprise AI Knowledge Portal"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-slate-400 mb-1">Project Classification</label>
                  <select
                    value={createForm.project_type}
                    onChange={(e) => setCreateForm({ ...createForm, project_type: e.target.value as ProjectType })}
                    className="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white"
                  >
                    <option value="customer_project">Customer Project</option>
                    <option value="internal_initiative">Internal Initiative</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-medium text-slate-400 mb-1">Category</label>
                  <select
                    value={createForm.category}
                    onChange={(e) => setCreateForm({ ...createForm, category: e.target.value as ProjectCategory })}
                    className="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white"
                  >
                    <option value="ai_automation">AI Automation</option>
                    <option value="web_app">Web App</option>
                    <option value="internal_platform">Internal Platform</option>
                    <option value="rnd">R&D</option>
                    <option value="branding_marketing">Branding</option>
                  </select>
                </div>
              </div>

              {createForm.project_type === 'customer_project' && (
                <div>
                  <label className="block text-xs font-medium text-slate-400 mb-1">Client</label>
                  <select
                    required
                    value={createForm.client_id}
                    onChange={(e) => setCreateForm({ ...createForm, client_id: e.target.value })}
                    className="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white"
                  >
                    <option value="">Select Client...</option>
                    {clients.map(c => (
                      <option key={c.id} value={c.id}>{c.company_name} ({c.client_code})</option>
                    ))}
                  </select>
                </div>
              )}

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-medium text-slate-400 mb-1">Proposed Budget (IDR)</label>
                  <input
                    type="number"
                    value={createForm.proposed_budget}
                    onChange={(e) => setCreateForm({ ...createForm, proposed_budget: Number(e.target.value) })}
                    className="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-slate-400 mb-1">Approved Budget Limit (IDR)</label>
                  <input
                    type="number"
                    value={createForm.approved_budget_limit}
                    onChange={(e) => setCreateForm({ ...createForm, approved_budget_limit: Number(e.target.value) })}
                    className="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-medium text-slate-400 mb-1">Description</label>
                <textarea
                  rows={2}
                  value={createForm.description}
                  onChange={(e) => setCreateForm({ ...createForm, description: e.target.value })}
                  className="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-800">
                <button
                  type="button"
                  onClick={() => setShowCreateModal(false)}
                  className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded font-medium"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded font-medium"
                >
                  Create Project
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ==================================================== */}
      {/* MODAL: ADVANCE STAGE */}
      {/* ==================================================== */}
      {showTransitionModal && projectDetail && (
        <div className="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50">
          <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full p-6 space-y-4">
            <h3 className="text-lg font-bold text-white">Advance Lifecycle Stage</h3>
            <p className="text-xs text-slate-400">Current Stage: <strong className="text-white capitalize">{projectDetail.lifecycle_stage.replace('_', ' ')}</strong></p>

            <form onSubmit={handleTransitionStage} className="space-y-3 text-sm">
              <div>
                <label className="block text-xs font-medium text-slate-400 mb-1">Target Stage</label>
                <select
                  required
                  value={transitionForm.target_stage}
                  onChange={(e) => setTransitionForm({ ...transitionForm, target_stage: e.target.value })}
                  className="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white"
                >
                  <option value="">Select Next Stage...</option>
                  {projectDetail.project_type === 'customer_project' ? (
                    <>
                      <option value="qualification">Qualification</option>
                      <option value="proposal">Proposal</option>
                      <option value="contracted">Contracted</option>
                      <option value="planning">Planning</option>
                      <option value="execution">Execution</option>
                      <option value="review_acceptance">Review & Acceptance</option>
                      <option value="delivery_closure">Delivery & Closure</option>
                      <option value="warranty_support">Warranty & Support</option>
                    </>
                  ) : (
                    <>
                      <option value="evaluation">Evaluation</option>
                      <option value="authorized">Authorized (DoA Level B/C)</option>
                      <option value="planning">Planning</option>
                      <option value="execution">Execution</option>
                      <option value="review">Review</option>
                      <option value="completed_archived">Completed & Archived</option>
                    </>
                  )}
                </select>
              </div>

              <div>
                <label className="block text-xs font-medium text-slate-400 mb-1">Governance Reason / Rationale</label>
                <textarea
                  required
                  rows={3}
                  value={transitionForm.reason}
                  onChange={(e) => setTransitionForm({ ...transitionForm, reason: e.target.value })}
                  className="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white"
                  placeholder="e.g. Milestones M1/M2 delivered, UAT sign-off completed by client CTO."
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-slate-800">
                <button
                  type="button"
                  onClick={() => setShowTransitionModal(false)}
                  className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded font-medium"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded font-medium"
                >
                  Confirm Transition
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ==================================================== */}
      {/* MODAL: ADD MILESTONE */}
      {/* ==================================================== */}
      {showMilestoneModal && (
        <div className="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50">
          <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full p-6 space-y-4">
            <h3 className="text-lg font-bold text-white">Add Project Milestone</h3>
            <form onSubmit={handleCreateMilestone} className="space-y-3 text-sm">
              <div>
                <label className="block text-xs font-medium text-slate-400 mb-1">Title</label>
                <input
                  type="text"
                  required
                  value={milestoneForm.title}
                  onChange={(e) => setMilestoneForm({ ...milestoneForm, title: e.target.value })}
                  className="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white"
                  placeholder="e.g. M1: Architecture & Vector DB Pipeline"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-slate-400 mb-1">Target Date</label>
                <input
                  type="date"
                  required
                  value={milestoneForm.target_date}
                  onChange={(e) => setMilestoneForm({ ...milestoneForm, target_date: e.target.value })}
                  className="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-slate-400 mb-1">Description</label>
                <textarea
                  rows={2}
                  value={milestoneForm.description}
                  onChange={(e) => setMilestoneForm({ ...milestoneForm, description: e.target.value })}
                  className="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white"
                />
              </div>
              <div className="flex justify-end gap-2 pt-3 border-t border-slate-800">
                <button
                  type="button"
                  onClick={() => setShowMilestoneModal(false)}
                  className="px-4 py-2 bg-slate-800 text-slate-300 rounded font-medium"
                >
                  Cancel
                </button>
                <button type="submit" className="px-4 py-2 bg-blue-600 text-white rounded font-medium">
                  Add Milestone
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ==================================================== */}
      {/* MODAL: ADD DELIVERABLE */}
      {/* ==================================================== */}
      {showDeliverableModal && (
        <div className="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50">
          <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full p-6 space-y-4">
            <h3 className="text-lg font-bold text-white">Add Deliverable</h3>
            <form onSubmit={handleCreateDeliverable} className="space-y-3 text-sm">
              <div>
                <label className="block text-xs font-medium text-slate-400 mb-1">Title</label>
                <input
                  type="text"
                  required
                  value={deliverableForm.title}
                  onChange={(e) => setDeliverableForm({ ...deliverableForm, title: e.target.value })}
                  className="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-slate-400 mb-1">Acceptance Criteria</label>
                <textarea
                  rows={2}
                  value={deliverableForm.acceptance_criteria}
                  onChange={(e) => setDeliverableForm({ ...deliverableForm, acceptance_criteria: e.target.value })}
                  className="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-slate-400 mb-1">Supporting Evidence URL</label>
                <input
                  type="text"
                  value={deliverableForm.supporting_evidence_url}
                  onChange={(e) => setDeliverableForm({ ...deliverableForm, supporting_evidence_url: e.target.value })}
                  className="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white"
                  placeholder="e.g. /docs/deliverable.pdf or https://..."
                />
              </div>
              <div className="flex justify-end gap-2 pt-3 border-t border-slate-800">
                <button
                  type="button"
                  onClick={() => setShowDeliverableModal(false)}
                  className="px-4 py-2 bg-slate-800 text-slate-300 rounded font-medium"
                >
                  Cancel
                </button>
                <button type="submit" className="px-4 py-2 bg-blue-600 text-white rounded font-medium">
                  Add Deliverable
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ==================================================== */}
      {/* MODAL: SUBMIT CHANGE REQUEST */}
      {/* ==================================================== */}
      {showCrModal && (
        <div className="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50">
          <div className="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full p-6 space-y-4">
            <h3 className="text-lg font-bold text-white">Submit Change Request (CR)</h3>
            <form onSubmit={handleCreateCr} className="space-y-3 text-sm">
              <div>
                <label className="block text-xs font-medium text-slate-400 mb-1">Title</label>
                <input
                  type="text"
                  required
                  value={crForm.title}
                  onChange={(e) => setCrForm({ ...crForm, title: e.target.value })}
                  className="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-slate-400 mb-1">Budget Impact (IDR)</label>
                <input
                  type="number"
                  value={crForm.budget_impact_amount}
                  onChange={(e) => setCrForm({ ...crForm, budget_impact_amount: Number(e.target.value) })}
                  className="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white"
                />
                <span className="text-[10px] text-slate-500">Amounts &gt; IDR 5M trigger DoA Level A (Human Owner approval)</span>
              </div>
              <div>
                <label className="block text-xs font-medium text-slate-400 mb-1">Schedule Impact (Days)</label>
                <input
                  type="number"
                  value={crForm.schedule_impact_days}
                  onChange={(e) => setCrForm({ ...crForm, schedule_impact_days: Number(e.target.value) })}
                  className="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-slate-400 mb-1">Business Justification</label>
                <textarea
                  rows={2}
                  value={crForm.business_justification}
                  onChange={(e) => setCrForm({ ...crForm, business_justification: e.target.value })}
                  className="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white"
                />
              </div>
              <div className="flex justify-end gap-2 pt-3 border-t border-slate-800">
                <button
                  type="button"
                  onClick={() => setShowCrModal(false)}
                  className="px-4 py-2 bg-slate-800 text-slate-300 rounded font-medium"
                >
                  Cancel
                </button>
                <button type="submit" className="px-4 py-2 bg-blue-600 text-white rounded font-medium">
                  Submit CR
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
