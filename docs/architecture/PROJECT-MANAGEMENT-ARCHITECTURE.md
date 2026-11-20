# MCE AI Office — Project Management Architecture Specification

**Document Version:** 1.0  
**Status:** Approved & Implemented  
**Author:** Senior Enterprise Project Management System Architect  
**Classification:** Internal Governance Architecture

---

## 1. Executive Summary

MCE (Mitra Creative Environment) operates as an autonomous, AI-workforce-driven enterprise. The **Project Management, Client Engagement, Project Lifecycle & Delivery Operations Subsystem** establishes an authoritative control plane for managing delivery commitments across external clients and internal strategic initiatives.

This subsystem is explicitly designed to:
1. Provide a **dual-lifecycle project model** separating commercial client deliverables from autonomous internal initiatives.
2. Interface directly with the **Workforce Tasks Engine** (Prompt 21) without duplicating task execution mechanics.
3. Enforce **Delegated Authority (DoA Level A, B, and C)** across project initiation, budget authorization, and scope change control.
4. Provide external client data boundaries and strict security classifications (`public`, `internal`, `confidential`, `restricted`).
5. Support agile sprint delivery with milestone critical paths, deliverable verification, and formal client acceptance gates.

---

## 2. High-Level System Architecture

```
+---------------------------------------------------------------------------------------------------+
|                                       MCE AI Office Architecture                                   |
+---------------------------------------------------------------------------------------------------+
|                                                                                                   |
|  [ Human Owner ]                  [ Continuous Executive Board ]             [ External Clients ]  |
|         │                                       │                                     │           |
|         │ (DoA Level A > IDR 5M)                │ (DoA Level B <= IDR 5M)             │           |
|         ▼                                       ▼                                     ▼           |
|  ┌─────────────────────────────────────────────────────────────────────────────────────────────┐  |
|  │                        PROJECT MANAGEMENT & DELIVERY SUBSYSTEM                              │  |
|  ├──────────────────────────────┬──────────────────────────────┬──────────────────────────────┤  |
|  │    Customer Projects         │     Internal Initiatives     │      Client Management       │  |
|  │  - Lead to Warranty Support  │  - Idea to Complete/Archive  │  - Profiles & Key Contacts   │  |
|  │  - Contract & Acceptance     │  - Autonomous Board Approval │  - Confidentiality / NDA     │  |
|  │  - Milestone Critical Paths  │  - Fast-Track Sprint Cycles  │  - Engagement Histories      │  |
|  └──────────────┬───────────────┴──────────────┬───────────────┴──────────────┬───────────────┘  |
|                 │                              │                              │                   |
|                 ▼                              ▼                              ▼                   |
|  ┌─────────────────────────────────────────────────────────────────────────────────────────────┐  |
|  │                                      DOMAIN SERVICES                                        │  |
|  │  - ProjectAccessService           - ProjectLifecycleService      - ClientManagementService  │  |
|  │  - ProjectService                 - ScopeDeliverableService      - ProjectRiskIssueService  │  |
|  │  - ProjectResourceBudgetService                                                             │  |
|  └──────────────┬─────────────────────────────────────────────────────────────┬────────────────┘  |
|                 │                                                             │                   |
|                 ▼                                                             ▼                   |
|  ┌──────────────────────────────────────────────┐           ┌──────────────────────────────────┐  |
|  │          EXECUTION PLANE INTEGRATION         │           │       PERSISTENCE & AUDIT        │  |
|  │  - workforce_tasks (Prompt 21)               │           │  - MariaDB Relational Schema     │  |
|  │  - channels & meetings (Prompt 26)           │           │  - project_status_history        │  |
|  │  - wallbook & knowledge (Prompt 25)          │           │  - project_audit_events          │  |
|  │  - 9Router Model Gateway & Hermes (Prompt 22)│           │  - baseline scope snapshots      │  |
|  └──────────────────────────────────────────────┘           └──────────────────────────────────┘  |
+---------------------------------------------------------------------------------------------------+
```

---

## 3. Dual Classification Paradigm

### 3.1 Customer Projects (`customer_project`)
Customer Projects represent formal commercial engagements entered into with external corporations, institutions, or government bodies.
- **Mandatory Requirements:**
  - Foreign key binding to a registered `project_clients` entity.
  - Sponsoring authority: Human Owner or delegated Executive Board member with formal client contract reference.
  - Baselined scope version approved by both internal delivery lead and client contact.
  - Multi-tier deliverable review: Internal technical review followed by formal client acceptance sign-off.
  - 9-Stage Lifecycle: `lead` $\rightarrow$ `qualification` $\rightarrow$ `proposal` $\rightarrow$ `contracted` $\rightarrow$ `planning` $\rightarrow$ `execution` $\rightarrow$ `review_acceptance` $\rightarrow$ `delivery_closure` $\rightarrow$ `warranty_support`.

