# MCE Knowledge Operations Guide

**Document Reference:** `MCE-OPS-KM-025`  
**Version:** `1.0.0`  
**Classification:** `INTERNAL`  
**Audience:** Operations Division, Knowledge Managers, System Administrators, AI Workforce Supervisors  
**Effective Date:** 2026-11-20  

---

## 1. Operating Rhythms & Responsibilities

The MCE Knowledge Management System operates continuously across all six operating divisions. The table below outlines standard operational cadences:

| Cadence | Operational Task | Primary Actor | Quality Gate |
| :--- | :--- | :--- | :--- |
| **Shift Transition (3x Daily)** | Verify handover quality checklists and promote reusable shift insights | Shift Duty Agent & Supervisor | 4-point checklist (`CHK-*`) |
| **Daily (20:00 WIB)** | Review pending agent knowledge contributions | Division Heads & Quality Reviewers | Zero-Self-Review Rule |
| **Weekly (Monday 09:00 WIB)**| Audit knowledge classification tags & clearance boundaries | Chief Information Security Officer / Admin | Least-Privilege Audit |
| **Monthly (1st of month)** | Review document version freshness and archive superseded policies | Corporate Secretary & Legal Unit | Document Expiration Review |

---

## 2. Standard Operating Procedures (SOP)

### SOP-KM-01: Reviewing a Knowledge Contribution
1. Navigate to **Knowledge & Memory** $\rightarrow$ **Contributions** tab in MCE Dashboard.
2. Select a contribution in `submitted` or `under_review` status.
3. Review proposed title, domain classification, and evidentiary source references.
4. Verify that you are **NOT** the contributor (Zero-Self-Review Rule).
5. If valid, click **Approve Contribution** and enter feedback notes.
6. The system automatically:
   - Updates contribution status to `approved`.
   - Generates or updates corresponding knowledge record.
   - Credits the contributing AI agent in `ai_agent_performance_history`.

### SOP-KM-02: Controlled Document Registration & Release
1. Prepare approved PDF/DOCX policy or specification.
2. Compute SHA-256 checksum of the target file.
3. Call `POST /api/v1/knowledge/documents` with metadata:
   - `document_code` (e.g. `DOC-POL-HR-2026-001`)
   - `title`, `document_type`, `domain`, `classification_level`
   - `file_path`, `file_size_bytes`, `mime_type`, `sha256_checksum`
4. When releasing a revision, call `POST /api/v1/knowledge/documents/{id}/versions` to record the new version without breaking legacy historical pointers.

### SOP-KM-03: AI RAG Context Retrieval Audit
1. Access **Knowledge & Memory** $\rightarrow$ **AI RAG Retrieval** tab.
2. Inspect `knowledge_retrieval_logs` table for recent prompt queries dispatched by Hermes runtime.
3. Confirm that:
   - No `prompt_injection` flags were triggered.
   - Retrieval latency remains under 100ms.
   - Classification filter prevented confidential leakage to unauthorized agents.

---

## 3. Incident Handling & Emergency Actions

| Scenario | Risk Severity | Immediate Action |
| :--- | :---: | :--- |
| **Confidential Leak in Public KB** | **CRITICAL** | Immediately call `POST /api/v1/knowledge/records/{id}/transition` with `target_status = 'withdrawn'`, then update `classification = 'confidential'`. |
| **Prompt Injection Detected** | **HIGH** | `KnowledgeSearchService` logs query and drops score. Security Administrator audits agent execution session in `runtime_executions`. |
| **Document Checksum Mismatch** | **MEDIUM** | Storage file altered or corrupted. Re-upload authoritative file from offline secure backup and verify against `document_versions.sha256_checksum`. |
