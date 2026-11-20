# MCE AI Office — Project & Task Management Integration Specification

**Document Version:** 1.0  
**Status:** Approved & Implemented  
**Author:** Senior Enterprise Project Management System Architect  
**Classification:** Technical Architecture & Engine Integration

---

## 1. Zero Duplicate Task Engine Architecture

A foundational architectural requirement of MCE AI Office is that **Project Management must NOT create a separate or redundant task execution engine**.

The **Workforce Tasks Engine** (implemented in Prompt 21) is the sole authoritative system of record for task lifecycle states (`draft`, `proposed`, `assigned`, `in_progress`, `blocked`, `under_review`, `completed`), delegations, dependencies, blockers, and runtime completions.

The Project Management Subsystem provides strategic portfolio oversight, customer milestone alignment, deliverable tracking, and governance above the task engine.

```
+---------------------------------------------------------------------------------------------------+
|                                 PORTFOLIO & PROJECT CONTROL PLANE                                 |
|                                                                                                   |
|    Project: "Enterprise AI Knowledge Portal" (PRJ-CUST-2026-001)                                  |
|    Stage: execution | Health: healthy | Progress: 45%                                             |
|    ├── Milestone 1: Architecture Design (achieved)                                                |
|    │     └── Deliverable: System Architecture Document (accepted_by_client)                       |
|    └── Milestone 2: RAG Core Engine & Ingestion Pipeline (in_progress)                            |
|          └── Deliverable: Retrieval API Service (submitted)                                       |
+---------------------------------------------------------------------------------------------------+
                                            │
               project_id / milestone_id    │ Direct Foreign Key Linkage
               (Schema Batch 12 Extension)  │ (Zero Duplication)
                                            ▼
+---------------------------------------------------------------------------------------------------+
|                                    WORKFORCE TASKS ENGINE (PROMPT 21)                             |
|                                                                                                   |
|    Task #1: "Design Milvus Ingestion Pipeline" (accountable_agent_id: AGT-TECH-001)               |
|             Status: completed | Progress: 100% | project_id: 1, milestone_id: 1                  |
|    Task #2: "Implement BM25 Hybrid Scoring" (accountable_agent_id: AGT-DEV-001)                  |
|             Status: in_progress | Progress: 60% | project_id: 1, milestone_id: 2                 |
|    Task #3: "Build Frontend Search Interface" (accountable_agent_id: AGT-DEV-001)                |
|             Status: assigned | Progress: 0% | project_id: 1, milestone_id: 2                      |
+---------------------------------------------------------------------------------------------------+
```

---

## 2. Mathematical Progress Recalculation Algorithm

Project progress percentage is calculated dynamically from linked milestones and tasks via `ProjectService::recalculateProjectProgress(int $projectId)`:

$$\text{Project Progress \%} = \min\left(100, \max\left(0, \text{round}\left(S_{\text{milestone}} \times 0.60 + S_{\text{task}} \times 0.40\right)\right)\right)$$

Where:
1. **Milestone Score ($S_{\text{milestone}}$):**
   $$S_{\text{milestone}} = \frac{\sum M_{\text{weight}}}{N_{\text{milestones}}} \times 100$$
   - Achieved Milestone = $1.0$ weight.
   - In-Progress Milestone = $0.4$ weight.
   - Pending / Delayed Milestone = $0.0$ weight.

2. **Task Score ($S_{\text{task}}$):**
   $$S_{\text{task}} = \frac{\sum T_{\text{weight}}}{N_{\text{tasks}}} \times 100$$
   - Completed / Accepted / Verified Task = $1.0$ weight.
   - In-Progress Task = $0.5$ weight.
   - Assigned / Draft Task = $0.0$ weight.

3. **Fallback Handling:**
   - If a project has only milestones and no linked execution tasks yet, progress is computed directly as $100\% \times S_{\text{milestone}}$.
   - If a project has only tasks and no milestone breakdown, progress is computed directly as $100\% \times S_{\text{task}}$.

---

## 3. Bi-Directional Linkage Operations

- **Linking Existing Task:** `POST /api/v1/projects/{projectId}/tasks/link` binds `task_id` and optional `milestone_id`. It automatically triggers recalculation and writes an audit event.
- **Unlinking Task:** `POST /api/v1/projects/{projectId}/tasks/unlink` detaches the task and re-evaluates project progress.
- **Task Querying:** `GET /api/v1/projects/{projectId}/tasks` retrieves all tasks linked to the project with assignee details and milestone labels, completely unified with the workforce task board.
