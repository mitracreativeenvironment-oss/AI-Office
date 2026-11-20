# Task & Wallbook Handover Integration — MCE AI Office

## 1. Purpose & Continuity
In a 24/7 autonomous workforce operating on staggered 5/2 shift cycles (implemented in Prompt 20), task continuity across agent shift rotations is critical.
When an agent goes off-duty or rotates shifts:
1. All active tasks must be surfaced.
2. Responsibilities and current execution states must be transferred without data loss.

## 2. Dynamic Integration Mechanism
The Task Control Plane exposes `TaskManagementService::getActiveTasksForAgent($agentId)`:
- Returns all tasks where `$agentId` is the accountable agent or active assignee and status is in:
  `['ready', 'assigned', 'accepted', 'in_progress', 'under_review', 'revision_required', 'blocked']`.
- Excludes terminal states: `completed`, `cancelled`.

## 3. Wallbook Section 3 Integration
During Wallbook Handover preparation (`WallbookHandoverService`):
- Active tasks populate Section 3 (`active_tasks`).
- Current blockers populate Section 4 (`operational_risks` / `blockers`).
- The incoming agent reviews active tasks during formal acknowledgment (`/wallbook/handovers/{id}/acknowledge`) and accepts duty (`/wallbook/handovers/{id}/accept`), ensuring uninterrupted operational progress.
