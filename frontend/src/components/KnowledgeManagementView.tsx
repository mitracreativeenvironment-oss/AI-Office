import React, { useState, useEffect, useCallback } from 'react';
import { knowledgeService } from '../services/knowledgeService';
import type {
  KnowledgeOverviewMetrics,
  KnowledgeCategory,
  KnowledgeRecord,
  KnowledgeContribution,
  DocumentItem,
  AgentMemoryItem,
  WallbookHandoverChecklist,
  SearchResultItem,
  KnowledgeDomain,
  KnowledgeClassification,
} from '../types/knowledge';

interface KnowledgeManagementViewProps {
  isAuthenticated?: boolean;
  onOpenLogin?: () => void;
}

export const KnowledgeManagementView: React.FC<KnowledgeManagementViewProps> = ({
  isAuthenticated = false,
  onOpenLogin,
}) => {
  // Navigation Tabs
  const [activeTab, setActiveTab] = useState<
    'overview' | 'kb' | 'documents' | 'memory' | 'wallbook' | 'contributions' | 'search' | 'rag'
  >('overview');

  const [loading, setLoading] = useState<boolean>(true);
  const [actionMessage, setActionMessage] = useState<{ text: string; type: 'success' | 'error' } | null>(null);

  // Core Data States
  const [metrics, setMetrics] = useState<KnowledgeOverviewMetrics | null>(null);
  const [categories, setCategories] = useState<KnowledgeCategory[]>([]);
  const [selectedDomain, setSelectedDomain] = useState<string>('');
  const [records, setRecords] = useState<KnowledgeRecord[]>([]);
  const [totalRecords, setTotalRecords] = useState<number>(0);
  const [selectedRecord, setSelectedRecord] = useState<KnowledgeRecord | null>(null);

  const [documents, setDocuments] = useState<DocumentItem[]>([]);
  const [contributions, setContributions] = useState<KnowledgeContribution[]>([]);
  const [selectedContribution, setSelectedContribution] = useState<KnowledgeContribution | null>(null);

  // Agent Memory States
  const [selectedAgentId, setSelectedAgentId] = useState<number>(1);
  const [agentMemories, setAgentMemories] = useState<AgentMemoryItem[]>([]);
  const [memoryTypeFilter, setMemoryTypeFilter] = useState<string>('');

  // Wallbook Bridge States
  const [selectedHandoverId, setSelectedHandoverId] = useState<number>(1);
  const [handoverChecklists, setHandoverChecklists] = useState<WallbookHandoverChecklist[]>([]);

  // Search & RAG States
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [searchResults, setSearchResults] = useState<SearchResultItem[]>([]);
  const [searchHits, setSearchHits] = useState<number>(0);
  const [searchLatency, setSearchLatency] = useState<number>(0);
  const [searchLoading, setSearchLoading] = useState<boolean>(false);

  const [ragQuery, setRagQuery] = useState<string>('DoA Framework and Level B autonomous authority');
  const [ragResult, setRagResult] = useState<any | null>(null);
  const [ragLoading, setRagLoading] = useState<boolean>(false);

  // Modals
  const [showCreateRecordModal, setShowCreateRecordModal] = useState<boolean>(false);
  const [recordForm, setRecordForm] = useState({
    title: '',
    category_id: 1,
    classification: 'internal' as KnowledgeClassification,
    summary: '',
    content: '',
  });

  const [showCreateDocModal, setShowCreateDocModal] = useState<boolean>(false);
  const [docForm, setDocForm] = useState({
    title: '',
    description: '',
    category: 'standard_procedure',
    classification: 'internal' as KnowledgeClassification,
    file_name: 'document.pdf',
    file_path: 'uploads/docs/document.pdf',
    file_size_bytes: 15400,
    mime_type: 'application/pdf',
  });

  const [showCreateMemModal, setShowCreateMemModal] = useState<boolean>(false);
  const [memForm, setMemForm] = useState({
    memory_type: 'semantic_memory',
    title: '',
    content: '',
    importance_score: 0.75,
    retention_policy: 'permanent',
    tags: 'operational, heuristic',
  });

  const [showReviewModal, setShowReviewModal] = useState<boolean>(false);
  const [reviewDecision, setReviewDecision] = useState<'approved' | 'rejected' | 'revision_requested'>('approved');
  const [reviewFeedback, setReviewFeedback] = useState<string>('Kontribusi telah ditinjau dan memenuhi standar keunggulan teknis MCE.');

  const notify = (text: string, type: 'success' | 'error' = 'success') => {
    setActionMessage({ text, type });
    setTimeout(() => setActionMessage(null), 5000);
  };

  // Data Loaders
  const loadOverview = useCallback(async () => {
    try {
      const data = await knowledgeService.getOverview();
      setMetrics(data);
    } catch (err: any) {
      console.error('Failed to load knowledge overview', err);
    }
  }, []);

  const loadCategories = useCallback(async () => {
    try {
      const data = await knowledgeService.listCategories();
      setCategories(data);
    } catch (err: any) {
      console.error('Failed to load categories', err);
    }
  }, []);

  const loadRecords = useCallback(async () => {
    try {
      const res = await knowledgeService.listRecords({
        domain: selectedDomain || undefined,
        limit: 50,
      });
      setRecords(res.items);
      setTotalRecords(res.total);
    } catch (err: any) {
      console.error('Failed to load knowledge records', err);
    }
  }, [selectedDomain]);

  const loadDocuments = useCallback(async () => {
    try {
      const res = await knowledgeService.listDocuments();
      setDocuments(res.items);
    } catch (err: any) {
      console.error('Failed to load documents', err);
    }
  }, []);

  const loadContributions = useCallback(async () => {
    try {
      const res = await knowledgeService.listContributions();
      setContributions(res.items);
    } catch (err: any) {
      console.error('Failed to load contributions', err);
    }
  }, []);

  const loadAgentMemories = useCallback(async () => {
    try {
      const items = await knowledgeService.listAgentMemories(selectedAgentId, {
        memory_type: memoryTypeFilter || undefined,
      });
      setAgentMemories(items);
    } catch (err: any) {
      console.error('Failed to load agent memories', err);
    }
  }, [selectedAgentId, memoryTypeFilter]);

  const loadWallbookChecklists = useCallback(async () => {
    try {
      const items = await knowledgeService.listWallbookChecklists(selectedHandoverId);
      setHandoverChecklists(items);
    } catch (err: any) {
      console.error('Failed to load wallbook checklists', err);
    }
  }, [selectedHandoverId]);

  useEffect(() => {
    setLoading(true);
    Promise.all([
      loadOverview(),
      loadCategories(),
      loadRecords(),
      loadDocuments(),
      loadContributions(),
      loadAgentMemories(),
      loadWallbookChecklists(),
    ]).finally(() => setLoading(false));
  }, [loadOverview, loadCategories, loadRecords, loadDocuments, loadContributions, loadAgentMemories, loadWallbookChecklists]);

  // Tab switcher
  const handleTabChange = (tab: typeof activeTab) => {
    setActiveTab(tab);
    if (tab === 'overview') loadOverview();
    else if (tab === 'kb') loadRecords();
    else if (tab === 'documents') loadDocuments();
    else if (tab === 'memory') loadAgentMemories();
    else if (tab === 'wallbook') loadWallbookChecklists();
    else if (tab === 'contributions') loadContributions();
  };

  // Actions
  const handleSearch = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!searchQuery.trim()) return;
    setSearchLoading(true);
    try {
      const res = await knowledgeService.search(searchQuery);
      setSearchResults(res.results);
      setSearchHits(res.total_hits);
      setSearchLatency(res.latency_ms);
    } catch (err: any) {
      notify(`Pencarian gagal: ${err.message}`, 'error');
    } finally {
      setSearchLoading(false);
    }
  };

  const handleRagRetrieve = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!ragQuery.trim()) return;
    setRagLoading(true);
    try {
      const res = await knowledgeService.retrieveRagContext(ragQuery, selectedAgentId);
      setRagResult(res);
      notify('Konteks AI RAG berhasil diekstraksi dengan jejak provenance.');
    } catch (err: any) {
      notify(`Gagal mengambil konteks RAG: ${err.message}`, 'error');
    } finally {
      setRagLoading(false);
    }
  };

  const handleToggleChecklist = async (chk: WallbookHandoverChecklist) => {
    try {
      await knowledgeService.updateWallbookChecklist(chk.id, !chk.is_satisfied, 'Diperbarui melalui dashboard');
      loadWallbookChecklists();
      notify(`Status checklist ${chk.checklist_code} berhasil diperbarui.`);
    } catch (err: any) {
      notify(`Gagal memperbarui checklist: ${err.message}`, 'error');
    }
  };

  const handlePromoteMemory = async (mem: AgentMemoryItem) => {
    try {
      await knowledgeService.promoteMemory(mem.id, {
        title: `Prosedur Pengetahuan: ${mem.title}`,
        summary: `Diangkat dari memori operasional ${mem.memory_code}.`,
        content: mem.content,
      });
      loadContributions();
      loadOverview();
      notify(`Memori ${mem.memory_code} berhasil diusulkan menjadi kandidat pengetahuan organisasi.`);
    } catch (err: any) {
      notify(`Gagal mengusulkan memori: ${err.message}`, 'error');
    }
  };

  const handleReviewSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedContribution) return;
    try {
      await knowledgeService.reviewContribution(selectedContribution.id, reviewDecision, reviewFeedback);
      setShowReviewModal(false);
      setSelectedContribution(null);
      loadContributions();
      loadRecords();
      loadOverview();
      notify(`Kontribusi pengetahuan berhasil ditinjau (${reviewDecision.toUpperCase()}).`);
    } catch (err: any) {
      notify(`Gagal meninjau kontribusi: ${err.message}`, 'error');
    }
  };

  const handleCreateRecordSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await knowledgeService.createRecord(recordForm);
      setShowCreateRecordModal(false);
      loadRecords();
      loadOverview();
      notify('Draft artikel pengetahuan berhasil dibuat.');
    } catch (err: any) {
      notify(`Gagal membuat artikel: ${err.message}`, 'error');
    }
  };

  const handleCreateDocSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await knowledgeService.registerDocument(docForm as any);
      setShowCreateDocModal(false);
      loadDocuments();
      loadOverview();
      notify('Dokumen resmi berhasil didaftarkan ke repositori.');
    } catch (err: any) {
      notify(`Gagal mendaftarkan dokumen: ${err.message}`, 'error');
    }
  };

  const handleCreateMemSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const tags = memForm.tags.split(',').map((t) => t.trim()).filter(Boolean);
      await knowledgeService.createAgentMemory(selectedAgentId, {
        ...memForm,
        tags,
      } as any);
      setShowCreateMemModal(false);
      loadAgentMemories();
      loadOverview();
      notify('Memori kerja agen berhasil dikonsolidasikan.');
    } catch (err: any) {
      notify(`Gagal menyimpan memori: ${err.message}`, 'error');
    }
  };

  return (
    <div className="mce-dashboard-container">
      {/* Banner / Header */}
      <div className="mce-alert-banner" style={{ borderLeft: '4px solid #6366f1', marginBottom: '1.25rem' }}>
        <div className="mce-alert-icon">📚</div>
        <div style={{ flex: 1 }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <h2 className="mce-alert-title" style={{ margin: 0, color: '#818cf8' }}>
              MCE Knowledge Management, Organizational Memory & Wallbook (Prompt 25)
            </h2>
            <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
              <span className="mce-pill mce-pill-success">
                {metrics?.published_records ?? 0} Artikel Diterbitkan
              </span>
              <button
                type="button"
                className="mce-btn mce-btn-secondary mce-btn-sm"
                onClick={() => {
                  loadOverview();
                  loadRecords();
                  notify('Data pengetahuan disegarkan.');
                }}
              >
                🔄 Segarkan
              </button>
            </div>
          </div>
          <p className="mce-alert-desc" style={{ marginTop: '0.25rem' }}>
            Pusat memori organisasi dan pengetahuan perusahaan: repositori pengetahuan resmi, SOP operasional, manajemen dokumen, integrasi serah terima Wallbook, pengelolaan memori kognitif AI Agent (Working, Episodic, Semantic, Procedural), dan mesin pencarian terpadu dengan RAG context retrieval.
          </p>
        </div>
      </div>

      {actionMessage && (
        <div
          className="mce-card"
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
          📊 Ringkasan Pengetahuan
        </button>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'kb' ? 'mce-tab-active' : ''}`}
          onClick={() => handleTabChange('kb')}
        >
          📚 Pusat Pengetahuan (KB)
        </button>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'documents' ? 'mce-tab-active' : ''}`}
          onClick={() => handleTabChange('documents')}
        >
          📑 Dokumen & SOP
        </button>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'memory' ? 'mce-tab-active' : ''}`}
          onClick={() => handleTabChange('memory')}
        >
          🧠 Memori Tenaga Kerja AI
        </button>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'wallbook' ? 'mce-tab-active' : ''}`}
          onClick={() => handleTabChange('wallbook')}
        >
          🔄 Wallbook Handover Bridge
        </button>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'contributions' ? 'mce-tab-active' : ''}`}
          onClick={() => handleTabChange('contributions')}
        >
          💡 Usulan Kontribusi Pengetahuan
        </button>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'search' ? 'mce-tab-active' : ''}`}
          onClick={() => handleTabChange('search')}
        >
          🔍 Pencarian Terpadu
        </button>
        <button
          type="button"
          className={`mce-tab-btn ${activeTab === 'rag' ? 'mce-tab-active' : ''}`}
          onClick={() => handleTabChange('rag')}
        >
          🤖 AI RAG Context Retrieval
        </button>
      </div>

      {loading && (
        <div style={{ textAlign: 'center', padding: '2rem', color: '#94a3b8' }}>
          <span className="mce-status-dot mce-pulse" style={{ display: 'inline-block', marginRight: '8px' }}></span>
          Memuat basis pengetahuan dan memori organisasi...
        </div>
      )}

      {/* TAB 1: OVERVIEW */}
      {!loading && activeTab === 'overview' && (
        <div>
          <div className="mce-dashboard-grid" style={{ marginBottom: '1.5rem' }}>
            <div className="mce-card">
              <div className="mce-card-header">
                <h3 className="mce-card-title">Status Pengetahuan Organisasi</h3>
                <span className="mce-pill mce-pill-success">KB REPOSITORY</span>
              </div>
              <div className="mce-card-body">
                <div style={{ fontSize: '2rem', fontWeight: 'bold', color: '#818cf8', marginBottom: '0.5rem' }}>
                  {metrics?.published_records ?? 0} <span style={{ fontSize: '1rem', color: '#94a3b8' }}>Artikel Terbit</span>
                </div>
                <dl className="mce-kv-list">
                  <div>
                    <dt>Total Catatan Pengetahuan</dt>
                    <dd>{metrics?.total_records ?? 0} Record</dd>
                  </div>
                  <div>
                    <dt>Menunggu Review / Persetujuan</dt>
                    <dd>{metrics?.under_review_records ?? 0} Record</dd>
                  </div>
                  <div>
                    <dt>Draft Sedang Disusun</dt>
                    <dd>{metrics?.draft_records ?? 0} Record</dd>
                  </div>
                  <div>
                    <dt>Dokumen Resmi Terdaftar</dt>
                    <dd>{metrics?.total_documents ?? 0} Dokumen</dd>
                  </div>
                </dl>
              </div>
            </div>

            <div className="mce-card">
              <div className="mce-card-header">
                <h3 className="mce-card-title">Kognisi Tenaga Kerja AI & Handover</h3>
                <span className="mce-pill mce-pill-neutral">MEMORY & HANDOVER</span>
              </div>
              <div className="mce-card-body">
                <dl className="mce-kv-list">
                  <div>
                    <dt>Memori Kognitif AI Aktif</dt>
                    <dd><strong>{metrics?.active_memories ?? 0}</strong> Memori</dd>
                  </div>
                  <div>
                    <dt>Kategori Memori</dt>
                    <dd>Working, Episodic, Semantic, Procedural</dd>
                  </div>
                  <div>
                    <dt>Handover Wallbook Aktif</dt>
                    <dd>{metrics?.active_wallbook_entries ?? 0} Sesi</dd>
                  </div>
                  <div>
                    <dt>Usulan Kontribusi Menunggu</dt>
                    <dd>{metrics?.pending_contributions ?? 0} Usulan</dd>
                  </div>
                  <div>
                    <dt>Pemisahan Akses Memori</dt>
                    <dd>Terkunci per-identitas agen (Zero Leakage)</dd>
                  </div>
                </dl>
              </div>
            </div>

            <div className="mce-card">
              <div className="mce-card-header">
                <h3 className="mce-card-title">Distribusi Domain Pengetahuan</h3>
                <span className="mce-pill mce-pill-neutral">BY DOMAIN</span>
              </div>
              <div className="mce-card-body">
                <dl className="mce-kv-list">
                  <div>
                    <dt>Corporate Knowledge</dt>
                    <dd>{metrics?.by_domain?.['corporate'] ?? 0} Artikel</dd>
                  </div>
                  <div>
                    <dt>Operational Knowledge</dt>
                    <dd>{metrics?.by_domain?.['operational'] ?? 0} Artikel</dd>
                  </div>
                  <div>
                    <dt>Project Knowledge</dt>
                    <dd>{metrics?.by_domain?.['project'] ?? 0} Artikel</dd>
                  </div>
                  <div>
                    <dt>Technical Knowledge</dt>
                    <dd>{metrics?.by_domain?.['technical'] ?? 0} Artikel</dd>
                  </div>
                  <div>
                    <dt>Agent Knowledge</dt>
                    <dd>{metrics?.by_domain?.['agent_knowledge'] ?? 0} Artikel</dd>
                  </div>
                </dl>
              </div>
            </div>
          </div>

          {/* Quick Search Widget */}
          <div className="mce-card" style={{ marginBottom: '1.5rem' }}>
            <div className="mce-card-header">
              <h3 className="mce-card-title">Pencarian Cepat Basis Pengetahuan</h3>
            </div>
            <div className="mce-card-body">
              <form onSubmit={handleSearch} style={{ display: 'flex', gap: '0.5rem' }}>
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Ketik topik, SOP, kebijakan tata kelola, atau nomor dokumen..."
                  style={{ flex: 1, padding: '0.6rem 1rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '6px' }}
                />
                <button
                  type="submit"
                  className="mce-btn mce-btn-primary"
                  onClick={() => setActiveTab('search')}
                >
                  🔍 Cari Pengetahuan
                </button>
              </form>
            </div>
          </div>
        </div>
      )}

      {/* TAB 2: KNOWLEDGE BASE ARTICLES */}
      {!loading && activeTab === 'kb' && (
        <div>
          <div className="mce-card">
            <div className="mce-card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                <h3 className="mce-card-title">Katalog Pengetahuan Organisasi (Knowledge Base)</h3>
                <p style={{ margin: 0, fontSize: '0.85rem', color: '#94a3b8' }}>
                  Dokumentasi resmi, pedoman tata kelola, SOP operasional, dan pengetahuan teknis MCE.
                </p>
              </div>
              <div style={{ display: 'flex', gap: '0.5rem' }}>
                <select
                  value={selectedDomain}
                  onChange={(e) => setSelectedDomain(e.target.value)}
                  className="mce-select"
                  style={{ padding: '0.35rem 0.6rem', fontSize: '0.85rem' }}
                >
                  <option value="">Semua Domain</option>
                  <option value="corporate">Corporate</option>
                  <option value="operational">Operational</option>
                  <option value="project">Project</option>
                  <option value="technical">Technical</option>
                  <option value="agent_knowledge">Agent Knowledge</option>
                </select>
                <button
                  type="button"
                  className="mce-btn mce-btn-primary mce-btn-sm"
                  onClick={() => setShowCreateRecordModal(true)}
                >
                  + Buat Draft Artikel
                </button>
              </div>
            </div>
            <div className="mce-card-body" style={{ padding: 0, overflowX: 'auto' }}>
              <table className="mce-table" style={{ width: '100%', borderCollapse: 'collapse' }}>
                <thead>
                  <tr style={{ background: '#1e293b', textAlign: 'left', borderBottom: '1px solid #334155' }}>
                    <th style={{ padding: '0.75rem 1rem' }}>Kode & Judul Artikel</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Domain & Kategori</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Klasifikasi</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Versi</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Penulis / Provenance</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Status</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  {records.map((r) => (
                    <tr key={r.id} style={{ borderBottom: '1px solid #1e293b' }}>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <code style={{ color: '#818cf8', fontWeight: 'bold' }}>{r.record_code}</code>
                        <div style={{ fontWeight: '600', color: '#f8fafc', marginTop: '2px' }}>{r.title}</div>
                        <div style={{ fontSize: '0.75rem', color: '#94a3b8' }}>{r.summary}</div>
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <span className="mce-pill mce-pill-neutral" style={{ textTransform: 'capitalize' }}>
                          {r.category_domain || 'General'}
                        </span>
                        <div style={{ fontSize: '0.75rem', color: '#94a3b8', marginTop: '2px' }}>
                          {r.category_name}
                        </div>
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <span
                          className={`mce-pill ${
                            r.classification === 'public'
                              ? 'mce-pill-success'
                              : r.classification === 'confidential' || r.classification === 'restricted'
                              ? 'mce-pill-warning'
                              : 'mce-pill-neutral'
                          }`}
                          style={{ textTransform: 'capitalize' }}
                        >
                          {r.classification.replace('_', ' ')}
                        </span>
                      </td>
                      <td style={{ padding: '0.75rem 1rem', fontWeight: 'bold' }}>
                        v{r.version}
                      </td>
                      <td style={{ padding: '0.75rem 1rem', fontSize: '0.85rem' }}>
                        <div>{r.author_agent_name || r.author_user_name || 'System'}</div>
                        <div style={{ fontSize: '0.75rem', color: '#64748b' }}>
                          {r.provenance_type.replace('_', ' ')}
                        </div>
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <span className={`mce-pill ${r.status === 'published' ? 'mce-pill-success' : 'mce-pill-neutral'}`}>
                          {r.status.toUpperCase()}
                        </span>
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <button
                          type="button"
                          className="mce-btn mce-btn-secondary mce-btn-sm"
                          onClick={() => setSelectedRecord(r)}
                        >
                          👁️ Baca
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* ARTICLE READER MODAL */}
      {selectedRecord && (
        <div className="mce-modal-overlay">
          <div className="mce-modal-content" style={{ maxWidth: '720px', maxHeight: '85vh', overflowY: 'auto' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', borderBottom: '1px solid #334155', paddingBottom: '0.75rem' }}>
              <div>
                <span className="mce-pill mce-pill-neutral">{selectedRecord.record_code}</span>
                <span className="mce-pill mce-pill-success" style={{ marginLeft: '0.5rem' }}>v{selectedRecord.version}</span>
                <h3 style={{ margin: '0.5rem 0 0 0', color: '#f8fafc' }}>{selectedRecord.title}</h3>
              </div>
              <button
                type="button"
                className="mce-btn mce-btn-secondary mce-btn-sm"
                onClick={() => setSelectedRecord(null)}
              >
                ✕ Tutup
              </button>
            </div>
            <div style={{ padding: '1rem 0' }}>
              <div style={{ background: '#0f172a', padding: '0.75rem 1rem', borderRadius: '6px', marginBottom: '1rem', fontSize: '0.85rem', color: '#94a3b8' }}>
                <strong>Ringkasan:</strong> {selectedRecord.summary || 'Tidak ada ringkasan.'}
              </div>
              <div
                style={{
                  background: '#1e293b',
                  padding: '1.25rem',
                  borderRadius: '6px',
                  fontFamily: 'monospace',
                  whiteSpace: 'pre-wrap',
                  color: '#e2e8f0',
                  lineHeight: '1.6',
                }}
              >
                {selectedRecord.content}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* TAB 3: DOCUMENTS */}
      {!loading && activeTab === 'documents' && (
        <div>
          <div className="mce-card">
            <div className="mce-card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                <h3 className="mce-card-title">Repositori Dokumen Resmi & Prosedur (Documents)</h3>
                <p style={{ margin: 0, fontSize: '0.85rem', color: '#94a3b8' }}>
                  Katalog dokumen terarsip, spesifikasi teknis, dan bukti deliverable dengan hash SHA-256 dan kontrol versi.
                </p>
              </div>
              <button
                type="button"
                className="mce-btn mce-btn-primary mce-btn-sm"
                onClick={() => setShowCreateDocModal(true)}
              >
                + Daftarkan Dokumen Baru
              </button>
            </div>
            <div className="mce-card-body" style={{ padding: 0, overflowX: 'auto' }}>
              <table className="mce-table" style={{ width: '100%', borderCollapse: 'collapse' }}>
                <thead>
                  <tr style={{ background: '#1e293b', textAlign: 'left', borderBottom: '1px solid #334155' }}>
                    <th style={{ padding: '0.75rem 1rem' }}>Kode & Judul Dokumen</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Kategori Dokumen</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Klasifikasi</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Versi</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Berkas & Ukuran</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Status</th>
                  </tr>
                </thead>
                <tbody>
                  {documents.map((d) => (
                    <tr key={d.id} style={{ borderBottom: '1px solid #1e293b' }}>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <code style={{ color: '#818cf8', fontWeight: 'bold' }}>{d.document_code}</code>
                        <div style={{ fontWeight: '600' }}>{d.title}</div>
                        <div style={{ fontSize: '0.75rem', color: '#94a3b8' }}>{d.description}</div>
                      </td>
                      <td style={{ padding: '0.75rem 1rem', textTransform: 'capitalize' }}>
                        {d.category.replace('_', ' ')}
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <span className="mce-pill mce-pill-neutral">
                          {d.classification.toUpperCase()}
                        </span>
                      </td>
                      <td style={{ padding: '0.75rem 1rem', fontWeight: 'bold' }}>
                        v{d.current_version}
                      </td>
                      <td style={{ padding: '0.75rem 1rem', fontSize: '0.85rem' }}>
                        <code>{d.file_name}</code>
                        <div style={{ fontSize: '0.75rem', color: '#64748b' }}>
                          {Math.round(d.file_size_bytes / 1024)} KB ({d.mime_type})
                        </div>
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <span className={`mce-pill ${d.status === 'active' ? 'mce-pill-success' : 'mce-pill-neutral'}`}>
                          {d.status.toUpperCase()}
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

      {/* TAB 4: AGENT MEMORY */}
      {!loading && activeTab === 'memory' && (
        <div>
          <div className="mce-card" style={{ marginBottom: '1.5rem' }}>
            <div className="mce-card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                <h3 className="mce-card-title">Manajemen Memori Kognitif AI Agent (Agent Memory)</h3>
                <p style={{ margin: 0, fontSize: '0.85rem', color: '#94a3b8' }}>
                  Memori kognitif otonom terpisah dari basis pengetahuan perusahaan. Dilindungi isolasi identitas dan retensi siklus kerja.
                </p>
              </div>
              <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
                <select
                  value={selectedAgentId}
                  onChange={(e) => setSelectedAgentId(Number(e.target.value))}
                  className="mce-select"
                  style={{ padding: '0.35rem 0.6rem', fontSize: '0.85rem' }}
                >
                  <option value={1}>Director AI (AGT-DIR-001)</option>
                  <option value={2}>Tech Lead AI (AGT-TECH-001)</option>
                  <option value={3}>Services Lead AI (AGT-SRV-001)</option>
                  <option value={4}>Academy Lead AI (AGT-ACD-001)</option>
                  <option value={5}>Dev AI Specialist (AGT-DEV-001)</option>
                  <option value={6}>Hardware Infra AI (AGT-HDW-001)</option>
                </select>
                <select
                  value={memoryTypeFilter}
                  onChange={(e) => setMemoryTypeFilter(e.target.value)}
                  className="mce-select"
                  style={{ padding: '0.35rem 0.6rem', fontSize: '0.85rem' }}
                >
                  <option value="">Semua Tipe Memori</option>
                  <option value="working_memory">Working Memory</option>
                  <option value="episodic_memory">Episodic Memory</option>
                  <option value="semantic_memory">Semantic Memory</option>
                  <option value="procedural_memory">Procedural Memory</option>
                </select>
                <button
                  type="button"
                  className="mce-btn mce-btn-primary mce-btn-sm"
                  onClick={() => setShowCreateMemModal(true)}
                >
                  + Tambah Memori
                </button>
              </div>
            </div>
            <div className="mce-card-body" style={{ padding: 0, overflowX: 'auto' }}>
              <table className="mce-table" style={{ width: '100%', borderCollapse: 'collapse' }}>
                <thead>
                  <tr style={{ background: '#1e293b', textAlign: 'left', borderBottom: '1px solid #334155' }}>
                    <th style={{ padding: '0.75rem 1rem' }}>Kode & Judul Memori</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Tipe Memori</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Tingkat Kepentingan</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Kebijakan Retensi</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Akses & Status</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Aksi Pengetahuan</th>
                  </tr>
                </thead>
                <tbody>
                  {agentMemories.map((m) => (
                    <tr key={m.id} style={{ borderBottom: '1px solid #1e293b' }}>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <code style={{ color: '#818cf8', fontWeight: 'bold' }}>{m.memory_code}</code>
                        <div style={{ fontWeight: '600' }}>{m.title}</div>
                        <div style={{ fontSize: '0.8rem', color: '#94a3b8', marginTop: '2px', maxWidth: '320px' }}>
                          {m.content}
                        </div>
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <span className="mce-pill mce-pill-neutral" style={{ textTransform: 'capitalize' }}>
                          {m.memory_type.replace('_', ' ')}
                        </span>
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                          <div style={{ width: '60px', height: '6px', background: '#334155', borderRadius: '3px', overflow: 'hidden' }}>
                            <div style={{ width: `${m.importance_score * 100}%`, height: '100%', background: '#10b981' }}></div>
                          </div>
                          <span style={{ fontSize: '0.8rem' }}>{Math.round(m.importance_score * 100)}%</span>
                        </div>
                      </td>
                      <td style={{ padding: '0.75rem 1rem', fontSize: '0.85rem', textTransform: 'capitalize' }}>
                        {m.retention_policy.replace('_', ' ')}
                      </td>
                      <td style={{ padding: '0.75rem 1rem', fontSize: '0.85rem' }}>
                        <div>Diakses {m.access_count}x</div>
                        <span className="mce-pill mce-pill-success" style={{ fontSize: '0.7rem' }}>
                          {m.status.toUpperCase()}
                        </span>
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <button
                          type="button"
                          className="mce-btn mce-btn-primary mce-btn-sm"
                          onClick={() => handlePromoteMemory(m)}
                          title="Usulkan memori berharga ini menjadi pengetahuan organisasi resmi"
                        >
                          💡 Usulkan ke KB
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* TAB 5: WALLBOOK BRIDGE */}
      {!loading && activeTab === 'wallbook' && (
        <div>
          <div className="mce-card">
            <div className="mce-card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                <h3 className="mce-card-title">Jembatan Pengetahuan Serah Terima (Wallbook Handover Bridge)</h3>
                <p style={{ margin: 0, fontSize: '0.85rem', color: '#94a3b8' }}>
                  Memverifikasi checklist kelengkapan handover sebelum off-duty dan mempromosikan solusi teknis handover ke basis pengetahuan.
                </p>
              </div>
            </div>
            <div className="mce-card-body" style={{ padding: '1rem' }}>
              <h4 style={{ margin: '0 0 1rem 0' }}>Checklist Serah Terima Tugas (Handover Checklist #1)</h4>
              <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                {handoverChecklists.map((chk) => (
                  <div
                    key={chk.id}
                    style={{
                      background: '#0f172a',
                      border: '1px solid #1e293b',
                      borderRadius: '6px',
                      padding: '0.75rem 1rem',
                      display: 'flex',
                      justifyContent: 'space-between',
                      alignItems: 'center',
                    }}
                  >
                    <div>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                        <code style={{ color: '#818cf8' }}>{chk.checklist_code}</code>
                        {chk.is_mandatory && <span className="mce-pill mce-pill-danger" style={{ fontSize: '0.65rem' }}>MANDATORY</span>}
                      </div>
                      <div style={{ fontWeight: '500', color: '#f8fafc', marginTop: '4px' }}>
                        {chk.item_label}
                      </div>
                      {chk.notes && <div style={{ fontSize: '0.8rem', color: '#94a3b8', marginTop: '2px' }}>{chk.notes}</div>}
                    </div>
                    <button
                      type="button"
                      className={`mce-btn ${chk.is_satisfied ? 'mce-btn-success' : 'mce-btn-secondary'} mce-btn-sm`}
                      onClick={() => handleToggleChecklist(chk)}
                    >
                      {chk.is_satisfied ? '✓ Terpenuhi' : '○ Belum Terpenuhi'}
                    </button>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* TAB 6: CONTRIBUTIONS */}
      {!loading && activeTab === 'contributions' && (
        <div>
          <div className="mce-card">
            <div className="mce-card-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                <h3 className="mce-card-title">Usulan Kontribusi Pengetahuan (Knowledge Contributions)</h3>
                <p style={{ margin: 0, fontSize: '0.85rem', color: '#94a3b8' }}>
                  Kontribusi dari manusia dan AI Agent yang menunggu review independen (Zero-Self-Review Rule).
                </p>
              </div>
            </div>
            <div className="mce-card-body" style={{ padding: 0, overflowX: 'auto' }}>
              <table className="mce-table" style={{ width: '100%', borderCollapse: 'collapse' }}>
                <thead>
                  <tr style={{ background: '#1e293b', textAlign: 'left', borderBottom: '1px solid #334155' }}>
                    <th style={{ padding: '0.75rem 1rem' }}>Kode & Usulan</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Kontributor</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Kategori Usulan</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Status Review</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Reviewer</th>
                    <th style={{ padding: '0.75rem 1rem' }}>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  {contributions.map((c) => (
                    <tr key={c.id} style={{ borderBottom: '1px solid #1e293b' }}>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <code style={{ color: '#818cf8', fontWeight: 'bold' }}>{c.contribution_code}</code>
                        <div style={{ fontWeight: '600' }}>{c.title}</div>
                        <div style={{ fontSize: '0.75rem', color: '#94a3b8' }}>{c.summary}</div>
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <div>{c.contributor_agent_name || c.contributor_user_name}</div>
                        <span className="mce-pill mce-pill-neutral" style={{ fontSize: '0.7rem' }}>
                          {c.contributor_type.toUpperCase()}
                        </span>
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        {c.proposed_category_name}
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        <span className={`mce-pill ${c.status === 'approved' ? 'mce-pill-success' : 'mce-pill-warning'}`}>
                          {c.status.toUpperCase()}
                        </span>
                      </td>
                      <td style={{ padding: '0.75rem 1rem', fontSize: '0.85rem' }}>
                        {c.reviewer_user_name || c.reviewer_agent_name || '-'}
                      </td>
                      <td style={{ padding: '0.75rem 1rem' }}>
                        {c.status === 'submitted' ? (
                          <button
                            type="button"
                            className="mce-btn mce-btn-primary mce-btn-sm"
                            onClick={() => {
                              setSelectedContribution(c);
                              setShowReviewModal(true);
                            }}
                          >
                            🔍 Review
                          </button>
                        ) : (
                          <span style={{ fontSize: '0.8rem', color: '#64748b' }}>Telah Ditinjau</span>
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

      {/* REVIEW CONTRIBUTION MODAL */}
      {showReviewModal && selectedContribution && (
        <div className="mce-modal-overlay">
          <div className="mce-modal-content" style={{ maxWidth: '580px' }}>
            <h3 style={{ marginTop: 0 }}>Review Kontribusi: {selectedContribution.contribution_code}</h3>
            <p style={{ fontSize: '0.85rem', color: '#94a3b8' }}>
              Penegakan <strong>Zero-Self-Review Rule</strong>: Reviewer tidak boleh sama dengan kontributor.
            </p>
            <form onSubmit={handleReviewSubmit}>
              <div style={{ marginBottom: '1rem' }}>
                <label style={{ fontSize: '0.85rem' }}>Keputusan Review</label>
                <select
                  value={reviewDecision}
                  onChange={(e: any) => setReviewDecision(e.target.value)}
                  style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                >
                  <option value="approved">Setujui & Publikasikan ke Knowledge Base (Approved)</option>
                  <option value="revision_requested">Minta Revisi (Needs Revision)</option>
                  <option value="rejected">Tolak Usulan (Rejected)</option>
                </select>
              </div>
              <div style={{ marginBottom: '1rem' }}>
                <label style={{ fontSize: '0.85rem' }}>Catatan Evaluasi / Umpan Balik</label>
                <textarea
                  required
                  value={reviewFeedback}
                  onChange={(e) => setReviewFeedback(e.target.value)}
                  style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px', minHeight: '80px' }}
                />
              </div>
              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '0.75rem' }}>
                <button
                  type="button"
                  className="mce-btn mce-btn-secondary"
                  onClick={() => setShowReviewModal(false)}
                >
                  Batal
                </button>
                <button type="submit" className="mce-btn mce-btn-primary">
                  Simpan Keputusan Review
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* TAB 7: SEARCH & DISCOVERY */}
      {!loading && activeTab === 'search' && (
        <div>
          <div className="mce-card" style={{ marginBottom: '1.5rem' }}>
            <div className="mce-card-header">
              <h3 className="mce-card-title">Pencarian Terpadu (Unified Knowledge Search)</h3>
              <p style={{ margin: 0, fontSize: '0.85rem', color: '#94a3b8' }}>
                Mencakup Artikel Pengetahuan, Dokumen Resmi, dan Catatan Serah Terima Wallbook dengan penyaringan hak akses otomatis.
              </p>
            </div>
            <div className="mce-card-body">
              <form onSubmit={handleSearch} style={{ display: 'flex', gap: '0.5rem', marginBottom: '1rem' }}>
                <input
                  type="text"
                  required
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Ketik kata kunci pencarian..."
                  style={{ flex: 1, padding: '0.6rem 1rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '6px' }}
                />
                <button type="submit" className="mce-btn mce-btn-primary">
                  {searchLoading ? 'Mencari...' : '🔍 Cari'}
                </button>
              </form>

              {searchHits > 0 && (
                <div style={{ fontSize: '0.85rem', color: '#94a3b8', marginBottom: '1rem' }}>
                  Ditemukan <strong>{searchHits}</strong> hasil dalam {searchLatency} ms.
                </div>
              )}

              <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                {searchResults.map((res, idx) => (
                  <div
                    key={idx}
                    style={{
                      background: '#0f172a',
                      border: '1px solid #1e293b',
                      borderRadius: '6px',
                      padding: '1rem',
                    }}
                  >
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.25rem' }}>
                      <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
                        <code style={{ color: '#818cf8', fontWeight: 'bold' }}>{res.code}</code>
                        <span className="mce-pill mce-pill-neutral" style={{ fontSize: '0.7rem' }}>{res.type.replace('_', ' ')}</span>
                      </div>
                      <span className="mce-pill mce-pill-success" style={{ fontSize: '0.7rem' }}>
                        Relevance: {Math.round(res.score * 100)}%
                      </span>
                    </div>
                    <h4 style={{ margin: '0.25rem 0', color: '#f8fafc' }}>{res.title}</h4>
                    <p style={{ margin: 0, fontSize: '0.85rem', color: '#94a3b8' }}>
                      {res.snippet}
                    </p>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* TAB 8: AI RETRIEVAL & RAG */}
      {!loading && activeTab === 'rag' && (
        <div>
          <div className="mce-card">
            <div className="mce-card-header">
              <h3 className="mce-card-title">Simulator Pengambilan Konteks AI (RAG Context Retrieval)</h3>
              <p style={{ margin: 0, fontSize: '0.85rem', color: '#94a3b8' }}>
                Uji otorisasi pra-retrieval sebelum konteks diumpankan ke model AI runtime Hermes via 9Router.
              </p>
            </div>
            <div className="mce-card-body">
              <form onSubmit={handleRagRetrieve} style={{ display: 'flex', gap: '0.5rem', marginBottom: '1.25rem' }}>
                <input
                  type="text"
                  required
                  value={ragQuery}
                  onChange={(e) => setRagQuery(e.target.value)}
                  placeholder="Kueri konteks untuk agen..."
                  style={{ flex: 1, padding: '0.6rem 1rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '6px' }}
                />
                <button type="submit" className="mce-btn mce-btn-primary">
                  {ragLoading ? 'Mengekstrak...' : '⚡ Ambil Konteks RAG'}
                </button>
              </form>

              {ragResult && (
                <div style={{ background: '#0f172a', border: '1px solid #1e293b', borderRadius: '8px', padding: '1.25rem' }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '1rem' }}>
                    <div>
                      <strong>Agen Penerima:</strong> #{ragResult.agent_id} | <strong>Potongan Konteks:</strong> {ragResult.chunks_count}
                    </div>
                    <span className="mce-pill mce-pill-success">
                      Latency: {ragResult.retrieval_latency} ms
                    </span>
                  </div>
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                    {ragResult.context_chunks?.map((chunk: any, i: number) => (
                      <div key={i} style={{ background: '#1e293b', padding: '1rem', borderRadius: '6px' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
                          <code style={{ color: '#38bdf8' }}>{chunk.source_id} (v{chunk.version})</code>
                          <span className="mce-pill mce-pill-neutral" style={{ fontSize: '0.7rem' }}>{chunk.provenance}</span>
                        </div>
                        <h4 style={{ margin: '0 0 0.5rem 0', color: '#f8fafc' }}>{chunk.title}</h4>
                        <div style={{ fontSize: '0.85rem', color: '#cbd5e1', whiteSpace: 'pre-wrap', fontFamily: 'monospace' }}>
                          {chunk.excerpt}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* CREATE RECORD MODAL */}
      {showCreateRecordModal && (
        <div className="mce-modal-overlay">
          <div className="mce-modal-content" style={{ maxWidth: '640px' }}>
            <h3 style={{ marginTop: 0 }}>Buat Draft Artikel Pengetahuan</h3>
            <form onSubmit={handleCreateRecordSubmit}>
              <div style={{ marginBottom: '1rem' }}>
                <label style={{ fontSize: '0.85rem' }}>Judul Artikel</label>
                <input
                  type="text"
                  required
                  value={recordForm.title}
                  onChange={(e) => setRecordForm({ ...recordForm, title: e.target.value })}
                  placeholder="e.g. Prosedur Operasional Penanganan Hambatan Teknis"
                  style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                />
              </div>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem', marginBottom: '1rem' }}>
                <div>
                  <label style={{ fontSize: '0.85rem' }}>Kategori</label>
                  <select
                    value={recordForm.category_id}
                    onChange={(e) => setRecordForm({ ...recordForm, category_id: Number(e.target.value) })}
                    style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                  >
                    {categories.map((c) => (
                      <option key={c.id} value={c.id}>
                        {c.name} ({c.domain})
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label style={{ fontSize: '0.85rem' }}>Klasifikasi Akses</label>
                  <select
                    value={recordForm.classification}
                    onChange={(e: any) => setRecordForm({ ...recordForm, classification: e.target.value })}
                    style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                  >
                    <option value="internal">Internal (Workforce umum)</option>
                    <option value="public">Public (Umum)</option>
                    <option value="confidential">Confidential (Divisi/Proyek)</option>
                    <option value="restricted">Restricted (Pimpinan/Direksi)</option>
                  </select>
                </div>
              </div>
              <div style={{ marginBottom: '1rem' }}>
                <label style={{ fontSize: '0.85rem' }}>Ringkasan Eksekutif</label>
                <textarea
                  value={recordForm.summary}
                  onChange={(e) => setRecordForm({ ...recordForm, summary: e.target.value })}
                  placeholder="Ringkasan singkat isi artikel..."
                  style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                />
              </div>
              <div style={{ marginBottom: '1rem' }}>
                <label style={{ fontSize: '0.85rem' }}>Isi Konten (Markdown)</label>
                <textarea
                  required
                  value={recordForm.content}
                  onChange={(e) => setRecordForm({ ...recordForm, content: e.target.value })}
                  placeholder="# Judul Bab&#10;&#10;Uraian prosedur atau pengetahuan..."
                  style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px', minHeight: '140px' }}
                />
              </div>
              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '0.75rem' }}>
                <button
                  type="button"
                  className="mce-btn mce-btn-secondary"
                  onClick={() => setShowCreateRecordModal(false)}
                >
                  Batal
                </button>
                <button type="submit" className="mce-btn mce-btn-primary">
                  Simpan Draft
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* CREATE DOCUMENT MODAL */}
      {showCreateDocModal && (
        <div className="mce-modal-overlay">
          <div className="mce-modal-content" style={{ maxWidth: '520px' }}>
            <h3 style={{ marginTop: 0 }}>Daftarkan Dokumen Resmi Baru</h3>
            <form onSubmit={handleCreateDocSubmit}>
              <div style={{ marginBottom: '1rem' }}>
                <label style={{ fontSize: '0.85rem' }}>Judul Dokumen</label>
                <input
                  type="text"
                  required
                  value={docForm.title}
                  onChange={(e) => setDocForm({ ...docForm, title: e.target.value })}
                  placeholder="e.g. Manual Operasi Server Hermes v2.1"
                  style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                />
              </div>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem', marginBottom: '1rem' }}>
                <div>
                  <label style={{ fontSize: '0.85rem' }}>Kategori</label>
                  <select
                    value={docForm.category}
                    onChange={(e) => setDocForm({ ...docForm, category: e.target.value })}
                    style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                  >
                    <option value="corporate_policy">Corporate Policy</option>
                    <option value="standard_procedure">Standard Procedure</option>
                    <option value="architecture_spec">Architecture Spec</option>
                    <option value="technical_manual">Technical Manual</option>
                  </select>
                </div>
                <div>
                  <label style={{ fontSize: '0.85rem' }}>Klasifikasi</label>
                  <select
                    value={docForm.classification}
                    onChange={(e: any) => setDocForm({ ...docForm, classification: e.target.value })}
                    style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                  >
                    <option value="internal">Internal</option>
                    <option value="confidential">Confidential</option>
                    <option value="restricted">Restricted</option>
                  </select>
                </div>
              </div>
              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '0.75rem' }}>
                <button
                  type="button"
                  className="mce-btn mce-btn-secondary"
                  onClick={() => setShowCreateDocModal(false)}
                >
                  Batal
                </button>
                <button type="submit" className="mce-btn mce-btn-primary">
                  Daftarkan Dokumen
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* CREATE MEMORY MODAL */}
      {showCreateMemModal && (
        <div className="mce-modal-overlay">
          <div className="mce-modal-content" style={{ maxWidth: '520px' }}>
            <h3 style={{ marginTop: 0 }}>Tambah Memori Kerja Agen #{selectedAgentId}</h3>
            <form onSubmit={handleCreateMemSubmit}>
              <div style={{ marginBottom: '1rem' }}>
                <label style={{ fontSize: '0.85rem' }}>Judul Memori</label>
                <input
                  type="text"
                  required
                  value={memForm.title}
                  onChange={(e) => setMemForm({ ...memForm, title: e.target.value })}
                  placeholder="e.g. Heuristik Routing Fallback Token 9Router"
                  style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                />
              </div>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem', marginBottom: '1rem' }}>
                <div>
                  <label style={{ fontSize: '0.85rem' }}>Tipe Memori</label>
                  <select
                    value={memForm.memory_type}
                    onChange={(e) => setMemForm({ ...memForm, memory_type: e.target.value })}
                    style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                  >
                    <option value="procedural_memory">Procedural (Metode / Heuristik)</option>
                    <option value="semantic_memory">Semantic (Fakta / Konsep)</option>
                    <option value="episodic_memory">Episodic (Riwayat Kejadian)</option>
                    <option value="working_memory">Working (Sesi Sementara)</option>
                  </select>
                </div>
                <div>
                  <label style={{ fontSize: '0.85rem' }}>Kebijakan Retensi</label>
                  <select
                    value={memForm.retention_policy}
                    onChange={(e) => setMemForm({ ...memForm, retention_policy: e.target.value })}
                    style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px' }}
                  >
                    <option value="permanent">Permanen</option>
                    <option value="90_days">90 Hari</option>
                    <option value="30_days">30 Hari</option>
                    <option value="session_ephemeral">Sesi Sementara</option>
                  </select>
                </div>
              </div>
              <div style={{ marginBottom: '1rem' }}>
                <label style={{ fontSize: '0.85rem' }}>Isi Memori Kognitif</label>
                <textarea
                  required
                  value={memForm.content}
                  onChange={(e) => setMemForm({ ...memForm, content: e.target.value })}
                  placeholder="Detail instruksi, konteks, atau aturan yang dipelajari..."
                  style={{ width: '100%', padding: '0.5rem', background: '#0f172a', border: '1px solid #334155', color: '#fff', borderRadius: '4px', minHeight: '100px' }}
                />
              </div>
              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '0.75rem' }}>
                <button
                  type="button"
                  className="mce-btn mce-btn-secondary"
                  onClick={() => setShowCreateMemModal(false)}
                >
                  Batal
                </button>
                <button type="submit" className="mce-btn mce-btn-primary">
                  Simpan Memori
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
