# AGENT WORKFORCE LIFECYCLE STATE MACHINE
## MCE AI Office — 10-State Governance Transition Matrix & Audit Logging
**Document Code:** `MCE-ARCH-ALC-2026-V1`  
**Classification:** Controlled Architecture Specification  
**Current Milestone:** Prompt 19 — AI Agent Registry, Identity Mapping & Workforce Lifecycle Implementation  

---

## 1. Executive Summary & Purpose

The **Agent Workforce Lifecycle State Machine** governs the creation, review, activation, operational suspension, and final retirement of AI Agents within MCE AI Office. AI Agents cannot transition directly from creation to active execution without passing rigorous organizational approval checkpoints.

---

## 2. The 10 Validated Lifecycle States

```
                               +---------------+
                               |     DRAFT     |
                               +-------+-------+
                                       |
                                       v
                               +---------------+
                               |   PROPOSED    |
                               +-------+-------+
                                       |
                                       v
                               +---------------+
                +------------> | UNDER_REVIEW  | <-------------+
                |              +-------+-------+               |
                |                      |                       |
                |                      v                       |
                |              +---------------+               |
                |              |   APPROVED    |               |
                |              +-------+-------+               |
                |                      |                       |
                |                      v                       |
                |              +---------------+               |
                |              | PROVISIONING  |               |
                |              |    PENDING    |               |
                |              +-------+-------+               |
                |                      |                       |
                |                      v                       |
                |              +---------------+               |
                |  +---------> |    ACTIVE     | <----------+  |
                |  |           +---+-------+---+            |  |
                |  |               |       |                |  |
                |  |   +-----------+       +------------+   |  |
                |  |   |                                |   |  |
                |  |   v                                v   |  |
                |  | +-----------------------+        +-------------+
                |  +-+ TEMPORARILY_SUSPENDED |        |    UNDER    |
                |    +-----------+-----------+        | RECONFIG    |
                |                |                    +-----+-------+
                |                |                          |
                +----------------+--------------------------+
                                 |
                                 v
                         +---------------+
                         |    RETIRED    |
                         +-------+-------+
                                 |
                                 v
                         +---------------+
                         |   ARCHIVED    |
                         +---------------+
```

### State Definitions:
1. **`draft`**: The Agent record is newly created by an architect or user. Incomplete profile or unassigned units permitted.
2. **`proposed`**: Initial configuration completed; proposed for organizational onboarding.
3. **`under_review`**: Under governance/managerial evaluation for DoA alignment, security, and necessity.
4. **`approved`**: Formally authorized by corporate governance for deployment.
5. **`provisioning_pending`**: Awaiting workspace provisioning, Hermes runtime container initialization, or credentials binding.
6. **`active`**: Fully operational workforce member participating in tasks, reporting, and shifts.
7. **`temporarily_suspended`**: Execution halted due to maintenance, investigation, or temporary policy restriction.
8. **`under_reconfiguration`**: Operational duties paused while undergoing prompt adjustment, model re-binding, or skill retraining.
9. **`retired`**: Permanently decommissioned from active service. Retains all historical records. Read-only.
10. **`archived`**: Cold storage state for retired agents; preserved indefinitely for historical governance audits.

---

## 3. Strict Transition Matrix Enforcement

The backend `AgentWorkforceService` enforces the following deterministic transition matrix. Any attempt to perform an illegal transition (e.g., `draft` directly to `active`) is rejected with an HTTP 400 Bad Request error:

| From State | Permitted Destination States | Required Conditions & Roles |
| :--- | :--- | :--- |
| `draft` | `proposed`, `archived` | Agent name and valid agent_code must be set. |
| `proposed` | `under_review`, `draft` | Must be routed to governance or management. |
| `under_review` | `approved`, `draft` | Requires managerial or executive role review. |
| `approved` | `provisioning_pending`, `draft` | Governance approval recorded with user ID & timestamp. |
| `provisioning_pending` | `active`, `under_reconfiguration` | Valid active assignment and runtime mapping required. |
| `active` | `temporarily_suspended`, `under_reconfiguration`, `retired` | Valid operational state change; reasons logged. |
| `temporarily_suspended`| `active`, `under_reconfiguration`, `retired` | Suspension reason resolved. |
| `under_reconfiguration`| `under_review`, `active`, `retired` | Reconfiguration validated. |
| `retired` | `archived` | Decommissioning completed. |
| `archived` | *(Terminal State)* | Immutable historical preservation. |

---

## 4. Lifecycle Audit Log (`ai_agent_lifecycle_events`)

Every state transition generates an immutable record in `ai_agent_lifecycle_events`:
- `agent_id`: FK to `ai_agent_registry`.
- `from_state` / `to_state`: Exactly captures previous and target states.
- `initiated_by_user_id`: Authenticated user who authorized the transition.
- `initiated_by_name`: Display name or system actor.
- `reason`: Mandatory audit explanation describing why the state was modified.
- `created_at`: Chronological UTC timestamp.
