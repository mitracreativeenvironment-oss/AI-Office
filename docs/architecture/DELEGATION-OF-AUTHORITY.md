# MCE AI Office — Delegation of Authority (DoA) Architecture

## 1. Overview & Principles

The Delegation of Authority (DoA) Framework establishes clear, enforceable boundaries for financial expenditure, operational commitments, and strategic actions. The framework prevents unauthorized high-value transactions while eliminating bureaucratic bottlenecks for routine autonomous agent operations.

---

## 2. Authority Levels & Financial Thresholds

| Level | Profile Code | Authority Domain | Financial Limit | Governing Body / Position | Permitted Scope |
|-------|--------------|------------------|-----------------|---------------------------|-----------------|
| **Level A** | `DOA-LEVEL-A` | Strategic / Capital | **Unlimited** (> IDR 5.000.000) | Human Owner (`GOV-OWNER`) | Capital expenditure, company restructuring, policy veto, high outlay approvals (> IDR 5.000.000). |
| **Level B** | `DOA-LEVEL-B` | Operational / Business | **IDR 5.000.000** | Continuous Executive Board (`GOV-BOARD`) | Internal operations, business growth, marketing campaigns, task dispatch, vendor engagement up to IDR 5.000.000. |
| **Level C** | `DOA-LEVEL-C` | Operational / Dispatch | **IDR 1.000.000** | Director AI & Division Heads | Daily task decomposition, agent assignment, tool subscriptions, routine operational spend up to IDR 1.000.000. |
| **Routine** | `DOA-OPS-ROUTINE` | Technical / Non-Financial | **IDR 0.00** | Specialist & Operator Agents | Task execution, file processing, status updates, code testing, report filing without financial spend. |

---

## 3. Autonomous Autonomy vs Human Hard Gate

```
Proposed Action / Expenditure
          |
          v
  Is Financial Spend > 0?
    |                 |
   NO                YES
    |                 |
    v                 v
[Routine Ops]    Spend Amount?
Allowed           |-- <= IDR 1.000.000  --> Level C (Director AI Autonomous)
                  |-- <= IDR 5.000.000  --> Level B (Continuous Board Autonomous)
                  |-- >  IDR 5.000.000  --> Level A (MANDATORY OWNER HUMAN-IN-THE-LOOP GATE)
```

### 3.1 Autonomous Operation
- The **Continuous Executive Board** possesses delegated authority to approve internal investments and operational tasks up to **IDR 5.000.000** without notifying or seeking micro-approvals from the Owner.
- The **Director AI** can autonomously orchestrate and assign tasks costing up to **IDR 1.000.000**.

### 3.2 Human Hard Gate
- Any financial outlay exceeding **IDR 5.000.000** automatically triggers an immutable hard stop.
- The system prevents auto-execution and routes the transaction to the Human Owner approval queue.

---

## 4. Delegation Records Lifecycle

Delegations granted between individuals, governance bodies, or AI agents follow a formalized lifecycle:
1. `draft`: Delegation document drafted.
2. `submitted`: Submitted for review.
3. `approved`: Approved by grantor.
4. `active`: Legally binding and enforceable in API checks.
5. `revoked`: Explicitly revoked with an immutable audit record and reason.
6. `expired`: Automatically terminated upon reaching `end_date`.

All delegation creations and revocations are logged in `governance_audit_logs`.
