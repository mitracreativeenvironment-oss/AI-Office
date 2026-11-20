import React, { useState, useEffect, useCallback } from 'react';
import { workforceService } from '../services/workforceService';
import type {
  AiAgent,
  WorkforceMetrics,
  PerformanceHistoryRecord,
  AgentAssignment,
  AgentSkill,
  AgentResponsibility,
  RuntimeMapping,
} from '../types/workforce';

interface WorkforceViewProps {
  isAuthenticated: boolean;
  onOpenLogin: () => void;
}

export const WorkforceView: React.FC<WorkforceViewProps> = ({ isAuthenticated, onOpenLogin }) => {
  const [metrics, setMetrics] = useState<WorkforceMetrics | null>(null);
  const [agents, setAgents] = useState<AiAgent[]>([]);
  const [totalAgents, setTotalAgents] = useState<number>(0);
  const [loading, setLoading] = useState<boolean>(true);
  const [search, setSearch] = useState<string>('');
  const [lifecycleFilter, setLifecycleFilter] = useState<string>('');
  const [classFilter, setClassFilter] = useState<string>('');

  // Selected Agent Detail Modal
  const [selectedAgent, setSelectedAgent] = useState<AiAgent | null>(null);
  const [activeDetailTab, setActiveDetailTab] = useState<'profile' | 'assignments' | 'skills' | 'performance' | 'runtime'>('profile');
  const [perfHistory, setPerfHistory] = useState<PerformanceHistoryRecord[]>([]);
  const [assignments, setAssignments] = useState<AgentAssignment[]>([]);
  const [skills, setSkills] = useState<AgentSkill[]>([]);
  const [responsibilities, setResponsibilities] = useState<AgentResponsibility[]>([]);
  const [runtimeMappings, setRuntimeMappings] = useState<RuntimeMapping[]>([]);
  const [loadingDetail, setLoadingDetail] = useState<boolean>(false);
  const [actionMessage, setActionMessage] = useState<string | null>(null);

  // Create Agent Modal
  const [isCreating, setIsCreating] = useState<boolean>(false);
  const [newName, setNewName] = useState<string>('');
  const [newCode, setNewCode] = useState<string>('');
  const [newClass, setNewClass] = useState<string>('specialist');
  const [newDesc, setNewDesc] = useState<string>('');

  const fetchWorkforce = useCallback(async () => {
    setLoading(true);
    try {
      const [metricsData, agentsData] = await Promise.all([
        workforceService.getMetrics().catch(() => null),
        workforceService.listAgents({
          search: search || undefined,
          lifecycle_state: lifecycleFilter || undefined,
          classification: classFilter || undefined,
        }, 50, 0).catch(() => ({ items: [], total: 0 })),
      ]);

      if (metricsData) setMetrics(metricsData);
      setAgents(agentsData.items);
      setTotalAgents(agentsData.total);
    } finally {
      setLoading(false);
    }
  }, [search, lifecycleFilter, classFilter]);

  useEffect(() => {
    fetchWorkforce();
  }, [fetchWorkforce]);

  const handleSelectAgent = async (agent: AiAgent) => {
    setSelectedAgent(agent);
    setLoadingDetail(true);
    setActionMessage(null);
    try {
      const [fullAgent, history, asgns, sks, resps, runtimes] = await Promise.all([
        workforceService.getAgent(agent.id),
        workforceService.getPerformanceHistory(agent.id, undefined, 50, 0),
        workforceService.getAssignments(agent.id),
        workforceService.getAgentSkills(agent.id),
        workforceService.getResponsibilities(agent.id),
        workforceService.getRuntimeMappings(agent.id),
      ]);
      setSelectedAgent(fullAgent);
      setPerfHistory(history.records);
      setAssignments(asgns);
      setSkills(sks);
      setResponsibilities(resps);
      setRuntimeMappings(runtimes);
    } finally {
      setLoadingDetail(false);
    }
  };

  const handleTransition = async (toState: string) => {
    if (!selectedAgent) return;
    try {
      const updated = await workforceService.transitionState(selectedAgent.id, toState, `Transitioned to ${toState} via dashboard.`);
      setSelectedAgent(updated);
      setActionMessage(`Agent transitioned to '${toState}' successfully.`);
      fetchWorkforce();
      // Refresh history
      const history = await workforceService.getPerformanceHistory(selectedAgent.id);
      setPerfHistory(history.records);
    } catch (err: unknown) {
      setActionMessage(`Error: ${err instanceof Error ? err.message : String(err)}`);
    }
  };

  const handleVerifyEvidence = async (recordId: number) => {
    try {
      const verified = await workforceService.verifyPerformanceEvidence(recordId, 'verified', 'Executive Board Reviewer', 'Verified via dashboard review.');
      setPerfHistory((prev) => prev.map((p) => (p.id === recordId ? verified : p)));
      setActionMessage('Performance record evidence verified successfully.');
    } catch (err: unknown) {
      setActionMessage(`Error: ${err instanceof Error ? err.message : String(err)}`);
    }
  };

  const handleCreateAgent = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newName.trim()) return;

    try {
      const created = await workforceService.createAgent({
        name: newName.trim(),
        agent_code: newCode.trim() || undefined,
        classification: newClass,
        description: newDesc.trim() || undefined,
      });
      setIsCreating(false);
      setNewName('');
      setNewCode('');
      setNewDesc('');
      fetchWorkforce();
      handleSelectAgent(created);
    } catch (err: unknown) {
      alert(`Failed to create agent: ${err instanceof Error ? err.message : String(err)}`);
    }
  };

  return (
    <div className="mce-workforce-container">
      {/* Metrics Row */}
      <section className="mce-cards-grid" style={{ marginBottom: '20px' }}>
        <div className="mce-card">
          <div className="mce-card-header">
            <h4 className="mce-card-title">Total Workforce</h4>
            <span className="mce-pill mce-pill-primary">REGISTERED</span>
          </div>
          <div className="mce-card-body">
            <div style={{ fontSize: '1.8rem', fontWeight: 800, color: '#f8fafc' }}>
              {metrics?.total_registered_agents ?? 0}
            </div>
            <p className="mce-card-desc" style={{ margin: '4px 0 0' }}>Permanent AI workforce identities</p>
          </div>
        </div>

        <div className="mce-card">
          <div className="mce-card-header">
            <h4 className="mce-card-title">Active Roster</h4>
            <span className="mce-pill mce-pill-success">OPERATIONAL</span>
          </div>
          <div className="mce-card-body">
            <div style={{ fontSize: '1.8rem', fontWeight: 800, color: '#4ade80' }}>
              {metrics?.active_agents ?? 0}
            </div>
            <p className="mce-card-desc" style={{ margin: '4px 0 0' }}>Active 5/2 staggered roster coverage</p>
          </div>
        </div>

        <div className="mce-card">
          <div className="mce-card-header">
            <h4 className="mce-card-title">Under Review</h4>
            <span className="mce-pill mce-pill-warning">GOVERNANCE</span>
          </div>
          <div className="mce-card-body">
            <div style={{ fontSize: '1.8rem', fontWeight: 800, color: '#fbbf24' }}>
              {metrics?.under_review_agents ?? 0}
            </div>
            <p className="mce-card-desc" style={{ margin: '4px 0 0' }}>Proposed & under qualification review</p>
          </div>
        </div>

        <div className="mce-card">
          <div className="mce-card-header">
            <h4 className="mce-card-title">Suspended / Retired</h4>
            <span className="mce-pill mce-pill-neutral">HISTORICAL</span>
          </div>
          <div className="mce-card-body">
            <div style={{ fontSize: '1.8rem', fontWeight: 800, color: '#94a3b8' }}>
              {(metrics?.suspended_agents ?? 0) + (metrics?.retired_agents ?? 0)}
            </div>
            <p className="mce-card-desc" style={{ margin: '4px 0 0' }}>History and records permanently intact</p>
          </div>
        </div>
      </section>

      {/* Directory & Controls */}
      <section className="mce-card">
        <div className="mce-card-header" style={{ flexWrap: 'wrap', gap: '12px' }}>
          <div>
            <h3 className="mce-card-title">AI Agent Workforce Directory</h3>
            <p className="mce-card-desc">Persistent organizational identities, responsibilities, and competencies.</p>
          </div>
          <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
            {isAuthenticated ? (
              <button
                type="button"
                className="mce-btn mce-btn-primary"
                onClick={() => setIsCreating(true)}
              >
                + Register New Agent
              </button>
            ) : (
              <button type="button" className="mce-btn mce-btn-secondary" onClick={onOpenLogin}>
                Sign In to Manage Lifecycle
              </button>
            )}
            <button
              type="button"
              className="mce-btn mce-btn-secondary mce-btn-sm"
              onClick={fetchWorkforce}
              disabled={loading}
            >
              {loading ? 'Refreshing...' : '🔄 Refresh'}
            </button>
          </div>
        </div>

        {/* Filter Bar */}
        <div style={{ padding: '0 20px 16px', display: 'flex', gap: '12px', flexWrap: 'wrap', alignItems: 'center' }}>
          <input
            type="text"
            className="mce-input"
            style={{ maxWidth: '280px' }}
            placeholder="Search code, name, or title..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />

          <select
            className="mce-input"
            style={{ maxWidth: '180px' }}
            value={lifecycleFilter}
            onChange={(e) => setLifecycleFilter(e.target.value)}
          >
            <option value="">All Lifecycle States</option>
            <option value="active">Active</option>
            <option value="draft">Draft</option>
            <option value="proposed">Proposed</option>
            <option value="under_review">Under Review</option>
            <option value="approved">Approved</option>
            <option value="temporarily_suspended">Temporarily Suspended</option>
            <option value="retired">Retired</option>
          </select>

          <select
            className="mce-input"
            style={{ maxWidth: '180px' }}
            value={classFilter}
            onChange={(e) => setClassFilter(e.target.value)}
          >
            <option value="">All Classifications</option>
            <option value="executive">Executive</option>
            <option value="division_lead">Division Lead</option>
            <option value="specialist">Specialist</option>
            <option value="operational">Operational</option>
            <option value="support">Support</option>
          </select>

          <span style={{ fontSize: '0.8rem', color: '#94a3b8', marginLeft: 'auto' }}>
            Showing {agents.length} of {totalAgents} registered agents
          </span>
        </div>

        {/* Table View */}
        <div className="mce-card-body" style={{ overflowX: 'auto', paddingTop: 0 }}>
          <table className="mce-table">
            <thead>
              <tr>
                <th>Agent Code</th>
                <th>Identity & Title</th>
                <th>Classification</th>
                <th>Primary Unit</th>
                <th>Lifecycle State</th>
                <th>Operational Status</th>
                <th>Skills</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              {agents.map((ag) => (
                <tr key={ag.id} style={{ cursor: 'pointer' }} onClick={() => handleSelectAgent(ag)}>
                  <td>
                    <code>{ag.agent_code}</code>
                    <div style={{ fontSize: '0.675rem', color: '#64748b' }} title={ag.agent_uuid}>
                      UUID: {ag.agent_uuid.substring(0, 8)}…
                    </div>
                  </td>
                  <td>
                    <strong>{ag.display_name}</strong>
                    <div style={{ fontSize: '0.725rem', color: '#94a3b8' }}>{ag.name}</div>
                  </td>
                  <td>
                    <span className="mce-pill mce-pill-neutral">{ag.classification.toUpperCase()}</span>
                  </td>
                  <td>
                    {ag.unit_name ? (
                      <span>{ag.unit_name}</span>
                    ) : (
                      <span style={{ opacity: 0.5 }}>Unassigned</span>
                    )}
                  </td>
                  <td>
                    <span
                      className={`mce-pill ${
                        ag.lifecycle_state === 'active'
                          ? 'mce-pill-success'
                          : ag.lifecycle_state === 'draft' || ag.lifecycle_state === 'proposed'
                          ? 'mce-pill-warning'
                          : ag.lifecycle_state === 'temporarily_suspended'
                          ? 'mce-pill-danger'
                          : 'mce-pill-neutral'
                      }`}
                    >
                      {ag.lifecycle_state.toUpperCase()}
                    </span>
                  </td>
                  <td>
                    <span className="mce-pill mce-pill-neutral">{ag.operational_status.toUpperCase()}</span>
                  </td>
                  <td>
                    <span className="mce-pill mce-pill-primary">{ag.skills_count ?? 0} Active</span>
                  </td>
                  <td>
                    <button
                      type="button"
                      className="mce-btn mce-btn-secondary mce-btn-sm"
                      onClick={(e) => {
                        e.stopPropagation();
                        handleSelectAgent(ag);
                      }}
                    >
                      Inspect Profile 🔍
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>

      {/* Selected Agent Modal / Detail View */}
      {selectedAgent && (
        <section className="mce-card" style={{ marginTop: '24px', borderColor: 'rgba(99, 102, 241, 0.4)' }}>
          <div className="mce-card-header" style={{ alignItems: 'flex-start' }}>
            <div>
              <div style={{ display: 'flex', gap: '8px', alignItems: 'center', marginBottom: '6px' }}>
                <span className="mce-pill mce-pill-primary">CODE: {selectedAgent.agent_code}</span>
                <span className="mce-pill mce-pill-success">STATE: {selectedAgent.lifecycle_state.toUpperCase()}</span>
                <span className="mce-pill mce-pill-neutral">ROSTER: {selectedAgent.roster_schedule_type}</span>
                <span className="mce-pill mce-pill-neutral">WELFARE: {selectedAgent.welfare_tier}</span>
              </div>
              <h3 className="mce-card-title">{selectedAgent.display_name}</h3>
              <p className="mce-card-desc">
                Permanent UUID: <code>{selectedAgent.agent_uuid}</code> · Registered: <code>{selectedAgent.created_at}</code>
              </p>
            </div>
            <button
              type="button"
              className="mce-btn mce-btn-secondary mce-btn-sm"
              onClick={() => setSelectedAgent(null)}
            >
              ✕ Close Detail
            </button>
          </div>

          {actionMessage && (
            <div style={{ margin: '0 20px 16px', padding: '10px 14px', background: 'rgba(99, 102, 241, 0.15)', borderRadius: '6px', fontSize: '0.8rem', color: '#c7d2fe' }}>
              ℹ️ {actionMessage}
            </div>
          )}

          {/* Lifecycle Action Bar (Auth Gated) */}
          {isAuthenticated && (
            <div style={{ padding: '0 20px 16px', display: 'flex', gap: '8px', flexWrap: 'wrap', alignItems: 'center', borderBottom: '1px solid rgba(255, 255, 255, 0.08)' }}>
              <span style={{ fontSize: '0.75rem', fontWeight: 700, color: '#94a3b8', textTransform: 'uppercase' }}>
                Available Lifecycle Transitions:
              </span>
              {selectedAgent.lifecycle_state === 'draft' && (
                <button type="button" className="mce-btn mce-btn-primary mce-btn-sm" onClick={() => handleTransition('proposed')}>
                  Propose for Review →
                </button>
              )}
              {selectedAgent.lifecycle_state === 'proposed' && (
                <button type="button" className="mce-btn mce-btn-primary mce-btn-sm" onClick={() => handleTransition('under_review')}>
                  Start Qualification Review →
                </button>
              )}
              {selectedAgent.lifecycle_state === 'under_review' && (
                <button type="button" className="mce-btn mce-btn-primary mce-btn-sm" onClick={() => handleTransition('approved')}>
                  Approve Qualifications →
                </button>
              )}
              {selectedAgent.lifecycle_state === 'approved' && (
                <button type="button" className="mce-btn mce-btn-primary mce-btn-sm" onClick={() => handleTransition('active')}>
                  Activate to Workforce ⚡
                </button>
              )}
              {selectedAgent.lifecycle_state === 'active' && (
                <>
                  <button type="button" className="mce-btn mce-btn-secondary mce-btn-sm" onClick={() => handleTransition('temporarily_suspended')}>
                    Suspend (Temporary) ⏸️
                  </button>
                  <button type="button" className="mce-btn mce-btn-secondary mce-btn-sm" onClick={() => handleTransition('under_reconfiguration')}>
                    Reconfigure Policy ⚙️
                  </button>
                  <button type="button" className="mce-btn mce-btn-secondary mce-btn-sm" style={{ color: '#fca5a5' }} onClick={() => handleTransition('retired')}>
                    Retire Agent 📦
                  </button>
                </>
              )}
              {selectedAgent.lifecycle_state === 'temporarily_suspended' && (
                <button type="button" className="mce-btn mce-btn-primary mce-btn-sm" onClick={() => handleTransition('active')}>
                  Reactivate to Workforce ⚡
                </button>
              )}
              {selectedAgent.lifecycle_state === 'under_reconfiguration' && (
                <button type="button" className="mce-btn mce-btn-primary mce-btn-sm" onClick={() => handleTransition('active')}>
                  Complete Reconfiguration ⚡
                </button>
              )}
            </div>
          )}

          {/* Sub Navigation */}
          <div className="mce-tabs-nav" style={{ paddingLeft: '20px', paddingRight: '20px' }}>
            <button
              type="button"
              className={`mce-tab-btn ${activeDetailTab === 'profile' ? 'mce-tab-active' : ''}`}
              onClick={() => setActiveDetailTab('profile')}
            >
              📄 Organizational Purpose & Profile
            </button>
            <button
              type="button"
              className={`mce-tab-btn ${activeDetailTab === 'assignments' ? 'mce-tab-active' : ''}`}
              onClick={() => setActiveDetailTab('assignments')}
            >
              🏛️ Placement & Assignments ({assignments.length})
            </button>
            <button
              type="button"
              className={`mce-tab-btn ${activeDetailTab === 'skills' ? 'mce-tab-active' : ''}`}
              onClick={() => setActiveDetailTab('skills')}
            >
              💡 Skills & Qualifications ({skills.length})
            </button>
            <button
              type="button"
              className={`mce-tab-btn ${activeDetailTab === 'performance' ? 'mce-tab-active' : ''}`}
              onClick={() => setActiveDetailTab('performance')}
            >
              📜 Individual Performance History ({perfHistory.length})
            </button>
            <button
              type="button"
              className={`mce-tab-btn ${activeDetailTab === 'runtime' ? 'mce-tab-active' : ''}`}
              onClick={() => setActiveDetailTab('runtime')}
            >
              ⚡ Runtime Technical Mapping
            </button>
          </div>

          <div className="mce-card-body">
            {loadingDetail ? (
              <p style={{ color: '#94a3b8' }}>Loading full workforce record...</p>
            ) : (
              <>
                {/* Profile Subtab */}
                {activeDetailTab === 'profile' && (
                  <div>
                    <div style={{ marginBottom: '16px' }}>
                      <h4 className="mce-subhead">Organizational Purpose:</h4>
                      <p style={{ margin: '4px 0 0', color: '#e2e8f0', fontSize: '0.875rem' }}>
                        {selectedAgent.profile?.organizational_purpose || 'Belum ada ringkasan tujuan organisasi.'}
                      </p>
                    </div>

                    <div style={{ marginBottom: '16px' }}>
                      <h4 className="mce-subhead">Description & Scope:</h4>
                      <p style={{ margin: '4px 0 0', color: '#94a3b8', fontSize: '0.85rem' }}>
                        {selectedAgent.profile?.description || 'Belum ada deskripsi profil.'}
                      </p>
                    </div>

                    <div style={{ marginBottom: '16px' }}>
                      <h4 className="mce-subhead">Primary Duties:</h4>
                      <p style={{ margin: '4px 0 0', color: '#cbd5e1', fontSize: '0.85rem' }}>
                        {selectedAgent.profile?.primary_responsibilities_summary || 'Tugas utama terpetakan di daftar tanggung jawab terstruktur.'}
                      </p>
                    </div>

                    {responsibilities.length > 0 && (
                      <div style={{ marginTop: '20px' }}>
                        <h4 className="mce-subhead">Documented Structured Responsibilities ({responsibilities.length}):</h4>
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                          {responsibilities.map((r) => (
                            <div key={r.id} style={{ background: 'rgba(255, 255, 255, 0.02)', border: '1px solid rgba(255, 255, 255, 0.07)', borderRadius: '6px', padding: '10px 14px' }}>
                              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                <strong>{r.title}</strong>
                                <span className={`mce-pill ${r.priority === 'critical' ? 'mce-pill-danger' : 'mce-pill-primary'}`}>
                                  {r.priority.toUpperCase()}
                                </span>
                              </div>
                              <p style={{ margin: '6px 0 0', fontSize: '0.8rem', color: '#94a3b8' }}>{r.description}</p>
                            </div>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                )}

                {/* Placement & Assignments Subtab */}
                {activeDetailTab === 'assignments' && (
                  <div>
                    <h4 className="mce-subhead">Active Placement:</h4>
                    <p style={{ margin: '4px 0 16px', color: '#e2e8f0', fontSize: '0.875rem' }}>
                      Unit: <strong>{selectedAgent.unit_name || 'Unassigned'}</strong> (<code>{selectedAgent.unit_code || '-'}</code>)
                      {selectedAgent.position_title && <span> · Position: <strong>{selectedAgent.position_title}</strong></span>}
                    </p>

                    <h4 className="mce-subhead">Historical Placement Timeline (Transfers Preserved Permanently):</h4>
                    <table className="mce-table">
                      <thead>
                        <tr>
                          <th>Start Date</th>
                          <th>End Date</th>
                          <th>Unit</th>
                          <th>Position</th>
                          <th>Type</th>
                          <th>Assigned By</th>
                          <th>Status</th>
                          <th>Reason</th>
                        </tr>
                      </thead>
                      <tbody>
                        {assignments.map((asgn) => (
                          <tr key={asgn.id}>
                            <td><small>{asgn.start_date}</small></td>
                            <td><small>{asgn.end_date || 'Present'}</small></td>
                            <td><strong>{asgn.unit_name || `Unit #${asgn.unit_id}`}</strong></td>
                            <td>{asgn.position_title || '-'}</td>
                            <td><span className="mce-pill mce-pill-neutral">{asgn.assignment_type.toUpperCase()}</span></td>
                            <td>{asgn.assigned_by}</td>
                            <td>
                              <span className={`mce-pill ${asgn.status === 'active' ? 'mce-pill-success' : 'mce-pill-neutral'}`}>
                                {asgn.status.toUpperCase()}
                              </span>
                            </td>
                            <td><small style={{ color: '#94a3b8' }}>{asgn.reason_for_change || '-'}</small></td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}

                {/* Skills Subtab */}
                {activeDetailTab === 'skills' && (
                  <div>
                    <h4 className="mce-subhead">Verified Competency & Skill Proficiencies:</h4>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '12px' }}>
                      {skills.map((sk) => (
                        <div key={sk.id} style={{ background: 'rgba(255, 255, 255, 0.02)', border: '1px solid rgba(255, 255, 255, 0.08)', borderRadius: '8px', padding: '14px' }}>
                          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '6px' }}>
                            <strong>{sk.skill_name || `Skill #${sk.skill_id}`}</strong>
                            <span className="mce-pill mce-pill-primary">{sk.proficiency_level.toUpperCase()}</span>
                          </div>
                          <div style={{ fontSize: '0.725rem', color: '#64748b', marginBottom: '8px' }}>
                            Code: <code>{sk.skill_code}</code> · Category: {sk.skill_category}
                          </div>
                          <p style={{ margin: 0, fontSize: '0.785rem', color: '#94a3b8' }}>{sk.skill_description}</p>
                          <div style={{ marginTop: '10px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <span className={`mce-pill ${sk.verification_status === 'verified' ? 'mce-pill-success' : 'mce-pill-warning'}`}>
                              {sk.verification_status.toUpperCase()}
                            </span>
                            {sk.verified_by && <small style={{ color: '#64748b' }}>By {sk.verified_by}</small>}
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* Performance History Subtab */}
                {activeDetailTab === 'performance' && (
                  <div>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '12px' }}>
                      <div>
                        <h4 className="mce-subhead" style={{ margin: 0 }}>Individual Agent Performance History (Immutable Provenance):</h4>
                        <p className="mce-card-desc" style={{ margin: '2px 0 0' }}>
                          Attached permanently to UUID <code>{selectedAgent.agent_uuid}</code> across all transfers and lifecycle states.
                        </p>
                      </div>
                    </div>

                    <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                      {perfHistory.map((item) => (
                        <div key={item.id} style={{ background: 'rgba(255, 255, 255, 0.02)', border: '1px solid rgba(255, 255, 255, 0.07)', borderRadius: '8px', padding: '12px 16px' }}>
                          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', flexWrap: 'wrap', gap: '8px' }}>
                            <div>
                              <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
                                <span className="mce-pill mce-pill-neutral">{item.category.toUpperCase()}</span>
                                <strong>{item.title}</strong>
                                {item.superseded_by_id && (
                                  <span className="mce-pill mce-pill-danger">SUPERSEDED BY #{item.superseded_by_id}</span>
                                )}
                              </div>
                              <p style={{ margin: '6px 0 0', fontSize: '0.825rem', color: '#cbd5e1' }}>{item.description}</p>
                            </div>
                            <div style={{ textAlign: 'right' }}>
                              <span className={`mce-pill ${item.evidence_status === 'verified' ? 'mce-pill-success' : item.evidence_status === 'submitted' ? 'mce-pill-warning' : 'mce-pill-danger'}`}>
                                {item.evidence_status.toUpperCase()}
                              </span>
                              <div style={{ fontSize: '0.7rem', color: '#64748b', marginTop: '4px' }}>
                                {item.created_at}
                              </div>
                            </div>
                          </div>

                          <div style={{ marginTop: '8px', paddingTop: '8px', borderTop: '1px solid rgba(255, 255, 255, 0.05)', display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '8px' }}>
                            <div style={{ fontSize: '0.725rem', color: '#94a3b8' }}>
                              {item.task_ref && <span>Ref: <code>{item.task_ref}</code> · </span>}
                              {item.evaluator_identity && <span>Evaluator: <strong>{item.evaluator_identity}</strong></span>}
                            </div>
                            {isAuthenticated && item.evidence_status === 'submitted' && (
                              <button
                                type="button"
                                className="mce-btn mce-btn-secondary mce-btn-sm"
                                onClick={() => handleVerifyEvidence(item.id)}
                              >
                                ✓ Verify Evidence
                              </button>
                            )}
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* Runtime Mapping Subtab */}
                {activeDetailTab === 'runtime' && (
                  <div>
                    <div style={{ marginBottom: '16px', padding: '12px 16px', background: 'rgba(99, 102, 241, 0.1)', border: '1px solid rgba(99, 102, 241, 0.25)', borderRadius: '8px' }}>
                      <h4 style={{ margin: '0 0 6px', fontSize: '0.9rem', color: '#c7d2fe' }}>
                        🔒 Hermes AI Agent Runtime Boundary (Prompt 23 Integration Gate)
                      </h4>
                      <p style={{ margin: 0, fontSize: '0.8rem', color: '#94a3b8', lineHeight: 1.45 }}>
                        Technical identity mappings are defined separately from organizational authority. No raw API secrets or live credentials are stored in database records. Actual Docker sandboxing and 9Router model gateway executions will be activated in Prompt 23.
                      </p>
                    </div>

                    <table className="mce-table">
                      <thead>
                        <tr>
                          <th>Provider</th>
                          <th>Profile Reference</th>
                          <th>Gateway Preference</th>
                          <th>Config Version</th>
                          <th>Integration Status</th>
                          <th>Effective Period</th>
                        </tr>
                      </thead>
                      <tbody>
                        {runtimeMappings.map((rt) => (
                          <tr key={rt.id}>
                            <td><strong>{rt.provider}</strong></td>
                            <td><code>{rt.runtime_profile_reference}</code></td>
                            <td><span className="mce-pill mce-pill-primary">{rt.model_gateway_preference}</span></td>
                            <td><code>{rt.configuration_version}</code></td>
                            <td>
                              <span className={`mce-pill ${rt.integration_status === 'mapped' ? 'mce-pill-neutral' : rt.integration_status === 'ready' ? 'mce-pill-success' : 'mce-pill-warning'}`}>
                                {rt.integration_status.toUpperCase()}
                              </span>
                            </td>
                            <td><small>{rt.effective_start_date} to {rt.effective_end_date || 'Active'}</small></td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </>
            )}
          </div>
        </section>
      )}

      {/* Register Agent Modal Form */}
      {isCreating && (
        <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.7)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000, padding: '20px' }}>
          <div className="mce-card" style={{ maxWidth: '500px', width: '100%', background: '#0e121b', border: '1px solid rgba(99, 102, 241, 0.5)' }}>
            <div className="mce-card-header">
              <h3 className="mce-card-title">Register New AI Agent Identity</h3>
              <button type="button" className="mce-btn mce-btn-secondary mce-btn-sm" onClick={() => setIsCreating(false)}>
                ✕
              </button>
            </div>
            <form onSubmit={handleCreateAgent} className="mce-card-body" style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>
              <div className="mce-form-group">
                <label className="mce-form-label">Canonical Agent Name *</label>
                <input
                  type="text"
                  className="mce-input"
                  required
                  placeholder="e.g. Security Compliance Specialist AI"
                  value={newName}
                  onChange={(e) => setNewName(e.target.value)}
                />
              </div>

              <div className="mce-form-group">
                <label className="mce-form-label">Agent Code (Optional — Auto-generated if blank)</label>
                <input
                  type="text"
                  className="mce-input"
                  placeholder="e.g. AGT-SEC-001"
                  value={newCode}
                  onChange={(e) => setNewCode(e.target.value)}
                />
              </div>

              <div className="mce-form-group">
                <label className="mce-form-label">Workforce Classification</label>
                <select className="mce-input" value={newClass} onChange={(e) => setNewClass(e.target.value)}>
                  <option value="specialist">Specialist</option>
                  <option value="operational">Operational</option>
                  <option value="support">Support</option>
                  <option value="research">Research</option>
                  <option value="coordination">Coordination</option>
                  <option value="division_lead">Division Lead</option>
                </select>
              </div>

              <div className="mce-form-group">
                <label className="mce-form-label">Organizational Purpose & Description</label>
                <textarea
                  className="mce-input"
                  rows={3}
                  placeholder="State the organizational mission and expected duties of this agent..."
                  value={newDesc}
                  onChange={(e) => setNewDesc(e.target.value)}
                />
              </div>

              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '8px', marginTop: '10px' }}>
                <button type="button" className="mce-btn mce-btn-secondary" onClick={() => setIsCreating(false)}>
                  Cancel
                </button>
                <button type="submit" className="mce-btn mce-btn-primary">
                  Register in Draft State
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
