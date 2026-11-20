# MCE AI Office — Project Governance & Financial Control Specification

**Document Version:** 1.0  
**Status:** Approved & Implemented  
**Author:** Senior Enterprise Project Management System Architect  
**Classification:** Financial & Corporate Governance Architecture

---

## 1. Governance Foundation & Delegated Authority (DoA)

MCE operates under a formalized **Delegated Authority (DoA)** hierarchy establishing precise boundaries between Human Owner prerogatives and autonomous AI executive actions:

```
+---------------------------------------------------------------------------------------------------+
|                                  MCE DELEGATED AUTHORITY TIERS                                    |
+---------------------------------------------------------------------------------------------------+
|  [ Level A — Human Owner Exclusive Authority ]                                                   |
|  - Commercial contracts execution and external commitments                                        |
|  - Project budgets > IDR 5,000,000 approval or increase                                           |
|  - Scope change requests classified as Level A                                                    |
|  - Company legal policies and overall strategic initiatives                                       |
+---------------------------------------------------------------------------------------------------+
|  [ Level B — Continuous Executive Board Autonomous Authority ]                                    |
|  - Autonomous initiation of Internal Initiatives with budget <= IDR 5,000,000                      |
|  - Multi-agent workforce allocations and sprint assignments                                       |
|  - Approval of internal technical deliverables and milestone sign-offs                            |
|  - Change requests <= IDR 5,000,000 budget impact                                                 |
+---------------------------------------------------------------------------------------------------+
|  [ Level C — Project Manager / Delivery Lead Routine Authority ]                                  |
|  - Task assignment, milestone sequence planning, deliverable review submissions                   |
|  - Risk and issue mitigation actions within authorized resource allocations                       |
|  - Day-to-day agile sprint adjustments without budget change                                      |
+---------------------------------------------------------------------------------------------------+
```

---

## 2. Financial Governance & Budget Control

### 2.1 Budget Lifecycle
Every project defines three distinct financial parameters in Indonesian Rupiah (IDR):
1. **`proposed_budget`:** Estimated cost required during initiation.
2. **`approved_budget_limit`:** Strict expenditure ceiling authorized by the appropriate governance tier.
3. **`actual_cost_incurred`:** Realized expenditure calculated dynamically from resource allocations:

$$\text{Actual Cost} = \sum_{r \in \text{Resources}} \left(\text{utilized\_quantity}_r \times \text{unit\_cost\_idr}_r\right)$$

### 2.2 Scope Change Request (CR) Governance
When a project requires additional scope, time, or funds, a formal Change Request is submitted (`POST /api/v1/projects/{id}/change-requests`):
- If `budget_impact_amount` $>$ IDR 5,000,000 $\rightarrow$ Automatically routed to **DoA Level A** (Human Owner approval mandatory).
- If `budget_impact_amount` $\le$ IDR 5,000,000 $\rightarrow$ Routed to **DoA Level B** (Continuous Executive Board approval authorized).
- Upon approval, the project's `approved_budget_limit` is incremented accordingly, and an immutable audit entry is written.

### 2.3 Non-Accounting Clarification
This subsystem provides operational project financial governance. It is NOT a full enterprise accounting/ERP system or payroll engine. Agent Welfare Tickets (Prompt 24) remain governed by their separate ledger.
