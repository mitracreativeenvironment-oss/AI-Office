# MCE Knowledge Management API Reference

**Document Reference:** `MCE-API-KM-025`  
**Version:** `1.0.0`  
**Base URL:** `/api/v1/knowledge`  
**Authentication:** Bearer JWT Token (`Authorization: Bearer <token>`) via `mce_auth` filter  
**Envelope Format:** Standardized MCE API JSON Envelope (`{ status: "success" | "error", data: ..., message: ..., metadata?: ... }`)

---

## 1. Overview & Categories

### 1.1 `GET /api/v1/knowledge/overview`
Retrieves operational metrics and domain distribution across the knowledge repository.
- **Access**: Public / Internal
- **Response `data`**:
  ```json
  {
    "total_records": 6,
    "published_records": 5,
    "draft_records": 1,
    "under_review_records": 0,
    "archived_records": 0,
    "total_documents": 4,
    "active_memories": 5,
    "total_contributions": 1,
    "pending_contributions": 0,
    "active_wallbook_entries": 4,
    "by_domain": {
      "corporate": 2,
      "operational": 1,
      "project": 1,
      "technical": 1,
      "agent_knowledge": 1
    }
  }
  ```

### 1.2 `GET /api/v1/knowledge/categories`
Lists all knowledge categories with record counts.
- **Query Params**: `domain` (optional), `is_active` (optional)
- **Response**: Array of category objects.

### 1.3 `POST /api/v1/knowledge/categories`
Creates a new knowledge category.
- **Auth**: Required (`mce_auth`)
- **Body**:
  ```json
  {
    "name": "Cloud Infrastructure",
    "domain": "technical",
    "description": "Guides for cloud and hosting setups.",
    "default_classification": "internal"
  }
  ```

---

## 2. Knowledge Records (Articles)

### 2.1 `GET /api/v1/knowledge/records`
Lists knowledge articles with least-privilege classification filtering.
- **Query Params**: `domain`, `category_id`, `classification`, `status`, `search`, `limit`, `offset`.
- **Response**: Paginated list of records.

### 2.2 `GET /api/v1/knowledge/records/{id}`
Retrieves details of a single record, including current version and access policies.
- **Access**: Authorized according to record classification.

### 2.3 `POST /api/v1/knowledge/records`
Creates a new knowledge article draft.
- **Auth**: Required
- **Body**:
  ```json
  {
    "category_id": 4,
    "slug": "api-gateway-routing-guide",
    "title": "API Gateway Routing Guide",
    "summary": "Best practices for 9Router configuration.",
    "content": "# Markdown documentation...",
    "classification_level": "internal",
    "target_audience": "ai_and_human"
  }
  ```

### 2.4 `PUT /api/v1/knowledge/records/{id}`
Updates an existing record. If `bump_version` or `change_summary` is provided, creates a new version revision in `knowledge_versions`.

### 2.5 `POST /api/v1/knowledge/records/{id}/transition`
Transitions the lifecycle state of a record.
- **Body**: `target_status` (`under_review`, `approved`, `published`, `archived`, `withdrawn`), `reason` (optional).

---

## 3. Knowledge Contributions

### 3.1 `GET /api/v1/knowledge/contributions`
Lists candidate knowledge contributions submitted by humans or AI agents.

### 3.2 `POST /api/v1/knowledge/contributions`
Submits a new contribution candidate.
- **Body**:
  ```json
  {
    "contributor_type": "agent",
    "contributor_agent_id": 2,
    "title": "Automated Linting Optimization Strategy",
    "proposed_classification": "internal",
    "summary": "Reduces CI execution latency by 40%.",
    "content": "Detailed procedural pattern...",
    "source_context": "Discovered during task delivery #104."
  }
  ```

### 3.3 `POST /api/v1/knowledge/contributions/{id}/review`
Performs independent peer review.
- **Rule**: Enforces **Zero-Self-Review Rule**. Throws 403 if reviewer is the contributor.
- **Body**: `decision` (`approved`, `rejected`, `revision_requested`), `feedback` (string).
- **Side Effect**: On approval, credits contributor in `ai_agent_performance_history`.

---

## 4. Controlled Documents

### 4.1 `GET /api/v1/knowledge/documents`
Lists corporate controlled documents.

### 4.2 `POST /api/v1/knowledge/documents`
Registers an official controlled document with cryptographic checksum.
- **Body**: `document_code`, `title`, `document_type`, `domain`, `classification_level`, `storage_path`, `file_size_bytes`, `mime_type`, `sha256_checksum`.

### 4.3 `POST /api/v1/knowledge/documents/{id}/versions`
Records a new version release of an existing document.

---

## 5. Agent Memory Enclave

### 5.1 `GET /api/v1/knowledge/agents/{agent_id}/memories`
Lists private memories for an agent. Requires supervisor or owner authorization.

### 5.2 `POST /api/v1/knowledge/agents/{agent_id}/memories`
Consolidates an agent memory item (`working_memory`, `episodic_memory`, `semantic_memory`, `procedural_memory`).

### 5.3 `POST /api/v1/knowledge/memories/{id}/promote`
Promotes a private memory item into a candidate knowledge contribution proposal.

---

## 6. Wallbook Knowledge Bridge

### 6.1 `GET /api/v1/knowledge/wallbook/{handover_id}/checklists`
Retrieves the 4-point quality checklist for a shift handover.

### 6.2 `POST /api/v1/knowledge/wallbook/checklists/{id}`
Updates and verifies a checklist criterion (`is_satisfied`, `notes`).

### 6.3 `POST /api/v1/knowledge/wallbook/{handover_id}/items/{item_id}/promote`
Promotes a shift handover observation item directly to a knowledge contribution.

---

## 7. Search & AI RAG Context Retrieval

### 7.1 `GET /api/v1/knowledge/search?q={query}`
Unified multi-domain search across KB articles, controlled documents, and permitted memories.

### 7.2 `POST /api/v1/knowledge/rag/retrieve`
Provides authorized RAG context chunks for Hermes agent runtime.
- **Body**: `query`, `agent_id`, `task_id` (optional).
- **Response**: Array of `context_chunks` with source provenance and latency metrics. Logs retrieval to `knowledge_retrieval_logs`.
