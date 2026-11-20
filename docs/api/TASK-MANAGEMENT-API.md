# Task Management, Delegation & Collaboration API Specification

All endpoints are grouped under `/api/v1/workforce` and require Bearer Token Authentication.

## Endpoints Summary

### Tasks
- `GET /api/v1/workforce/tasks`: List tasks with query filtering (`unit_id`, `status`, `priority`, `category`, `accountable_agent_id`, `search`, `limit`, `offset`).
- `GET /api/v1/workforce/tasks/metrics`: Aggregate metrics (total, in_progress, blocked, ready, under_review, completed, avg_progress).
- `GET /api/v1/workforce/tasks/{id}`: Detailed task inspect with subtasks, deliverables, blockers, and assignments.
- `POST /api/v1/workforce/tasks`: Create task in `draft` or `ready` state.
- `POST /api/v1/workforce/tasks/{id}/assign`: Assign lead agent or collaborator.
- `POST /api/v1/workforce/tasks/{id}/transition`: Transition task through 17-state lifecycle. Payload: `{"target_status": "in_progress", "reason": "Execution started"}`.
- `GET /api/v1/workforce/agents/{agentId}/active-tasks`: Retrieve active tasks for an agent (for Wallbook handover continuity).

### Delegations
- `GET /api/v1/workforce/tasks/delegations`: List all delegations (filterable by `task_id`, `status`).
- `POST /api/v1/workforce/tasks/delegations`: Propose formal task delegation. Validates delegator authority and prevents circular cycles.
- `POST /api/v1/workforce/tasks/delegations/{id}/accept`: Delegated agent formally accepts scope.

### Multi-Agent Collaborations
- `GET /api/v1/workforce/tasks/collaborations`: List collaboration groups.
- `POST /api/v1/workforce/tasks/{taskId}/collaborations`: Create collaboration group workspace.
- `POST /api/v1/workforce/tasks/collaborations/{groupId}/members`: Add specialist member to collaboration.

### Dependencies
- `GET /api/v1/workforce/tasks/{id}/dependencies`: Get inbound/outbound dependencies.
- `POST /api/v1/workforce/tasks/{id}/dependencies`: Add dependency (`finish_to_start`, `start_to_start`, `finish_to_finish`). Traverses directed graph to prevent cyclic loops.

### Deliverables & Independent Review
- `GET /api/v1/workforce/tasks/{id}/deliverables`: List deliverables with versions.
- `POST /api/v1/workforce/tasks/{id}/deliverables`: Submit output artifact (v1, v2...). Auto-transitions task to `under_review`.
- `POST /api/v1/workforce/tasks/{taskId}/deliverables/{delivId}/review`: Submit independent review. Enforces zero self-review rule. If `is_verified_contribution = 1`, links to `ai_agent_performance_history`.

### Operational Blockers
- `POST /api/v1/workforce/tasks/{id}/blockers`: Report operational impediment. Auto-transitions task to `blocked`.
- `POST /api/v1/workforce/tasks/blockers/{id}/resolve`: Resolve blocker with resolution notes. Auto-resumes task to `in_progress`.
