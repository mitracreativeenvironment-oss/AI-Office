# MCE AI Office — Organization & Governance REST API Specification

## 1. Overview
This specification details the REST API endpoints for organizational structure, positions, appointments, reporting relationships, 7 Core Values, governance bodies, Delegation of Authority (DoA), Owner directions, and Executive decisions.

All endpoints adhere to the standardized MCE API envelope:
```json
{
  "status": "success",
  "code": 200,
  "message": "...",
  "data": { ... },
  "meta": { ... },
  "timestamp": "2026-11-20T10:15:00+00:00"
}
```

---

## 2. Organization Endpoints

### 2.1 Get Organizational Hierarchy Tree
- **Method / Path**: `GET /api/v1/organization/tree`
- **Auth**: Public / Authenticated
- **Response**: Recursive nested array of active organizational units (`children: []`).

### 2.2 List Flat Organizational Units
- **Method / Path**: `GET /api/v1/organization/units`
- **Query Params**: `status` (optional, e.g. `active`, `restructuring`)
- **Response**: Array of units with `positions_count` and `subunits_count`.

### 2.3 Get Unit Detail
- **Method / Path**: `GET /api/v1/organization/units/{id}`
- **Response**: Single unit with `parent` details, `subunits`, and associated `positions`.

### 2.4 Create Organizational Unit
- **Method / Path**: `POST /api/v1/organization/units`
- **Auth**: Bearer Token required (`mce_auth`)
- **Body**:
  ```json
  {
    "parent_id": 2,
    "code": "UNIT-DEV-SQUAD",
    "name": "Core Dev Squad",
    "type": "unit",
    "description": "Autonomous full-stack development squad",
    "division_key": "technology"
  }
  ```

### 2.5 Update Organizational Unit
- **Method / Path**: `PUT /api/v1/organization/units/{id}`
- **Auth**: Bearer Token required (`mce_auth`)
- **Note**: Triggers circular cycle detection via `isCircularParent`.

### 2.6 List Positions
- **Method / Path**: `GET /api/v1/organization/positions`
- **Query Params**: `unit_id` (optional)
- **Response**: List of positions with current active appointee (human or AI).

### 2.7 Create Position
- **Method / Path**: `POST /api/v1/organization/positions`
- **Auth**: Bearer Token required (`mce_auth`)

### 2.8 List Appointments
- **Method / Path**: `GET /api/v1/organization/appointments`
- **Query Params**: `position_id`, `unit_id`, `status`

### 2.9 Create Appointment
- **Method / Path**: `POST /api/v1/organization/appointments`
- **Auth**: Bearer Token required (`mce_auth`)
- **Body**:
  ```json
  {
    "position_id": 3,
    "appointee_type": "agent",
    "appointee_id": "tech_head",
    "appointee_name": "Tech Division Head AI",
    "status": "active"
  }
  ```

### 2.10 End Appointment
- **Method / Path**: `POST /api/v1/organization/appointments/{id}/end`
- **Auth**: Bearer Token required (`mce_auth`)

---

## 3. Governance & DoA Endpoints

### 3.1 Get 7 Core Values
- **Method / Path**: `GET /api/v1/governance/core-values`
- **Auth**: Public / Authenticated
- **Response**: 7 controlled reference values ordered by `display_order`.

### 3.2 List Governance Bodies
- **Method / Path**: `GET /api/v1/governance/bodies`
- **Response**: Bodies (`GOV-OWNER`, `GOV-BOARD`, `GOV-DIR-AI`) with active member counts.

### 3.3 List Authority Profiles (DoA)
- **Method / Path**: `GET /api/v1/governance/authority-profiles`
- **Response**: 4 Profiles (`DOA-LEVEL-A`, `DOA-LEVEL-B`, `DOA-LEVEL-C`, `DOA-OPS-ROUTINE`).

### 3.4 Authority Check
- **Method / Path**: `POST /api/v1/governance/authority-check`
- **Body**:
  ```json
  {
    "authority_level": "B",
    "amount": 3500000.00,
    "action": "marketing_campaign"
  }
  ```
- **Response**:
  ```json
  {
    "status": "success",
    "data": {
      "allowed": true,
      "authority_level": "B",
      "amount": 3500000.0,
      "action": "marketing_campaign",
      "requires_owner": false,
      "reason": "Permitted under Executive Board autonomous authority limit."
    }
  }
  ```

### 3.5 Owner Directions (Advisory vs Binding)
- **Method / Path**: `GET /api/v1/governance/directions`
- **Query Params**: `status`, `type` (`advisory` | `binding`)
- **Create**: `POST /api/v1/governance/directions` (Bearer token required)
- **Acknowledge**: `POST /api/v1/governance/directions/{id}/acknowledge`

### 3.6 Executive Decisions
- **Method / Path**: `GET /api/v1/governance/decisions`
- **Create**: `POST /api/v1/governance/decisions` (Bearer token required)
- **Ratify**: `POST /api/v1/governance/decisions/{id}/ratify`

### 3.7 Governance Audit Trail
- **Method / Path**: `GET /api/v1/governance/audit-logs`
- **Query Params**: `limit`, `offset`
- **Response**: Immutable chronological audit logs recorded across all governance events.
