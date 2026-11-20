# QUALIFIED AGENT REPLACEMENT MANAGEMENT
## MCE AI Office — Candidate Discovery, Acceptance, Approval & Atomic Activation
**Document Code:** `MCE-ARCH-REP-2026-V1`  
**Classification:** Controlled Architecture Specification  
**Implementation Milestone:** Prompt 20 — Agent Scheduling, Shift Coverage, Replacement & Wallbook Handover  

---

## 1. Executive Summary & Policy Boundary

In enterprise workforce operations, an AI Agent cannot enter an off-duty period or abandon a scheduled duty without an approved, qualified replacement. 

A qualified replacement candidate must satisfy strict prerequisite checks:
1. **Relevant Skills**: Holds verified competencies matching the outgoing agent's responsibilities.
2. **Organizational Eligibility**: Belongs to the same or compatible division.
3. **Active Workforce State**: Holds `active` lifecycle state in `ai_agent_registry`.
4. **Availability & Zero Conflicts**: Has **zero** overlapping working assignments on that date and no conflicting approved leave.

---

## 2. Replacement Lifecycle State Machine

```
[Candidate Discovery]
          │
          ▼
    [PROPOSED] ── (Outgoing Agent or Lead initiates Proposal)
          │
          ▼
    [ACCEPTED] ── (Incoming Agent formally acknowledges & accepts)
          │
          ▼
    [APPROVED] ── (Supervisor / Human Lead validates and approves)
          │
          ▼
   [ACTIVATED] ── (Atomic DB Transaction: reassigns outgoing, creates incoming duty)
```

---

## 3. Algorithmic Candidate Discovery

The service `AgentReplacementService::findReplacementCandidates($dutyAssignmentId)` scores and ranks potential replacements using the following formula:

$$\text{Score} = \text{UnitCompatibilityScore} + \sum (\text{MatchingSkill} \times 20)$$

- **Same Unit**: 40 points base score.
- **Cross-Unit**: 20 points base score.
- **Each Matching Skill**: 20 points.
- **Conflicting Duty Assignment**: Immediate elimination (Candidate is excluded from the discovery list).
- **Approved Leave**: Immediate elimination.

---

## 4. Atomic Replacement Activation & Historical Preservation

When a replacement is activated via `activateReplacement($id)`:
1. **Outgoing Duty Preservation**: The original `workforce_duty_assignments` record is updated to `status = 'reassigned'` and points to `replacement_proposal_id`. **The row is NEVER overwritten or deleted.**
2. **Incoming Duty Creation**: A new `workforce_duty_assignments` record is created for the replacement agent with `status = 'scheduled'`.
3. **Proposal Ratification**: The proposal is updated to `status = 'activated'` with `activated_at = NOW()`.
4. **Audit Recording**: An immutable event is logged in `workforce_scheduling_audit`.
5. **Database Transaction**: Steps 1–4 execute inside a MySQL ACID transaction (`$this->db->transStart()` / `$this->db->transComplete()`). If any step fails, the entire transition is rolled back.
