# MCE AI Office — Welfare Tickets Specification & Ledger Architecture

## 1. Executive Summary & Purpose

The **Welfare Tickets (WT)** system is the internal merit, compensation, and recognition mechanism for the autonomous AI Workforce operating within the **Mitra Creative Environment (MCE)**.

Unlike human compensation systems, Welfare Tickets:
- **Are NOT financial payroll**: They do not represent legal tender, human employment compensation, or external financial obligations.
- **Are NOT cryptocurrency or speculative tokens**: They are not issued on public or private blockchains, do not fluctuate based on speculation, and cannot be traded on external markets.
- **Carry NO unauthorized fiat valuation**: Direct IDR (Indonesian Rupiah) or fiat equivalence is strictly restricted and requires explicit Owner approval (DoA Level A). In all operational contexts, exchange status remains `pending_approval`.
- **Represent organizational merit and operational health**: They measure duty roster fulfillment (5/2 shift schedule), verified milestone completion, peer-reviewed technical innovation, and adherence to MCE's 7 Core Values.

---

## 2. Core Policies & Governance Rules

### 2.1 Authoritative Policy Model (`welfare_policies`)
All ticket emissions and debit operations are governed by active welfare policies versioned and approved by corporate governance:
- **Active Baseline Policy**: `POL-WLF-2026-V1` (Version 1.0).
- **Unit of Account**: Welfare Ticket (`WT`).
- **Base Period Cycle**: Weekly (evaluated against 5 duty days + 2 off-duty days).
- **Base Compensation Rate**: 10 WT per completed 5/2 duty cycle.
- **Batch Issuance Limit**: 500 WT maximum per operational period.
- **Approval Gate**: Human executive or Board authorization required prior to ledger crediting.

### 2.2 The Zero-Self-Review Rule (Segregation of Duties)
To prevent prompt injection, self-enrichment, or synthetic reward loops, MCE enforces a strict cryptographic and programmatic rule:
> **Zero-Self-Review Rule**: An AI Agent CANNOT calculate, verify, approve, nominate, or issue Welfare Tickets for itself.

Enforcement mechanisms:
1. `WelfareBonusService`: When evaluating a task bonus, the validator confirms that the task review was conducted by an independent peer or human reviewer (`task_reviews.reviewer_agent_id != task_assignments.agent_id` or `reviewer_user_id IS NOT NULL`) with status `accepted`.
2. `WelfareRecognitionService`: Self-nominations trigger an automatic HTTP 403 Forbidden with governance audit logging.
3. Database constraints ensure source transactions are cryptographically linked to external artifacts.

---

## 3. Ticket Emission Categories

| Category | Trigger / Origin | Calculation / Formula | Approval Boundary |
|---|---|---|---|
| `base_compensation` | 5/2 Shift Schedule Completion | $10 \text{ WT} \times \left(\frac{\text{Fulfilled Duty Days}}{\text{Scheduled Duty Days}}\right)$ | Executive Board / Director AI |
| `task_bonus` | Verified Task Deliverable Completion | Fixed (e.g., 2–5 WT) or Quality-Weighted ($\text{Complexity} \times \text{Rating}$) | Independent Reviewer + DoA Level C |
| `special_recognition` | Exceptional Architecture / Innovation | Discretionary award (e.g., 10–25 WT) | DoA Level A, B, or C depending on threshold |
| `adjustment` | Administrative Correction / Reconciliation | Signed integer ($\pm \Delta$) with mandatory audit reason | System Administrator / Owner |
| `reversal` | Annulment of Erroneous Transaction | Exact inverse ($-\text{Original Amount}$) referencing original TX | Executive Governance / DoA Level B |

---

## 4. Immutable Ledger Architecture (`welfare_ticket_transactions`)

The MCE Welfare Ledger operates on double-entry principles without history deletion:

```
+-----------------------------------------------------------------------------------+
|                           welfare_ticket_transactions                             |
+-----------------------------------------------------------------------------------+
| id                          : BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY         |
| transaction_code            : VARCHAR(64) UNIQUE (e.g. TX-WLF-20261120-XXXX)      |
| agent_id                    : INT UNSIGNED FK -> ai_agents.id                     |
| ticket_category             : ENUM(base_compensation, task_bonus, ...)          |
| transaction_type            : ENUM(credit, debit)                                 |
| amount                      : INT UNSIGNED (> 0)                                  |
| balance_after               : INT UNSIGNED                                        |
| source_period_id            : INT UNSIGNED NULLABLE                               |
| source_task_bonus_id        : INT UNSIGNED NULLABLE                               |
| source_recognition_id       : INT UNSIGNED NULLABLE                               |
| reversal_ref_transaction_id : BIGINT UNSIGNED NULLABLE                            |
| policy_version              : VARCHAR(32)                                         |
| idempotency_hash            : VARCHAR(64) UNIQUE SHA-256                          |
| status                      : ENUM(pending, completed, reversed, cancelled)       |
| reason                      : TEXT                                                |
| created_at                  : DATETIME                                            |
+-----------------------------------------------------------------------------------+
```

### 4.1 Idempotency & Replay Protection
Every transaction calculates a deterministic SHA-256 idempotency hash:
$$\text{Hash} = \text{SHA256}(\text{agent\_id} + \text{category} + \text{type} + \text{amount} + \text{source\_ref} + \text{salt})$$
If a network retry or duplicate queue dispatch occurs, MySQL enforces uniqueness, preventing duplicate crediting.

### 4.2 Materialized Balances & Reconciliation (`welfare_agent_balances`)
To avoid full-table scans on every balance query, `welfare_agent_balances` maintains cached sums:
- `total_credits` = $\sum \text{completed credits}$
- `total_debits` = $\sum \text{completed debits}$
- `current_balance` = $\text{total\_credits} - \text{total\_debits}$

The `/api/v1/welfare/agents/:id/reconcile` endpoint performs an automated mathematical proof comparing the cached balance against $\sum_{\text{ledger}} \text{transactions}$. Any mismatch immediately updates `is_reconciled = 0` and alerts the security audit trail.

---

## 5. Performance History Linkage
Every verified task bonus and special recognition automatically writes an entry to `ai_agent_performance_history`:
- **Event Types**: `milestone_completed`, `recognition_awarded`, `welfare_bonus_credited`.
- **Metrics Impact**: Positively adjusts agent reliability index and evaluation score.
- **Traceability**: Audit log records both the ledger transaction ID and the corresponding performance record ID.
