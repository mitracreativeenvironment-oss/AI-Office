# Multi-Agent Execution Orchestration & Lifecycle Architecture

## 1. End-to-End Orchestration Workflow

The orchestration layer links CI4 task management with Hermes runtime execution and 9Router model routing:

```mermaid
sequenceDiagram
    autonumber
    actor User as Human Operator / Supervisor
    participant CI4 as CI4 Business Control Plane
    participant DB as MySQL System of Record
    participant Hermes as Hermes Runtime Engine
    participant Router as 9Router Model Gateway

    User->>CI4: POST /runtime/executions (task_id, agent_id)
    CI4->>DB: Evaluate 15 Preconditions
    DB-->>CI4: Preconditions Verified
    CI4->>DB: Create Execution Session (dispatched)
    CI4->>Hermes: Dispatch Scoped Context & Tools
    Hermes->>Router: Model Completion Request (ROUTE-*)
    Router-->>Hermes: Streamed / Completed Reasoning
    Hermes->>Hermes: Execute Sandbox Tools
    Hermes-->>CI4: Return Session Output & Deliverable
    CI4->>DB: Update Session Status (completed)
    CI4->>DB: Submit Task Deliverable Artifact
    CI4->>DB: Record Execution Event & Model Usage
    CI4-->>User: HTTP 201 Created (Session Details)
```

---

## 2. Idempotency & Fault Tolerance

1. **Deterministic Deduplication**: Dispatches with matching idempotency keys return existing sessions rather than spawning concurrent duplicate processes.
2. **Cancellation Protocol**: Operators can cancel long-running or stalled sessions via `POST /runtime/executions/{id}/cancel`.
3. **Execution Attempt Tracking**: Every dispatch attempts counter (`attempt_number`) is logged in `task_execution_attempts`.
4. **Deliverable Auto-Submission**: On session completion, generated output is submitted directly as a `task_deliverables` record (`source_code` or `report`) ready for independent review.
