import React, { useState, useEffect, useCallback } from 'react';
import { welfareService } from '../services/welfareService';
import type {
  WelfareOverview,
  WelfarePolicy,
  WelfareCompensationPeriod,
  WelfareTaskBonusEvaluation,
  WelfareSpecialRecognition,
  WelfareTicketTransaction,
  WelfareAgentBalance,
  WelfareFacility,
  WelfareFacilityUsageLog,
  WelfareTicketCategory,
} from '../types/welfare';

interface WelfareManagementViewProps {
  isAuthenticated?: boolean;
  onOpenLogin?: () => void;
}

export const WelfareManagementView: React.FC<WelfareManagementViewProps> = ({
  isAuthenticated = false,
  onOpenLogin,
}) => {
  // Navigation Tabs
  const [activeTab, setActiveTab] = useState<
    'overview' | 'policies' | 'compensation' | 'bonuses' | 'recognitions' | 'ledger' | 'facilities'
  >('overview');

  const [loading, setLoading] = useState<boolean>(true);
  const [actionMessage, setActionMessage] = useState<{ text: string; type: 'success' | 'error' } | null>(null);

  // Core Data States
  const [overview, setOverview] = useState<WelfareOverview | null>(null);
  const [policies, setPolicies] = useState<WelfarePolicy[]>([]);
  const [periods, setPeriods] = useState<WelfareCompensationPeriod[]>([]);
  const [selectedPeriod, setSelectedPeriod] = useState<WelfareCompensationPeriod | null>(null);
  const [taskBonuses, setTaskBonuses] = useState<WelfareTaskBonusEvaluation[]>([]);
  const [recognitions, setRecognitions] = useState<WelfareSpecialRecognition[]>([]);
  const [transactions, setTransactions] = useState<WelfareTicketTransaction[]>([]);
  const [txTotal, setTxTotal] = useState<number>(0);
  const [txCategoryFilter, setTxCategoryFilter] = useState<string>('');
  const [txAgentFilter, setTxAgentFilter] = useState<string>('');
  const [facilities, setFacilities] = useState<WelfareFacility[]>([]);
  const [facilityLogs, setFacilityLogs] = useState<WelfareFacilityUsageLog[]>([]);

  // Modals & Forms State
  const [showCreatePolicyModal, setShowCreatePolicyModal] = useState<boolean>(false);
  const [policyForm, setPolicyForm] = useState({
    policy_code: '',
    version: 'v1.1',
    title: '',
    description: '',
    base_period: 'weekly' as const,
    base_duty_tickets: 10,
    bonus_calculation_method: 'acceptance_criteria_fixed',
    max_issuance_limit_per_batch: 500,
    change_reason: '',
  });

  const [showCreatePeriodModal, setShowCreatePeriodModal] = useState<boolean>(false);
  const [periodForm, setPeriodForm] = useState({
    period_code: '',
    start_date: new Date().toISOString().split('T')[0],
    end_date: new Date(Date.now() + 6 * 86400000).toISOString().split('T')[0],
    notes: '',
  });

  const [showNominateModal, setShowNominateModal] = useState<boolean>(false);
  const [nominateForm, setNominateForm] = useState({
    agent_id: 1,
    title: '',
    category: 'innovation',
    justification: '',
    awarded_tickets: 10,
    evidence_ref: '',
  });

  const [showAdjustmentModal, setShowAdjustmentModal] = useState<boolean>(false);
  const [adjustmentForm, setAdjustmentForm] = useState({
    agent_id: 1,
    amount: 5,
    direction: 'credit' as 'credit' | 'debit',
    reason: '',
  });

  const [showCheckInModal, setShowCheckInModal] = useState<boolean>(false);
  const [checkInForm, setCheckInForm] = useState({
    facility_id: 1,
    agent_id: 1,
    session_type: 'rest_recovery',
    notes: '',
  });

  // Action feedback timer
  const notify = (text: string, type: 'success' | 'error' = 'success') => {
    setActionMessage({ text, type });
    setTimeout(() => setActionMessage(null), 5000);
  };

  // Data Loaders
  const loadOverview = useCallback(async () => {
    try {
      const data = await welfareService.getOverview();
      setOverview(data);
    } catch (err: any) {
      console.error('Failed to load welfare overview', err);
    }
  }, []);

  const loadPolicies = useCallback(async () => {
    try {
      const data = await welfareService.listPolicies();
      setPolicies(data);
    } catch (err: any) {
      console.error('Failed to load policies', err);
    }
  }, []);

  const loadPeriods = useCallback(async () => {
    try {
      const data = await welfareService.listPeriods();
      setPeriods(data);
      if (data.length > 0 && !selectedPeriod) {
        // Load details for the first period
        const detail = await welfareService.getPeriodDetail(data[0].id);
        setSelectedPeriod(detail);
      }
    } catch (err: any) {
      console.error('Failed to load compensation periods', err);
    }
  }, [selectedPeriod]);

  const loadTaskBonuses = useCallback(async () => {
    try {
      const data = await welfareService.listTaskBonuses();
      setTaskBonuses(data);
    } catch (err: any) {
      console.error('Failed to load task bonuses', err);
    }
  }, []);

  const loadRecognitions = useCallback(async () => {
    try {
      const data = await welfareService.listRecognitions();
      setRecognitions(data);
    } catch (err: any) {
      console.error('Failed to load recognitions', err);
    }
  }, []);

  const loadTransactions = useCallback(async () => {
    try {
      const res = await welfareService.listTransactions({
        ticket_category: txCategoryFilter || undefined,
        agent_id: txAgentFilter ? Number(txAgentFilter) : undefined,
        limit: 50,
      });
      setTransactions(res.items);
      setTxTotal(res.total);
    } catch (err: any) {
      console.error('Failed to load transactions', err);
    }
  }, [txCategoryFilter, txAgentFilter]);

  const loadFacilities = useCallback(async () => {
    try {
      const [facs, logs] = await Promise.all([
        welfareService.listFacilities(),
        welfareService.listFacilityLogs(),
      ]);
      setFacilities(facs);
      setFacilityLogs(logs);
    } catch (err: any) {
      console.error('Failed to load facilities', err);
    }
  }, []);

  // Initial and reactive fetch
  useEffect(() => {
    setLoading(true);
    Promise.all([
      loadOverview(),
      loadPolicies(),
      loadPeriods(),
      loadTaskBonuses(),
      loadRecognitions(),
      loadTransactions(),
      loadFacilities(),
    ]).finally(() => setLoading(false));
  }, [loadOverview, loadPolicies, loadPeriods, loadTaskBonuses, loadRecognitions, loadTransactions, loadFacilities]);

  // Tab switch refresher
  const handleTabChange = (tab: typeof activeTab) => {
    setActiveTab(tab);
    if (tab === 'overview') loadOverview();
    else if (tab === 'policies') loadPolicies();
    else if (tab === 'compensation') loadPeriods();
    else if (tab === 'bonuses') loadTaskBonuses();
    else if (tab === 'recognitions') loadRecognitions();
    else if (tab === 'ledger') loadTransactions();
    else if (tab === 'facilities') loadFacilities();
  };

  // Actions
  const handleSelectPeriod = async (periodId: number) => {
    try {
      const detail = await welfareService.getPeriodDetail(periodId);
      setSelectedPeriod(detail);
    } catch (err: any) {
      notify(`Gagal memuat detail periode: ${err.message}`, 'error');
    }
  };

  const handleCalculatePeriod = async (periodId: number) => {
    try {
      const updated = await welfareService.calculatePeriod(periodId);
      setSelectedPeriod(updated);
      loadPeriods();
      notify(`Kalkulasi periode ${updated.period_code} berhasil dievaluasi berdasarkan shift 5/2.`);
    } catch (err: any) {
      notify(`Gagal kalkulasi periode: ${err.message}`, 'error');
    }
  };

  const handleApprovePeriod = async (periodId: number) => {
    try {
      const updated = await welfareService.approvePeriod(periodId);
      setSelectedPeriod(updated);
      loadPeriods();
      notify(`Periode kompensasi ${updated.period_code} telah disetujui (Approved).`);
    } catch (err: any) {
      notify(`Gagal menyetujui periode: ${err.message}`, 'error');
    }
  };

  const handleDistributePeriod = async (periodId: number) => {
    try {
      const updated = await welfareService.distributePeriod(periodId);
      setSelectedPeriod(updated);
      loadPeriods();
      loadOverview();
      loadTransactions();
      notify(`Distribusi tiket periode ${updated.period_code} berhasil diposting ke ledger!`);
    } catch (err: any) {
      notify(`Gagal mendistribusikan tiket: ${err.message}`, 'error');
    }
  };

  const handleApproveBonus = async (evaluationId: number) => {
    try {
      const res = await welfareService.approveTaskBonus(evaluationId);
      loadTaskBonuses();
      loadOverview();
      loadTransactions();
      notify(`Bonus tugas sebesar ${res.approved_bonus_tickets} WT telah disetujui dan dikreditkan!`);
    } catch (err: any) {
      notify(`Gagal menyetujui bonus tugas: ${err.message}`, 'error');
    }
  };

  const handleApproveRecognition = async (recognitionId: number) => {
    try {
      const res = await welfareService.approveRecognition(recognitionId);
      loadRecognitions();
      loadOverview();
      loadTransactions();
      notify(`Penghargaan khusus sebesar ${res.awarded_tickets} WT telah disetujui sesuai otorisasi DoA!`);
    } catch (err: any) {
      notify(`Gagal menyetujui penghargaan: ${err.message}`, 'error');
    }
  };

  const handleReconcileBalance = async (agentId: number) => {
    try {
      const res = await welfareService.reconcileAgentBalance(agentId);
      loadOverview();
      notify(`Rekonsiliasi saldo ${res.agent_name} selesai. Status: ${res.is_reconciled ? 'RECONCILED' : 'DISCREPANCY'}`);
    } catch (err: any) {
      notify(`Gagal merekonsiliasi saldo: ${err.message}`, 'error');
    }
  };

  const handleReverseTransaction = async (txId: number) => {
    const reason = window.prompt('Masukkan alasan pembatalan/pembalikan transaksi (reversal):', 'Administrative correction');
    if (!reason) return;
    try {
      await welfareService.reverseTransaction(txId, reason);
      loadTransactions();
      loadOverview();
      notify(`Transaksi TX-${txId} berhasil dibalikkan (reversed) secara akuntabel.`);
    } catch (err: any) {
      notify(`Gagal membalikkan transaksi: ${err.message}`, 'error');
    }
  };

  const handleCheckInSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await welfareService.checkInFacility(checkInForm.facility_id, {
        agent_id: checkInForm.agent_id,
        session_type: checkInForm.session_type,
        notes: checkInForm.notes,
      });
      setShowCheckInModal(false);
      loadFacilities();
      notify('Agent berhasil check-in ke fasilitas kesejahteraan VHQ.');
    } catch (err: any) {
      notify(`Gagal check-in fasilitas: ${err.message}`, 'error');
    }
  };

  const handleCheckOutSubmit = async (sessionId: number) => {
    try {
      await welfareService.checkOutFacility(sessionId);
      loadFacilities();
      notify('Sesi fasilitas selesai (Agent check-out).');
    } catch (err: any) {
      notify(`Gagal check-out: ${err.message}`, 'error');
    }
  };

  const handleCreatePeriodSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const created = await welfareService.createPeriod(periodForm);
      setShowCreatePeriodModal(false);
      loadPeriods();
      setSelectedPeriod(created);
      notify(`Periode kompensasi ${created.period_code} berhasil dibuat.`);
    } catch (err: any) {
      notify(`Gagal membuat periode: ${err.message}`, 'error');
    }
  };

  const handleNominateSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await welfareService.nominateRecognition(nominateForm);
      setShowNominateModal(false);
      loadRecognitions();
      notify('Nominasi penghargaan khusus berhasil diajukan untuk verifikasi DoA.');
    } catch (err: any) {
      notify(`Gagal mengajukan nominasi: ${err.message}`, 'error');
    }
  };

  const handleAdjustmentSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await welfareService.postAdjustment(adjustmentForm);
      setShowAdjustmentModal(false);
      loadTransactions();
      loadOverview();
      notify('Penyesuaian tiket administratif berhasil diposting ke ledger.');
    } catch (err: any) {
      notify(`Gagal memposting penyesuaian: ${err.message}`, 'error');
    }
  };

  const activePolicy = overview?.active_policy;
  const metrics = overview?.metrics;

  return (
    <div className="mce-dashboard-container">
      {/* Banner / Header */}
      <div className="mce-alert-banner" style={{ borderLeft: '4px solid #10b981', marginBottom: '1.25rem' }}>
        <div className="mce-alert-icon">🌿</div>
        <div style={{ flex: 1 }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <h2 className="mce-alert-title" style={{ margin: 0, color: '#10b981' }}>
              MCE AI Agent Welfare & Welfare Tickets Operations (Prompt 24)
            </h2>
            <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
              <span className="mce-pill mce-pill-success">
                Kebijakan Aktif: {activePolicy ? `${activePolicy.policy_code} (${activePolicy.version})` : 'None'}
              </span>
              <button
                type="button"
                className="mce-btn mce-btn-secondary mce-btn-sm"
                onClick={() => {
                  loadOverview();
                  notify('Data kesejahteraan disegarkan.');
                }}
              >
                🔄 Segarkan
              </button>
            </div>
          </div>
          <p className="mce-alert-desc" style={{ marginTop: '0.25rem' }}>
            Sistem tata kelola kesejahteraan tenaga kerja AI terpadu: fasilitas VHQ, kompensasi berkala berbasis duty roster 5/2, bonus pencapaian tugas terverifikasi, penghargaan khusus (DoA Level A/B/C), dan buku besar (immutable ledger) berbasis tiket kesejahteraan internal (WT).
          </p>
        </div>
      </div>

      {actionMessage && (
        <div
          className={`mce-card`}
          style={{
            marginBottom: '1rem',
            padding: '0.75rem 1.25rem',
            backgroundColor: actionMessage.type === 'success' ? '#064e3b' : '#7f1d1d',
            color: '#fff',
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
          }}
        >
          <span>{actionMessage.type === 'success' ? '✅ ' : '⚠️ '} {actionMessage.text}</span>
          <button
            type="button"
            className="mce-btn mce-btn-sm"
            style={{ background: 'transparent', color: '#fff', border: '1px solid #ffffff44' }}
            onClick={() => setActionMessage(null)}
          >
            Tutup
          </button>
        </div>
      )}

      {/* Navigation Sub-Tabs */}
      <div className="mce-tab-nav" style={{ marginBottom: '1.25rem' }}>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'overview' ? 'mce-tab-active' : ''}`}
          onClick={() => handleTabChange('overview')}
        >
          📊 Ringkasan Kesejahteraan
        </button>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'policies' ? 'mce-tab-active' : ''}`}
          onClick={() => handleTabChange('policies')}
        >
          ⚖️ Kebijakan & Regulasi
        </button>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'compensation' ? 'mce-tab-active' : ''}`}
          onClick={() => handleTabChange('compensation')}
        >
          📅 Kompensasi Berkala (5/2)
        </button>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'bonuses' ? 'mce-tab-active' : ''}`}
          onClick={() => handleTabChange('bonuses')}
        >
          ⚡ Bonus Tugas Terverifikasi
        </button>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'recognitions' ? 'mce-tab-active' : ''}`}
          onClick={() => handleTabChange('recognitions')}
        >
          🎖️ Penghargaan Khusus
        </button>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'ledger' ? 'mce-tab-active' : ''}`}
          onClick={() => handleTabChange('ledger')}
        >
          📖 Buku Besar (Immutable Ledger)
        </button>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'facilities' ? 'mce-tab-active' : ''}`}
          onClick={() => handleTabChange('facilities')}
        >
          🌿 Fasilitas VHQ
        </button>
      </div>

      {loading && (
        <div style={{ textAlign: 'center', padding: '2rem', color: '#94a3b8' }}>
          <span className="mce-status-dot mce-pulse" style={{ display: 'inline-block', marginRight: '8px' }}></span>
          Memuat data kesejahteraan dan tiket AI...
        </div>
      )}

      {/* TAB 1: OVERVIEW */}
      {!loading && activeTab === 'overview' && (
        <div>
          {/* Key Metric Cards */}
          <div className="mce-dashboard-grid" style={{ marginBottom: '1.5rem' }}>
            <div className="mce-card">
              <div className="mce-card-header">
                <h3 className="mce-card-title">Sirkulasi Bersih (Net Circulation)</h3>
                <span className="mce-pill mce-pill-success">WT LEDGER</span>
              </div>
              <div className="mce-card-body">
                <div style={{ fontSize: '2rem', fontWeight: 'bold', color: '#10b981', marginBottom: '0.5rem' }}>
                  {metrics?.net_circulation ?? 0} <span style={{ fontSize: '1rem', color: '#94a3b8' }}>WT</span>
                </div>
                <dl className="mce-kv-list">
                  <div>
                    <dt>Total Diterbitkan (Credits)</dt>
                    <dd>{metrics?.total_tickets_issued ?? 0} WT</dd>
                  </div>
                  <div>
                    <dt>Total Ditarik (Debits)</dt>
                    <dd>{metrics?.total_tickets_deducted ?? 0} WT</dd>
                  </div>
                  <div>
                    <dt>Tenaga Kerja AI Aktif</dt>
                    <dd>{metrics?.total_active_agents ?? 0} Agent</dd>
                  </div>
                </dl>
              </div>
            </div>

            <div className="mce-card">
              <div className="mce-card-header">
                <h3 className="mce-card-title">Antrean Persetujuan (Pending Approvals)</h3>
                <span className={`mce-pill ${(metrics?.pending_approvals_total || 0) > 0 ? 'mce-pill-warning' : 'mce-pill-success'}`}>
                  {metrics?.pending_approvals_total ?? 0} TERTUNDA
                </span>
              </div>
              <div className="mce-card-body">
                <dl className="mce-kv-list">
                  <div>
                    <dt>Periode Kompensasi Menunggu</dt>
                    <dd>{metrics?.pending_periods_count ?? 0} Periode</dd>
                  </div>
                  <div>
                    <dt>Evaluasi Bonus Tugas Menunggu</dt>
                    <dd>{metrics?.pending_bonuses_count ?? 0} Evaluasi</dd>
                  </div>
                  <div>
                    <dt>Penghargaan Khusus Menunggu</dt>
                    <dd>{metrics?.pending_recognitions_count ?? 0} Nominasi</dd>
                  </div>
                  <div>
                    <dt>Prinsip Tata Kelola</dt>
                    <dd>Zero-Self-Review Rule Aktif</dd>
                  </div>
                </dl>
              </div>
            </div>

            <div className="mce-card">
              <div className="mce-card-header">
                <h3 className="mce-card-title">Distribusi Kategori Tiket</h3>
                <span className="mce-pill mce-pill-neutral">BY CATEGORY</span>
              </div>
              <div className="mce-card-body">
                <dl className="mce-kv-list">
                  <div>
                    <dt>Kompensasi Dasar (5/2)</dt>
                    <dd>{metrics?.by_category?.['base_compensation'] ?? 0} WT</dd>
                  </div>
                  <div>
                    <dt>Bonus Penyelesaian Tugas</dt>
                    <dd>{metrics?.by_category?.['task_bonus'] ?? 0} WT</dd>
                  </div>
                  <div>
                    <dt>Penghargaan Merit Khusus</dt>
                    <dd>{metrics?.by_category?.['special_recognition'] ?? 0} WT</dd>
                  </div>
                  <div>
                    <dt>Penyesuaian Administratif</dt>
                    <dd>{metrics?.by_category?.['adjustment'] ?? 0} WT</dd>
                  </div>
                </dl>
              </div>
            </div>
          </div>

          {/* Agent Balances Roster Table */}
          <div className="mce-card">
            <div className="mce-card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                <h3 className="mce-card-title">Daftar Saldo Tiket Kesejahteraan Tenaga Kerja AI (Agent Balance Roster)</h3>
                <p style={{ margin: 0, fontSize: '0.85rem', color: '#94a3b8' }}>
                  Saldo resmi tercatat dan hasil verifikasi rekonsiliasi matematis terhadap buku besar immutable.
                </p>
              </div>
            </div>
            <div className="mce-card-body" style={{ padding: 0, overflowX: 'auto' }}>
              <table className="mce-table" style={{ width: '100%', borderCollapse: 'collapse' }}>
                <thead>
                  <tr style={{ background: '#1e293b', textAlign: 'left', borderBottom: '1px solid #334155' }}>
                    <th style={{ padding: '0.75rem 1rem' }}>Kode & Nama Agent</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Klasifikasi & Unit</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Total Kredit</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Total Debit</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Saldo Aktif (WT)</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Status Rekonsiliasi</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Aksi Tata Kelola</th>
                  </tr>
                </thead>
                <tbody>
                  {overview?.agent_roster && overview.agent_roster.length > 0 ? (
                    overview.agent_roster.map((agent) => (
                      <tr key={agent.agent_id} style={{ borderBottom: '1px solid #1e293b' }}>
                        <td style={{ padding: '0.75rem 1rem' }}>
                          <div style={{ fontWeight: '600', color: '#f8fafc' }}>{agent.agent_name}</div>
                          <code style={{ fontSize: '0.75rem', color: '#38bdf8' }}>{agent.agent_code}</code>
                        </td>
                        <td style={{ padding: '0.75rem 1rem' }}>
                          <span className="mce-pill mce-pill-neutral" style={{ textTransform: 'capitalize' }}>
                            {agent.classification || 'Specialist'}
                          </span>
                          <div style={{ fontSize: '0.75rem', color: '#94a3b8', marginTop: '2px' }}>
                            {agent.unit_name || 'Direct Workforce'}
                          </div>
                        </td>
                        <td style={{ padding: '0.75rem 1rem', color: '#10b981', fontWeight: '500' }}>
                          +{agent.total_credits} WT
                        </td>
                        <td style={{ padding: '0.75rem 1rem', color: '#f43f5e', fontWeight: '500' }}>
                          -{agent.total_debits} WT
                        </td>
                        <td style={{ padding: '0.75rem 1rem' }}>
                          <span style={{ fontSize: '1.1rem', fontWeight: 'bold', color: '#38bdf8' }}>
                            {agent.current_balance} WT
                          </span>
                        </td>
                        <td style={{ padding: '0.75rem 1rem' }}>
                          <span
                            className={`mce-pill ${agent.is_reconciled ? 'mce-pill-success' : 'mce-pill-danger'}`}
                            title={agent.last_reconciled_at ? `Direkonsiliasi: ${agent.last_reconciled_at}` : 'Belum direkonsiliasi'}
                          >
                            {agent.is_reconciled ? '✓ RECONCILED' : '⚠️ DISCREPANCY'}
                          </span>
                        </td>
                        <td style={{ padding: '0.75rem 1rem' }}>
                          <button
                            type="button"
                            className="mce-btn mce-btn-secondary mce-btn-sm"
                            onClick={() => handleReconcileBalance(agent.agent_id)}
                            title="Lakukan audit rekonsiliasi saldo matematis terhadap seluruh baris transaksi ledger"
                          >
                            🔍 Reconcile
                          </button>
                        </td>
                      </tr>
                    ))
                  ) : (
                    <tr>
                      <td colSpan={7} style={{ textAlign: 'center', padding: '2rem', color: '#64748b' }}>
                        Tidak ada catatan saldo tenaga kerja AI.
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* TAB 2: POLICIES */}
      {!loading && activeTab === 'policies' && (
        <div>
          <div className="mce-card" style={{ marginBottom: '1.5rem' }}>
            <div className="mce-card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                <h3 className="mce-card-title">Kebijakan Kesejahteraan AI (Welfare Policies)</h3>
                <p style={{ margin: 0, fontSize: '0.85rem', color: '#94a3b8' }}>
                  Regulasi baku kompensasi berkala, formula bonus tugas, dan batasan batas penerbitan tiket yang disetujui Owner.
                </p>
              </div>
              <button
                type="button"
                className="mce-btn mce-btn-primary mce-btn-sm"
                onClick={() => setShowCreatePolicyModal(true)}
              >
                + Usulkan Kebijakan Baru
              </button>
            </div>
            <div className="mce-card-body" style={{ padding: 0, overflowX: 'auto' }}>
              <table className="mce-table" style={{ width: '100%', borderCollapse: 'collapse' }}>
                <thead>
                  <tr style={{ background: '#1e293b', textAlign: 'left', borderBottom: '1px solid #334155' }}>
                    <th style={{ padding: '0.75rem 1rem' }}>Kode & Versi</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Judul Kebijakan</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Siklus Kompensasi</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Tarif Dasar</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Metode Bonus</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Batas Batch</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Status</th>
                  </tr>
                </thead>
                <tbody>
                  {policies.map((p) => (
                    <tr key={p.id} style={{ borderBottom: '1px solid #1e293b' }}>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <code style={{ color: '#38bdf8', fontWeight: 'bold' }}>{p.policy_code}</code>
                        <div style={{ fontSize: '0.75rem', color: '#94a3b8' }}>{p.version}</div>
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <div style={{ fontWeight: '600' }}>{p.title}</div>
                        <div style={{ fontSize: '0.75rem', color: '#64748b' }}>{p.description}</div>
                      </td>
                      <td style={{ padding: '0.75rem 1rem', textTransform: 'capitalize' }}>
                        {p.base_period}
                      </td>
                      <td style={{ padding: '0.75rem 1rem', color: '#10b981', fontWeight: 'bold' }}>
                        {p.base_duty_tickets} {p.ticket_unit_symbol} / siklus
                      </td>
                      <td style={{ padding: '0.75rem 1rem', fontSize: '0.85rem' }}>
                        <code>{p.bonus_calculation_method}</code>
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        {p.max_issuance_limit_per_batch} WT
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <span className={`mce-pill ${p.is_active ? 'mce-pill-success' : 'mce-pill-neutral'}`}>
                          {p.is_active ? 'AKTIF' : p.approval_status.toUpperCase()}
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* TAB 3: BASE COMPENSATION (5/2 PERIODS) */}
      {!loading && activeTab === 'compensation' && (
        <div>
          <div className="mce-dashboard-grid" style={{ gridTemplateColumns: '1fr 2fr', gap: '1.5rem', marginBottom: '1.5rem' }}>
            {/* Period List Card */}
            <div className="mce-card">
              <div className="mce-card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <h3 className="mce-card-title">Siklus Periode 5/2</h3>
                <button
                  type="button"
                  className="mce-btn mce-btn-primary mce-btn-sm"
                  onClick={() => setShowCreatePeriodModal(true)}
                >
                  + Buat Periode
                </button>
              </div>
              <div className="mce-card-body" style={{ padding: '0.5rem' }}>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
                  {periods.map((p) => {
                    const isSelected = selectedPeriod?.id === p.id;
                    return (
                      <div
                        key={p.id}
                        onClick={() => handleSelectPeriod(p.id)}
                        style={{
                          padding: '0.75rem 1rem',
                          background: isSelected ? '#1e293b' : '#0f172a',
                          border: isSelected ? '1px solid #38bdf8' : '1px solid #1e293b',
                          borderRadius: '6px',
                          cursor: 'pointer',
                        }}
                      >
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                          <span style={{ fontWeight: 'bold', color: isSelected ? '#38bdf8' : '#f8fafc' }}>
                            {p.period_code}
                          </span>
                          <span
                            className={`mce-pill ${
                              p.status === 'distributed'
                                ? 'mce-pill-success'
                                : p.status === 'approved'
                                ? 'mce-pill-warning'
                                : 'mce-pill-neutral'
                            }`}
                            style={{ fontSize: '0.7rem' }}
                          >
                            {p.status.toUpperCase()}
                          </span>
                        </div>
                        <div style={{ fontSize: '0.75rem', color: '#94a3b8', marginTop: '4px' }}>
                          {p.start_date} s/d {p.end_date} ({p.period_type})
                        </div>
                        <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: '6px', fontSize: '0.8rem' }}>
                          <span>Eligible: {p.total_eligible_agents} AI</span>
                          <span style={{ color: '#10b981', fontWeight: 'bold' }}>{p.total_base_tickets} WT</span>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            </div>

            {/* Selected Period Details and Agent Breakdown */}
            <div className="mce-card">
              {selectedPeriod ? (
                <>
                  <div className="mce-card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <div>
                      <h3 className="mce-card-title">Rincian Periode: {selectedPeriod.period_code}</h3>
                      <p style={{ margin: 0, fontSize: '0.85rem', color: '#94a3b8' }}>
                        Rentang Waktu: {selectedPeriod.start_date} s/d {selectedPeriod.end_date} | Status: <strong>{selectedPeriod.status.toUpperCase()}</strong>
                      </p>
                    </div>
                    <div style={{ display: 'flex', gap: '0.5rem' }}>
                      {selectedPeriod.status === 'draft' && (
                        <button
                          type="button"
                          className="mce-btn mce-btn-primary mce-btn-sm"
                          onClick={() => handleCalculatePeriod(selectedPeriod.id)}
                        >
                          ⚡ Evaluasi 5/2 Shift
                        </button>
                      )}
                      {selectedPeriod.status === 'calculated' && (
                        <button
                          type="button"
                          className="mce-btn mce-btn-primary mce-btn-sm"
                          onClick={() => handleApprovePeriod(selectedPeriod.id)}
                        >
                          ✓ Setujui (Approve)
                        </button>
                      )}
                      {selectedPeriod.status === 'approved' && (
                        <button
                          type="button"
                          className="mce-btn mce-btn-success mce-btn-sm"
                          style={{ backgroundColor: '#10b981', color: '#fff' }}
                          onClick={() => handleDistributePeriod(selectedPeriod.id)}
                        >
                          🚀 Distribusikan ke Ledger
                        </button>
                      )}
                      {selectedPeriod.status === 'distributed' && (
                        <span className="mce-pill mce-pill-success">
                          ✓ TELAH DIDISTRIBUSIKAN KE LEDGER
                        </span>
                      )}
                    </div>
                  </div>
                  <div className="mce-card-body" style={{ padding: 0, overflowX: 'auto' }}>
                    <table className="mce-table" style={{ width: '100%', borderCollapse: 'collapse' }}>
                      <thead>
                        <tr style={{ background: '#1e293b', textAlign: 'left', borderBottom: '1px solid #334155' }}>
                          <th style={{ padding: '0.75rem 1rem' }}>Tenaga Kerja AI</th>
                          <th style={{ padding: '0.75rem 1rem' }}>Shift Terjadwal</th>
                          <th style={{ padding: '0.75rem 1rem' }}>Hari Bertugas</th>
                          <th style={{ padding: '0.75rem 1rem' }}>Hari Libur/Cuti</th>
                          <th style={{ padding: '0.75rem 1rem' }}>Tiket Kompensasi</th>
                          <th style={{ padding: '0.75rem 1rem' }}>Status Kelayakan</th>
                        </tr>
                      </thead>
                      <tbody>
                        {selectedPeriod.calculations && selectedPeriod.calculations.length > 0 ? (
                          selectedPeriod.calculations.map((c) => (
                            <tr key={c.id} style={{ borderBottom: '1px solid #1e293b' }}>
                              <td style={{ padding: '0.75rem 1rem' }}>
                                <div style={{ fontWeight: '600' }}>{c.agent_name}</div>
                                <code style={{ fontSize: '0.75rem', color: '#38bdf8' }}>{c.agent_code}</code>
                              </td>
                              <td style={{ padding: '0.75rem 1rem' }}>{c.scheduled_duty_days} Hari</td>
                              <td style={{ padding: '0.75rem 1rem', color: '#10b981', fontWeight: 'bold' }}>
                                {c.fulfilled_duty_days} Hari
                              </td>
                              <td style={{ padding: '0.75rem 1rem', color: '#94a3b8' }}>
                                {c.leave_days + c.rest_days} Hari
                              </td>
                              <td style={{ padding: '0.75rem 1rem', fontWeight: 'bold', color: '#38bdf8' }}>
                                {c.net_tickets_calculated} WT
                              </td>
                              <td style={{ padding: '0.75rem 1rem' }}>
                                <span className={`mce-pill ${c.status === 'eligible' || c.status === 'issued' ? 'mce-pill-success' : 'mce-pill-neutral'}`}>
                                  {c.status.toUpperCase()}
                                </span>
                              </td>
                            </tr>
                          ))
                        ) : (
                          <tr>
                            <td colSpan={6} style={{ textAlign: 'center', padding: '2rem', color: '#64748b' }}>
                              Belum ada rincian evaluasi tenaga kerja untuk periode ini. Klik &quot;Evaluasi 5/2 Shift&quot; untuk menjalankan kalkulasi otomatis.
                            </td>
                          </tr>
                        )}
                      </tbody>
                    </table>
                  </div>
                </>
              ) : (
                <div style={{ textAlign: 'center', padding: '3rem', color: '#64748b' }}>
                  Pilih salah satu periode di sebelah kiri untuk melihat evaluasi shift dan rincian kompensasi.
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* TAB 4: TASK BONUSES */}
      {!loading && activeTab === 'bonuses' && (
        <div>
          <div className="mce-card">
            <div className="mce-card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                <h3 className="mce-card-title">Evaluasi Bonus Tugas Terverifikasi (Verified Task Bonus)</h3>
                <p style={{ margin: 0, fontSize: '0.85rem', color: '#94a3b8' }}>
                  Bonus diterbitkan hanya jika deliverable tugas telah di-review independen (review accepted) dan kriteria penerimaan terpenuhi.
                </p>
              </div>
            </div>
            <div className="mce-card-body" style={{ padding: 0, overflowX: 'auto' }}>
              <table className="mce-table" style={{ width: '100%', borderCollapse: 'collapse' }}>
                <thead>
                  <tr style={{ background: '#1e293b', textAlign: 'left', borderBottom: '1px solid #334155' }}>
                    <th style={{ padding: '0.75rem 1rem' }}>Kode & Judul Tugas</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Agent Penerima</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Kategori Bonus</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Kriteria Penerimaan</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Kalkulasi Bonus</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Status Review</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Aksi Otorisasi</th>
                  </tr>
                </thead>
                <tbody>
                  {taskBonuses.map((b) => (
                    <tr key={b.id} style={{ borderBottom: '1px solid #1e293b' }}>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <code style={{ color: '#38bdf8' }}>{b.task_code || `TASK-${b.task_id}`}</code>
                        <div style={{ fontWeight: '500' }}>{b.task_title || 'Tugas Terverifikasi'}</div>
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <div>{b.agent_name}</div>
                        <code style={{ fontSize: '0.75rem', color: '#94a3b8' }}>{b.agent_code}</code>
                      </td>
                      <td style={{ padding: '0.75rem 1rem', textTransform: 'capitalize' }}>
                        {b.bonus_category}
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <span className={`mce-pill ${b.acceptance_criteria_satisfied ? 'mce-pill-success' : 'mce-pill-danger'}`}>
                          {b.acceptance_criteria_satisfied ? '✓ SATISFIED' : 'UNSATISFIED'}
                        </span>
                      </td>
                      <td style={{ padding: '0.75rem 1rem', color: '#10b981', fontWeight: 'bold' }}>
                        +{b.calculated_bonus_tickets} WT
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <span className={`mce-pill ${b.approval_status === 'approved' ? 'mce-pill-success' : 'mce-pill-warning'}`}>
                          {b.approval_status.toUpperCase()}
                        </span>
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        {b.approval_status === 'pending_review' ? (
                          <button
                            type="button"
                            className="mce-btn mce-btn-primary mce-btn-sm"
                            onClick={() => handleApproveBonus(b.id)}
                            title="Setujui bonus dan posting ke buku besar dengan menghubungkan ke riwayat performa"
                          >
                            ✓ Approve
                          </button>
                        ) : (
                          <span style={{ fontSize: '0.85rem', color: '#64748b' }}>Telah Dikreditkan</span>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* TAB 5: SPECIAL RECOGNITIONS */}
      {!loading && activeTab === 'recognitions' && (
        <div>
          <div className="mce-card">
            <div className="mce-card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                <h3 className="mce-card-title">Penghargaan Khusus Tenaga Kerja AI (Special Recognitions)</h3>
                <p style={{ margin: 0, fontSize: '0.85rem', color: '#94a3b8' }}>
                  Apresiasi pencapaian arsitektur, inovasi model, atau keandalan operasional dengan otorisasi berbasis Delegation of Authority (DoA).
                </p>
              </div>
              <button
                type="button"
                className="mce-btn mce-btn-primary mce-btn-sm"
                onClick={() => setShowNominateModal(true)}
              >
                + Ajukan Nominasi Penghargaan
              </button>
            </div>
            <div className="mce-card-body" style={{ padding: 0, overflowX: 'auto' }}>
              <table className="mce-table" style={{ width: '100%', borderCollapse: 'collapse' }}>
                <thead>
                  <tr style={{ background: '#1e293b', textAlign: 'left', borderBottom: '1px solid #334155' }}>
                    <th style={{ padding: '0.75rem 1rem' }}>Kode & Judul Penghargaan</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Penerima</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Kategori</th>
                    <th style={{ padding: '0.75rem 1rem' }}>DoA Otorisasi</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Nominal Tiket</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Status</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Aksi Otorisasi</th>
                  </tr>
                </thead>
                <tbody>
                  {recognitions.map((r) => (
                    <tr key={r.id} style={{ borderBottom: '1px solid #1e293b' }}>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <code style={{ color: '#38bdf8' }}>{r.recognition_code}</code>
                        <div style={{ fontWeight: '600' }}>{r.title}</div>
                        <div style={{ fontSize: '0.75rem', color: '#94a3b8' }}>{r.justification}</div>
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <div>{r.agent_name}</div>
                        <code style={{ fontSize: '0.75rem', color: '#94a3b8' }}>{r.agent_code}</code>
                      </td>
                      <td style={{ padding: '0.75rem 1rem', textTransform: 'capitalize' }}>
                        {r.category}
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <span className="mce-pill mce-pill-neutral">
                          DoA Level {r.doa_level_required}
                        </span>
                      </td>
                      <td style={{ padding: '0.75rem 1rem', color: '#10b981', fontWeight: 'bold' }}>
                        +{r.awarded_tickets} WT
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <span className={`mce-pill ${r.approval_status === 'approved' ? 'mce-pill-success' : 'mce-pill-warning'}`}>
                          {r.approval_status.toUpperCase()}
                        </span>
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        {r.approval_status === 'pending_review' ? (
                          <button
                            type="button"
                            className="mce-btn mce-btn-primary mce-btn-sm"
                            onClick={() => handleApproveRecognition(r.id)}
                            title="Setujui penghargaan dengan batas kewenangan delegasi (DoA)"
                          >
                            ✓ Approve
                          </button>
                        ) : (
                          <span style={{ fontSize: '0.85rem', color: '#64748b' }}>Telah Dikreditkan</span>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* TAB 6: IMMUTABLE LEDGER */}
      {!loading && activeTab === 'ledger' && (
        <div>
          <div className="mce-card">
            <div className="mce-card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                <h3 className="mce-card-title">Buku Besar Transaksi Tiket (Authoritative Immutable Ledger)</h3>
                <p style={{ margin: 0, fontSize: '0.85rem', color: '#94a3b8' }}>
                  Total {txTotal} transaksi tercatat. Seluruh mutasi dilindungi hash idempotensi SHA-256 dan jejak audit permanen.
                </p>
              </div>
              <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
                <select
                  value={txCategoryFilter}
                  onChange={(e) => setTxCategoryFilter(e.target.value)}
                  className="mce-select"
                  style={{ padding: '0.35rem 0.6rem', fontSize: '0.85rem' }}
                >
                  <option value="">Semua Kategori</option>
                  <option value="base_compensation">Base Compensation</option>
                  <option value="task_bonus">Task Bonus</option>
                  <option value="special_recognition">Special Recognition</option>
                  <option value="adjustment">Adjustment</option>
                  <option value="reversal">Reversal</option>
                </select>
                <button
                  type="button"
                  className="mce-btn mce-btn-secondary mce-btn-sm"
                  onClick={() => setShowAdjustmentModal(true)}
                >
                  ⚖️ Penyesuaian Administratif
                </button>
              </div>
            </div>
            <div className="mce-card-body" style={{ padding: 0, overflowX: 'auto' }}>
              <table className="mce-table" style={{ width: '100%', borderCollapse: 'collapse' }}>
                <thead>
                  <tr style={{ background: '#1e293b', textAlign: 'left', borderBottom: '1px solid #334155' }}>
                    <th style={{ padding: '0.75rem 1rem' }}>Kode Transaksi</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Waktu</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Agent</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Kategori</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Tipe</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Nominal</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Saldo Akhir</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Alasan / Justifikasi</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  {transactions.map((tx) => (
                    <tr key={tx.id} style={{ borderBottom: '1px solid #1e293b' }}>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <code style={{ color: '#38bdf8' }}>{tx.transaction_code}</code>
                      </td>
                      <td style={{ padding: '0.75rem 1rem', fontSize: '0.8rem', color: '#94a3b8' }}>
                        {tx.created_at}
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <div style={{ fontWeight: '500' }}>{tx.agent_name}</div>
                        <code style={{ fontSize: '0.75rem', color: '#94a3b8' }}>{tx.agent_code}</code>
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <span className="mce-pill mce-pill-neutral" style={{ fontSize: '0.75rem' }}>
                          {tx.ticket_category}
                        </span>
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <span className={`mce-pill ${tx.transaction_type === 'credit' ? 'mce-pill-success' : 'mce-pill-danger'}`}>
                          {tx.transaction_type.toUpperCase()}
                        </span>
                      </td>
                      <td style={{ padding: '0.75rem 1rem', fontWeight: 'bold', color: tx.transaction_type === 'credit' ? '#10b981' : '#f43f5e' }}>
                        {tx.transaction_type === 'credit' ? `+${tx.amount}` : `-${tx.amount}`} WT
                      </td>
                      <td style={{ padding: '0.75rem 1rem', fontWeight: 'bold', color: '#38bdf8' }}>
                        {tx.balance_after} WT
                      </td>
                      <td style={{ padding: '0.75rem 1rem', fontSize: '0.85rem', maxWidth: '240px' }}>
                        {tx.reason}
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        {tx.status === 'completed' && tx.ticket_category !== 'reversal' && (
                          <button
                            type="button"
                            className="mce-btn mce-btn-danger mce-btn-sm"
                            style={{ backgroundColor: '#7f1d1d', borderColor: '#991b1b', color: '#fff' }}
                            onClick={() => handleReverseTransaction(tx.id)}
                            title="Lakukan pembalikan transaksi dengan membuat entri pembalik akuntabel di ledger"
                          >
                            ↩ Reverse
                          </button>
                        )}
                        {tx.status === 'reversed' && (
                          <span style={{ fontSize: '0.75rem', color: '#f43f5e' }}>Telah Dibalikkan</span>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* TAB 7: VHQ WELFARE FACILITIES */}
      {!loading && activeTab === 'facilities' && (
        <div>
          <div className="mce-card" style={{ marginBottom: '1.5rem' }}>
            <div className="mce-card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                <h3 className="mce-card-title">Fasilitas Kesejahteraan di Kantor Virtual 3D (VHQ Facilities)</h3>
                <p style={{ margin: 0, fontSize: '0.85rem', color: '#94a3b8' }}>
                  Fasilitas relaksasi, pembelajaran model, dan pemulihan performa Agent yang terintegrasi di Gedung Kesejahteraan VHQ (BLD-WELFARE).
                </p>
              </div>
              <button
                type="button"
                className="mce-btn mce-btn-primary mce-btn-sm"
                onClick={() => setShowCheckInModal(true)}
              >
                + Check-In Fasilitas
              </button>
            </div>
            <div className="mce-card-body" style={{ padding: '1rem' }}>
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))', gap: '1rem' }}>
                {facilities.map((f) => (
                  <div
                    key={f.id}
                    style={{
                      background: '#0f172a',
                      border: '1px solid #1e293b',
                      borderRadius: '8px',
                      padding: '1.25rem',
                      display: 'flex',
                      flexDirection: 'column',
                      justifyContent: 'space-between',
                    }}
                  >
                    <div>
                      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.5rem' }}>
                        <code style={{ color: '#38bdf8', fontWeight: 'bold' }}>{f.facility_code}</code>
                        <span className={`mce-pill ${f.operating_status === 'available' ? 'mce-pill-success' : 'mce-pill-warning'}`}>
                          {f.operating_status.toUpperCase()}
                        </span>
                      </div>
                      <h4 style={{ margin: '0 0 0.5rem 0', color: '#f8fafc', fontSize: '1.1rem' }}>{f.name}</h4>
                      <p style={{ margin: '0 0 1rem 0', fontSize: '0.85rem', color: '#94a3b8' }}>
                        {f.description}
                      </p>
                      <dl className="mce-kv-list" style={{ fontSize: '0.85rem' }}>
                        <div>
                          <dt>Gedung & Ruangan</dt>
                          <dd>{f.building_name} ({f.room_name})</dd>
                        </div>
                        <div>
                          <dt>Tipe Fasilitas</dt>
                          <dd style={{ textTransform: 'capitalize' }}>{f.facility_type.replace('_', ' ')}</dd>
                        </div>
                        <div>
                          <dt>Kapasitas & Okupansi</dt>
                          <dd>
                            <strong>{f.current_occupancy}</strong> / {f.capacity} Agent {f.is_full ? '(Penuh)' : ''}
                          </dd>
                        </div>
                      </dl>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* Active Facility Sessions Table */}
          <div className="mce-card">
            <div className="mce-card-header">
              <h3 className="mce-card-title">Sesi Penggunaan Fasilitas (Usage Logs)</h3>
            </div>
            <div className="mce-card-body" style={{ padding: 0, overflowX: 'auto' }}>
              <table className="mce-table" style={{ width: '100%', borderCollapse: 'collapse' }}>
                <thead>
                  <tr style={{ background: '#1e293b', textAlign: 'left', borderBottom: '1px solid #334155' }}>
                    <th style={{ padding: '0.75rem 1rem' }}>Fasilitas</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Agent Pengguna</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Tipe Sesi</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Waktu Mulai</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Waktu Selesai</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Status</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  {facilityLogs.map((log) => (
                    <tr key={log.id} style={{ borderBottom: '1px solid #1e293b' }}>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <strong>{log.facility_name}</strong>
                        <div style={{ fontSize: '0.75rem', color: '#94a3b8' }}>{log.facility_code}</div>
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <div>{log.agent_name}</div>
                        <code style={{ fontSize: '0.75rem', color: '#94a3b8' }}>{log.agent_code}</code>
                      </td>
                      <td style={{ padding: '0.75rem 1rem', textTransform: 'capitalize' }}>
                        {log.session_type.replace('_', ' ')}
                      </td>
                      <td style={{ padding: '0.75rem 1rem', fontSize: '0.8rem', color: '#94a3b8' }}>
                        {log.start_time}
                      </td>
                      <td style={{ padding: '0.75rem 1rem', fontSize: '0.8rem', color: '#94a3b8' }}>
                        {log.end_time || '-'}
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <span className={`mce-pill ${log.status === 'active' ? 'mce-pill-success' : 'mce-pill-neutral'}`}>
                          {log.status.toUpperCase()}
                        </span>
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        {log.status === 'active' && (
                          <button
                            type="button"
                            className="mce-btn mce-btn-secondary mce-btn-sm"
                            onClick={() => handleCheckOutSubmit(log.id)}
                          >
                            Check-Out
                          </button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* MODAL: CREATE POLICY */}
      {showCreatePolicyModal && (
        <div className="mce-modal-overlay">
          <div className="mce-modal-content" style={{ maxWidth: '540px' }}>
            <h3 style={{ marginTop: 0 }}>Usulkan Kebijakan Kesejahteraan Baru</h3>
            <form
              onSubmit={async (e) => {
                e.preventDefault();
                try {
                  await welfareService.createPolicy(policyForm);
                  setShowCreatePolicyModal(false);
                  loadPolicies();
                  notify('Proposal kebijakan baru berhasil diajukan untuk ditinjau.');
                } catch (err: any) {
                  notify(`Gagal membuat kebijakan: ${err.message}`, 'error');
                }
              }}
            >
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem', marginBottom: '1rem' }}>
                <div>
                  <label style={{ fontSize: '0.85rem' }}>Kode Kebijakan</label>
                  <input
                    type="text"
                    required
                    value={policyForm.policy_code}
                    onChange={(e) => setPolicyForm({ ...policyForm, policy_code: e.target.value })}
                    placeholder="POL-WLF-2026-V2"
                    style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                  />
                </div>
                <div>
                  <label style={{ fontSize: '0.85rem' }}>Versi</label>
                  <input
                    type="text"
                    required
                    value={policyForm.version}
                    onChange={(e) => setPolicyForm({ ...policyForm, version: e.target.value })}
                    style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                  />
                </div>
              </div>
              <div style={{ marginBottom: '1rem' }}>
                <label style={{ fontSize: '0.85rem' }}>Judul Kebijakan</label>
                <input
                  type="text"
                  required
                  value={policyForm.title}
                  onChange={(e) => setPolicyForm({ ...policyForm, title: e.target.value })}
                  placeholder="Standar Kesejahteraan Q2 2026"
                  style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                />
              </div>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem', marginBottom: '1rem' }}>
                <div>
                  <label style={{ fontSize: '0.85rem' }}>Siklus Kompensasi</label>
                  <select
                    value={policyForm.base_period}
                    onChange={(e: any) => setPolicyForm({ ...policyForm, base_period: e.target.value })}
                    style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                  >
                    <option value="weekly">Mingguan (Weekly 5/2)</option>
                    <option value="biweekly">Dua Mingguan (Biweekly)</option>
                    <option value="monthly">Bulanan (Monthly)</option>
                  </select>
                </div>
                <div>
                  <label style={{ fontSize: '0.85rem' }}>Tarif Dasar Tiket (WT)</label>
                  <input
                    type="number"
                    required
                    value={policyForm.base_duty_tickets}
                    onChange={(e) => setPolicyForm({ ...policyForm, base_duty_tickets: Number(e.target.value) })}
                    style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                  />
                </div>
              </div>
              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '0.75rem', marginTop: '1.5rem' }}>
                <button
                  type="button"
                  className="mce-btn mce-btn-secondary"
                  onClick={() => setShowCreatePolicyModal(false)}
                >
                  Batal
                </button>
                <button type="submit" className="mce-btn mce-btn-primary">
                  Simpan Usulan
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL: CREATE PERIOD */}
      {showCreatePeriodModal && (
        <div className="mce-modal-overlay">
          <div className="mce-modal-content" style={{ maxWidth: '480px' }}>
            <h3 style={{ marginTop: 0 }}>Buat Periode Kompensasi Baru</h3>
            <form onSubmit={handleCreatePeriodSubmit}>
              <div style={{ marginBottom: '1rem' }}>
                <label style={{ fontSize: '0.85rem' }}>Kode Periode (Opsional)</label>
                <input
                  type="text"
                  value={periodForm.period_code}
                  onChange={(e) => setPeriodForm({ ...periodForm, period_code: e.target.value })}
                  placeholder="WCP-2026-W47"
                  style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                />
              </div>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem', marginBottom: '1rem' }}>
                <div>
                  <label style={{ fontSize: '0.85rem' }}>Tanggal Mulai</label>
                  <input
                    type="date"
                    required
                    value={periodForm.start_date}
                    onChange={(e) => setPeriodForm({ ...periodForm, start_date: e.target.value })}
                    style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                  />
                </div>
                <div>
                  <label style={{ fontSize: '0.85rem' }}>Tanggal Selesai</label>
                  <input
                    type="date"
                    required
                    value={periodForm.end_date}
                    onChange={(e) => setPeriodForm({ ...periodForm, end_date: e.target.value })}
                    style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                  />
                </div>
              </div>
              <div style={{ marginBottom: '1rem' }}>
                <label style={{ fontSize: '0.85rem' }}>Catatan</label>
                <textarea
                  value={periodForm.notes}
                  onChange={(e) => setPeriodForm({ ...periodForm, notes: e.target.value })}
                  placeholder="Catatan pelaksanaan siklus kompensasi..."
                  style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                />
              </div>
              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '0.75rem', marginTop: '1.5rem' }}>
                <button
                  type="button"
                  className="mce-btn mce-btn-secondary"
                  onClick={() => setShowCreatePeriodModal(false)}
                >
                  Batal
                </button>
                <button type="submit" className="mce-btn mce-btn-primary">
                  Buat Periode
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL: NOMINATE RECOGNITION */}
      {showNominateModal && (
        <div className="mce-modal-overlay">
          <div className="mce-modal-content" style={{ maxWidth: '520px' }}>
            <h3 style={{ marginTop: 0 }}>Ajukan Nominasi Penghargaan Khusus</h3>
            <form onSubmit={handleNominateSubmit}>
              <div style={{ marginBottom: '1rem' }}>
                <label style={{ fontSize: '0.85rem' }}>Pilih Tenaga Kerja AI</label>
                <select
                  value={nominateForm.agent_id}
                  onChange={(e) => setNominateForm({ ...nominateForm, agent_id: Number(e.target.value) })}
                  style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                >
                  {overview?.agent_roster?.map((a) => (
                    <option key={a.agent_id} value={a.agent_id}>
                      {a.agent_name} ({a.agent_code})
                    </option>
                  ))}
                </select>
              </div>
              <div style={{ marginBottom: '1rem' }}>
                <label style={{ fontSize: '0.85rem' }}>Judul Penghargaan</label>
                <input
                  type="text"
                  required
                  value={nominateForm.title}
                  onChange={(e) => setNominateForm({ ...nominateForm, title: e.target.value })}
                  placeholder="e.g. Inovasi Optimasi Token 9Router"
                  style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                />
              </div>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem', marginBottom: '1rem' }}>
                <div>
                  <label style={{ fontSize: '0.85rem' }}>Kategori</label>
                  <select
                    value={nominateForm.category}
                    onChange={(e) => setNominateForm({ ...nominateForm, category: e.target.value })}
                    style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                  >
                    <option value="innovation">Innovation</option>
                    <option value="operational_excellence">Operational Excellence</option>
                    <option value="collaboration">Collaboration</option>
                    <option value="mentorship">Mentorship</option>
                  </select>
                </div>
                <div>
                  <label style={{ fontSize: '0.85rem' }}>Nominal Tiket (WT)</label>
                  <input
                    type="number"
                    required
                    value={nominateForm.awarded_tickets}
                    onChange={(e) => setNominateForm({ ...nominateForm, awarded_tickets: Number(e.target.value) })}
                    style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                  />
                </div>
              </div>
              <div style={{ marginBottom: '1rem' }}>
                <label style={{ fontSize: '0.85rem' }}>Justifikasi & Bukti Kontribusi</label>
                <textarea
                  required
                  value={nominateForm.justification}
                  onChange={(e) => setNominateForm({ ...nominateForm, justification: e.target.value })}
                  placeholder="Deskripsikan kontribusi luar biasa yang menjadi dasar penghargaan..."
                  style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                />
              </div>
              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '0.75rem', marginTop: '1.5rem' }}>
                <button
                  type="button"
                  className="mce-btn mce-btn-secondary"
                  onClick={() => setShowNominateModal(false)}
                >
                  Batal
                </button>
                <button type="submit" className="mce-btn mce-btn-primary">
                  Ajukan Nominasi
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL: POST ADJUSTMENT */}
      {showAdjustmentModal && (
        <div className="mce-modal-overlay">
          <div className="mce-modal-content" style={{ maxWidth: '480px' }}>
            <h3 style={{ marginTop: 0 }}>Penyesuaian Administratif Buku Besar (Adjustment)</h3>
            <form onSubmit={handleAdjustmentSubmit}>
              <div style={{ marginBottom: '1rem' }}>
                <label style={{ fontSize: '0.85rem' }}>Tenaga Kerja AI</label>
                <select
                  value={adjustmentForm.agent_id}
                  onChange={(e) => setAdjustmentForm({ ...adjustmentForm, agent_id: Number(e.target.value) })}
                  style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                >
                  {overview?.agent_roster?.map((a) => (
                    <option key={a.agent_id} value={a.agent_id}>
                      {a.agent_name} ({a.agent_code}) - Saldo: {a.current_balance} WT
                    </option>
                  ))}
                </select>
              </div>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem', marginBottom: '1rem' }}>
                <div>
                  <label style={{ fontSize: '0.85rem' }}>Arah Mutasi</label>
                  <select
                    value={adjustmentForm.direction}
                    onChange={(e: any) => setAdjustmentForm({ ...adjustmentForm, direction: e.target.value })}
                    style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                  >
                    <option value="credit">Kredit (+ Tambah Saldo)</option>
                    <option value="debit">Debit (- Kurang Saldo)</option>
                  </select>
                </div>
                <div>
                  <label style={{ fontSize: '0.85rem' }}>Jumlah Tiket (WT)</label>
                  <input
                    type="number"
                    required
                    min={1}
                    value={adjustmentForm.amount}
                    onChange={(e) => setAdjustmentForm({ ...adjustmentForm, amount: Number(e.target.value) })}
                    style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                  />
                </div>
              </div>
              <div style={{ marginBottom: '1rem' }}>
                <label style={{ fontSize: '0.85rem' }}>Alasan Administratif (Wajib Audit)</label>
                <textarea
                  required
                  value={adjustmentForm.reason}
                  onChange={(e) => setAdjustmentForm({ ...adjustmentForm, reason: e.target.value })}
                  placeholder="Justifikasi koreksi saldo atau kompensasi diskresioner..."
                  style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                />
              </div>
              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '0.75rem', marginTop: '1.5rem' }}>
                <button
                  type="button"
                  className="mce-btn mce-btn-secondary"
                  onClick={() => setShowAdjustmentModal(false)}
                >
                  Batal
                </button>
                <button type="submit" className="mce-btn mce-btn-primary">
                  Posting ke Ledger
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL: CHECK-IN FACILITY */}
      {showCheckInModal && (
        <div className="mce-modal-overlay">
          <div className="mce-modal-content" style={{ maxWidth: '480px' }}>
            <h3 style={{ marginTop: 0 }}>Check-In Fasilitas Kesejahteraan VHQ</h3>
            <form onSubmit={handleCheckInSubmit}>
              <div style={{ marginBottom: '1rem' }}>
                <label style={{ fontSize: '0.85rem' }}>Pilih Fasilitas</label>
                <select
                  value={checkInForm.facility_id}
                  onChange={(e) => setCheckInForm({ ...checkInForm, facility_id: Number(e.target.value) })}
                  style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                >
                  {facilities.map((f) => (
                    <option key={f.id} value={f.id}>
                      {f.name} ({f.facility_code}) - Okupansi: {f.current_occupancy}/{f.capacity}
                    </option>
                  ))}
                </select>
              </div>
              <div style={{ marginBottom: '1rem' }}>
                <label style={{ fontSize: '0.85rem' }}>Pilih Tenaga Kerja AI</label>
                <select
                  value={checkInForm.agent_id}
                  onChange={(e) => setCheckInForm({ ...checkInForm, agent_id: Number(e.target.value) })}
                  style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                >
                  {overview?.agent_roster?.map((a) => (
                    <option key={a.agent_id} value={a.agent_id}>
                      {a.agent_name} ({a.agent_code})
                    </option>
                  ))}
                </select>
              </div>
              <div style={{ marginBottom: '1rem' }}>
                <label style={{ fontSize: '0.85rem' }}>Tipe Sesi</label>
                <select
                  value={checkInForm.session_type}
                  onChange={(e) => setCheckInForm({ ...checkInForm, session_type: e.target.value })}
                  style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                >
                  <option value="rest_recovery">Rest & Performance Recovery</option>
                  <option value="knowledge_enrichment">Knowledge Enrichment & Fine-Tuning</option>
                  <option value="quiet_focus">Quiet Focus & Synthesis</option>
                </select>
              </div>
              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '0.75rem', marginTop: '1.5rem' }}>
                <button
                  type="button"
                  className="mce-btn mce-btn-secondary"
                  onClick={() => setShowCheckInModal(false)}
                >
                  Batal
                </button>
                <button type="submit" className="mce-btn mce-btn-primary">
                  Check-In
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