### 3.2 Internal Initiatives (`internal_initiative`)
Internal Initiatives represent platform enhancements, Virtual Headquarters expansions, artificial intelligence R&D, tooling pipelines, and organizational process improvements.
- **Mandatory Requirements:**
  - External client binding is prohibited (`client_id = null`).
  - Sponsoring authority: Continuous Executive Board (`continuous_executive_board`) operating autonomously within Delegated Authority Level B (budget ceiling $\le$ IDR 5,000,000) or Human Owner (DoA Level A if $>$ IDR 5,000,000).
  - 7-Stage Fast-Track Lifecycle: `idea_proposal` $\rightarrow$ `evaluation` $\rightarrow$ `authorized` $\rightarrow$ `planning` $\rightarrow$ `execution` $\rightarrow$ `review` $\rightarrow$ `completed_archived`.
  - Zero external client acceptance gate required; verification is conducted via internal review and Wallbook knowledge documentation.

---

## 4. Database Schema Architecture (Migration Batch 12)

Migration `2026-11-20-000012_CreateProjectManagementAndDeliverySchema` provisions 15 authoritative relational tables with safe foreign key identifiers and indexes:

| Table Name | Primary Role | Key Foreign Keys & Relations |
| :--- | :--- | :--- |
| `project_clients` | Customer enterprise profile | `created_by_user_id` $\rightarrow$ `users` |
| `project_client_contacts` | Client key stakeholder directory | `client_id` $\rightarrow$ `project_clients` |
| `projects` | Master project entity | `client_id`, `unit_id`, `project_owner_user_id`, `delivery_lead_agent_id` |
| `project_team_members` | Workforce roster & role allocation | `project_id`, `user_id`, `agent_id` $\rightarrow$ `ai_agent_registry` |
| `project_briefs` | Strategic objectives & criteria | `project_id` (Unique 1:1) |
| `project_scopes` | Versioned scope baselines | `project_id`, `approved_by_user_id` |
| `project_milestones` | Critical path timeline milestones | `project_id` |
| `project_deliverables` | Verifiable release artifacts | `project_id`, `milestone_id`, `responsible_agent_id` |
| `project_risks` | Risk registry & mitigations | `project_id`, `owner_user_id`, `owner_agent_id` |
| `project_issues` | Delivery blockers & incidents | `project_id`, `responsible_user_id`, `responsible_agent_id` |
| `project_change_requests` | Scope, cost & timeline changes | `project_id`, `requester_user_id`, `approved_by_user_id` |
| `project_resource_allocations` | Compute & capacity budgets | `project_id` |
| `project_status_history` | State transition audit log | `project_id`, `changed_by_user_id`, `changed_by_agent_id` |
| `project_templates` | Reusable lifecycle templates | N/A |
| `project_audit_events` | Immutable security audit events | `project_id` |
| `workforce_tasks` *(extension)* | Execution linkage | Added `project_id` and `milestone_id` columns |

---

## 5. Security & Access Control Model

Project data is protected via multi-tiered clearance validation handled by `ProjectAccessService`:

1. **Classification Levels:**
   - `public`: Visible to all internal staff and authorized external portal users.
   - `internal`: Accessible to all authenticated MCE workforce agents and users.
   - `confidential`: Restricted to Executive Board, Division Leads, assigned Project Managers, and explicit project team members.
   - `restricted`: Highest confidentiality tier; accessible only by Human Owner and C-level Executive Board.

2. **Action Permissions:**
   - `view`: Permitted if classification clearance is met.
   - `edit`: Requires `project_sponsor`, `project_owner`, `project_manager`, or `delivery_lead` role.
   - `advance_stage`: Requires `project_sponsor` or `project_owner` authority.
   - `approve_deliverable`: Requires internal reviewer or project manager role.
   - `client_accept`: Reserved for client sponsor or Human Owner representation.
   - `approve_change_request`: Evaluated against DoA Level (Level A: Human Owner, Level B: Executive Board).

---

## 6. Integration Architecture

- **Prompt 21 (Task Management):** Instead of creating duplicate project tasks, projects link directly to existing `workforce_tasks` records via `project_id` and `milestone_id`. Progress percentage is automatically computed from milestone achievements and task completions.
- **Prompt 26 (Internal Collaboration):** Projects link to communication channels (`#CHN-PRJ-...`) and meeting schedules, allowing executive directives and team decisions to bridge into project action items.
- **Prompt 25 (Knowledge Management):** Baselined scopes, technical RFCs, and closure acceptance certificates are archived into organizational memory and Wallbook handovers.
