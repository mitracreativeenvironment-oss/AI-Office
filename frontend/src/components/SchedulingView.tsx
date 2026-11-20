import React, { useState, useEffect } from 'react';
import { schedulingService } from '../services/schedulingService';
import type {
  SchedulePolicy,
  WorkforceRoster,
  CoverageEvaluationResult,
  ReplacementProposal,
  ReplacementCandidate,
  WallbookHandover,
  DutyAssignment,
} from '../types/scheduling';

export const SchedulingView: React.FC = () => {
  const [activeTab, setActiveTab] = useState<'roster' | 'coverage' | 'replacements' | 'wallbook'>('roster');
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [policies, setPolicies] = useState<SchedulePolicy[]>([]);
  const [rosters, setRosters] = useState<WorkforceRoster[]>([]);
  const [selectedRoster, setSelectedRoster] = useState<WorkforceRoster | null>(null);
  const [coverageData, setCoverageData] = useState<CoverageEvaluationResult | null>(null);
  const [replacements, setReplacements] = useState<ReplacementProposal[]>([]);
  const [handovers, setHandovers] = useState<WallbookHandover[]>([]);
  const [selectedHandover, setSelectedHandover] = useState<WallbookHandover | null>(null);
  const [candidatesDiscovery, setCandidatesDiscovery] = useState<{
    duty: DutyAssignment | null;
    candidates: ReplacementCandidate[];
    loading: boolean;
  }>({ duty: null, candidates: [], loading: false });

  const [filterAgent, setFilterAgent] = useState<string>('');

  const fetchData = async () => {
    setIsLoading(true);
    try {
      const [policiesRes, rostersRes, coverageRes, replacementsRes, handoversRes] = await Promise.all([
        schedulingService.listPolicies(),
        schedulingService.listRosters(),
        schedulingService.evaluateCoverage(),
        schedulingService.listReplacements(),
        schedulingService.listHandovers(),
      ]);

      setPolicies(policiesRes);
      setRosters(rostersRes.items);
      if (rostersRes.items.length > 0) {
        const fullRoster = await schedulingService.getRoster(rostersRes.items[0].id);
        setSelectedRoster(fullRoster);
      }
      setCoverageData(coverageRes);
      setReplacements(replacementsRes);
      setHandovers(handoversRes);
      if (handoversRes.length > 0) {
        const fullH = await schedulingService.getHandover(handoversRes[0].id);
        setSelectedHandover(fullH);
      }
    } catch (err: unknown) {
      console.error('Failed to load scheduling data', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, []);

  const handleSelectRoster = async (id: number) => {
    setIsLoading(true);
    try {
      const r = await schedulingService.getRoster(id);
      setSelectedRoster(r);
    } catch (err) {
      alert(`Error loading roster #${id}`);
    } finally {
      setIsLoading(false);
    }
  };

  const handleSelectHandover = async (id: number) => {
    try {
      const h = await schedulingService.getHandover(id);
      setSelectedHandover(h);
    } catch (err) {
      alert(`Error loading handover #${id}`);
    }
  };

  const handleCompleteDuty = async (assignmentId: number) => {
    try {
      await schedulingService.completeDutyAssignment(assignmentId);
      if (selectedRoster) {
        const updated = await schedulingService.getRoster(selectedRoster.id);
        setSelectedRoster(updated);
      }
      alert(`Duty assignment #${assignmentId} marked as completed.`);
    } catch (err: any) {
      alert(`Error completing duty: ${err.message}`);
    }
  };

  const handleDiscoverCandidates = async (duty: DutyAssignment) => {
    setCandidatesDiscovery({ duty, candidates: [], loading: true });
    try {
      const res = await schedulingService.findCandidates(duty.id);
      setCandidatesDiscovery({ duty, candidates: res.candidates, loading: false });
    } catch (err: any) {
      alert(`Error finding candidates: ${err.message}`);
      setCandidatesDiscovery({ duty: null, candidates: [], loading: false });
    }
  };

  const handleAcceptProposal = async (id: number, identity: string) => {
    try {
      await schedulingService.acceptProposal(id, identity);
      fetchData();
      alert(`Replacement proposal #${id} formally accepted by incoming agent.`);
    } catch (err: any) {
      alert(`Acceptance error: ${err.message}`);
    }
  };

  const handleApproveProposal = async (id: number) => {
    try {
      await schedulingService.approveProposal(id);
      fetchData();
      alert(`Replacement proposal #${id} approved by supervisor.`);
    } catch (err: any) {
      alert(`Approval error: ${err.message}`);
    }
  };

  const handleActivateReplacement = async (id: number) => {
    try {
      await schedulingService.activateReplacement(id);
      fetchData();
      alert(`Replacement #${id} activated! Outgoing duty reassigned and incoming duty activated.`);
    } catch (err: any) {
      alert(`Activation error: ${err.message}`);
    }
  };

  const handleAcknowledgeHandover = async (id: number, incomingAgentId: number) => {
    try {
      await schedulingService.acknowledgeHandover(id, incomingAgentId, 'Operational context and active tasks acknowledged via dashboard.');
      const updated = await schedulingService.getHandover(id);
      setSelectedHandover(updated);
      fetchData();
      alert(`Handover #${id} acknowledged.`);
    } catch (err: any) {
      alert(`Acknowledgement error: ${err.message}`);
    }
  };

  const handleAcceptHandover = async (id: number, incomingAgentId: number) => {
    try {
      await schedulingService.acceptHandover(id, incomingAgentId, 'Formal handover accepted. Incoming agent assumes operational duty.');
      const updated = await schedulingService.getHandover(id);
      setSelectedHandover(updated);
      fetchData();
      alert(`Handover #${id} accepted.`);
    } catch (err: any) {
      alert(`Acceptance error: ${err.message}`);
    }
  };

  const handleEscalateOverdue = async () => {
    try {
      const res = await schedulingService.escalateOverdue();
      fetchData();
      alert(`Overdue scan complete. ${res.escalated_count} handovers escalated.`);
    } catch (err: any) {
      alert(`Escalation error: ${err.message}`);
    }
  };

  if (isLoading && !selectedRoster) {
    return (
      <div style={{ padding: '2rem', textAlign: 'center', color: '#94a3b8' }}>
        <p>Loading Workforce Scheduling & Wallbook Handover Center...</p>
      </div>
    );
  }

  // Group assignments by agent for calendar view
  const assignmentsByAgent: Record<string, { agent_name: string; agent_code: string; classification: string; assignments: DutyAssignment[] }> = {};
  if (selectedRoster?.assignments) {
    for (const a of selectedRoster.assignments) {
      const key = `${a.agent_id}`;
      if (!assignmentsByAgent[key]) {
        assignmentsByAgent[key] = {
          agent_name: a.agent_name || `Agent #${a.agent_id}`,
          agent_code: a.agent_code || `AGT-${a.agent_id}`,
          classification: a.classification || 'operational',
          assignments: [],
        };
      }
      assignmentsByAgent[key].assignments.push(a);
    }
  }

  const filteredAgentKeys = Object.keys(assignmentsByAgent).filter((k) => {
    if (!filterAgent) return true;
    const a = assignmentsByAgent[k];
    return a.agent_name.toLowerCase().includes(filterAgent.toLowerCase()) || a.agent_code.toLowerCase().includes(filterAgent.toLowerCase());
  });

  return (
    <div style={{ padding: '1.5rem', maxWidth: '1440px', margin: '0 auto', color: '#f1f5f9' }}>
      {/* Header & Metric Summary */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem', flexWrap: 'wrap', gap: '1rem' }}>
        <div>
          <h2 style={{ fontSize: '1.5rem', fontWeight: 700, margin: 0, color: '#f8fafc' }}>
            📅 Agent Scheduling, Shift Coverage & Wallbook Handover
          </h2>
          <p style={{ margin: '0.25rem 0 0 0', color: '#94a3b8', fontSize: '0.875rem' }}>
            Enforcing standard 5/2 staggered rosters, 24/7 operational continuity, qualified replacement, and mandatory Wallbook handover.
          </p>
        </div>

        <div style={{ display: 'flex', gap: '0.5rem' }}>
          <button
            onClick={() => fetchData()}
            style={{ padding: '0.5rem 1rem', background: '#334155', border: '1px solid #475569', borderRadius: '6px', color: '#fff', cursor: 'pointer' }}
          >
            🔄 Refresh Live Data
          </button>
        </div>
      </div>

      {/* KPI Cards */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '1rem', marginBottom: '1.5rem' }}>
        <div style={{ background: '#1e293b', padding: '1rem', borderRadius: '8px', border: '1px solid #334155' }}>
          <div style={{ color: '#94a3b8', fontSize: '0.75rem', fontWeight: 600 }}>ROSTER POLICY</div>
          <div style={{ fontSize: '1.25rem', fontWeight: 700, color: '#38bdf8', marginTop: '0.25rem' }}>5/2 Staggered</div>
          <div style={{ fontSize: '0.75rem', color: '#64748b', marginTop: '0.25rem' }}>5 Working / 2 Rest Days</div>
        </div>

        <div style={{ background: '#1e293b', padding: '1rem', borderRadius: '8px', border: '1px solid #334155' }}>
          <div style={{ color: '#94a3b8', fontSize: '0.75rem', fontWeight: 600 }}>COVERAGE STATUS</div>
          <div style={{ fontSize: '1.25rem', fontWeight: 700, color: '#4ade80', marginTop: '0.25rem' }}>
            {coverageData ? `${coverageData.summary.covered} / ${coverageData.summary.total_requirements} Units` : 'Evaluating...'}
          </div>
          <div style={{ fontSize: '0.75rem', color: '#64748b', marginTop: '0.25rem' }}>
            {coverageData?.summary.coverage_at_risk ? `⚠️ ${coverageData.summary.coverage_at_risk} at risk` : 'All critical units covered'}
          </div>
        </div>

        <div style={{ background: '#1e293b', padding: '1rem', borderRadius: '8px', border: '1px solid #334155' }}>
          <div style={{ color: '#94a3b8', fontSize: '0.75rem', fontWeight: 600 }}>REPLACEMENTS</div>
          <div style={{ fontSize: '1.25rem', fontWeight: 700, color: '#f59e0b', marginTop: '0.25rem' }}>
            {replacements.length} Proposals
          </div>
          <div style={{ fontSize: '0.75rem', color: '#64748b', marginTop: '0.25rem' }}>
            {replacements.filter((r) => r.status === 'activated').length} Activated
          </div>
        </div>

        <div style={{ background: '#1e293b', padding: '1rem', borderRadius: '8px', border: '1px solid #334155' }}>
          <div style={{ color: '#94a3b8', fontSize: '0.75rem', fontWeight: 600 }}>WALLBOOK HANDOVERS</div>
          <div style={{ fontSize: '1.25rem', fontWeight: 700, color: '#c084fc', marginTop: '0.25rem' }}>
            {handovers.length} Total
          </div>
          <div style={{ fontSize: '0.75rem', color: '#64748b', marginTop: '0.25rem' }}>
            {handovers.filter((h) => h.status === 'awaiting_acknowledgement').length} Awaiting Ack
          </div>
        </div>
      </div>

      {/* Tabs */}
      <div style={{ display: 'flex', gap: '0.5rem', borderBottom: '1px solid #334155', marginBottom: '1.5rem' }}>
        {[
          { key: 'roster', label: '📅 5/2 Roster Calendar' },
          { key: 'coverage', label: '🛡️ Operational Coverage Monitor' },
          { key: 'replacements', label: '🔄 Qualified Replacement Center' },
          { key: 'wallbook', label: '📖 Wallbook Handover Center' },
        ].map((tab) => (
          <button
            key={tab.key}
            onClick={() => setActiveTab(tab.key as any)}
            style={{
              padding: '0.75rem 1.25rem',
              background: activeTab === tab.key ? '#334155' : 'transparent',
              color: activeTab === tab.key ? '#38bdf8' : '#94a3b8',
              border: 'none',
              borderBottom: activeTab === tab.key ? '2px solid #38bdf8' : '2px solid transparent',
              fontWeight: 600,
              fontSize: '0.875rem',
              cursor: 'pointer',
            }}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {/* TAB 1: ROSTER CALENDAR (5/2 STAGGERED) */}
      {activeTab === 'roster' && selectedRoster && (
        <div>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem', flexWrap: 'wrap', gap: '1rem' }}>
            <div>
              <span style={{ fontSize: '1.1rem', fontWeight: 600, color: '#f8fafc' }}>{selectedRoster.title}</span>
              <span style={{ marginLeft: '0.75rem', fontSize: '0.75rem', background: '#0284c7', padding: '0.2rem 0.5rem', borderRadius: '4px' }}>
                {selectedRoster.status.toUpperCase()}
              </span>
              <p style={{ margin: '0.25rem 0 0 0', fontSize: '0.8rem', color: '#94a3b8' }}>
                Period: {selectedRoster.start_date} to {selectedRoster.end_date} (Policy: {selectedRoster.policy_name || '5/2 Standard'})
              </p>
            </div>

            <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
              <input
                type="text"
                placeholder="Filter by agent..."
                value={filterAgent}
                onChange={(e) => setFilterAgent(e.target.value)}
                style={{ padding: '0.4rem 0.75rem', background: '#0f172a', border: '1px solid #334155', borderRadius: '6px', color: '#fff', fontSize: '0.85rem' }}
              />
              {rosters.length > 1 && (
                <select
                  value={selectedRoster.id}
                  onChange={(e) => handleSelectRoster(Number(e.target.value))}
                  style={{ padding: '0.4rem 0.75rem', background: '#0f172a', border: '1px solid #334155', borderRadius: '6px', color: '#fff', fontSize: '0.85rem' }}
                >
                  {rosters.map((r) => (
                    <option key={r.id} value={r.id}>
                      {r.roster_code} ({r.start_date})
                    </option>
                  ))}
                </select>
              )}
            </div>
          </div>

          {/* Staggered 5/2 Calendar Table */}
          <div style={{ background: '#1e293b', borderRadius: '8px', border: '1px solid #334155', overflowX: 'auto' }}>
            <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left', minWidth: '900px' }}>
              <thead>
                <tr style={{ background: '#0f172a', borderBottom: '1px solid #334155', color: '#94a3b8', fontSize: '0.75rem' }}>
                  <th style={{ padding: '0.75rem 1rem' }}>WORKFORCE AGENT</th>
                  <th style={{ padding: '0.75rem 0.5rem', textAlign: 'center' }}>MON</th>
                  <th style={{ padding: '0.75rem 0.5rem', textAlign: 'center' }}>TUE</th>
                  <th style={{ padding: '0.75rem 0.5rem', textAlign: 'center' }}>WED</th>
                  <th style={{ padding: '0.75rem 0.5rem', textAlign: 'center' }}>THU</th>
                  <th style={{ padding: '0.75rem 0.5rem', textAlign: 'center' }}>FRI</th>
                  <th style={{ padding: '0.75rem 0.5rem', textAlign: 'center', background: '#1e1b4b' }}>SAT (W/END)</th>
                  <th style={{ padding: '0.75rem 0.5rem', textAlign: 'center', background: '#1e1b4b' }}>SUN (W/END)</th>
                </tr>
              </thead>
              <tbody>
                {filteredAgentKeys.map((key) => {
                  const agentGroup = assignmentsByAgent[key];
                  return (
                    <tr key={key} style={{ borderBottom: '1px solid #334155' }}>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <div style={{ fontWeight: 600, color: '#f8fafc', fontSize: '0.875rem' }}>{agentGroup.agent_name}</div>
                        <div style={{ fontSize: '0.75rem', color: '#64748b' }}>
                          {agentGroup.agent_code} • <span style={{ textTransform: 'capitalize' }}>{agentGroup.classification}</span>
                        </div>
                      </td>

                      {agentGroup.assignments.map((assignment) => {
                        const isRest = assignment.duty_type === 'rest';
                        const isReassigned = assignment.status === 'reassigned';
                        const isCompleted = assignment.is_actual_completed === 1;

                        return (
                          <td key={assignment.id} style={{ padding: '0.5rem', textAlign: 'center', verticalAlign: 'top' }}>
                            <div
                              style={{
                                padding: '0.4rem',
                                borderRadius: '6px',
                                fontSize: '0.75rem',
                                background: isRest ? '#334155' : isReassigned ? '#78350f' : '#064e3b',
                                border: isRest ? '1px solid #475569' : isReassigned ? '1px solid #d97706' : '1px solid #059669',
                                color: isRest ? '#cbd5e1' : isReassigned ? '#fde68a' : '#a7f3d0',
                              }}
                            >
                              <div style={{ fontWeight: 600 }}>{isRest ? '🛌 REST DAY' : assignment.shift_name || 'DAY SHIFT'}</div>
                              {!isRest && (
                                <div style={{ fontSize: '0.65rem', opacity: 0.9, marginTop: '0.15rem' }}>
                                  {assignment.start_time?.substring(0, 5)} - {assignment.end_time?.substring(0, 5)}
                                </div>
                              )}
                              {isReassigned && <div style={{ fontSize: '0.65rem', color: '#f59e0b' }}>[Replacement Active]</div>}

                              {!isRest && !isCompleted && !isReassigned && (
                                <button
                                  onClick={() => handleCompleteDuty(assignment.id)}
                                  title="Mark duty completed"
                                  style={{ marginTop: '0.3rem', fontSize: '0.65rem', padding: '0.15rem 0.35rem', background: '#0284c7', border: 'none', borderRadius: '3px', color: '#fff', cursor: 'pointer' }}
                                >
                                  ✓ Done
                                </button>
                              )}
                              {isCompleted && <div style={{ fontSize: '0.65rem', color: '#38bdf8', marginTop: '0.2rem' }}>✓ Completed</div>}

                              {!isRest && !isReassigned && (
                                <button
                                  onClick={() => handleDiscoverCandidates(assignment)}
                                  title="Find qualified replacement"
                                  style={{ marginTop: '0.2rem', display: 'block', width: '100%', fontSize: '0.6rem', padding: '0.1rem', background: 'transparent', border: '1px dashed #64748b', borderRadius: '3px', color: '#94a3b8', cursor: 'pointer' }}
                                >
                                  + Replace
                                </button>
                              )}
                            </div>
                          </td>
                        );
                      })}
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>

          {/* Candidate Discovery Drawer / Modal */}
          {candidatesDiscovery.duty && (
            <div style={{ marginTop: '1.5rem', background: '#0f172a', border: '1px solid #38bdf8', borderRadius: '8px', padding: '1rem' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <h4 style={{ margin: 0, color: '#38bdf8' }}>
                  🔍 Qualified Replacement Candidate Matcher for Duty #{candidatesDiscovery.duty.id} ({candidatesDiscovery.duty.assignment_date})
                </h4>
                <button
                  onClick={() => setCandidatesDiscovery({ duty: null, candidates: [], loading: false })}
                  style={{ background: 'transparent', border: 'none', color: '#94a3b8', fontSize: '1rem', cursor: 'pointer' }}
                >
                  ✕
                </button>
              </div>
              <p style={{ fontSize: '0.8rem', color: '#94a3b8', margin: '0.25rem 0 1rem 0' }}>
                Algorithmic discovery evaluates verified skills, unit compatibility, active status, and confirms zero overlapping duty assignments.
              </p>

              {candidatesDiscovery.loading ? (
                <p style={{ color: '#94a3b8' }}>Evaluating candidate agents...</p>
              ) : candidatesDiscovery.candidates.length === 0 ? (
                <p style={{ color: '#ef4444' }}>No available replacement candidates found with zero schedule conflicts.</p>
              ) : (
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '0.75rem' }}>
                  {candidatesDiscovery.candidates.map((cand) => (
                    <div key={cand.candidate_agent_id} style={{ background: '#1e293b', padding: '0.75rem', borderRadius: '6px', border: '1px solid #334155' }}>
                      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <span style={{ fontWeight: 600, color: '#f8fafc' }}>{cand.name}</span>
                        <span style={{ fontSize: '0.75rem', background: '#0284c7', color: '#fff', padding: '0.15rem 0.4rem', borderRadius: '4px' }}>
                          Score: {cand.compatibility_score}%
                        </span>
                      </div>
                      <div style={{ fontSize: '0.75rem', color: '#94a3b8', marginTop: '0.2rem' }}>
                        {cand.agent_code} • {cand.classification} • {cand.is_same_unit ? 'Same Unit' : 'Cross-Unit'}
                      </div>
                      <div style={{ fontSize: '0.7rem', color: '#cbd5e1', marginTop: '0.4rem' }}>
                        Matching Skills: {cand.matching_skills.join(', ') || 'General Competency'}
                      </div>
                      <button
                        onClick={async () => {
                          if (!candidatesDiscovery.duty) return;
                          try {
                            await schedulingService.createProposal({
                              duty_assignment_id: candidatesDiscovery.duty.id,
                              incoming_agent_id: cand.candidate_agent_id,
                              reason: `Formal replacement for ${candidatesDiscovery.duty.agent_name || 'Agent'} covering shift.`,
                            });
                            alert(`Replacement proposal created for ${cand.name}!`);
                            setCandidatesDiscovery({ duty: null, candidates: [], loading: false });
                            fetchData();
                          } catch (err: any) {
                            alert(`Error: ${err.message}`);
                          }
                        }}
                        style={{ marginTop: '0.5rem', width: '100%', padding: '0.35rem', background: '#10b981', border: 'none', borderRadius: '4px', color: '#fff', fontSize: '0.75rem', fontWeight: 600, cursor: 'pointer' }}
                      >
                        Select & Propose Replacement
                      </button>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}
        </div>
      )}

      {/* TAB 2: OPERATIONAL COVERAGE MONITOR */}
      {activeTab === 'coverage' && coverageData && (
        <div>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem', flexWrap: 'wrap', gap: '0.5rem' }}>
            <h3 style={{ margin: 0, fontSize: '1.1rem', color: '#f8fafc' }}>
              Real-Time Unit & Function Coverage Evaluation ({coverageData.date})
            </h3>
            <div style={{ fontSize: '0.85rem', color: '#94a3b8' }}>
              Total Requirements: {coverageData.summary.total_requirements}
            </div>
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))', gap: '1rem' }}>
            {coverageData.evaluations.map((item) => {
              const statusColor =
                item.status === 'covered'
                  ? '#10b981'
                  : item.status === 'coverage_at_risk'
                  ? '#f59e0b'
                  : item.status === 'partially_covered'
                  ? '#eab308'
                  : '#ef4444';

              return (
                <div key={item.requirement_id} style={{ background: '#1e293b', border: `1px solid ${statusColor}`, borderRadius: '8px', padding: '1rem' }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <span style={{ fontWeight: 700, color: '#f8fafc', fontSize: '0.95rem' }}>{item.requirement_name}</span>
                    <span style={{ fontSize: '0.7rem', padding: '0.2rem 0.5rem', borderRadius: '4px', background: statusColor, color: '#fff', textTransform: 'uppercase', fontWeight: 600 }}>
                      {item.status.replace('_', ' ')}
                    </span>
                  </div>

                  <div style={{ fontSize: '0.8rem', color: '#94a3b8', marginTop: '0.25rem' }}>
                    Unit: <strong style={{ color: '#cbd5e1' }}>{item.unit_name}</strong> • Shift: {item.shift_name}
                  </div>

                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: '0.75rem', background: '#0f172a', padding: '0.5rem', borderRadius: '4px', fontSize: '0.8rem' }}>
                    <span>Required Agents: <strong>{item.min_agents_required}</strong></span>
                    <span>Actual Qualified: <strong style={{ color: statusColor }}>{item.actual_agents_count}</strong></span>
                    <span>Criticality: <strong style={{ textTransform: 'capitalize' }}>{item.criticality}</strong></span>
                  </div>

                  <div style={{ marginTop: '0.5rem', fontSize: '0.75rem', color: '#cbd5e1' }}>
                    Qualified Assigned Agents:
                    {item.qualified_agents.length > 0 ? (
                      <ul style={{ margin: '0.25rem 0 0 1rem', padding: 0 }}>
                        {item.qualified_agents.map((ag) => (
                          <li key={ag.agent_id}>
                            {ag.agent_name} ({ag.agent_code}) - {ag.classification}
                          </li>
                        ))}
                      </ul>
                    ) : (
                      <span style={{ color: '#ef4444', marginLeft: '0.5rem' }}>None Assigned</span>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* TAB 3: QUALIFIED REPLACEMENT MANAGEMENT */}
      {activeTab === 'replacements' && (
        <div>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
            <h3 style={{ margin: 0, fontSize: '1.1rem', color: '#f8fafc' }}>
              Qualified Replacement Proposals & Transition Ledger
            </h3>
            <span style={{ fontSize: '0.85rem', color: '#94a3b8' }}>
              {replacements.length} registered proposals
            </span>
          </div>

          <div style={{ background: '#1e293b', borderRadius: '8px', border: '1px solid #334155', overflowX: 'auto' }}>
            <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left', minWidth: '700px' }}>
              <thead>
                <tr style={{ background: '#0f172a', borderBottom: '1px solid #334155', color: '#94a3b8', fontSize: '0.75rem' }}>
                  <th style={{ padding: '0.75rem 1rem' }}>PROPOSAL CODE</th>
                  <th style={{ padding: '0.75rem 1rem' }}>OUTGOING AGENT</th>
                  <th style={{ padding: '0.75rem 1rem' }}>INCOMING AGENT</th>
                  <th style={{ padding: '0.75rem 1rem' }}>DUTY DATE</th>
                  <th style={{ padding: '0.75rem 1rem' }}>REASON & EVIDENCE</th>
                  <th style={{ padding: '0.75rem 1rem' }}>STATUS</th>
                  <th style={{ padding: '0.75rem 1rem', textAlign: 'right' }}>ACTIONS</th>
                </tr>
              </thead>
              <tbody>
                {replacements.map((prop) => (
                  <tr key={prop.id} style={{ borderBottom: '1px solid #334155', fontSize: '0.85rem' }}>
                    <td style={{ padding: '0.75rem 1rem', fontWeight: 600, color: '#38bdf8' }}>{prop.proposal_code}</td>
                    <td style={{ padding: '0.75rem 1rem' }}>
                      <div style={{ fontWeight: 600, color: '#f8fafc' }}>{prop.outgoing_agent_name}</div>
                      <div style={{ fontSize: '0.75rem', color: '#64748b' }}>{prop.outgoing_agent_code}</div>
                    </td>
                    <td style={{ padding: '0.75rem 1rem' }}>
                      <div style={{ fontWeight: 600, color: '#10b981' }}>{prop.incoming_agent_name}</div>
                      <div style={{ fontSize: '0.75rem', color: '#64748b' }}>{prop.incoming_agent_code}</div>
                    </td>
                    <td style={{ padding: '0.75rem 1rem' }}>{prop.assignment_date || prop.start_time.substring(0, 10)}</td>
                    <td style={{ padding: '0.75rem 1rem', maxWidth: '240px', fontSize: '0.75rem', color: '#cbd5e1' }}>
                      {prop.reason}
                    </td>
                    <td style={{ padding: '0.75rem 1rem' }}>
                      <span
                        style={{
                          fontSize: '0.7rem',
                          padding: '0.2rem 0.5rem',
                          borderRadius: '4px',
                          background:
                            prop.status === 'activated'
                              ? '#059669'
                              : prop.status === 'approved'
                              ? '#0284c7'
                              : prop.status === 'accepted'
                              ? '#d97706'
                              : '#475569',
                          color: '#fff',
                          fontWeight: 600,
                          textTransform: 'uppercase',
                        }}
                      >
                        {prop.status}
                      </span>
                    </td>
                    <td style={{ padding: '0.75rem 1rem', textAlign: 'right' }}>
                      {prop.status === 'proposed' && (
                        <button
                          onClick={() => handleAcceptProposal(prop.id, prop.incoming_agent_code || 'AGT-AI')}
                          style={{ padding: '0.25rem 0.5rem', background: '#d97706', border: 'none', borderRadius: '4px', color: '#fff', fontSize: '0.75rem', cursor: 'pointer', marginRight: '0.25rem' }}
                        >
                          Accept
                        </button>
                      )}
                      {prop.status === 'accepted' && (
                        <button
                          onClick={() => handleApproveProposal(prop.id)}
                          style={{ padding: '0.25rem 0.5rem', background: '#0284c7', border: 'none', borderRadius: '4px', color: '#fff', fontSize: '0.75rem', cursor: 'pointer', marginRight: '0.25rem' }}
                        >
                          Approve
                        </button>
                      )}
                      {(prop.status === 'approved' || prop.status === 'accepted') && (
                        <button
                          onClick={() => handleActivateReplacement(prop.id)}
                          style={{ padding: '0.25rem 0.5rem', background: '#10b981', border: 'none', borderRadius: '4px', color: '#fff', fontSize: '0.75rem', cursor: 'pointer' }}
                        >
                          ⚡ Activate
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

      {/* TAB 4: WALLBOOK HANDOVER CENTER */}
      {activeTab === 'wallbook' && (
        <div style={{ display: 'grid', gridTemplateColumns: selectedHandover ? '1fr 1.2fr' : '1fr', gap: '1.5rem' }}>
          <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
              <h3 style={{ margin: 0, fontSize: '1.1rem', color: '#f8fafc' }}>
                Operational Wallbook Handovers
              </h3>
              <button
                onClick={handleEscalateOverdue}
                style={{ padding: '0.35rem 0.75rem', background: '#dc2626', border: 'none', borderRadius: '4px', color: '#fff', fontSize: '0.75rem', cursor: 'pointer' }}
              >
                🚨 Scan Overdue Handovers
              </button>
            </div>

            <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
              {handovers.map((h) => {
                const isSelected = selectedHandover?.id === h.id;
                return (
                  <div
                    key={h.id}
                    onClick={() => handleSelectHandover(h.id)}
                    style={{
                      background: isSelected ? '#1e293b' : '#0f172a',
                      border: isSelected ? '1px solid #38bdf8' : '1px solid #334155',
                      padding: '1rem',
                      borderRadius: '8px',
                      cursor: 'pointer',
                    }}
                  >
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                      <span style={{ fontWeight: 700, color: '#f8fafc', fontSize: '0.9rem' }}>{h.title}</span>
                      <span
                        style={{
                          fontSize: '0.65rem',
                          padding: '0.15rem 0.4rem',
                          borderRadius: '4px',
                          background:
                            h.status === 'accepted'
                              ? '#059669'
                              : h.status === 'acknowledged'
                              ? '#0284c7'
                              : h.status === 'awaiting_acknowledgement'
                              ? '#d97706'
                              : h.status === 'escalated'
                              ? '#dc2626'
                              : '#475569',
                          color: '#fff',
                          fontWeight: 600,
                          textTransform: 'uppercase',
                        }}
                      >
                        {h.status.replace('_', ' ')}
                      </span>
                    </div>

                    <div style={{ fontSize: '0.75rem', color: '#94a3b8', marginTop: '0.3rem' }}>
                      Code: <strong style={{ color: '#38bdf8' }}>{h.handover_code}</strong> • Unit: {h.unit_name}
                    </div>

                    <div style={{ fontSize: '0.75rem', color: '#cbd5e1', marginTop: '0.3rem' }}>
                      From: <strong>{h.outgoing_agent_name}</strong> ➔ To: <strong>{h.incoming_agent_name}</strong>
                    </div>

                    <div style={{ fontSize: '0.7rem', color: '#64748b', marginTop: '0.3rem' }}>
                      Deadline: {h.deadline_at}
                    </div>
                  </div>
                );
              })}
            </div>
          </div>

          {/* Selected Handover Details & 13 Structured Sections */}
          {selectedHandover && (
            <div style={{ background: '#1e293b', border: '1px solid #334155', borderRadius: '8px', padding: '1.25rem' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                <div>
                  <h4 style={{ margin: 0, fontSize: '1.1rem', color: '#f8fafc' }}>{selectedHandover.title}</h4>
                  <div style={{ fontSize: '0.75rem', color: '#94a3b8', marginTop: '0.2rem' }}>
                    {selectedHandover.handover_code} • Created: {selectedHandover.created_at}
                  </div>
                </div>

                <span
                  style={{
                    fontSize: '0.75rem',
                    padding: '0.25rem 0.6rem',
                    borderRadius: '4px',
                    background: '#0284c7',
                    color: '#fff',
                    fontWeight: 600,
                  }}
                >
                  {selectedHandover.status.toUpperCase()}
                </span>
              </div>

              {/* Handover Actions for Incoming Agent */}
              <div style={{ display: 'flex', gap: '0.5rem', margin: '1rem 0', padding: '0.75rem', background: '#0f172a', borderRadius: '6px' }}>
                {selectedHandover.status === 'awaiting_acknowledgement' && (
                  <button
                    onClick={() => handleAcknowledgeHandover(selectedHandover.id, selectedHandover.incoming_agent_id)}
                    style={{ flex: 1, padding: '0.4rem', background: '#0284c7', border: 'none', borderRadius: '4px', color: '#fff', fontWeight: 600, cursor: 'pointer' }}
                  >
                    ✓ Acknowledge Receipt
                  </button>
                )}
                {(selectedHandover.status === 'acknowledged' || selectedHandover.status === 'clarification_required') && (
                  <button
                    onClick={() => handleAcceptHandover(selectedHandover.id, selectedHandover.incoming_agent_id)}
                    style={{ flex: 1, padding: '0.4rem', background: '#10b981', border: 'none', borderRadius: '4px', color: '#fff', fontWeight: 600, cursor: 'pointer' }}
                  >
                    🚀 Accept Handover & Assume Duty
                  </button>
                )}
              </div>

              {/* 13 Structured Sections Items */}
              <h5 style={{ margin: '1rem 0 0.5rem 0', color: '#38bdf8', fontSize: '0.9rem' }}>
                Structured Handover Content ({selectedHandover.items?.length || 0} Sections)
              </h5>

              <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem', maxHeight: '420px', overflowY: 'auto' }}>
                {selectedHandover.items && selectedHandover.items.length > 0 ? (
                  selectedHandover.items.map((item) => (
                    <div key={item.id} style={{ background: '#0f172a', padding: '0.75rem', borderRadius: '6px', border: '1px solid #334155' }}>
                      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <span style={{ fontWeight: 600, color: '#f8fafc', fontSize: '0.85rem' }}>{item.title}</span>
                        <span style={{ fontSize: '0.65rem', background: '#334155', color: '#38bdf8', padding: '0.15rem 0.4rem', borderRadius: '4px', textTransform: 'uppercase' }}>
                          {item.section_category.replace('_', ' ')}
                        </span>
                      </div>
                      <p style={{ margin: '0.4rem 0 0 0', fontSize: '0.8rem', color: '#cbd5e1', lineHeight: 1.4 }}>
                        {item.content}
                      </p>
                      {item.reference_id && (
                        <div style={{ marginTop: '0.4rem', fontSize: '0.7rem', color: '#94a3b8' }}>
                          Ref: <strong style={{ color: '#a7f3d0' }}>{item.reference_type} #{item.reference_id}</strong>
                        </div>
                      )}
                    </div>
                  ))
                ) : (
                  <p style={{ color: '#94a3b8', fontSize: '0.8rem' }}>No structured items recorded yet.</p>
                )}
              </div>

              {/* Acknowledgement Audit History */}
              {selectedHandover.acknowledgements && selectedHandover.acknowledgements.length > 0 && (
                <div style={{ marginTop: '1rem', borderTop: '1px solid #334155', paddingTop: '0.75rem' }}>
                  <h6 style={{ margin: '0 0 0.5rem 0', color: '#94a3b8', fontSize: '0.75rem' }}>AUDIT & ACKNOWLEDGEMENT TIMELINE</h6>
                  {selectedHandover.acknowledgements.map((ack) => (
                    <div key={ack.id} style={{ fontSize: '0.75rem', color: '#cbd5e1', marginBottom: '0.25rem' }}>
                      • <strong>{ack.created_at}</strong>: <span style={{ textTransform: 'capitalize', color: '#38bdf8' }}>{ack.action}</span> by {ack.agent_name} ({ack.agent_code}) - <em>"{ack.comments}"</em>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}
        </div>
      )}
    </div>
  );
};
