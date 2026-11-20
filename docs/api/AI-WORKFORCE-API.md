# AI AGENT WORKFORCE & REGISTRY REST API SPECIFICATION
## MCE AI Office — API Contract & Endpoint Documentation
**Document Code:** `MCE-API-WFD-2026-V1`  
**Classification:** Controlled Technical Specification  
**Current Milestone:** Prompt 19 — AI Agent Registry, Identity Mapping & Workforce Lifecycle Implementation  

---

## 1. Base URL & Security Standards

- **Base URL:** `/api/v1/workforce`
- **Security:** Requires valid Bearer Token in `Authorization: Bearer <token>` header for state mutations.
- **Envelope Format:** Standard MCE JSON envelope (`status`, `code`, `message`, `data`, `meta`, `timestamp`).

---

## 2. API Endpoints Reference

### 2.1 Workforce Metrics
- **Endpoint:** `GET /api/v1/workforce/metrics`
- **Description:** Returns aggregate workforce numbers (total registered, active agents, idle, working, on leave, by classification, by lifecycle state).
- **Response `data`:**
  ```json
  {
    "total_registered_agents": 6,
    "active_agents": 6,
    "operational_status": { "idle": 6, "working": 0, "on_leave": 0, "handover_in_progress": 0, "offline": 0 },
    "by_classification": { "executive": 1, "managerial": 4, "specialist": 1, "operational": 0, "support": 0 },
    "by_lifecycle_state": { "active": 6 }
  }
  ```

---

### 2.2 Agent Registry Management
- **`GET /api/v1/workforce/agents`**: Paginated listing of agents. Supports query parameters `lifecycle_state`, `operational_status`, `classification`, `unit_id`, `search`, `limit`, `offset`.
- **`GET /api/v1/workforce/agents/(:num)`**: Returns complete Agent details including profile, active assignment, active skills, active responsibilities, and runtime mapping preview.
- **`POST /api/v1/workforce/agents`**: Register a new agent identity in `draft` state.
  - **Body:** `{ "name": "Security Auditor AI", "agent_code": "AGT-SEC-001", "classification": "specialist", "primary_unit_id": 1, "description": "Responsible for automated penetration testing." }`
  - **Response Code:** `201 Created`.
- **`PUT /api/v1/workforce/agents/(:num)`**: Update agent editable metadata (`display_name`, `classification`, `roster_schedule_type`, `welfare_tier`, `designated_replacement_id`).
- **`PUT /api/v1/workforce/agents/(:num)/profile`**: Update extended profile (`organizational_purpose`, `primary_responsibilities_summary`, `core_capabilities_summary`, `communication_preferences`).

---

### 2.3 Workforce Lifecycle State Machine
- **`GET /api/v1/workforce/agents/(:num)/lifecycle`**: List full chronological history of lifecycle state changes from `ai_agent_lifecycle_events`.
- **`POST /api/v1/workforce/agents/(:num)/transition`**: Transition agent to a new lifecycle state.
  - **Body:** `{ "to_state": "proposed", "reason": "Draft profile completed; submitted for managerial evaluation." }`
  - **Validation:** Strict state matrix check. Rejects illegal transitions with `400 Bad Request`.

---

### 2.4 Organizational Assignments & Transfers
- **`GET /api/v1/workforce/agents/(:num)/assignments`**: Full assignment history (past and present).
- **`POST /api/v1/workforce/agents/(:num)/assignments`**: Assign or transfer an agent to a new unit/position.
  - **Body:** `{ "unit_id": 2, "position_id": 3, "assignment_role": "primary", "transfer_reason": "Promoted to Tech Lead." }`
  - **Behavior:** Closes existing active assignment as `transferred`, creates new active record, and updates `primary_unit_id` in registry.
- **`POST /api/v1/workforce/assignments/(:num)/end`**: Formally end an assignment.

---

### 2.5 Skills & Competencies
- **`GET /api/v1/workforce/skills`**: Controlled list of skills from `skill_catalogue`. Supports `category` filter.
- **`POST /api/v1/workforce/skills`**: Add a new skill to the enterprise catalogue.
- **`GET /api/v1/workforce/agents/(:num)/skills`**: List skills assigned to an agent.
- **`POST /api/v1/workforce/agents/(:num)/skills`**: Assign a skill to an agent with proficiency level (`beginner`, `intermediate`, `advanced`, `expert`, `master`).
- **`POST /api/v1/workforce/skills/verify`**: Verify an agent's skill proficiency.
  - **Body:** `{ "agent_id": 1, "skill_id": 2, "verification_status": "certified", "notes": "Passed practical code evaluation." }`

---

### 2.6 Individual Agent Performance History
- **`GET /api/v1/workforce/agents/(:num)/performance-history`**: Chronological performance timeline.
- **`POST /api/v1/workforce/agents/(:num)/performance-history`**: Record a new performance achievement, milestone, or review.
  - **Body:** `{ "category": "milestone_achievement", "title": "Completed Architecture Blueprint", "description": "Delivered Prompt 19 specification on schedule.", "score": 98.00, "task_ref": "PROMPT-19" }`
  - **Response Code:** `201 Created`.
- **`POST /api/v1/workforce/performance-history/(:num)/verify`**: Formally verify performance evidence.
  - **Body:** `{ "evidence_status": "verified", "notes": "Verified by Lead Architect." }`
- **`POST /api/v1/workforce/performance-history/(:num)/amend`**: Amend an existing performance record using the Superseded Record Pattern.

---

### 2.7 Runtime Identity Mapping (Hermes & 9Router Boundary)
- **`GET /api/v1/workforce/agents/(:num)/runtime-mappings`**: Retrieve execution engine configuration.
- **`POST /api/v1/workforce/agents/(:num)/runtime-mappings`**: Update or bind runtime execution metadata (`provider`, `model_route_key`, `workspace_root_path`, `docker_container_ref`, `integration_status`).
  - **Security Rule:** Never returns or accepts raw API keys or secret credentials.
