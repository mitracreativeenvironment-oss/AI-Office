# WORKFORCE SCHEDULING, REPLACEMENT & WALLBOOK REST API
## MCE AI Office — API Contract Specification
**Document Code:** `MCE-API-SCH-2026-V1`  
**Classification:** Controlled Technical Specification  
**Implementation Milestone:** Prompt 20 — Agent Scheduling, Shift Coverage, Replacement & Wallbook Handover  

---

## 1. Scheduling & Shifts Endpoints (`/api/v1/workforce/scheduling/*`)

### 1.1 Schedule Policies
- `GET /api/v1/workforce/scheduling/policies`: List all configured scheduling policies (5/2 Standard, 24/7 Staggered).
- `POST /api/v1/workforce/scheduling/policies`: Create a new schedule policy (Admin/Owner authorization required).

### 1.2 Shift Definitions
- `GET /api/v1/workforce/scheduling/shifts`: List all active shift definitions (Morning, Afternoon, Night, Standard Day).
- `POST /api/v1/workforce/scheduling/shifts`: Create a new shift definition with timezone and duration.

### 1.3 Rosters & Duty Assignments
- `GET /api/v1/workforce/scheduling/rosters`: List weekly/cycle rosters with status filters (`draft`, `published`, `archived`).
- `GET /api/v1/workforce/scheduling/rosters/(:num)`: Retrieve full roster details including all agent duty assignments across 7 days.
- `POST /api/v1/workforce/scheduling/rosters`: Create a new draft roster for a designated date range.
- `POST /api/v1/workforce/scheduling/rosters/(:num)/publish`: Formally publish a roster (Bearer token required).
- `POST /api/v1/workforce/scheduling/duty-assignments`: Add an individual duty assignment (with conflict checking).
- `POST /api/v1/workforce/scheduling/duty-assignments/(:num)/complete`: Mark a scheduled duty assignment as completed.

### 1.4 Agent Availability & Off-Duty Requests
- `GET /api/v1/workforce/agents/(:num)/availability`: Retrieve chronological availability blocks for an agent.
- `POST /api/v1/workforce/agents/(:num)/off-duty-requests`: Submit planned leave or off-duty request.
  - **Enforcement Rule:** If active duties are scheduled, request remains `pending` unless a qualified replacement and Wallbook handover are satisfied.

### 1.5 Operational Coverage Management
- `GET /api/v1/workforce/scheduling/coverage/requirements`: List operational staffing rules by unit, shift, and skill.
- `GET /api/v1/workforce/scheduling/coverage/status`: Real-time coverage evaluation for a date (`covered`, `partially_covered`, `uncovered`, `coverage_at_risk`, `pending_replacement`).
- `GET /api/v1/workforce/scheduling/coverage/gaps`: Calculate coverage gaps and deficient units.

---

## 2. Qualified Replacement Endpoints (`/api/v1/workforce/replacements/*`)

- `GET /api/v1/workforce/replacements`: List all replacement proposals.
- `GET /api/v1/workforce/replacements/(:num)`: Retrieve single proposal detail.
- `GET /api/v1/workforce/replacements/candidates?duty_assignment_id=(:num)`: Algorithmic discovery of eligible replacement candidates matching verified skills and zero schedule conflicts.
- `POST /api/v1/workforce/replacements`: Create a new replacement proposal in `proposed` state.
- `POST /api/v1/workforce/replacements/(:num)/accept`: Incoming agent formally accepts proposal.
- `POST /api/v1/workforce/replacements/(:num)/approve`: Supervisor/Owner approves proposal.
- `POST /api/v1/workforce/replacements/(:num)/activate`: Atomic activation: reassigns outgoing assignment, schedules incoming assignment, ratifies proposal.

---

## 3. Wallbook Operational Handover Endpoints (`/api/v1/wallbook/*`)

- `GET /api/v1/wallbook/handovers`: List handovers with status and agent filters.
- `GET /api/v1/wallbook/handovers/(:num)`: Fetch complete handover record with all 13 structured section items and acknowledgement history.
- `POST /api/v1/wallbook/handovers`: Initiate draft Wallbook handover.
- `POST /api/v1/wallbook/handovers/(:num)/items`: Add structured content item under one of the 13 categories.
- `POST /api/v1/wallbook/handovers/(:num)/submit`: Submit handover (validates presence of mandatory sections).
- `POST /api/v1/wallbook/handovers/(:num)/acknowledge`: Incoming agent acknowledges receipt.
- `POST /api/v1/wallbook/handovers/(:num)/clarify`: Incoming agent requests clarification.
- `POST /api/v1/wallbook/handovers/(:num)/accept`: Incoming agent formally accepts handover and assumes operational duty.
- `POST /api/v1/wallbook/handovers/escalate-overdue`: Automated scanner: detects and escalates overdue handovers exceeding deadline.
