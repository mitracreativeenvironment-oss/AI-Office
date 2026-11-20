# INDIVIDUAL AGENT PERFORMANCE HISTORY
## MCE AI Office — Permanent Timeline, Evidence Verification & Superseded Immutability
**Document Code:** `MCE-ARCH-APH-2026-V1`  
**Classification:** Controlled Architecture Specification  
**Current Milestone:** Prompt 19 — AI Agent Registry, Identity Mapping & Workforce Lifecycle Implementation  

---

## 1. Core Principle: Individual Identity Attachment

In enterprise workforce management, an employee's professional track record belongs to the individual, not to the desk they currently sit at. Similarly, in MCE AI Office, **Individual Agent Performance History** attaches permanently to the Agent's authoritative `agent_uuid`.

When an Agent is promoted or transferred from one division to another:
- Past achievements, completed milestones, peer evaluations, and incident reports do **not** disappear.
- Past records do **not** remain stuck in the previous division's ledger.
- The Agent retains a continuous, lifelong professional portfolio across all organizational assignments.

---

## 2. Relational Schema (`ai_agent_performance_history`)

| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | Surrogate primary key. |
| `agent_id` | `BIGINT UNSIGNED` | Relational FK to `ai_agent_registry`. |
| `agent_uuid` | `CHAR(36)` | Denormalized RFC 4122 v4 UUID for permanent audit association. |
| `category` | `ENUM` | Category: `task_execution`, `milestone_achievement`, `incident_report`, `evaluation_review`, `lifecycle_transition`. |
| `title` | `VARCHAR(160)` | Brief summary title of the performance event. |
| `description` | `TEXT` | Detailed contextual description. |
| `score` | `DECIMAL(5,2) NULL` | Optional numerical score (e.g., `95.50` / `100.00`). |
| `task_ref` | `VARCHAR(64) NULL` | Reference to task or ticket (e.g., `REGISTRATION`, `TASK-2026-001`). |
| `evidence_status` | `ENUM` | Evidence audit state: `submitted`, `under_review`, `verified`, `rejected`, `disputed`. |
| `evidence_verified_by_user_id`| `BIGINT UNSIGNED NULL` | User ID of verifier. |
| `evidence_verified_at` | `DATETIME NULL` | Timestamp of verification. |
| `verification_notes` | `TEXT NULL` | Evaluator audit notes. |
| `is_immutable` | `TINYINT(1)` | Defaults to `1`. Prevents in-place row edits. |
| `superseded_by_id` | `BIGINT UNSIGNED NULL` | Self-referencing FK pointing to the newer record if amended. |
| `created_at` / `updated_at` | `DATETIME` | Audit timestamps. |

---

## 3. Immutability & The Superseded Record Pattern

In enterprise governance and ISO-compliant audit systems, historical records must never be silently edited or deleted. If a performance review or incident report contains an error, the MCE architecture mandates the **Superseded Record Pattern**:

```
+-------------------------------------------------------------+
| ORIGINAL RECORD (#101)                                      |
| Status: verified | is_immutable: 1                          |
| Title: "Milestone Q3 Delivery"                              |
| Score: 85.00                                                |
| superseded_by_id: 102  ----------------------------------+  |
+----------------------------------------------------------|--+
                                                           |
                                                           v
+-------------------------------------------------------------+
| AMENDED RECORD (#102)                                       |
| Status: verified | is_immutable: 1                          |
| Title: "Milestone Q3 Delivery (Amended by Auditor)"         |
| Score: 92.50                                                |
| superseded_by_id: NULL (Active authoritative entry)         |
+-------------------------------------------------------------+
```

### Amending Rules:
1. When `amendPerformanceRecord($originalId, $data)` is called:
   - A new record is inserted with the corrected values.
   - The original record is updated *only* to set `superseded_by_id = $newRecordId`.
   - The original record's score, title, description, and audit notes remain permanently intact for historical auditors.

---

## 4. Evidence Verification Workflow

Performance claims (such as task completion or milestone delivery) transition through an evidence verification lifecycle:
- **`submitted`**: Performance entry recorded by agent or task engine.
- **`under_review`**: Assigned to manager or QA reviewer.
- **`verified`**: Authenticated reviewer signs off with `evidence_verified_by_user_id` and timestamp.
- **`rejected`** / **`disputed`**: Discrepancies noted in `verification_notes`.
