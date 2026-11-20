/**
 * MCE AI Office — Knowledge Management, Organizational Memory & Wallbook Types (Prompt 25)
 */

export type KnowledgeDomain =
  | 'corporate'
  | 'operational'
  | 'project'
  | 'technical'
  | 'agent_knowledge';

export type KnowledgeClassification =
  | 'public'
  | 'internal'
  | 'confidential'
  | 'restricted'
  | 'highly_restricted';

export type KnowledgeStatus =
  | 'draft'
  | 'submitted'
  | 'under_review'
  | 'approved'
  | 'published'
  | 'needs_revision'
  | 'rejected'
  | 'superseded'
  | 'expired'
  | 'archived'
  | 'withdrawn';

export type AgentMemoryType =
  | 'working_memory'
  | 'episodic_memory'
  | 'semantic_memory'
  | 'procedural_memory';

export type DocumentCategory =
  | 'corporate_policy'
  | 'standard_procedure'
  | 'architecture_spec'
  | 'project_deliverable'
  | 'handover_evidence'
  | 'technical_manual';

export interface KnowledgeCategory {
  id: number;
  category_code: string;
  domain: KnowledgeDomain;
  name: string;
  description?: string | null;
  parent_category_id?: number | null;
  default_classification: KnowledgeClassification;
  is_active: number;
  records_count?: number;
  created_at: string;
  updated_at: string;
}

export interface KnowledgeVersion {
  id: number;
  record_id: number;
  version_number: string;
  title: string;
  summary?: string | null;
  content: string;
  change_summary: string;
  author_type: 'human' | 'agent';
  author_user_id?: number | null;
  author_agent_id?: number | null;
  approved_by_user_id?: number | null;
  approved_by_name?: string | null;
  approved_at?: string | null;
  created_at: string;
}

export interface KnowledgeRecord {
  id: number;
  record_code: string;
  category_id: number;
  category_name?: string;
  category_domain?: KnowledgeDomain;
  title: string;
  summary?: string | null;
  content: string;
  content_format: 'markdown' | 'plain_text' | 'html' | 'json';
  classification: KnowledgeClassification;
  status: KnowledgeStatus;
  version: string;
  owner_type: 'unit' | 'human_user' | 'agent';
  owner_unit_id?: number | null;
  unit_name?: string | null;
  author_type: 'human' | 'agent';
  author_user_id?: number | null;
  author_user_name?: string | null;
  author_agent_id?: number | null;
  author_agent_name?: string | null;
  author_agent_code?: string | null;
  provenance_type: 'human_authored' | 'agent_generated' | 'wallbook_promoted' | 'executive_directive' | 'task_deliverable_distilled';
  provenance_source_ref?: string | null;
  related_task_id?: number | null;
  tags?: string[];
  effective_from?: string | null;
  review_due_date?: string | null;
  is_latest: number;
  versions?: KnowledgeVersion[];
  created_at: string;
  updated_at: string;
}

export interface KnowledgeContribution {
  id: number;
  contribution_code: string;
  contributor_type: 'human' | 'agent';
  contributor_user_id?: number | null;
  contributor_user_name?: string | null;
  contributor_agent_id?: number | null;
  contributor_agent_name?: string | null;
  contributor_agent_code?: string | null;
  target_record_id?: number | null;
  proposed_category_id: number;
  proposed_category_name?: string;
  proposed_domain?: KnowledgeDomain;
  proposed_classification: KnowledgeClassification;
  title: string;
  summary?: string | null;
  content: string;
  evidence_references?: string | null;
  source_context?: string | null;
  status: 'submitted' | 'under_review' | 'approved' | 'rejected' | 'revision_requested';
  reviewer_user_id?: number | null;
  reviewer_user_name?: string | null;
  reviewer_agent_id?: number | null;
  reviewer_agent_name?: string | null;
  reviewer_agent_code?: string | null;
  review_feedback?: string | null;
  reviewed_at?: string | null;
  published_record_id?: number | null;
  performance_history_id?: number | null;
  created_at: string;
  updated_at: string;
}

export interface DocumentItem {
  id: number;
  document_code: string;
  title: string;
  description?: string | null;
  classification: KnowledgeClassification;
  category: DocumentCategory;
  owner_unit_id?: number | null;
  unit_name?: string | null;
  current_version: string;
  status: 'draft' | 'active' | 'under_review' | 'superseded' | 'archived';
  file_path: string;
  file_name: string;
  file_size_bytes: number;
  mime_type: string;
  sha256_checksum: string;
  uploaded_by_type: 'human' | 'agent';
  uploaded_by_user_name?: string | null;
  uploaded_by_agent_name?: string | null;
  uploaded_by_agent_code?: string | null;
  is_latest: number;
  versions?: any[];
  created_at: string;
  updated_at: string;
}

export interface AgentMemoryItem {
  id: number;
  memory_code: string;
  agent_id: number;
  agent_name?: string;
  agent_code?: string;
  memory_type: AgentMemoryType;
  title: string;
  content: string;
  tags?: string[];
  source_type: string;
  source_reference?: string | null;
  classification: 'internal' | 'confidential' | 'restricted';
  status: 'active' | 'archived' | 'expired' | 'discarded';
  retention_policy: string;
  expires_at?: string | null;
  importance_score: number;
  access_count: number;
  last_accessed_at?: string | null;
  versions?: any[];
  created_at: string;
  updated_at: string;
}

export interface WallbookHandoverChecklist {
  id: number;
  handover_id: number;
  checklist_code: string;
  item_label: string;
  is_mandatory: boolean;
  is_satisfied: boolean;
  satisfied_at?: string | null;
  notes?: string | null;
  evidence_reference?: string | null;
  created_at: string;
}

export interface SearchResultItem {
  type: 'knowledge_record' | 'document' | 'wallbook_handover';
  id: number;
  code: string;
  title: string;
  snippet: string;
  domain: string;
  category: string;
  classification: KnowledgeClassification;
  version: string;
  score: number;
}

export interface KnowledgeOverviewMetrics {
  total_records: number;
  published_records: number;
  draft_records: number;
  under_review_records: number;
  archived_records: number;
  total_documents: number;
  active_memories: number;
  total_contributions: number;
  pending_contributions: number;
  active_wallbook_entries: number;
  by_domain: Record<string, number>;
}
