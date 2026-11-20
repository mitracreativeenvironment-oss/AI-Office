import React, { useState, useEffect, useCallback } from 'react';
import { resourcePlanningService } from '../services/resourcePlanningService';
import type {
  ResourceItem,
  AgentCapacity,
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
  UtilizationClassification,
} from '../types/resourcePlanning';
import type { User } from '../types/auth';

interface ResourcePlanningViewProps {
  currentUser: User | null;
}

export const ResourcePlanningView: React.FC<ResourcePlanningViewProps> = ({ currentUser }) => {
  const [activeTab, setActiveTab] = useState<
    'overview' | 'workload' | 'skills' | 'resources' | 'reservations' | 'requests' | 'conflicts' | 'priorities' | 'forecast'
  >('overview');

  const [loading, setLoading] = useState<boolean>(true);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);

  // Data states
  const [overview, setOverview] = useState<OrganizationOverview | null>(null);
  const [divisions, setDivisions] = useState<DivisionalCapacityComparison[]>([]);
  const [agentsCapacity, setAgentsCapacity] = useState<AgentCapacity[]>([]);
  const [workloadHeatmap, setWorkloadHeatmap] = useState<AgentWorkloadHeatmapItem[]>([]);
  const [workloadSummary, setWorkloadSummary] = useState<WorkloadSummary | null>(null);
  const [skillsMatrix, setSkillsMatrix] = useState<SkillMatrixItem[]>([]);
  const [bottlenecks, setBottlenecks] = useState<SkillBottleneck[]>([]);
  const [spofList, setSpofList] = useState<SingleAgentDependency[]>([]);
  const [resources, setResources] = useState<ResourceItem[]>([]);
  const [reservations, setReservations] = useState<CapacityReservation[]>([]);
  const [requests, setRequests] = useState<ResourceRequest[]>([]);
  const [allocations, setAllocations] = useState<ResourceAllocation[]>([]);
  const [conflicts, setConflicts] = useState<ResourceConflict[]>([]);
  const [priorities, setPriorities] = useState<PortfolioPriority[]>([]);
  const [forecasts, setForecasts] = useState<ForecastSnapshot[]>([]);
  const [gaps, setGaps] = useState<GapAnalysis[]>([]);

  // Modals & form states
  const [showResourceModal, setShowResourceModal] = useState<boolean>(false);
  const [newResourceName, setNewResourceName] = useState<string>('');
  const [newResourceCategory, setNewResourceCategory] = useState<string>('software_tool');
  const [newResourceCapacity, setNewResourceCapacity] = useState<number>(100);
  const [newResourceCost, setNewResourceCost] = useState<number>(50000);

  const [showRequestModal, setShowRequestModal] = useState<boolean>(false);
  const [newRequestTitle, setNewRequestTitle] = useState<string>('');
  const [newRequestHours, setNewRequestHours] = useState<number>(20);
  const [newRequestCategory, setNewRequestCategory] = useState<string>('workforce_agent');
  const [newRequestPriority, setNewRequestPriority] = useState<string>('high');
  const [newRequestCost, setNewRequestCost] = useState<number>(2500000);

  const [showReservationModal, setShowReservationModal] = useState<boolean>(false);
  const [newResTitle, setNewResTitle] = useState<string>('');
  const [newResType, setNewResType] = useState<string>('internal_operations');
  const [newResPct, setNewResPct] = useState<number>(20);
  const [newResHours, setNewResHours] = useState<number>(8);

  const [resolvingConflict, setResolvingConflict] = useState<ResourceConflict | null>(null);
  const [conflictNotes, setConflictNotes] = useState<string>('');

  const [overridingProject, setOverridingProject] = useState<PortfolioPriority | null>(null);
  const [newRankVal, setNewRankVal] = useState<number>(1);
  const [newTierVal, setNewTierVal] = useState<string>('tier_1_critical');
  const [overrideReasonVal, setOverrideReasonVal] = useState<string>('');

  const loadAllData = useCallback(async () => {
    setLoading(true);
    setErrorMsg(null);
    try {
      const [
        ovRes,
        divRes,
        agRes,
        wlRes,
        wlSum,
        skRes,
        bnRes,
        spofRes,
        resRes,
        rsvRes,
        reqRes,
        alcRes,
        cnfRes,
        priRes,
        fcsRes,
        gapRes,
      ] = await Promise.all([
        resourcePlanningService.getOrganizationOverview(),
        resourcePlanningService.getDivisionalComparison(),
        resourcePlanningService.getAllAgentsCapacity(),
        resourcePlanningService.getWorkloadHeatmap(4),
        resourcePlanningService.getWorkloadSummary(),
        resourcePlanningService.getSkillCoverageMatrix(),
        resourcePlanningService.getSkillBottlenecks(),
        resourcePlanningService.getSingleAgentDependencies(),
        resourcePlanningService.getResources(),
        resourcePlanningService.getReservations(),
        resourcePlanningService.getRequests(),
        resourcePlanningService.getAllocations(),
        resourcePlanningService.getConflicts(),
        resourcePlanningService.getPortfolioPriorities(),
        resourcePlanningService.getForecastSnapshots('weekly'),
        resourcePlanningService.getGapAnalyses(),
      ]);

      setOverview(ovRes);
      setDivisions(divRes);
      setAgentsCapacity(agRes);
      setWorkloadHeatmap(wlRes);
      setWorkloadSummary(wlSum);
      setSkillsMatrix(skRes);
      setBottlenecks(bnRes);
      setSpofList(spofRes);
      setResources(resRes);
      setReservations(rsvRes);
      setRequests(reqRes);
      setAllocations(alcRes);
      setConflicts(cnfRes);
      setPriorities(priRes);
      setForecasts(fcsRes);
      setGaps(gapRes);
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to load Resource & Capacity Planning data.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadAllData();
  }, [loadAllData]);

  const handleScanConflicts = async () => {
    try {
      setLoading(true);
      const res = await resourcePlanningService.detectConflicts();
      setConflicts(res);
      setSuccessMsg(`Conflict scan complete: ${res.length} potential conflict(s) detected.`);
      setTimeout(() => setSuccessMsg(null), 4000);
    } catch (err: any) {
      setErrorMsg(err.message || 'Conflict scan failed.');
    } finally {
      setLoading(false);
    }
  };

  const handleResolveConflict = async () => {
    if (!resolvingConflict) return;
    try {
      await resourcePlanningService.resolveConflict(resolvingConflict.id, conflictNotes || 'Resolved by human coordinator.');
      setSuccessMsg(`Conflict ${resolvingConflict.conflict_code} resolved.`);
      setResolvingConflict(null);
      setConflictNotes('');
      loadAllData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to resolve conflict.');
    }
  };

  const handleDismissConflict = async (conflict: ResourceConflict) => {
    try {
      await resourcePlanningService.dismissConflict(conflict.id, 'Temporary variance approved by management.');
      setSuccessMsg(`Conflict ${conflict.conflict_code} dismissed.`);
      loadAllData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to dismiss conflict.');
    }
  };

  const handleCreateResource = async () => {
    if (!newResourceName) return;
    try {
      await resourcePlanningService.createResource({
        name: newResourceName,
        category: newResourceCategory as any,
        total_capacity: newResourceCapacity,
        unit_cost_idr: newResourceCost,
      });
      setShowResourceModal(false);
      setNewResourceName('');
      setSuccessMsg('Operational resource registered successfully.');
      loadAllData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to create resource.');
    }
  };

  const handleCreateReservation = async () => {
    if (!newResTitle) return;
    try {
      await resourcePlanningService.createReservation({
        title: newResTitle,
        reservation_type: newResType as any,
        reserved_percentage: newResPct,
        reserved_hours_per_week: newResHours,
      });
      setShowReservationModal(false);
      setNewResTitle('');
      setSuccessMsg('Capacity reservation ring-fenced successfully.');
      loadAllData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to create reservation.');
    }
  };

  const handleCreateRequest = async () => {
    if (!newRequestTitle) return;
    try {
      await resourcePlanningService.createRequest({
        title: newRequestTitle,
        hours_per_week: newRequestHours,
        resource_category: newRequestCategory as any,
        priority: newRequestPriority as any,
        estimated_cost_idr: newRequestCost,
      });
      setShowRequestModal(false);
      setNewRequestTitle('');
      setSuccessMsg('Resource request draft created.');
      loadAllData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to create resource request.');
    }
  };

  const handleSubmitRequest = async (id: number) => {
    try {
      await resourcePlanningService.submitRequest(id);
      setSuccessMsg('Request submitted for capacity assessment.');
      loadAllData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Failed to submit request.');
    }
  };

  const handleAssessRequest = async (id: number) => {
    try {
      await resourcePlanningService.assessRequestCapacity(id, 'Capacity assessment executed automatically.');
      setSuccessMsg('Capacity assessment completed.');
      loadAllData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Capacity assessment failed.');
    }
  };

  const handleApproveRequest = async (id: number) => {
    try {
      await resourcePlanningService.approveRequest(id);
      setSuccessMsg('Request approved under Delegated Authority.');
      loadAllData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Approval failed.');
    }
  };

  const handleAllocateRequest = async (id: number) => {
    try {
      await resourcePlanningService.allocateRequest(id, {});
      setSuccessMsg('Resource allocation confirmed.');
      loadAllData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Allocation failed.');
    }
  };

  const handleOverrideRank = async () => {
    if (!overridingProject) return;
    try {
      await resourcePlanningService.overridePriority(
        overridingProject.project_id,
        newRankVal,
        newTierVal,
        overrideReasonVal || 'Executive rebalancing.'
      );
      setSuccessMsg(`Project ${overridingProject.project_code} priority updated.`);
      setOverridingProject(null);
      loadAllData();
    } catch (err: any) {
      setErrorMsg(err.message || 'Override failed.');
    }
  };

  const getUtilizationBadge = (classification: UtilizationClassification, pct: number) => {
    switch (classification) {
      case 'overallocated':
        return <span className="mce-badge mce-badge-danger">Overallocated ({pct}%)</span>;
      case 'high_utilization':
        return <span className="mce-badge mce-badge-warning">High Load ({pct}%)</span>;
      case 'balanced':
        return <span className="mce-badge mce-badge-success">Balanced ({pct}%)</span>;
      case 'underutilized':
        return <span className="mce-badge mce-badge-info">Underutilized ({pct}%)</span>;
      case 'unavailable':
      default:
        return <span className="mce-badge mce-badge-secondary">Unavailable</span>;
    }
  };

  const getTierBadge = (tier: string) => {
    switch (tier) {
      case 'tier_1_critical':
        return <span className="mce-badge mce-badge-danger">Tier 1 Critical</span>;
      case 'tier_2_high':
        return <span className="mce-badge mce-badge-warning">Tier 2 High</span>;
      case 'tier_3_medium':
        return <span className="mce-badge mce-badge-info">Tier 3 Medium</span>;
      case 'tier_4_deferrable':
      default:
        return <span className="mce-badge mce-badge-secondary">Tier 4 Deferrable</span>;
    }
  };

  return (
    <div className="mce-dashboard-container" style={{ padding: '24px' }}>
      {/* Header Banner */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
        <div>
          <h2 style={{ margin: 0, fontSize: '24px', fontWeight: 700, color: '#f8fafc' }}>
            Resource & Capacity Planning, Portfolio Prioritization & Operational Allocation
          </h2>
          <p style={{ margin: '4px 0 0 0', color: '#94a3b8', fontSize: '14px' }}>
            Workforce capacity management, skill coverage matrix, internal ops ring-fencing & multi-criteria portfolio ranking.
          </p>
        </div>
        <div style={{ display: 'flex', gap: '10px' }}>
          <button className="mce-btn mce-btn-secondary" onClick={handleScanConflicts}>
            ⚡ Scan Conflicts Now
          </button>
          <button className="mce-btn mce-btn-primary" onClick={loadAllData}>
            🔄 Refresh
          </button>
        </div>
      </div>

      {/* Notifications */}
      {errorMsg && (
        <div className="mce-alert mce-alert-danger" style={{ marginBottom: '16px' }}>
          {errorMsg}
        </div>
      )}
      {successMsg && (
        <div className="mce-alert mce-alert-success" style={{ marginBottom: '16px' }}>
          {successMsg}
        </div>
      )}

      {/* KPI Cards */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: '16px', marginBottom: '24px' }}>
        <div className="mce-card" style={{ padding: '16px', borderLeft: '4px solid #3b82f6' }}>
          <div style={{ color: '#94a3b8', fontSize: '12px', textTransform: 'uppercase' }}>Workforce Headcount</div>
          <div style={{ fontSize: '26px', fontWeight: 700, color: '#f8fafc', marginTop: '4px' }}>
            {overview?.overview.headcount || 0} Agents
          </div>
          <div style={{ fontSize: '12px', color: '#64748b', marginTop: '4px' }}>5/2 Shift Compliance</div>
        </div>

        <div className="mce-card" style={{ padding: '16px', borderLeft: '4px solid #10b981' }}>
          <div style={{ color: '#94a3b8', fontSize: '12px', textTransform: 'uppercase' }}>Scheduled Capacity</div>
          <div style={{ fontSize: '26px', fontWeight: 700, color: '#10b981', marginTop: '4px' }}>
            {overview?.overview.scheduled_capacity_hours || 0} hrs/wk
          </div>
          <div style={{ fontSize: '12px', color: '#64748b', marginTop: '4px' }}>Net Available: {overview?.overview.available_capacity_hours || 0}h</div>
        </div>

        <div className="mce-card" style={{ padding: '16px', borderLeft: '4px solid #f59e0b' }}>
          <div style={{ color: '#94a3b8', fontSize: '12px', textTransform: 'uppercase' }}>Overall Utilization</div>
          <div style={{ fontSize: '26px', fontWeight: 700, color: '#f59e0b', marginTop: '4px' }}>
            {overview?.overview.utilization_percent || 0}%
          </div>
          <div style={{ fontSize: '12px', color: '#64748b', marginTop: '4px' }}>
            Reserved Ops: {overview?.overview.reserved_capacity_hours || 0}h
          </div>
        </div>

        <div className="mce-card" style={{ padding: '16px', borderLeft: '4px solid #ef4444' }}>
          <div style={{ color: '#94a3b8', fontSize: '12px', textTransform: 'uppercase' }}>Active Conflicts</div>
          <div style={{ fontSize: '26px', fontWeight: 700, color: '#ef4444', marginTop: '4px' }}>
            {conflicts.filter((c) => c.resolution_status === 'detected' || c.resolution_status === 'acknowledged').length}
          </div>
          <div style={{ fontSize: '12px', color: '#64748b', marginTop: '4px' }}>
            {conflicts.filter((c) => c.severity === 'critical').length} Critical Collision(s)
          </div>
        </div>

        <div className="mce-card" style={{ padding: '16px', borderLeft: '4px solid #8b5cf6' }}>
          <div style={{ color: '#94a3b8', fontSize: '12px', textTransform: 'uppercase' }}>Prioritized Projects</div>
          <div style={{ fontSize: '26px', fontWeight: 700, color: '#8b5cf6', marginTop: '4px' }}>
            {priorities.length}
          </div>
          <div style={{ fontSize: '12px', color: '#64748b', marginTop: '4px' }}>
            {priorities.filter((p) => p.priority_tier === 'tier_1_critical').length} Tier 1 Critical
          </div>
        </div>

        <div className="mce-card" style={{ padding: '16px', borderLeft: '4px solid #06b6d4' }}>
          <div style={{ color: '#94a3b8', fontSize: '12px', textTransform: 'uppercase' }}>Operational Assets</div>
          <div style={{ fontSize: '26px', fontWeight: 700, color: '#06b6d4', marginTop: '4px' }}>
            {resources.length}
          </div>
          <div style={{ fontSize: '12px', color: '#64748b', marginTop: '4px' }}>
            {resources.filter((r) => r.status === 'active').length} Active Tools & Pools
          </div>
        </div>
      </div>

      {/* Navigation Sub-Tabs */}
      <div className="mce-tabs" style={{ display: 'flex', gap: '8px', borderBottom: '1px solid #334155', marginBottom: '20px', overflowX: 'auto' }}>
        {[
          { key: 'overview', label: '📊 Capacity Overview', icon: '📊' },
          { key: 'workload', label: '🔥 Workload Heatmap', icon: '🔥' },
          { key: 'skills', label: '🎯 Skill Coverage & SPOF', icon: '🎯' },
          { key: 'resources', label: '🛠️ Resource Inventory', icon: '🛠️' },
          { key: 'reservations', label: '🛡️ Internal Ops Ring-Fence', icon: '🛡️' },
          { key: 'requests', label: '📝 Requests & Allocation', icon: '📝' },
          { key: 'conflicts', label: '⚠️ Conflict Center', icon: '⚠️' },
          { key: 'priorities', label: '⚖️ Portfolio Prioritization', icon: '⚖️' },
          { key: 'forecast', label: '📈 Forecast & Gap Analysis', icon: '📈' },
        ].map((tab) => (
          <button
            key={tab.key}
            className={`mce-tab-btn ${activeTab === tab.key ? 'mce-tab-active' : ''}`}
            onClick={() => setActiveTab(tab.key as any)}
            style={{
              padding: '10px 16px',
              border: 'none',
              background: activeTab === tab.key ? '#1e293b' : 'transparent',
              color: activeTab === tab.key ? '#38bdf8' : '#94a3b8',
              fontWeight: 600,
              cursor: 'pointer',
              borderBottom: activeTab === tab.key ? '2px solid #38bdf8' : '2px solid transparent',
            }}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {/* TAB CONTENT */}

      {/* 1. OVERVIEW & CAPACITY PLANNING */}
      {activeTab === 'overview' && (
        <div>
          {/* Divisional Roll-up Cards */}
          <h3 style={{ color: '#f8fafc', fontSize: '18px', marginBottom: '12px' }}>Divisional Capacity Allocation</h3>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '16px', marginBottom: '24px' }}>
            {divisions.map((div) => (
              <div key={div.division_id} className="mce-card" style={{ padding: '16px' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <span style={{ fontWeight: 700, color: '#f8fafc' }}>{div.division_name}</span>
                  <span className="mce-badge mce-badge-secondary">{div.division_code}</span>
                </div>
                <div style={{ marginTop: '10px', fontSize: '13px', color: '#94a3b8' }}>
                  Headcount: <strong style={{ color: '#f8fafc' }}>{div.headcount}</strong> | Scheduled: <strong style={{ color: '#f8fafc' }}>{div.scheduled_capacity_hours}h</strong>
                </div>
                <div style={{ marginTop: '8px' }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '12px', color: '#94a3b8', marginBottom: '4px' }}>
                    <span>Utilization</span>
                    <span>{div.utilization_percent}%</span>
                  </div>
                  <div style={{ width: '100%', height: '8px', background: '#334155', borderRadius: '4px', overflow: 'hidden' }}>
                    <div
                      style={{
                        width: `${Math.min(100, div.utilization_percent)}%`,
                        height: '100%',
                        background: div.utilization_percent > 100 ? '#ef4444' : div.utilization_percent > 85 ? '#f59e0b' : '#10b981',
                      }}
                    />
                  </div>
                </div>
                <div style={{ display: 'flex', gap: '8px', marginTop: '12px', fontSize: '11px' }}>
                  <span style={{ color: '#10b981' }}>Allocated: {div.allocated_capacity_hours}h</span>
                  <span style={{ color: '#8b5cf6' }}>Reserved: {div.reserved_capacity_hours}h</span>
                  <span style={{ color: '#38bdf8' }}>Avail: {div.available_capacity_hours}h</span>
                </div>
              </div>
            ))}
          </div>

          {/* Agent Capacity Table */}
          <h3 style={{ color: '#f8fafc', fontSize: '18px', marginBottom: '12px' }}>AI Agent Workforce Capacity</h3>
          <div className="mce-card" style={{ overflowX: 'auto' }}>
            <table className="mce-table" style={{ width: '100%', textAlign: 'left' }}>
              <thead>
                <tr>
                  <th>Agent</th>
                  <th>Unit</th>
                  <th>Shift Policy</th>
                  <th>Gross (h)</th>
                  <th>Scheduled (h)</th>
                  <th>Allocated (h)</th>
                  <th>Reserved (h)</th>
                  <th>Available (h)</th>
                  <th>Utilization</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                {agentsCapacity.map((ag) => (
                  <tr key={ag.agent_id}>
                    <td>
                      <div style={{ fontWeight: 600, color: '#f8fafc' }}>{ag.name}</div>
                      <div style={{ fontSize: '11px', color: '#64748b' }}>{ag.agent_code} ({ag.classification})</div>
                    </td>
                    <td>{ag.unit_code || ag.unit_name}</td>
                    <td>{ag.roster_schedule_type}</td>
                    <td>{ag.gross_capacity_hours}h</td>
                    <td>{ag.scheduled_capacity_hours}h</td>
                    <td style={{ color: '#10b981' }}>{ag.allocated_capacity_hours}h</td>
                    <td style={{ color: '#8b5cf6' }}>{ag.reserved_capacity_hours}h</td>
                    <td style={{ fontWeight: 700, color: '#38bdf8' }}>{ag.available_capacity_hours}h</td>
                    <td>{getUtilizationBadge(ag.utilization_classification, ag.utilization_percent)}</td>
                    <td>
                      <span className={`mce-badge ${ag.operational_status === 'working' ? 'mce-badge-success' : 'mce-badge-secondary'}`}>
                        {ag.operational_status}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* 2. WORKLOAD HEATMAP */}
      {activeTab === 'workload' && (
        <div>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
            <h3 style={{ color: '#f8fafc', fontSize: '18px', margin: 0 }}>Workload Heatmap (4-Week Rolling Horizon)</h3>
            <span style={{ fontSize: '13px', color: '#94a3b8' }}>
              Overall Workload Health: <strong style={{ color: workloadSummary?.status === 'healthy' ? '#10b981' : '#f59e0b' }}>{workloadSummary?.status}</strong>
            </span>
          </div>

          <div className="mce-card" style={{ overflowX: 'auto', padding: '16px' }}>
            <table className="mce-table" style={{ width: '100%', textAlign: 'left' }}>
              <thead>
                <tr>
                  <th>Agent</th>
                  <th>Current Load</th>
                  <th>Week 1</th>
                  <th>Week 2</th>
                  <th>Week 3</th>
                  <th>Week 4</th>
                </tr>
              </thead>
              <tbody>
                {workloadHeatmap.map((row) => (
                  <tr key={row.agent_id}>
                    <td>
                      <strong style={{ color: '#f8fafc' }}>{row.name}</strong>
                      <div style={{ fontSize: '11px', color: '#64748b' }}>{row.agent_code} | {row.unit_code}</div>
                    </td>
                    <td>{getUtilizationBadge(row.current_classification, row.current_utilization_pct)}</td>
                    {row.timeline.map((wk, idx) => (
                      <td key={idx} style={{ minWidth: '140px' }}>
                        <div style={{ fontSize: '11px', color: '#94a3b8', marginBottom: '4px' }}>
                          W{wk.week_number} ({wk.allocated_capacity_hours + wk.reserved_capacity_hours}h / {wk.scheduled_capacity_hours}h)
                        </div>
                        <div style={{ width: '100%', height: '8px', background: '#334155', borderRadius: '4px', overflow: 'hidden' }}>
                          <div
                            style={{
                              width: `${Math.min(100, wk.utilization_percent)}%`,
                              height: '100%',
                              background: wk.utilization_percent > 100 ? '#ef4444' : wk.utilization_percent > 85 ? '#f59e0b' : '#10b981',
                            }}
                          />
                        </div>
                      </td>
                    ))}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* 3. SKILL COVERAGE & SPOF */}
      {activeTab === 'skills' && (
        <div>
          {/* SPOF Alert Callout */}
          {spofList.length > 0 && (
            <div className="mce-alert mce-alert-warning" style={{ marginBottom: '20px' }}>
              <div style={{ fontWeight: 700, marginBottom: '6px' }}>⚠️ Single Point of Failure (SPOF) Identified</div>
              <div>The following competencies are only mastered by 1 active agent. Cross-training recommended:</div>
              <ul style={{ margin: '8px 0 0 16px', padding: 0 }}>
                {spofList.map((spof) => (
                  <li key={spof.skill_id}>
                    <strong>{spof.skill_name} ({spof.skill_code})</strong>: Sole agent is{' '}
                    <em>{spof.sole_agent?.agent_name} ({spof.sole_agent?.agent_code})</em>. {spof.recommendation}
                  </li>
                ))}
              </ul>
            </div>
          )}

          {/* Skill Bottlenecks */}
          {bottlenecks.length > 0 && (
            <div className="mce-alert mce-alert-danger" style={{ marginBottom: '20px' }}>
              <div style={{ fontWeight: 700, marginBottom: '6px' }}>🚨 Critical Competency Bottlenecks</div>
              <ul style={{ margin: '8px 0 0 16px', padding: 0 }}>
                {bottlenecks.map((bn) => (
                  <li key={bn.skill_id}>
                    <strong>{bn.skill_name}</strong>: Deficit of {bn.deficit_hours}h/wk across {bn.open_request_count} open requests. {bn.recommended_action}
                  </li>
                ))}
              </ul>
            </div>
          )}

          <h3 style={{ color: '#f8fafc', fontSize: '18px', marginBottom: '12px' }}>Competency Coverage & Verification Matrix</h3>
          <div className="mce-card" style={{ overflowX: 'auto' }}>
            <table className="mce-table" style={{ width: '100%', textAlign: 'left' }}>
              <thead>
                <tr>
                  <th>Skill Code</th>
                  <th>Skill Name</th>
                  <th>Category</th>
                  <th>Agents Qualified</th>
                  <th>Verified vs Pending</th>
                  <th>Proficiency Distribution</th>
                  <th>SPOF Risk</th>
                </tr>
              </thead>
              <tbody>
                {skillsMatrix.map((sk) => (
                  <tr key={sk.skill_id}>
                    <td><code>{sk.skill_code}</code></td>
                    <td><strong>{sk.skill_name}</strong></td>
                    <td>{sk.category}</td>
                    <td>{sk.total_agents_count} Agent(s)</td>
                    <td>
                      <span className="mce-badge mce-badge-success">{sk.verified_agents_count} Verified</span>
                      {sk.pending_agents_count > 0 && (
                        <span className="mce-badge mce-badge-warning" style={{ marginLeft: '4px' }}>
                          {sk.pending_agents_count} Pending
                        </span>
                      )}
                    </td>
                    <td>
                      <span style={{ fontSize: '11px', color: '#94a3b8' }}>
                        Beg: {sk.proficiency_breakdown.beginner || 0} | Comp: {sk.proficiency_breakdown.competent || 0} | Prof:{' '}
                        {sk.proficiency_breakdown.proficient || 0} | Exp: {sk.proficiency_breakdown.expert || 0} | Mst:{' '}
                        {sk.proficiency_breakdown.master || 0}
                      </span>
                    </td>
                    <td>
                      {sk.is_single_point_of_failure ? (
                        <span className="mce-badge mce-badge-danger">SPOF ALERT</span>
                      ) : (
                        <span className="mce-badge mce-badge-success">Protected</span>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* 4. RESOURCE INVENTORY & REGISTRY */}
      {activeTab === 'resources' && (
        <div>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
            <h3 style={{ color: '#f8fafc', fontSize: '18px', margin: 0 }}>Operational Resource Inventory</h3>
            <button className="mce-btn mce-btn-primary" onClick={() => setShowResourceModal(true)}>
              + Register Resource
            </button>
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))', gap: '16px' }}>
            {resources.map((res) => (
              <div key={res.id} className="mce-card" style={{ padding: '16px' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <span style={{ fontWeight: 700, color: '#f8fafc' }}>{res.name}</span>
                  <span className={`mce-badge ${res.status === 'active' ? 'mce-badge-success' : 'mce-badge-warning'}`}>
                    {res.status}
                  </span>
                </div>
                <div style={{ fontSize: '11px', color: '#64748b', marginTop: '2px' }}>
                  <code>{res.resource_code}</code> | {res.category}
                </div>
                <p style={{ fontSize: '13px', color: '#94a3b8', margin: '8px 0' }}>{res.description}</p>
                <div style={{ fontSize: '12px', color: '#cbd5e1', marginBottom: '8px' }}>
                  Total Capacity: <strong>{res.total_capacity} {res.unit_of_measure}</strong> | Allocated:{' '}
                  <strong style={{ color: '#10b981' }}>{res.allocated_capacity}</strong> | Available:{' '}
                  <strong style={{ color: '#38bdf8' }}>{res.available_capacity}</strong>
                </div>
                <div style={{ width: '100%', height: '8px', background: '#334155', borderRadius: '4px', overflow: 'hidden' }}>
                  <div
                    style={{
                      width: `${Math.min(100, res.utilization_percent)}%`,
                      height: '100%',
                      background: res.utilization_percent > 85 ? '#f59e0b' : '#38bdf8',
                    }}
                  />
                </div>
                <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: '10px', fontSize: '11px', color: '#64748b' }}>
                  <span>Owner: {res.owner_unit_code || 'Enterprise'}</span>
                  <span>Cost: IDR {res.unit_cost_idr.toLocaleString()}/{res.unit_of_measure}</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* 5. CAPACITY RESERVATIONS (Internal Ops) */}
      {activeTab === 'reservations' && (
        <div>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
            <div>
              <h3 style={{ color: '#f8fafc', fontSize: '18px', margin: 0 }}>Internal Operations Capacity Reservations</h3>
              <p style={{ margin: '4px 0 0 0', color: '#94a3b8', fontSize: '13px' }}>
                Continuous Executive Board ring-fenced capacity ensuring commercial client delivery does not starve internal operations, R&D, or welfare.
              </p>
            </div>
            <button className="mce-btn mce-btn-primary" onClick={() => setShowReservationModal(true)}>
              + Ring-Fence Capacity
            </button>
          </div>

          <div className="mce-card" style={{ overflowX: 'auto' }}>
            <table className="mce-table" style={{ width: '100%', textAlign: 'left' }}>
              <thead>
                <tr>
                  <th>Code</th>
                  <th>Title & Purpose</th>
                  <th>Type</th>
                  <th>Scope / Target</th>
                  <th>Reserved Capacity</th>
                  <th>Authority</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                {reservations.map((rsv) => (
                  <tr key={rsv.id}>
                    <td><code>{rsv.reservation_code}</code></td>
                    <td>
                      <strong style={{ color: '#f8fafc' }}>{rsv.title}</strong>
                      <div style={{ fontSize: '12px', color: '#94a3b8' }}>{rsv.justification}</div>
                    </td>
                    <td><span className="mce-badge mce-badge-info">{rsv.reservation_type}</span></td>
                    <td>{rsv.agent_name || rsv.unit_name || 'Enterprise'}</td>
                    <td>
                      <strong style={{ color: '#8b5cf6' }}>{rsv.reserved_percentage}%</strong> ({rsv.reserved_hours_per_week}h/wk)
                    </td>
                    <td>{rsv.sponsoring_authority}</td>
                    <td>
                      <span className={`mce-badge ${rsv.status === 'active' ? 'mce-badge-success' : 'mce-badge-secondary'}`}>
                        {rsv.status}
                      </span>
                    </td>
                    <td>
                      {rsv.status === 'active' && (
                        <button
                          className="mce-btn mce-btn-sm mce-btn-secondary"
                          onClick={() => resourcePlanningService.deactivateReservation(rsv.id).then(loadAllData)}
                        >
                          Conclude
                        </button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* 6. RESOURCE REQUESTS & ALLOCATIONS */}
      {activeTab === 'requests' && (
        <div>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
            <h3 style={{ color: '#f8fafc', fontSize: '18px', margin: 0 }}>Resource Requests & Allocations</h3>
            <button className="mce-btn mce-btn-primary" onClick={() => setShowRequestModal(true)}>
              + New Resource Request
            </button>
          </div>

          <div className="mce-card" style={{ overflowX: 'auto' }}>
            <table className="mce-table" style={{ width: '100%', textAlign: 'left' }}>
              <thead>
                <tr>
                  <th>Request Code</th>
                  <th>Title</th>
                  <th>Category</th>
                  <th>Hours / Wk</th>
                  <th>Est. Cost</th>
                  <th>DoA Level</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {requests.map((req) => (
                  <tr key={req.id}>
                    <td><code>{req.request_code}</code></td>
                    <td>
                      <strong style={{ color: '#f8fafc' }}>{req.title}</strong>
                      <div style={{ fontSize: '12px', color: '#94a3b8' }}>{req.purpose}</div>
                    </td>
                    <td>{req.resource_category}</td>
                    <td>{req.hours_per_week}h</td>
                    <td>IDR {req.estimated_cost_idr.toLocaleString()}</td>
                    <td>
                      <span className={`mce-badge ${req.approval_authority_level === 'A' ? 'mce-badge-danger' : 'mce-badge-info'}`}>
                        Level {req.approval_authority_level}
                      </span>
                    </td>
                    <td>
                      <span className={`mce-badge mce-badge-${req.lifecycle_status === 'approved' || req.lifecycle_status === 'allocated' ? 'success' : 'warning'}`}>
                        {req.lifecycle_status}
                      </span>
                    </td>
                    <td>
                      <div style={{ display: 'flex', gap: '4px' }}>
                        {req.lifecycle_status === 'draft' && (
                          <button className="mce-btn mce-btn-sm mce-btn-primary" onClick={() => handleSubmitRequest(req.id)}>
                            Submit
                          </button>
                        )}
                        {req.lifecycle_status === 'submitted' && (
                          <button className="mce-btn mce-btn-sm mce-btn-info" onClick={() => handleAssessRequest(req.id)}>
                            Assess
                          </button>
                        )}
                        {req.lifecycle_status === 'capacity_assessed' && (
                          <button className="mce-btn mce-btn-sm mce-btn-success" onClick={() => handleApproveRequest(req.id)}>
                            Approve
                          </button>
                        )}
                        {req.lifecycle_status === 'approved' && (
                          <button className="mce-btn mce-btn-sm mce-btn-primary" onClick={() => handleAllocateRequest(req.id)}>
                            Allocate
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* 7. CONFLICT DETECTION CENTER */}
      {activeTab === 'conflicts' && (
        <div>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
            <div>
              <h3 style={{ color: '#f8fafc', fontSize: '18px', margin: 0 }}>Automated Conflict Detection Center</h3>
              <p style={{ margin: '4px 0 0 0', color: '#94a3b8', fontSize: '13px' }}>
                Automated detection of overallocations, 5/2 rest cycle collisions, skill competency mismatches & maintenance outages.
              </p>
            </div>
            <button className="mce-btn mce-btn-secondary" onClick={handleScanConflicts}>
              ⚡ Run Full Conflict Scan
            </button>
          </div>

          <div style={{ display: 'grid', gap: '16px' }}>
            {conflicts.map((cnf) => (
              <div key={cnf.id} className="mce-card" style={{ padding: '16px', borderLeft: `4px solid ${cnf.severity === 'critical' ? '#ef4444' : '#f59e0b'}` }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <div>
                    <span className={`mce-badge ${cnf.severity === 'critical' ? 'mce-badge-danger' : 'mce-badge-warning'}`}>
                      {cnf.severity.toUpperCase()}
                    </span>
                    <strong style={{ color: '#f8fafc', marginLeft: '8px', fontSize: '16px' }}>{cnf.title}</strong>
                  </div>
                  <span className="mce-badge mce-badge-secondary">{cnf.resolution_status}</span>
                </div>
                <p style={{ color: '#cbd5e1', fontSize: '14px', margin: '8px 0' }}>{cnf.description}</p>
                <div style={{ fontSize: '13px', color: '#94a3b8' }}>
                  <strong>Impact:</strong> {cnf.impact_summary}
                </div>
                <div style={{ fontSize: '13px', color: '#38bdf8', marginTop: '4px' }}>
                  <strong>Recommended Resolution:</strong> {cnf.recommended_resolution}
                </div>
                {cnf.resolution_status !== 'resolved' && (
                  <div style={{ display: 'flex', gap: '8px', marginTop: '12px' }}>
                    <button className="mce-btn mce-btn-sm mce-btn-primary" onClick={() => setResolvingConflict(cnf)}>
                      Resolve Conflict
                    </button>
                    <button className="mce-btn mce-btn-sm mce-btn-secondary" onClick={() => handleDismissConflict(cnf)}>
                      Dismiss Variance
                    </button>
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>
      )}

      {/* 8. PORTFOLIO PRIORITIZATION */}
      {activeTab === 'priorities' && (
        <div>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
            <div>
              <h3 style={{ color: '#f8fafc', fontSize: '18px', margin: 0 }}>Strategic Portfolio Prioritization Matrix</h3>
              <p style={{ margin: '4px 0 0 0', color: '#94a3b8', fontSize: '13px' }}>
                Multi-criteria scoring model: Strategic Alignment, Customer Commitment, Business Value, Urgency, Feasibility & Risk.
              </p>
            </div>
          </div>

          <div className="mce-card" style={{ overflowX: 'auto' }}>
            <table className="mce-table" style={{ width: '100%', textAlign: 'left' }}>
              <thead>
                <tr>
                  <th>Rank</th>
                  <th>Project Code</th>
                  <th>Project Name</th>
                  <th>Type</th>
                  <th>Strategic</th>
                  <th>Customer</th>
                  <th>Business</th>
                  <th>Composite Score</th>
                  <th>Priority Tier</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                {priorities.map((p) => (
                  <tr key={p.id}>
                    <td>
                      <strong style={{ fontSize: '16px', color: '#38bdf8' }}>#{p.calculated_rank}</strong>
                      {p.is_overridden === 1 && <span style={{ fontSize: '10px', color: '#f59e0b', marginLeft: '4px' }}>(OVERRIDDEN)</span>}
                    </td>
                    <td><code>{p.project_code}</code></td>
                    <td><strong>{p.project_name}</strong></td>
                    <td>{p.project_type === 'customer_project' ? 'Customer' : 'Internal Initiative'}</td>
                    <td>{p.strategic_alignment_score}</td>
                    <td>{p.customer_commitment_score}</td>
                    <td>{p.business_value_score}</td>
                    <td>
                      <strong style={{ fontSize: '15px', color: '#10b981' }}>{p.composite_priority_score}</strong>
                    </td>
                    <td>{getTierBadge(p.priority_tier)}</td>
                    <td>
                      <button
                        className="mce-btn mce-btn-sm mce-btn-secondary"
                        onClick={() => {
                          setOverridingProject(p);
                          setNewRankVal(p.calculated_rank);
                          setNewTierVal(p.priority_tier);
                        }}
                      >
                        Override Rank
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* 9. FORECASTING & GAP ANALYSIS */}
      {activeTab === 'forecast' && (
        <div>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
            <div>
              <h3 style={{ color: '#f8fafc', fontSize: '18px', margin: 0 }}>Capacity Forecasting & Gap Analysis</h3>
              <p style={{ margin: '4px 0 0 0', color: '#94a3b8', fontSize: '13px' }}>
                <span className="mce-badge mce-badge-info" style={{ marginRight: '6px' }}>FORECAST PROJECTION</span>
                Forward-looking demand vs supply models and competency deficit remediation.
              </p>
            </div>
          </div>

          {/* Forecast Snapshots Table */}
          <h4 style={{ color: '#f8fafc', marginBottom: '8px' }}>Rolling Demand vs Supply Snapshots</h4>
          <div className="mce-card" style={{ overflowX: 'auto', marginBottom: '24px' }}>
            <table className="mce-table" style={{ width: '100%', textAlign: 'left' }}>
              <thead>
                <tr>
                  <th>Period</th>
                  <th>Headcount</th>
                  <th>Scheduled Capacity</th>
                  <th>Reserved Capacity</th>
                  <th>Projected Demand</th>
                  <th>Net Available</th>
                  <th>Proj. Utilization</th>
                  <th>Forecast Status</th>
                </tr>
              </thead>
              <tbody>
                {forecasts.map((fc) => (
                  <tr key={fc.id}>
                    <td>
                      <strong>{fc.snapshot_code}</strong>
                      <div style={{ fontSize: '11px', color: '#64748b' }}>{fc.period_start_date} to {fc.period_end_date}</div>
                    </td>
                    <td>{fc.total_agents_headcount}</td>
                    <td>{fc.scheduled_capacity_hours}h</td>
                    <td>{fc.reserved_capacity_hours}h</td>
                    <td style={{ color: '#f59e0b' }}>{fc.projected_demand_hours}h</td>
                    <td style={{ fontWeight: 700, color: fc.net_available_capacity_hours < 0 ? '#ef4444' : '#10b981' }}>
                      {fc.net_available_capacity_hours}h
                    </td>
                    <td>{fc.utilization_projected_percent}%</td>
                    <td>
                      <span className={`mce-badge ${fc.status === 'deficit' || fc.status === 'critical_shortage' ? 'mce-badge-danger' : 'mce-badge-success'}`}>
                        {fc.status.toUpperCase()}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Gap Analyses */}
          <h4 style={{ color: '#f8fafc', marginBottom: '8px' }}>Identified Competency & Capacity Deficits</h4>
          <div className="mce-card" style={{ overflowX: 'auto' }}>
            <table className="mce-table" style={{ width: '100%', textAlign: 'left' }}>
              <thead>
                <tr>
                  <th>Gap Code</th>
                  <th>Category</th>
                  <th>Required vs Available</th>
                  <th>Net Deficit</th>
                  <th>Recommended Action Plan</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                {gaps.map((gap) => (
                  <tr key={gap.id}>
                    <td><code>{gap.gap_code}</code></td>
                    <td>{gap.gap_category}</td>
                    <td>{gap.required_capacity_hours}h / {gap.available_capacity_hours}h</td>
                    <td style={{ color: '#ef4444', fontWeight: 700 }}>-{gap.deficit_hours}h</td>
                    <td>
                      <strong style={{ color: '#f8fafc' }}>{gap.recommended_action}</strong>
                      <div style={{ fontSize: '12px', color: '#94a3b8' }}>{gap.action_plan_description}</div>
                    </td>
                    <td>
                      <span className={`mce-badge ${gap.status === 'resolved' ? 'mce-badge-success' : 'mce-badge-warning'}`}>
                        {gap.status}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* MODALS */}

      {/* Modal: Register Resource */}
      {showResourceModal && (
        <div className="mce-modal-backdrop" style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.7)', display: 'flex', justifyContent: 'center', alignItems: 'center', zIndex: 1000 }}>
          <div className="mce-card" style={{ width: '480px', padding: '24px' }}>
            <h3 style={{ color: '#f8fafc', margin: '0 0 16px 0' }}>Register Operational Resource</h3>
            <div style={{ marginBottom: '12px' }}>
              <label style={{ display: 'block', color: '#94a3b8', fontSize: '13px', marginBottom: '4px' }}>Resource Name</label>
              <input
                className="mce-input"
                style={{ width: '100%' }}
                value={newResourceName}
                onChange={(e) => setNewResourceName(e.target.value)}
                placeholder="e.g. 9Router Fast Inference Pool"
              />
            </div>
            <div style={{ marginBottom: '12px' }}>
              <label style={{ display: 'block', color: '#94a3b8', fontSize: '13px', marginBottom: '4px' }}>Category</label>
              <select
                className="mce-input"
                style={{ width: '100%' }}
                value={newResourceCategory}
                onChange={(e) => setNewResourceCategory(e.target.value)}
              >
                <option value="software_tool">Software Tool</option>
                <option value="ai_model_service">AI Model Service (9Router)</option>
                <option value="compute_infrastructure">Compute Infrastructure (Hermes)</option>
                <option value="development_environment">Development Environment</option>
                <option value="shared_facility">Shared Facility (VHQ)</option>
              </select>
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', marginBottom: '16px' }}>
              <div>
                <label style={{ display: 'block', color: '#94a3b8', fontSize: '13px', marginBottom: '4px' }}>Total Capacity</label>
                <input
                  type="number"
                  className="mce-input"
                  style={{ width: '100%' }}
                  value={newResourceCapacity}
                  onChange={(e) => setNewResourceCapacity(Number(e.target.value))}
                />
              </div>
              <div>
                <label style={{ display: 'block', color: '#94a3b8', fontSize: '13px', marginBottom: '4px' }}>Unit Cost (IDR)</label>
                <input
                  type="number"
                  className="mce-input"
                  style={{ width: '100%' }}
                  value={newResourceCost}
                  onChange={(e) => setNewResourceCost(Number(e.target.value))}
                />
              </div>
            </div>
            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '8px' }}>
              <button className="mce-btn mce-btn-secondary" onClick={() => setShowResourceModal(false)}>
                Cancel
              </button>
              <button className="mce-btn mce-btn-primary" onClick={handleCreateResource}>
                Save Resource
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal: New Reservation */}
      {showReservationModal && (
        <div className="mce-modal-backdrop" style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.7)', display: 'flex', justifyContent: 'center', alignItems: 'center', zIndex: 1000 }}>
          <div className="mce-card" style={{ width: '480px', padding: '24px' }}>
            <h3 style={{ color: '#f8fafc', margin: '0 0 16px 0' }}>Ring-Fence Capacity (Internal Operations)</h3>
            <div style={{ marginBottom: '12px' }}>
              <label style={{ display: 'block', color: '#94a3b8', fontSize: '13px', marginBottom: '4px' }}>Reservation Title</label>
              <input
                className="mce-input"
                style={{ width: '100%' }}
                value={newResTitle}
                onChange={(e) => setNewResTitle(e.target.value)}
                placeholder="e.g. AI Platform Core Tooling R&D"
              />
            </div>
            <div style={{ marginBottom: '12px' }}>
              <label style={{ display: 'block', color: '#94a3b8', fontSize: '13px', marginBottom: '4px' }}>Reservation Type</label>
              <select
                className="mce-input"
                style={{ width: '100%' }}
                value={newResType}
                onChange={(e) => setNewResType(e.target.value)}
              >
                <option value="internal_operations">Internal Operations</option>
                <option value="bizdev_growth">BizDev & Client Solutions</option>
                <option value="platform_rnd">Platform Architecture & R&D</option>
                <option value="system_maintenance">System Maintenance</option>
                <option value="agent_welfare_buffer">Agent Welfare & Handover Buffer</option>
              </select>
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', marginBottom: '16px' }}>
              <div>
                <label style={{ display: 'block', color: '#94a3b8', fontSize: '13px', marginBottom: '4px' }}>Reserved %</label>
                <input
                  type="number"
                  className="mce-input"
                  style={{ width: '100%' }}
                  value={newResPct}
                  onChange={(e) => setNewResPct(Number(e.target.value))}
                />
              </div>
              <div>
                <label style={{ display: 'block', color: '#94a3b8', fontSize: '13px', marginBottom: '4px' }}>Hours / Week</label>
                <input
                  type="number"
                  className="mce-input"
                  style={{ width: '100%' }}
                  value={newResHours}
                  onChange={(e) => setNewResHours(Number(e.target.value))}
                />
              </div>
            </div>
            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '8px' }}>
              <button className="mce-btn mce-btn-secondary" onClick={() => setShowReservationModal(false)}>
                Cancel
              </button>
              <button className="mce-btn mce-btn-primary" onClick={handleCreateReservation}>
                Activate Reservation
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal: New Request */}
      {showRequestModal && (
        <div className="mce-modal-backdrop" style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.7)', display: 'flex', justifyContent: 'center', alignItems: 'center', zIndex: 1000 }}>
          <div className="mce-card" style={{ width: '480px', padding: '24px' }}>
            <h3 style={{ color: '#f8fafc', margin: '0 0 16px 0' }}>New Resource Request</h3>
            <div style={{ marginBottom: '12px' }}>
              <label style={{ display: 'block', color: '#94a3b8', fontSize: '13px', marginBottom: '4px' }}>Request Title</label>
              <input
                className="mce-input"
                style={{ width: '100%' }}
                value={newRequestTitle}
                onChange={(e) => setNewRequestTitle(e.target.value)}
                placeholder="e.g. Lead Frontend TypeScript Engineer"
              />
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', marginBottom: '12px' }}>
              <div>
                <label style={{ display: 'block', color: '#94a3b8', fontSize: '13px', marginBottom: '4px' }}>Resource Category</label>
                <select
                  className="mce-input"
                  style={{ width: '100%' }}
                  value={newRequestCategory}
                  onChange={(e) => setNewRequestCategory(e.target.value)}
                >
                  <option value="workforce_agent">Workforce AI Agent</option>
                  <option value="operational_tool">Operational Tool</option>
                  <option value="compute_infra">Compute Infrastructure</option>
                  <option value="ai_model_service">AI Model Service</option>
                </select>
              </div>
              <div>
                <label style={{ display: 'block', color: '#94a3b8', fontSize: '13px', marginBottom: '4px' }}>Hours / Week</label>
                <input
                  type="number"
                  className="mce-input"
                  style={{ width: '100%' }}
                  value={newRequestHours}
                  onChange={(e) => setNewRequestHours(Number(e.target.value))}
                />
              </div>
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', marginBottom: '16px' }}>
              <div>
                <label style={{ display: 'block', color: '#94a3b8', fontSize: '13px', marginBottom: '4px' }}>Estimated Cost (IDR)</label>
                <input
                  type="number"
                  className="mce-input"
                  style={{ width: '100%' }}
                  value={newRequestCost}
                  onChange={(e) => setNewRequestCost(Number(e.target.value))}
                />
                <span style={{ fontSize: '11px', color: newRequestCost > 5000000 ? '#ef4444' : '#10b981' }}>
                  {newRequestCost > 5000000 ? 'DoA Level A (Owner)' : 'DoA Level B (Board)'}
                </span>
              </div>
              <div>
                <label style={{ display: 'block', color: '#94a3b8', fontSize: '13px', marginBottom: '4px' }}>Priority</label>
                <select
                  className="mce-input"
                  style={{ width: '100%' }}
                  value={newRequestPriority}
                  onChange={(e) => setNewRequestPriority(e.target.value)}
                >
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
            </div>
            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '8px' }}>
              <button className="mce-btn mce-btn-secondary" onClick={() => setShowRequestModal(false)}>
                Cancel
              </button>
              <button className="mce-btn mce-btn-primary" onClick={handleCreateRequest}>
                Create Draft
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal: Resolve Conflict */}
      {resolvingConflict && (
        <div className="mce-modal-backdrop" style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.7)', display: 'flex', justifyContent: 'center', alignItems: 'center', zIndex: 1000 }}>
          <div className="mce-card" style={{ width: '480px', padding: '24px' }}>
            <h3 style={{ color: '#f8fafc', margin: '0 0 16px 0' }}>Resolve Resource Conflict</h3>
            <p style={{ color: '#cbd5e1', fontSize: '14px', marginBottom: '12px' }}>
              <strong>{resolvingConflict.conflict_code}:</strong> {resolvingConflict.title}
            </p>
            <div style={{ marginBottom: '16px' }}>
              <label style={{ display: 'block', color: '#94a3b8', fontSize: '13px', marginBottom: '4px' }}>Resolution Notes</label>
              <textarea
                className="mce-input"
                style={{ width: '100%', minHeight: '80px' }}
                value={conflictNotes}
                onChange={(e) => setConflictNotes(e.target.value)}
                placeholder="Detail the shift rebalance, replacement assignment, or mitigation actions..."
              />
            </div>
            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '8px' }}>
              <button className="mce-btn mce-btn-secondary" onClick={() => setResolvingConflict(null)}>
                Cancel
              </button>
              <button className="mce-btn mce-btn-primary" onClick={handleResolveConflict}>
                Mark Resolved
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal: Override Portfolio Priority */}
      {overridingProject && (
        <div className="mce-modal-backdrop" style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.7)', display: 'flex', justifyContent: 'center', alignItems: 'center', zIndex: 1000 }}>
          <div className="mce-card" style={{ width: '480px', padding: '24px' }}>
            <h3 style={{ color: '#f8fafc', margin: '0 0 16px 0' }}>Override Portfolio Rank</h3>
            <p style={{ color: '#cbd5e1', fontSize: '14px', marginBottom: '12px' }}>
              Project: <strong>{overridingProject.project_name}</strong> (<code>{overridingProject.project_code}</code>)
            </p>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', marginBottom: '12px' }}>
              <div>
                <label style={{ display: 'block', color: '#94a3b8', fontSize: '13px', marginBottom: '4px' }}>New Rank</label>
                <input
                  type="number"
                  className="mce-input"
                  style={{ width: '100%' }}
                  value={newRankVal}
                  onChange={(e) => setNewRankVal(Number(e.target.value))}
                />
              </div>
              <div>
                <label style={{ display: 'block', color: '#94a3b8', fontSize: '13px', marginBottom: '4px' }}>Priority Tier</label>
                <select
                  className="mce-input"
                  style={{ width: '100%' }}
                  value={newTierVal}
                  onChange={(e) => setNewTierVal(e.target.value)}
                >
                  <option value="tier_1_critical">Tier 1 Critical</option>
                  <option value="tier_2_high">Tier 2 High</option>
                  <option value="tier_3_medium">Tier 3 Medium</option>
                  <option value="tier_4_deferrable">Tier 4 Deferrable</option>
                </select>
              </div>
            </div>
            <div style={{ marginBottom: '16px' }}>
              <label style={{ display: 'block', color: '#94a3b8', fontSize: '13px', marginBottom: '4px' }}>Executive Justification</label>
              <textarea
                className="mce-input"
                style={{ width: '100%', minHeight: '70px' }}
                value={overrideReasonVal}
                onChange={(e) => setOverrideReasonVal(e.target.value)}
                placeholder="Reason for manual rank/tier adjustment..."
              />
            </div>
            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '8px' }}>
              <button className="mce-btn mce-btn-secondary" onClick={() => setOverridingProject(null)}>
                Cancel
              </button>
              <button className="mce-btn mce-btn-primary" onClick={handleOverrideRank}>
                Save Override
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
