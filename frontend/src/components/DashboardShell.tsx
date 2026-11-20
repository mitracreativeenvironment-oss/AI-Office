import React, { useState, useEffect, useCallback } from 'react';
import { apiClient } from '../services/apiClient';
import { authService } from '../services/authService';
import { organizationService } from '../services/organizationService';
import { WorkforceView } from './WorkforceView';
import { SchedulingView } from './SchedulingView';
import { TaskManagementView } from './TaskManagementView';
import { RuntimeManagementView } from './RuntimeManagementView';
import { WelfareManagementView } from './WelfareManagementView';
import { KnowledgeManagementView } from './KnowledgeManagementView';
import type { ApiResponse, SystemHealthData, DatabaseHealthData } from '../types/api';
import type { User, Role, SecurityEvent } from '../types/auth';
import type {
  OrganizationUnit,
  OrganizationPosition,
  OrganizationAppointment,
  CoreValue,
  GovernanceBody,
  AuthorityProfile,
  OwnerDirection,
  ExecutiveDecision,
} from '../types/organization';

interface DashboardShellProps {
  onRefreshHealth: () => void;
  apiStatus: 'checking' | 'online' | 'degraded' | 'offline';
  latencyMs: number | null;
  currentUser: User | null;
  onOpenLogin: () => void;
}

