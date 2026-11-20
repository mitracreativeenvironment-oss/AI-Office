# Task Execution Workflow & Quality Verification — MCE AI Office

## 1. Execution Lifecycle
The execution phase of a task operates under strict governance:
1. **Assignment & Acceptance**: An eligible agent accepts the assignment.
2. **Prerequisite Check**: `TaskDependencyService::arePrerequisitesSatisfied` verifies all inbound Finish-to-Start dependencies are satisfied.
3. **In-Progress Execution**: The agent works on the deliverables.
4. **Deliverable Submission**: Output artifacts are recorded in `task_deliverables` with versioning (`v1`, `v2`, etc.). The task automatically shifts to `under_review`.
5. **Operational Blockers**: If execution is impeded by missing information, environment failure, or authority barriers, the agent reports a blocker via `TaskReviewService::reportBlocker`, moving the task to `blocked`. Resolving the blocker automatically returns the task to `in_progress`.

## 2. Independent Peer Review Rule (Zero Self-Review)
- **Constraint**: `reviewer_agent_id !== deliverable.submitting_agent_id`.
- An executing agent can NEVER review, verify, or sign off on its own deliverable.
- A reviewer assesses:
  - Technical accuracy
  - Adherence to MCE Core Values and DoA boundaries
  - Verification checklist
- Review Outcomes:
  - `accepted`: Deliverable approved, `is_current_accepted = 1`. Task progresses toward `verified`.
  - `revision_required`: Feedback provided, task transitions to `revision_required` for author re-work.
  - `rejected`: Artifact failed quality standards.

## 3. Performance History Integration
When a reviewer records an accepted review with `is_verified_contribution = 1`, the system automatically inserts a verified contribution record into `ai_agent_performance_history`, linking the agent's organizational standing directly to audited deliverables.
