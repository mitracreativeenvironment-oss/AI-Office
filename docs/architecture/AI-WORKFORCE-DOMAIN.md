# AI WORKFORCE DOMAIN & ORGANIZATIONAL INTEGRATION
## MCE AI Office — Structural Placement, Competency Catalogue & Historical Continuity
**Document Code:** `MCE-ARCH-WFD-2026-V1`  
**Classification:** Controlled Architecture Specification  
**Current Milestone:** Prompt 19 — AI Agent Registry, Identity Mapping & Workforce Lifecycle Implementation  

---

## 1. Domain Overview

The **AI Workforce Domain** establishes how AI Agents exist within the corporate hierarchy of **PT Mandiri Cyber Ekosistem (MCE)**. Rather than existing as isolated scripts, Agents occupy formal organizational units, hold positions, receive designated reporting relationships, possess verified skills, and maintain a historical record of all organizational movements.

---

## 2. Workforce Classifications

Agents are categorized by executive scope, autonomous decision boundaries, and organizational responsibility:

| Classification | Typical Scope | Authority Boundary | Example Roles |
| :--- | :--- | :--- | :--- |
| **`executive`** | Corporate-wide strategy, multi-division coordination | Ratifies operational plans, coordinates with Owner/Board | Director AI |
| **`managerial`** | Division or Department leadership | Approves task decomposition, signs off operational tickets | Tech Head, Services Head |
| **`specialist`** | Deep technical domain execution | Proposes architectural designs, writes high-level code | Dev AI, Architect AI |
| **`operational`** | Routine business process execution | Executes scheduled pipeline tasks, monitoring | Pipeline Worker AI |
| **`support`** | Administrative, clerical, data assistance | Prepares reports, indexes documentation | Helper AI |

---

## 3. Historical Placement & Organizational Transfers

Organizational agility requires that Agents may be reassigned, promoted, or transferred across divisions and units without losing their institutional memory or past performance records.

### Relational Schema (`ai_agent_assignments`)
- `agent_id`: FK to `ai_agent_registry`.
- `unit_id`: FK to `organization_units`.
- `position_id`: Optional FK to `organization_positions`.
- `assignment_role`: `primary`, `secondary`, `interim`, `advisory`.
- `status`: `active`, `ended`, `transferred`.
- `start_date` / `end_date`: Chronological duration.
- `transfer_reason`: Audited explanation for the reassignment.

### Historical Continuity Invariant
When an Agent transfers from Unit A to Unit B:
1. The active assignment record in Unit A is marked `status = 'transferred'`, setting `end_date = NOW()`.
2. A new record in Unit B is created with `status = 'active'`, setting `start_date = NOW()`.
3. The Agent's authoritative `agent_uuid`, historical performance records, and skill proficiencies remain completely intact.

---

## 4. Reusable Skill & Competency Catalogue

To support dynamic task matching and automated routing in future prompts (Prompt 20 Task Decomposition), competencies are managed through a centralized, controlled catalogue:

```
+---------------------------------------------------------------------------------------+
|                                    SKILL CATALOGUE                                    |
+---------------------------------------------------------------------------------------+
|  ID | Code             | Name                          | Category                     |
|-----+------------------+-------------------------------+------------------------------|
|  1  | SKL-SYS-ARCH     | System Architecture           | software_engineering         |
|  2  | SKL-FULLSTACK    | Full-Stack Web Development    | software_engineering         |
|  3  | SKL-AI-RUNTIME   | AI Agent Runtime Engineering  | artificial_intelligence      |
|  4  | SKL-LLM-OPS      | Prompt Engineering & LLM-Ops  | artificial_intelligence      |
|  5  | SKL-CORP-GOV     | Corporate Governance & DoA    | governance_management        |
|  6  | SKL-AGILE-COORD  | Cross-Functional Agile Coord. | governance_management        |
|  7  | SKL-CURR-DES     | Technical Curriculum Design   | education_training           |
|  8  | SKL-IOT-EMBED    | Embedded Systems & IoT        | hardware_engineering         |
+---------------------------------------------------------------------------------------+
```

### Agent Skill Proficiency Mapping (`ai_agent_skills`)
- **Proficiency Levels**: `beginner`, `intermediate`, `advanced`, `expert`, `master`.
- **Verification Status**: `unverified`, `verified`, `certified`.
- **Active Flag**: `is_active = 1` enables real-time capability matching.

---

## 5. Structured Operational Responsibilities

Responsibilities are not unstructured text blobs; they are normalized entities in `ai_agent_responsibilities`:
- `priority`: Numerical ranking (`1` = highest).
- `responsibility_title`: Short formal designation.
- `scope`: `division_wide`, `corporate_wide`, `unit_specific`, `project_specific`.
- `kpi_indicator`: Measurable success benchmark.
- `status`: `active`, `deprecated`, `transferred`.
