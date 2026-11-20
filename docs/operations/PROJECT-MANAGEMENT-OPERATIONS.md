# MCE AI Office — Project Management Operations Manual

**Document Version:** 1.0  
**Status:** Approved & Implemented  
**Author:** Senior Enterprise Project Management System Architect  
**Classification:** Standard Operating Procedures (SOP)

---

## 1. Operational Overview

This manual establishes standard operating procedures (SOP) for managing customer delivery contracts and internal strategic initiatives within MCE AI Office.

---

## 2. Standard Operating Procedures (SOP)

### SOP-PRJ-01: Customer Project Onboarding & Inception
1. **Client Registration:** Client profile is created in `project_clients` with enterprise tier and NDA record.
2. **Key Contacts:** Primary stakeholder and technical leads are added to `project_client_contacts`.
3. **Project Proposal:** Project record is created in `lead` / `proposal` stage with commercial budget estimate.
4. **Contract Execution:** Formal contract signed; project advanced to `contracted` stage with approved budget ceiling.
5. **Kickoff & Scope Baseline:** Project Manager agent and delivery team assigned; scope baseline v1.0 created in `project_scopes`.

### SOP-PRJ-02: Sprint Execution & Deliverable Review
1. **Milestone Tracking:** Project broken into sequence of milestones with critical path flags.
2. **Task Integration:** Implementation units created in `workforce_tasks` and linked via `project_id` and `milestone_id`.
3. **Internal Review:** When deliverable artifact is completed, developer agent calls `submitDeliverable()`. Project Manager agent conducts technical verification and marks `review_status = 'accepted'`.
4. **Client Acceptance Gate:** For customer projects, external client decision maker evaluates the artifact and grants formal acceptance (`client_accepted = 1`).

### SOP-PRJ-03: Scope Change & Budget Governance (DoA Level A/B)
1. **Change Request Logging:** If new requirements emerge, a Change Request is submitted with schedule and budget impact.
2. **Authority Routing:**
   - Impact $\le$ IDR 5,000,000 $\rightarrow$ Approved by Continuous Executive Board (DoA Level B).
   - Impact $>$ IDR 5,000,000 $\rightarrow$ Routed to Human Owner for formal sign-off (DoA Level A).
3. **Ceiling Adjustment:** Approved budget limit dynamically adjusted in master project record upon approval.

### SOP-PRJ-04: Project Closure & Delivery Handover
1. **Blocker Verification:** System confirms zero open blocker issues exist before closure is permitted.
2. **Deliverables Sign-Off:** All milestone deliverables must be in `accepted` state (and `client_accepted = 1` for customer projects).
3. **Advancement to Closure:** Project advanced to `delivery_closure` (`completed_archived` for internal initiatives).
4. **Knowledge Archival:** Architecture specs, technical manuals, and operational post-mortems archived to Wallbook (Prompt 25).
5. **Warranty Support:** For customer projects, transitions into `warranty_support` until warranty expiry date.
