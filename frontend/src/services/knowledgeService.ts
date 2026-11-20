// Knowledge Management & Organizational Memory API Client Service (Prompt 25)

import { apiClient } from './apiClient';
import type {
  KnowledgeOverviewMetrics,
  KnowledgeCategory,
  KnowledgeRecord,
  KnowledgeContribution,
  DocumentItem,
  AgentMemoryItem,
  WallbookHandoverChecklist,
  SearchResultItem,
} from '../types/knowledge';

class KnowledgeService {
  /**
   * Overview Metrics
   */
  public async getOverview(): Promise<KnowledgeOverviewMetrics> {
    const res = await apiClient.request<KnowledgeOverviewMetrics>('/knowledge/overview');
    return res.data;
  }

  /**
   * Categories
   */
  public async listCategories(filters?: { domain?: string; is_active?: number }): Promise<KnowledgeCategory[]> {
    const params = new URLSearchParams();
    if (filters?.domain) params.append('domain', filters.domain);
    if (filters?.is_active !== undefined) params.append('is_active', String(filters.is_active));
    const endpoint = `/knowledge/categories${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<KnowledgeCategory[]>(endpoint);
    return res.data;
  }

  public async createCategory(payload: Partial<KnowledgeCategory>): Promise<KnowledgeCategory> {
    const res = await apiClient.request<KnowledgeCategory>('/knowledge/categories', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  /**
   * Knowledge Records
   */
  public async listRecords(filters?: {
    domain?: string;
    category_id?: number;
    classification?: string;
    status?: string;
    owner_unit_id?: number;
    search?: string;
    limit?: number;
    offset?: number;
  }): Promise<{ items: KnowledgeRecord[]; total: number }> {
    const params = new URLSearchParams();
    if (filters?.domain) params.append('domain', filters.domain);
    if (filters?.category_id) params.append('category_id', String(filters.category_id));
    if (filters?.classification) params.append('classification', filters.classification);
    if (filters?.status) params.append('status', filters.status);
    if (filters?.owner_unit_id) params.append('owner_unit_id', String(filters.owner_unit_id));
    if (filters?.search) params.append('search', filters.search);
    if (filters?.limit) params.append('limit', String(filters.limit));
    if (filters?.offset) params.append('offset', String(filters.offset));

    const endpoint = `/knowledge/records${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<KnowledgeRecord[]>(endpoint);
    return {
      items: res.data,
      total: (res as any).meta?.total ?? res.data.length,
    };
  }

  public async getRecord(id: number): Promise<KnowledgeRecord> {
    const res = await apiClient.request<KnowledgeRecord>(`/knowledge/records/${id}`);
    return res.data;
  }

  public async createRecord(payload: Partial<KnowledgeRecord>): Promise<KnowledgeRecord> {
    const res = await apiClient.request<KnowledgeRecord>('/knowledge/records', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async updateRecord(id: number, payload: Partial<KnowledgeRecord> & { change_summary?: string; bump_version?: boolean }): Promise<KnowledgeRecord> {
    const res = await apiClient.request<KnowledgeRecord>(`/knowledge/records/${id}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async transitionRecord(id: number, targetStatus: string, reason?: string): Promise<KnowledgeRecord> {
    const res = await apiClient.request<KnowledgeRecord>(`/knowledge/records/${id}/transition`, {
      method: 'POST',
      body: JSON.stringify({ target_status: targetStatus, reason }),
    });
    return res.data;
  }

  /**
   * Knowledge Contributions
   */
  public async listContributions(filters?: { status?: string; contributor_agent_id?: number }): Promise<{ items: KnowledgeContribution[]; total: number }> {
    const params = new URLSearchParams();
    if (filters?.status) params.append('status', filters.status);
    if (filters?.contributor_agent_id) params.append('contributor_agent_id', String(filters.contributor_agent_id));
    const endpoint = `/knowledge/contributions${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<KnowledgeContribution[]>(endpoint);
    return {
      items: res.data,
      total: (res as any).meta?.total ?? res.data.length,
    };
  }

  public async submitContribution(payload: Partial<KnowledgeContribution>): Promise<KnowledgeContribution> {
    const res = await apiClient.request<KnowledgeContribution>('/knowledge/contributions', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async reviewContribution(id: number, decision: 'approved' | 'rejected' | 'revision_requested', feedback?: string): Promise<KnowledgeContribution> {
    const res = await apiClient.request<KnowledgeContribution>(`/knowledge/contributions/${id}/review`, {
      method: 'POST',
      body: JSON.stringify({ decision, feedback }),
    });
    return res.data;
  }

  /**
   * Corporate & Technical Documents
   */
  public async listDocuments(filters?: { category?: string; classification?: string; search?: string }): Promise<{ items: DocumentItem[]; total: number }> {
    const params = new URLSearchParams();
    if (filters?.category) params.append('category', filters.category);
    if (filters?.classification) params.append('classification', filters.classification);
    if (filters?.search) params.append('search', filters.search);
    const endpoint = `/knowledge/documents${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<DocumentItem[]>(endpoint);
    return {
      items: res.data,
      total: (res as any).meta?.total ?? res.data.length,
    };
  }

  public async registerDocument(payload: Partial<DocumentItem>): Promise<DocumentItem> {
    const res = await apiClient.request<DocumentItem>('/knowledge/documents', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  /**
   * Agent Memories
   */
  public async listAgentMemories(agentId: number, filters?: { memory_type?: string; search?: string }): Promise<AgentMemoryItem[]> {
    const params = new URLSearchParams();
    if (filters?.memory_type) params.append('memory_type', filters.memory_type);
    if (filters?.search) params.append('search', filters.search);
    const endpoint = `/knowledge/agents/${agentId}/memories${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<AgentMemoryItem[]>(endpoint);
    return res.data;
  }

  public async createAgentMemory(agentId: number, payload: Partial<AgentMemoryItem>): Promise<AgentMemoryItem> {
    const res = await apiClient.request<AgentMemoryItem>(`/knowledge/agents/${agentId}/memories`, {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.data;
  }

  public async promoteMemory(memoryId: number, proposalData?: { title?: string; summary?: string; content?: string }): Promise<KnowledgeContribution> {
    const res = await apiClient.request<KnowledgeContribution>(`/knowledge/memories/${memoryId}/promote`, {
      method: 'POST',
      body: JSON.stringify(proposalData ?? {}),
    });
    return res.data;
  }

  /**
   * Wallbook Handover Bridge
   */
  public async listWallbookChecklists(handoverId: number): Promise<WallbookHandoverChecklist[]> {
    const res = await apiClient.request<WallbookHandoverChecklist[]>(`/knowledge/wallbook/${handoverId}/checklists`);
    return res.data;
  }

  public async updateWallbookChecklist(checklistId: number, isSatisfied: boolean, notes?: string): Promise<WallbookHandoverChecklist> {
    const res = await apiClient.request<WallbookHandoverChecklist>(`/knowledge/wallbook/checklists/${checklistId}`, {
      method: 'POST',
      body: JSON.stringify({ is_satisfied: isSatisfied, notes }),
    });
    return res.data;
  }

  public async promoteWallbookItem(handoverId: number, itemId: number, proposalData?: { title?: string; summary?: string; content?: string }): Promise<KnowledgeContribution> {
    const res = await apiClient.request<KnowledgeContribution>(`/knowledge/wallbook/${handoverId}/items/${itemId}/promote`, {
      method: 'POST',
      body: JSON.stringify(proposalData ?? {}),
    });
    return res.data;
  }

  /**
   * Unified Search & RAG Context Retrieval
   */
  public async search(query: string, filters?: { domain?: string; classification?: string }): Promise<{
    query: string;
    total_hits: number;
    results: SearchResultItem[];
    latency_ms: number;
  }> {
    const params = new URLSearchParams();
    params.append('q', query);
    if (filters?.domain) params.append('domain', filters.domain);
    if (filters?.classification) params.append('classification', filters.classification);
    const endpoint = `/knowledge/search?${params.toString()}`;
    const res = await apiClient.request<any>(endpoint);
    return res.data;
  }

  public async retrieveRagContext(query: string, agentId: number, taskId?: number): Promise<{
    agent_id: number;
    query: string;
    chunks_count: number;
    context_chunks: any[];
    retrieval_latency: number;
  }> {
    const res = await apiClient.request<any>('/knowledge/rag/retrieve', {
      method: 'POST',
      body: JSON.stringify({ query, agent_id: agentId, task_id: taskId }),
    });
    return res.data;
  }
}

export const knowledgeService = new KnowledgeService();
