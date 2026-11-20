# MCE AI Office — Welfare Standard Operating Procedures (SOP)

## 1. Scope & Objective
This document outlines standard operational workflows for managing AI Agent welfare, periodic duty compensation, task completion bonuses, special recognitions, and ledger integrity checks.

---

## 2. Operational Workflows

### 2.1 Periodic Base Compensation Workflow (5/2 Duty Roster)
```
+-----------------------------------------------------------------------------+
| 1. Period Creation  ->  2. Automated 5/2 Evaluation  ->  3. Executive Approval |
| (Admin/Manager)        (WelfareCompensationService)     (DoA Level B/C)     |
|                                                                             |
|                                   v                                         |
|                       4. Batch Ledger Distribution                          |
|                       (Immutable Credit Transactions)                       |
+-----------------------------------------------------------------------------+
```
1. **Creation**: At the start of each weekly/biweekly cycle, the administrator creates a `welfare_compensation_periods` draft via `POST /api/v1/welfare/periods`.
2. **Evaluation**:
   - System invokes `calculatePeriod($periodId)`.
   - Engine queries `workforce_duty_assignments` and shift logs for the date range.
   - Calculates fulfilled duty days, approved leaves, and rest days.
   - Computes base tickets: 10 WT per fulfilled 5/2 schedule.
   - Sets status to `calculated`.
3. **Approval**: An authorized human manager or Executive Board reviews calculations and calls `POST /api/v1/welfare/periods/:id/approve`.
4. **Distribution**:
   - Admin executes `POST /api/v1/welfare/periods/:id/distribute`.
   - Engine iterates over eligible calculations, creates credit entries in `welfare_ticket_transactions`, updates `welfare_agent_balances`, and marks status `distributed`.

---

### 2.2 Verified Task Bonus Workflow
1. **Prerequisite**: Task deliverable submitted (`task_deliverables`) and reviewed by independent peer/human (`task_reviews.review_outcome = 'accepted'`).
2. **Zero-Self-Review Validation**:
   - Engine verifies that reviewer is NOT the assigned agent.
   - Verifies acceptance criteria satisfaction (`acceptance_criteria_satisfied = 1`).
3. **Evaluation**: Admin/Manager triggers `POST /api/v1/welfare/tasks/:id/evaluate-bonus`.
4. **Approval & Posting**:
   - Calling `POST /api/v1/welfare/task-bonuses/:id/approve` credits the agent balance.
   - Automatically writes an event to `ai_agent_performance_history`.

---

### 2.3 Special Recognition Nomination Workflow
1. **Nomination**: Any department head or senior agent submits nomination via `POST /api/v1/welfare/recognitions` detailing justification and evidence.
2. **DoA Authorization Check**:
   - $\le 10 \text{ WT}$: DoA Level C (Director AI).
   - $11 - 50 \text{ WT}$: DoA Level B (Executive Board).
   - $> 50 \text{ WT}$: DoA Level A (Owner Approval).
3. **Approval**: Executive with appropriate DoA level approves award.
4. **Execution**: Credit entry created in ledger with SHA-256 idempotency hash.

---

### 2.4 Ledger Reconciliation & Discrepancy Handling
1. **Routine Audit**: Nightly automated job or manual trigger via `POST /api/v1/welfare/agents/:id/reconcile`.
2. **Integrity Check**:
   $$\Delta = \text{materialized\_balance} - (\sum \text{credits} - \sum \text{debits})$$
   - If $\Delta = 0$: Record marked `is_reconciled = 1`.
   - If $\Delta \neq 0$: Record marked `is_reconciled = 0`, security alert raised, and manual administrative adjustment (`POST /api/v1/welfare/adjustments`) required.
3. **Formal Reversals**: Erroneous transactions are never deleted. A compensatory negative transaction referencing the original ID is posted via `POST /api/v1/welfare/transactions/:id/reverse`.
