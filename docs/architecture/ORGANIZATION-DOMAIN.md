# MCE AI Office — Organization Domain Architecture

## 1. Executive Summary

The Organization Domain establishes a dynamic, multi-tier organizational hierarchy capable of representing corporate structures ranging from enterprise roots down to autonomous AI project groups. In accordance with the approved MCE AI Office architecture, organizational units, organizational positions, and historical appointments are decoupled to ensure corporate governance continuity across human executives and autonomous AI agents.

## 2. Core Entities & Relational Design

```
+-------------------------------------------------------+
|                 organization_units                    |
|  - id: INT (PK)                                       |
|  - parent_id: INT (FK, self-referencing, nullable)    |
|  - code: VARCHAR(50) UNIQUE                           |
|  - name: VARCHAR(150)                                 |
|  - type: ENUM(root, executive_body, division, etc.)   |
|  - division_key: VARCHAR(50)                          |
|  - status: ENUM(active, restructuring, suspended, ...) |
+-------------------------------------------------------+
                           | 1
                           |
                           | N
+-------------------------------------------------------+
|                organization_positions                 |
|  - id: INT (PK)                                       |
|  - unit_id: INT (FK -> organization_units.id)         |
|  - code: VARCHAR(50) UNIQUE                           |
|  - title: VARCHAR(150)                                |
|  - level: ENUM(owner, executive, director, head, ...) |
|  - authority_profile_id: INT (FK -> authority_profile)|
|  - is_leadership: TINYINT(1)                          |
+-------------------------------------------------------+
                           | 1
                           |
                           | N (Historical Timeline)
+-------------------------------------------------------+
|              organization_appointments                |
|  - id: INT (PK)                                       |
|  - position_id: INT (FK -> organization_positions.id) |
|  - unit_id: INT (FK -> organization_units.id)         |
|  - appointee_type: ENUM('human', 'agent')             |
|  - appointee_id: VARCHAR(100)                         |
|  - appointee_name: VARCHAR(150)                       |
|  - status: ENUM(active, acting, interim, ended, ...)  |
|  - start_date / end_date                              |
+-------------------------------------------------------+
```

## 3. Structural Capabilities

### 3.1 Multi-Level Dynamic Hierarchy
- Hierarchy depth is theoretically unbounded, supporting:
  1. `root`: Company enterprise entity (`ORG-MCE`).
  2. `executive_body`: Executive leadership council (`ORG-EXEC`).
  3. `division`: 6 verified operational & technical divisions (`technology`, `operations`, `services`, `hardware`, `commerce`, `academy`).
  4. `department`: Functional operational areas.
  5. `sub_division`: Domain sub-units.
  6. `unit`: Focused technical, operational, or business teams.
  7. `team` / `project_group`: Ad-hoc and autonomous multi-agent task squads.

### 3.2 Circular Parent Prevention (`isCircularParent`)
To ensure structural data integrity, `OrganizationService::isCircularParent(int $unitId, int $newParentId)` performs cycle detection traversing upward ancestor chains. Any operation attempting to place a unit as a child of its own descendant is blocked with an HTTP 400 Bad Request exception before database commits.

### 3.3 Position Decoupling & Appointment History
- Positions exist independently of the individual or AI agent assigned to them.
- When an appointee rotates or departs, their appointment record transitions to `ended` with a timestamp and audit reason. Historical continuity is preserved for audit trails, task attributions, and Delegation of Authority records.
- Positions explicitly support both `human` (Owner, System Admins, Auditors) and `agent` (Director AI, Division Heads, Specialists) appointments.

### 3.4 Reporting Relationships Graph
Reporting structures are maintained via `reporting_relationships`, distinct from geographic or functional unit trees:
- `direct`: Traditional line management.
- `functional`: Cross-functional reporting (e.g., Academy curriculum alignment).
- `executive`: Continuous Executive Board oversight.
- `advisory`: Human Owner strategic counsel and mentorship.