export const DashboardShell: React.FC<DashboardShellProps> = ({
  onRefreshHealth,
  apiStatus,
  latencyMs,
  currentUser,
  onOpenLogin,
}) => {
  const [systemHealth, setSystemHealth] = useState<SystemHealthData | null>(null);
  const [dbHealth, setDbHealth] = useState<DatabaseHealthData | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const [consoleOutput, setConsoleOutput] = useState<string>('Select an action or test endpoint to verify API communication.');
  const [activeTest, setActiveTest] = useState<string | null>(null);

  // Active Tab State
  const [activeTab, setActiveTab] = useState<'overview' | 'workforce' | 'scheduling' | 'tasks' | 'runtime' | 'welfare' | 'knowledge' | 'organization' | 'governance' | 'users' | 'roles' | 'audit'>('overview');

  // RBAC State
  const [usersList, setUsersList] = useState<User[]>([]);
  const [rolesList, setRolesList] = useState<Role[]>([]);
  const [auditList, setAuditList] = useState<SecurityEvent[]>([]);
  const [loadingRbac, setLoadingRbac] = useState<boolean>(false);

  // Organization & Governance State (Prompt 18)
  const [orgTree, setOrgTree] = useState<OrganizationUnit[]>([]);
  const [positionsList, setPositionsList] = useState<OrganizationPosition[]>([]);
  const [appointmentsList, setAppointmentsList] = useState<OrganizationAppointment[]>([]);
  const [coreValuesList, setCoreValuesList] = useState<CoreValue[]>([]);
  const [bodiesList, setBodiesList] = useState<GovernanceBody[]>([]);
  const [doaProfilesList, setDoaProfilesList] = useState<AuthorityProfile[]>([]);
  const [directionsList, setDirectionsList] = useState<OwnerDirection[]>([]);
  const [decisionsList, setDecisionsList] = useState<ExecutiveDecision[]>([]);
  const [loadingOrgGov, setLoadingOrgGov] = useState<boolean>(false);

  const fetchDiagnostics = useCallback(async () => {
    setLoading(true);
    try {
      const [sysRes, dbRes] = await Promise.all([
        apiClient.checkHealth().catch(() => null),
        apiClient.checkDatabaseHealth().catch(() => null),
      ]);

      if (sysRes?.data) setSystemHealth(sysRes.data);
      if (dbRes?.data) setDbHealth(dbRes.data);
    } finally {
      setLoading(false);
    }
  }, []);

  const fetchRbacData = useCallback(async () => {
    if (!currentUser) return;
    setLoadingRbac(true);
    try {
      const [users, roles, audit] = await Promise.all([
        authService.listUsers().catch(() => []),
        authService.listRoles().catch(() => []),
        authService.listAuditLogs().catch(() => []),
      ]);
      setUsersList(users);
      setRolesList(roles);
      setAuditList(audit);
    } finally {
      setLoadingRbac(false);
    }
  }, [currentUser]);

  const fetchOrgGovData = useCallback(async () => {
    setLoadingOrgGov(true);
    try {
      const [tree, positions, appointments, coreValues, bodies, doa, directions, decisions] = await Promise.all([
        organizationService.getTree().catch(() => []),
        organizationService.getPositions().catch(() => []),
        organizationService.getAppointments().catch(() => []),
        organizationService.getCoreValues().catch(() => []),
        organizationService.getBodies().catch(() => []),
        organizationService.getAuthorityProfiles().catch(() => []),
        organizationService.getDirections().catch(() => []),
        organizationService.getDecisions().catch(() => []),
      ]);
      setOrgTree(tree);
      setPositionsList(positions);
      setAppointmentsList(appointments);
      setCoreValuesList(coreValues);
      setBodiesList(bodies);
      setDoaProfilesList(doa);
      setDirectionsList(directions);
      setDecisionsList(decisions);
    } finally {
      setLoadingOrgGov(false);
    }
  }, []);

  useEffect(() => {
    fetchDiagnostics();
    fetchOrgGovData();
  }, [fetchDiagnostics, fetchOrgGovData]);

  useEffect(() => {
    if (currentUser) {
      fetchRbacData();
    }
  }, [currentUser, fetchRbacData]);

  const handleTestEndpoint = async (endpoint: 'health' | 'database' | 'me' | 'tree' | 'values') => {
    setActiveTest(endpoint);
    const start = performance.now();
    try {
      if (endpoint === 'health') {
        const res: ApiResponse<SystemHealthData> = await apiClient.checkHealth();
        const took = Math.round(performance.now() - start);
        setConsoleOutput(`[HTTP ${res.code} OK - ${took}ms] GET /api/v1/health\n\n${JSON.stringify(res, null, 2)}`);
      } else if (endpoint === 'database') {
        const res: ApiResponse<DatabaseHealthData> = await apiClient.checkDatabaseHealth();
        const took = Math.round(performance.now() - start);
        setConsoleOutput(`[HTTP ${res.code} OK - ${took}ms] GET /api/v1/health/database\n\n${JSON.stringify(res, null, 2)}`);
      } else if (endpoint === 'me') {
        const user = await authService.getMe();
        const took = Math.round(performance.now() - start);
        setConsoleOutput(`[HTTP 200 OK - ${took}ms] GET /api/v1/auth/me (Bearer Token)\n\n${JSON.stringify(user, null, 2)}`);
      } else if (endpoint === 'tree') {
        const tree = await organizationService.getTree();
        const took = Math.round(performance.now() - start);
        setConsoleOutput(`[HTTP 200 OK - ${took}ms] GET /api/v1/organization/tree\n\n${JSON.stringify(tree, null, 2)}`);
      } else if (endpoint === 'values') {
        const values = await organizationService.getCoreValues();
        const took = Math.round(performance.now() - start);
        setConsoleOutput(`[HTTP 200 OK - ${took}ms] GET /api/v1/governance/core-values\n\n${JSON.stringify(values, null, 2)}`);
      }
      onRefreshHealth();
    } catch (err: unknown) {
      const took = Math.round(performance.now() - start);
      setConsoleOutput(`[ERROR - ${took}ms] Request failed:\n\n${err instanceof Error ? err.message : String(err)}`);
    } finally {
      setActiveTest(null);
    }
  };

  // Helper to render tree nodes recursively
  const renderTreeNode = (unit: OrganizationUnit, depth = 0): React.ReactNode => (
    <div key={unit.id} className="mce-tree-node" style={{ marginLeft: depth > 0 ? `${depth * 12}px` : undefined }}>
      <div className="mce-tree-node-header">
        <div className="mce-tree-node-title">
          <span>{unit.type === 'root' ? '🏛️' : unit.type === 'executive_body' ? '⚖️' : '🏢'}</span>
          <div>
            <h4>{unit.name} <code>({unit.code})</code></h4>
            {unit.division_key && <span className="mce-pill mce-pill-neutral">Division Key: {unit.division_key}</span>}
          </div>
        </div>
        <div style={{ display: 'flex', gap: '6px', alignItems: 'center' }}>
          <span className="mce-pill mce-pill-primary">{unit.type.toUpperCase()}</span>
          <span className={`mce-pill ${unit.status === 'active' ? 'mce-pill-success' : 'mce-pill-neutral'}`}>
            {unit.status.toUpperCase()}
          </span>
        </div>
      </div>
      {unit.mission && <p className="mce-unit-mission"><strong>Mission:</strong> {unit.mission}</p>}
      {unit.children && unit.children.length > 0 && (
        <div className="mce-tree-children">
          {unit.children.map((child) => renderTreeNode(child, depth + 1))}
        </div>
      )}
    </div>
  );

  return (
    <main className="mce-dashboard-container">
      {/* Foundation Status Alert */}
      <section className="mce-alert-banner">
        <div className="mce-alert-icon">🏛️</div>
        <div>
          <h2 className="mce-alert-title">Organization Structure & Corporate Governance Operational (Prompt 18)</h2>
          <p className="mce-alert-desc">
            Dynamic hierarchy, positions, human & AI appointments, Delegation of Authority (DoA Level A/B/C), 7 Core Values, Owner Directions, and Continuous Executive Decisions are active.
            {currentUser ? (
              <span> Authenticated as <strong>{currentUser.display_name}</strong> ({currentUser.roles.join(', ')}).</span>
            ) : (
              <span> You are in view mode. Sign in to issue directions, record decisions, or adjust organizational units.</span>
            )}
          </p>
        </div>
      </section>

      {/* Authenticated User Identity Card */}
      {currentUser ? (
        <section className="mce-card mce-user-profile-card">
          <div className="mce-card-header">
            <div className="mce-identity-badge-row">
              <div className="mce-user-avatar mce-avatar-large">
                {currentUser.display_name.charAt(0).toUpperCase()}
              </div>
              <div>
                <h3 className="mce-card-title">{currentUser.display_name}</h3>
                <p className="mce-card-desc">
                  Username: <code>@{currentUser.username}</code> · Email: <code>{currentUser.email}</code>
                </p>
              </div>
            </div>
            <div className="mce-identity-tags">
              <span className="mce-pill mce-pill-success">ACCOUNT ACTIVE</span>
              {currentUser.roles.map((r) => (
                <span key={r} className="mce-pill mce-pill-primary">
                  ROLE: {r.toUpperCase()}
                </span>
              ))}
            </div>
          </div>
          <div className="mce-card-body">
            <h4 className="mce-subhead">Effective Granted Permissions ({currentUser.permissions.length}):</h4>
            <div className="mce-permissions-chips">
              {currentUser.permissions.map((p) => (
                <span key={p} className="mce-perm-chip">
                  ✓ {p}
                </span>
              ))}
            </div>
          </div>
        </section>
      ) : (
        <section className="mce-card mce-login-prompt-card">
          <div className="mce-card-body mce-prompt-body">
            <div>
              <h3 className="mce-card-title">Authentication Available</h3>
              <p className="mce-card-desc">Sign in with an authorized user account to manage governance, delegations, and organizational positions.</p>
            </div>
            <button
              type="button"
              className="mce-btn mce-btn-primary"
              onClick={onOpenLogin}
            >
              Sign In to MCE Portal
            </button>
          </div>
        </section>
      )}

      {/* View Tabs */}
      <div className="mce-tabs-nav">
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'overview' ? 'mce-tab-active' : ''}`}
          onClick={() => setActiveTab('overview')}
        >
          ⚙️ System Overview
        </button>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'workforce' ? 'mce-tab-active' : ''}`}
          onClick={() => setActiveTab('workforce')}
        >
          🤖 AI Workforce Registry
        </button>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'scheduling' ? 'mce-tab-active' : ''}`}
          onClick={() => setActiveTab('scheduling')}
        >
          📅 Scheduling & Wallbook
        </button>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'tasks' ? 'mce-tab-active' : ''}`}
          onClick={() => setActiveTab('tasks')}
        >
          📋 Task Control Plane
        </button>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'runtime' ? 'mce-tab-active' : ''}`}
          onClick={() => setActiveTab('runtime')}
        >
          ⚡ AI Runtime & 9Router
        </button>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'welfare' ? 'mce-tab-active' : ''}`}
          onClick={() => setActiveTab('welfare')}
        >
          🌿 Agent Welfare
        </button>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'knowledge' ? 'mce-tab-active' : ''}`}
          onClick={() => setActiveTab('knowledge')}
        >
          📚 Knowledge Base & Memory
        </button>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'organization' ? 'mce-tab-active' : ''}`}
          onClick={() => setActiveTab('organization')}
        >
          🏛️ Organization Hierarchy
        </button>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'governance' ? 'mce-tab-active' : ''}`}
          onClick={() => setActiveTab('governance')}
        >
          📜 Corporate Governance & DoA
        </button>
        {currentUser && (
          <>
            <button
              type="button"
              className={`mce-tab-btn ${activeTab === 'users' ? 'mce-tab-active' : ''}`}
              onClick={() => setActiveTab('users')}
            >
              👥 User Accounts ({usersList.length})
            </button>
            <button
              type="button"
              className={`mce-tab-btn ${activeTab === 'roles' ? 'mce-tab-active' : ''}`}
              onClick={() => setActiveTab('roles')}
            >
              🛡️ Roles & Permissions ({rolesList.length})
            </button>
            <button
              type="button"
              className={`mce-tab-btn ${activeTab === 'audit' ? 'mce-tab-active' : ''}`}
              onClick={() => setActiveTab('audit')}
            >
              📋 Security Audit Logs ({auditList.length})
            </button>
          </>
        )}
      </div>

      {/* Overview Tab */}
      {activeTab === 'overview' && (
        <>
          <section className="mce-cards-grid">
            {/* Backend API Status */}
            <div className="mce-card">
              <div className="mce-card-header">
                <h3 className="mce-card-title">Backend REST Engine</h3>
                <span className={`mce-pill ${apiStatus === 'online' ? 'mce-pill-success' : 'mce-pill-warning'}`}>
                  {apiStatus.toUpperCase()}
                </span>
              </div>
              <div className="mce-card-body">
                <dl className="mce-kv-list">
                  <div>
                    <dt>Service Name</dt>
                    <dd>{systemHealth?.service || 'mce-ai-office-api'}</dd>
                  </div>
                  <div>
                    <dt>Framework</dt>
                    <dd>CodeIgniter {systemHealth?.ci_version || '4.7.6'}</dd>
                  </div>
                  <div>
                    <dt>PHP Runtime</dt>
                    <dd>PHP {systemHealth?.php_version || '8.2.12'}</dd>
                  </div>
                  <div>
                    <dt>Governance Models</dt>
                    <dd>DoA Levels A/B/C + 7 Core Values</dd>
                  </div>
                  <div>
                    <dt>API Latency</dt>
                    <dd>{latencyMs !== null ? `${latencyMs} ms` : 'Checking...'}</dd>
                  </div>
                </dl>
              </div>
            </div>

            {/* Database System of Record */}
            <div className="mce-card">
              <div className="mce-card-header">
                <h3 className="mce-card-title">Database System of Record</h3>
                <span className={`mce-pill ${dbHealth?.connected ? 'mce-pill-success' : 'mce-pill-danger'}`}>
                  {dbHealth?.connected ? 'CONNECTED' : 'DISCONNECTED'}
                </span>
              </div>
              <div className="mce-card-body">
                <dl className="mce-kv-list">
                  <div>
                    <dt>Database Server</dt>
                    <dd>{dbHealth?.version || 'MySQL / MariaDB (Port 3306)'}</dd>
                  </div>
                  <div>
                    <dt>Active Schema</dt>
                    <dd><code>{dbHealth?.database || 'mce_db'}</code></dd>
                  </div>
                  <div>
                    <dt>Applied Migrations</dt>
                    <dd>Batches 1, 2 & 3 (12 Org & Gov Tables)</dd>
                  </div>
                  <div>
                    <dt>Seeded Core Values</dt>
                    <dd>7 Official Principles (Controlled)</dd>
                  </div>
                  <div>
                    <dt>Audit Trail</dt>
                    <dd><code>governance_audit_logs</code> Active</dd>
                  </div>
                </dl>
              </div>
            </div>

            {/* Corporate Governance & DoA Boundary */}
            <div className="mce-card">
              <div className="mce-card-header">
                <h3 className="mce-card-title">Delegation of Authority (DoA)</h3>
                <span className="mce-pill mce-pill-success">ACTIVE FRAMEWORK</span>
              </div>
              <div className="mce-card-body">
                <dl className="mce-kv-list">
                  <div>
                    <dt>Level A (Owner)</dt>
                    <dd>&gt; IDR 5.000.000 / Capital / Veto</dd>
                  </div>
                  <div>
                    <dt>Level B (Board)</dt>
                    <dd>≤ IDR 5.000.000 (Autonomous Ops)</dd>
                  </div>
                  <div>
                    <dt>Level C (Dir AI)</dt>
                    <dd>≤ IDR 1.000.000 (Operational Dispatch)</dd>
                  </div>
                  <div>
                    <dt>Routine Operational</dt>
                    <dd>Non-Financial Technical Execution</dd>
                  </div>
                  <div>
                    <dt>Owner Directions</dt>
                    <dd>Advisory vs Binding Distinction</dd>
                  </div>
                </dl>
              </div>
            </div>
          </section>

          {/* Interactive Communication Console */}
          <section className="mce-card mce-console-card">
            <div className="mce-card-header">
              <div>
                <h3 className="mce-card-title">REST API & Governance Diagnostic</h3>
                <p className="mce-card-desc">Execute real HTTP requests to verify backend endpoints and organizational state.</p>
              </div>
              <div className="mce-console-actions">
                <button
                  type="button"
                  className="mce-btn mce-btn-secondary"
                  disabled={activeTest !== null}
                  onClick={() => handleTestEndpoint('tree')}
                >
                  {activeTest === 'tree' ? 'Fetching...' : 'GET /organization/tree'}
                </button>
                <button
                  type="button"
                  className="mce-btn mce-btn-secondary"
                  disabled={activeTest !== null}
                  onClick={() => handleTestEndpoint('values')}
                >
                  {activeTest === 'values' ? 'Fetching...' : 'GET /governance/core-values'}
                </button>
                <button
                  type="button"
                  className="mce-btn mce-btn-secondary"
                  disabled={activeTest !== null}
                  onClick={() => handleTestEndpoint('database')}
                >
                  {activeTest === 'database' ? 'Checking...' : 'GET /health/database'}
                </button>
                <button
                  type="button"
                  className="mce-btn mce-btn-primary"
                  disabled={loading}
                  onClick={() => {
                    fetchDiagnostics();
                    fetchOrgGovData();
                  }}
                >
                  {loading ? 'Refreshing...' : '🔄 Refresh All Data'}
                </button>
              </div>
            </div>
            <div className="mce-card-body">
              <pre className="mce-console-view">{consoleOutput}</pre>
            </div>
          </section>
        </>
      )}

      {/* AI Workforce Registry Tab (Prompt 19) */}
      {activeTab === 'workforce' && (
        <WorkforceView isAuthenticated={!!currentUser} onOpenLogin={onOpenLogin} />
      )}

      {/* Scheduling & Wallbook Handover Tab (Prompt 20) */}
      {activeTab === 'scheduling' && (
        <section className="mce-card" style={{ padding: 0, background: 'transparent', border: 'none' }}>
          <SchedulingView />
        </section>
      )}

      {/* Task Control Plane Tab (Prompt 21) */}
      {activeTab === 'tasks' && (
        <section className="mce-card" style={{ padding: 0, background: 'transparent', border: 'none' }}>
          <TaskManagementView />
        </section>
      )}

      {/* AI Runtime & 9Router Model Gateway Tab (Prompt 22) */}
      {activeTab === 'runtime' && (
        <section className="mce-card" style={{ padding: 0, background: 'transparent', border: 'none' }}>
          <RuntimeManagementView onNotify={(msg, type) => setConsoleOutput(`[${type?.toUpperCase() || 'INFO'}] ${msg}`)} />
        </section>
      )}

      {/* AI Agent Welfare & Tickets Operations Tab (Prompt 24) */}
      {activeTab === 'welfare' && (
        <section className="mce-card" style={{ padding: 0, background: 'transparent', border: 'none' }}>
          <WelfareManagementView isAuthenticated={!!currentUser} onOpenLogin={onOpenLogin} />
        </section>
      )}

      {/* Knowledge Management & Organizational Memory Tab (Prompt 25) */}
      {activeTab === 'knowledge' && (
        <section className="mce-card" style={{ padding: 0, background: 'transparent', border: 'none' }}>
          <KnowledgeManagementView isAuthenticated={!!currentUser} onOpenLogin={onOpenLogin} />
        </section>
      )}

      {/* Organization Hierarchy Tab */}
      {activeTab === 'organization' && (
        <section className="mce-card">
          <div className="mce-card-header">
            <div>
              <h3 className="mce-card-title">Dynamic Organizational Hierarchy</h3>
              <p className="mce-card-desc">Multi-level corporate tree: Root company, Continuous Executive Board, and 6 specialized operating divisions.</p>
            </div>
            <button
              type="button"
              className="mce-btn mce-btn-secondary mce-btn-sm"
              onClick={fetchOrgGovData}
              disabled={loadingOrgGov}
            >
              {loadingOrgGov ? 'Refreshing...' : '🔄 Refresh Hierarchy'}
            </button>
          </div>
          <div className="mce-card-body">
            <div className="mce-tree-wrapper">
              {orgTree.map((node) => renderTreeNode(node))}
            </div>

            <div style={{ marginTop: '28px' }}>
              <h4 className="mce-subhead">Key Leadership Positions & Appointees ({positionsList.length})</h4>
              <div style={{ overflowX: 'auto' }}>
                <table className="mce-table">
                  <thead>
                    <tr>
                      <th>Code</th>
                      <th>Title</th>
                      <th>Unit</th>
                      <th>Level</th>
                      <th>Leadership</th>
                      <th>Current Appointee</th>
                      <th>Type</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {positionsList.map((p) => {
                      const appt = p.active_appointment;
                      return (
                        <tr key={p.id}>
                          <td><code>{p.code}</code></td>
                          <td><strong>{p.title}</strong></td>
                          <td>{p.unit_name}</td>
                          <td><span className="mce-pill mce-pill-primary">{p.level.toUpperCase()}</span></td>
                          <td>{p.is_leadership ? '⭐ Yes' : 'No'}</td>
                          <td>{appt ? <strong>{appt.appointee_name}</strong> : <span style={{ opacity: 0.5 }}>Unassigned</span>}</td>
                          <td>
                            {appt ? (
                              <span className={`mce-pill ${appt.appointee_type === 'human' ? 'mce-pill-neutral' : 'mce-pill-primary'}`}>
                                {appt.appointee_type.toUpperCase()}
                              </span>
                            ) : '-'}
                          </td>
                          <td>
                            {appt ? (
                              <span className={`mce-pill ${appt.status === 'active' ? 'mce-pill-success' : 'mce-pill-warning'}`}>
                                {appt.status.toUpperCase()}
                              </span>
                            ) : '-'}
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>
      )}

      {/* Corporate Governance & DoA Tab */}
      {activeTab === 'governance' && (
        <>
          {/* 7 Core Values */}
          <section className="mce-card">
            <div className="mce-card-header">
              <div>
                <h3 className="mce-card-title">7 Core Values of MCE (Controlled Reference Data)</h3>
                <p className="mce-card-desc">The foundational ethical and operational principles governing all human and autonomous AI actions.</p>
              </div>
              <span className="mce-pill mce-pill-primary">7 PILLARS</span>
            </div>
            <div className="mce-card-body">
              <div className="mce-corevalues-grid">
                {coreValuesList.map((val) => (
                  <div key={val.id} className="mce-corevalue-card">
                    <span className="mce-corevalue-num">{val.display_order}</span>
                    <div className="mce-corevalue-header">
                      <h4 className="mce-corevalue-title">{val.name_en}</h4>
                      <span className="mce-corevalue-sub">({val.name_id})</span>
                    </div>
                    <p className="mce-corevalue-desc">{val.description}</p>
                  </div>
                ))}
              </div>
            </div>
          </section>

          {/* Delegation of Authority Matrix */}
          <section className="mce-card">
            <div className="mce-card-header">
              <div>
                <h3 className="mce-card-title">Delegation of Authority (DoA) Framework</h3>
                <p className="mce-card-desc">Clearly defined spending and decision boundaries ensuring autonomous speed without loss of control.</p>
              </div>
              <span className="mce-pill mce-pill-success">ENFORCED</span>
            </div>
            <div className="mce-card-body">
              <div className="mce-doa-grid">
                {doaProfilesList.map((p) => {
                  const levelClass =
                    p.authority_level === 'A'
                      ? 'mce-doa-level-a'
                      : p.authority_level === 'B'
                      ? 'mce-doa-level-b'
                      : p.authority_level === 'C'
                      ? 'mce-doa-level-c'
                      : 'mce-doa-level-op';

                  return (
                    <div key={p.id} className="mce-doa-card">
                      <span className={`mce-doa-level ${levelClass}`}>LEVEL {p.authority_level}</span>
                      <h4>{p.name}</h4>
                      <div className="mce-doa-amount">
                        {p.financial_limit > 0
                          ? `Limit: IDR ${Number(p.financial_limit).toLocaleString('id-ID')}`
                          : p.authority_level === 'A'
                          ? 'Strategic / Capital (No Limit)'
                          : 'IDR 0.00 (Non-Financial)'}
                      </div>
                      <p className="mce-unit-mission">{p.approval_conditions}</p>
                    </div>
                  );
                })}
              </div>
            </div>
          </section>

          {/* Governance Bodies */}
          <section className="mce-card">
            <div className="mce-card-header">
              <div>
                <h3 className="mce-card-title">Governance Bodies & Decision Rules</h3>
                <p className="mce-card-desc">Authoritative councils and executive forums with collective or delegated mandates.</p>
              </div>
            </div>
            <div className="mce-card-body" style={{ overflowX: 'auto' }}>
              <table className="mce-table">
                <thead>
                  <tr>
                    <th>Code</th>
                    <th>Body Name</th>
                    <th>Type</th>
                    <th>Decision Rule</th>
                    <th>Mandate</th>
                    <th>Members</th>
                  </tr>
                </thead>
                <tbody>
                  {bodiesList.map((b) => (
                    <tr key={b.id}>
                      <td><code>{b.code}</code></td>
                      <td><strong>{b.name}</strong></td>
                      <td><span className="mce-pill mce-pill-primary">{b.type.toUpperCase()}</span></td>
                      <td><code>{b.decision_rule}</code></td>
                      <td>{b.mandate}</td>
                      <td><span className="mce-pill mce-pill-neutral">{b.member_count ?? b.members?.length ?? 0} Active</span></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>

          {/* Owner Directions & Executive Decisions Grid */}
          <div className="mce-cards-grid">
            {/* Owner Directions */}
            <div className="mce-card">
              <div className="mce-card-header">
                <div>
                  <h3 className="mce-card-title">Owner Directions ({directionsList.length})</h3>
                  <p className="mce-card-desc">Advisory recommendations vs Binding instructions issued by Human Owner.</p>
                </div>
              </div>
              <div className="mce-card-body" style={{ overflowX: 'auto' }}>
                <table className="mce-table">
                  <thead>
                    <tr>
                      <th>Code</th>
                      <th>Type</th>
                      <th>Subject</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {directionsList.map((d) => (
                      <tr key={d.id}>
                        <td><code>{d.direction_code}</code></td>
                        <td>
                          <span className={`mce-pill ${d.direction_type === 'binding' ? 'mce-pill-danger' : 'mce-pill-primary'}`}>
                            {d.direction_type.toUpperCase()}
                          </span>
                        </td>
                        <td>
                          <strong>{d.subject}</strong>
                          <div style={{ fontSize: '0.725rem', color: '#94a3b8' }}>{d.description}</div>
                        </td>
                        <td>
                          <span className={`mce-pill ${d.status === 'acknowledged' ? 'mce-pill-success' : 'mce-pill-warning'}`}>
                            {d.status.toUpperCase()}
                          </span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>

            {/* Executive Decisions */}
            <div className="mce-card">
              <div className="mce-card-header">
                <div>
                  <h3 className="mce-card-title">Executive Decisions ({decisionsList.length})</h3>
                  <p className="mce-card-desc">Continuous Executive Board ratified resolutions on operations and growth.</p>
                </div>
              </div>
              <div className="mce-card-body" style={{ overflowX: 'auto' }}>
                <table className="mce-table">
                  <thead>
                    <tr>
                      <th>Code</th>
                      <th>Category</th>
                      <th>Title</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {decisionsList.map((dec) => (
                      <tr key={dec.id}>
                        <td><code>{dec.decision_code}</code></td>
                        <td><span className="mce-pill mce-pill-neutral">{dec.decision_category}</span></td>
                        <td>
                          <strong>{dec.title}</strong>
                          <div style={{ fontSize: '0.725rem', color: '#94a3b8' }}>{dec.summary}</div>
                        </td>
                        <td>
                          <span className={`mce-pill ${dec.status === 'ratified' ? 'mce-pill-success' : 'mce-pill-primary'}`}>
                            {dec.status.toUpperCase()}
                          </span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </>
      )}

      {/* Users Tab */}
      {activeTab === 'users' && currentUser && (
        <section className="mce-card">
          <div className="mce-card-header">
            <h3 className="mce-card-title">Human Application Users (`users`)</h3>
            <span className="mce-pill mce-pill-primary">{usersList.length} Accounts</span>
          </div>
          <div className="mce-card-body" style={{ overflowX: 'auto' }}>
            <table className="mce-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Username</th>
                  <th>Full Name</th>
                  <th>Email</th>
                  <th>Roles</th>
                  <th>Status</th>
                  <th>Last Login</th>
                </tr>
              </thead>
              <tbody>
                {usersList.map((u) => (
                  <tr key={u.id}>
                    <td><code>#{u.id}</code></td>
                    <td><strong>{u.username}</strong></td>
                    <td>{u.full_name || u.display_name}</td>
                    <td><code>{u.email}</code></td>
                    <td>
                      {u.roles?.map((r) => (
                        <span key={r} className="mce-pill mce-pill-primary" style={{ marginRight: '4px' }}>
                          {r}
                        </span>
                      ))}
                    </td>
                    <td>
                      <span className={`mce-pill ${u.status === 'active' ? 'mce-pill-success' : 'mce-pill-danger'}`}>
                        {u.status.toUpperCase()}
                      </span>
                    </td>
                    <td>{u.last_login_at || 'Never'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>
      )}

      {/* Roles & Permissions Tab */}
      {activeTab === 'roles' && currentUser && (
        <section className="mce-card">
          <div className="mce-card-header">
            <h3 className="mce-card-title">Role-Based Access Control Model (`roles`)</h3>
            <span className="mce-pill mce-pill-primary">{rolesList.length} Roles Registered</span>
          </div>
          <div className="mce-card-body">
            <div className="mce-roles-grid">
              {rolesList.map((r) => (
                <div key={r.id} className="mce-role-box">
                  <div className="mce-role-box-header">
                    <h4>{r.name}</h4>
                    <code>{r.slug}</code>
                  </div>
                  <p className="mce-role-desc">{r.description}</p>
                  <div className="mce-role-perms">
                    <span className="mce-perm-count">Permissions ({r.permissions?.length || 0}):</span>
                    <div className="mce-perms-tags">
                      {r.permissions?.map((p) => (
                        <span key={p.slug} className="mce-perm-tag">
                          {p.slug}
                        </span>
                      ))}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* Security Audit Logs Tab */}
      {activeTab === 'audit' && currentUser && (
        <section className="mce-card">
          <div className="mce-card-header">
            <h3 className="mce-card-title">Recent Security Events (`security_events`)</h3>
            <button type="button" className="mce-btn mce-btn-secondary mce-btn-sm" onClick={fetchRbacData} disabled={loadingRbac}>
              {loadingRbac ? 'Refreshing...' : '🔄 Refresh Logs'}
            </button>
          </div>
          <div className="mce-card-body" style={{ overflowX: 'auto' }}>
            <table className="mce-table">
              <thead>
                <tr>
                  <th>Timestamp</th>
                  <th>Event Type</th>
                  <th>Actor</th>
                  <th>IP Address</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                {auditList.map((log) => (
                  <tr key={log.id}>
                    <td><small>{log.created_at}</small></td>
                    <td><code>{log.event_type}</code></td>
                    <td>{log.actor_identifier || (log.user_id ? `User #${log.user_id}` : 'Anonymous')}</td>
                    <td><small>{log.ip_address}</small></td>
                    <td>
                      <span className={`mce-pill ${log.status === 'success' ? 'mce-pill-success' : 'mce-pill-danger'}`}>
                        {log.status.toUpperCase()}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>
      )}
    </main>
  );
};
