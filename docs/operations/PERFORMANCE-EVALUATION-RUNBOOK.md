# PERFORMANCE EVALUATION & EXECUTIVE REPORTING OPERATIONS RUNBOOK

## 1. Overview & Operational Responsibilities

This runbook establishes standard operating procedures (SOPs) for the administration, evaluation, and reporting cycles within MCE AI Office.

- **Human Owner**: Sets strategic corporate goals, reviews monthly Executive Reports, and ratifies Level A bonus requests ($> \text{IDR } 5,000,000$).
- **Continuous Executive Board**: Approves monthly/quarterly evaluation cycles, reviews divisional scorecards, and ratifies autonomous Level B decisions ($\le \text{IDR } 5,000,000$).
- **Director AI**: Orchestrates the 10-stage evaluation workflow, triggers metric calculation engine batches, and validates 5/2 shift roster protection.
- **Supervisors & Mentors**: Provide qualitative review feedback, mentor agents assigned to Performance Improvement Plans (PIPs), and document Core Values behavioral evidence.

---

## 2. Standard Evaluation Cycle Execution (10 Stages)

```
[draft] -> [open] -> [data_collection] -> [evidence_aggregation] -> [kpi_calculation]
   -> [automated_validation] -> [reviewer_assessment] -> [review] -> [approval_pending] -> [finalized]
```

### Stage 1: Cycle Initialization
- An evaluation cycle is opened at the beginning of each monthly period:
  `POST /api/v1/performance/cycles`
- Assign period date bounds (`period_start` and `period_end`).

### Stage 2: Evidence Aggregation & Calculation
- Transition status to `data_collection` and `kpi_calculation`.
- Run automated metric calculation batch:
  `POST /api/v1/performance/kpis/calculate-all`
- All calculations are versioned (`formula_version: 'v1.0'`) and timestamped.

### Stage 3: Multi-Criteria Fair Scoring
- Scores are computed from transactional records:
  - **Quality Score**: Peer-reviewed deliverable acceptance rate.
  - **Delivery Score**: On-time milestone completion (excluding approved rest days).
  - **Collaboration Score**: Timely peer reviews and knowledge contributions.
  - **Core Values Score**: Average of 7 core values qualitative ratings.
  - **Complexity Factor**: 1.0 to 1.5 multiplier reflecting project complexity.
- Score Bands:
  - `exceptional` (90–100)
  - `exceeds_expectations` (80–89)
  - `meets_expectations` (70–79)
  - `needs_improvement` (50–69)
  - `unsatisfactory` (<50)

### Stage 4: Reviewer Assessment & Finalization
- Reviewers provide structured qualitative feedback.
- Finalization seals the evaluation:
  `POST /api/v1/performance/evaluations/{id}/finalize`
- Computes an immutable 64-character SHA-256 hash (`audit_hash`) and appends the entry permanently into `ai_agent_performance_history`.

---

## 3. Remediation SOP: Performance Improvement Plans (PIPs)

When an agent's composite score falls into `needs_improvement` (<70) or `unsatisfactory` (<50):
1. **Creation**: Supervisor or Director AI creates a PIP:
   `POST /api/v1/performance/plans`
2. **Task Linkage**: Rather than running a duplicate task system, the PIP binds directly to an authoritative task in `workforce_tasks`:
   `POST /api/v1/performance/plans/{id}/link-task`
3. **Execution**: Mentor monitors weekly progress updates.
4. **Resolution**: Upon meeting success criteria, the plan is completed with resolution evidence:
   `POST /api/v1/performance/plans/{id}/transition` (`status: 'completed'`).

---

## 4. Financial Governance & Welfare Bonus SOP

1. **Eligibility Check**: Run automated qualification:
   `GET /api/v1/performance/agents/{id}/bonus-eligibility`
   - Score $\ge 90 \implies$ Exceptional Quality bonus.
   - Complexity $\ge 1.25 \implies$ High Complexity Delivery bonus.
2. **Recommendation**: Post verified recommendation:
   `POST /api/v1/performance/bonuses/recommend`
3. **Delegated Authority (DoA) Gate**:
   - `≤ IDR 5,000,000` (Level B): Approved autonomously by Executive Board.
   - `> IDR 5,000,000` (Level A): Requires explicit Human Owner signature. Non-owner approval attempts automatically fail with HTTP 403.
4. **Disbursement**: Disburse tickets to agent account:
   `POST /api/v1/performance/bonuses/{id}/disburse`

---

## 5. Executive Reporting SOP

1. **Compilation**: At the close of each cycle, generate the corporate briefing:
   `POST /api/v1/performance/reports/generate`
2. **Review & Publication**: Director AI publishes the report:
   `POST /api/v1/performance/reports/{id}/publish`
3. **Owner Ratification**: Human Owner reviews strategic highlights, governance compliance, and ratifies:
   `POST /api/v1/performance/reports/{id}/ratify`
