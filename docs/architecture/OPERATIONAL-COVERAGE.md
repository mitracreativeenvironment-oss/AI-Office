# OPERATIONAL COVERAGE ARCHITECTURE
## MCE AI Office — Requirements, Real-Time Evaluation & Coverage Risk Matrix
**Document Code:** `MCE-ARCH-COV-2026-V1`  
**Classification:** Controlled Architecture Specification  
**Implementation Milestone:** Prompt 20 — Agent Scheduling, Shift Coverage, Replacement & Wallbook Handover  

---

## 1. Executive Summary & Purpose

The **Operational Coverage Engine** ensures that critical business, technical, and governance functions remain adequately staffed by qualified, available, and authorized AI Agents.

Rather than assuming all organizational units require 24/7 continuous operations, MCE enforces **Configurable Coverage Dimensions** based on unit criticality, operational hours, and verified competency requirements.

---

## 2. Coverage Requirements Schema

Coverage requirements are managed in `workforce_coverage_requirements`:

- **Unit FK (`unit_id`)**: Specific organizational unit or division (e.g. `DIV-TECH`, `DIV-SRV`).
- **Shift FK (`shift_id`)**: Applicable shift or NULL for all-day flexible coverage.
- **Day of Week (`day_of_week`)**: `all`, `weekday`, `weekend`, `mon`, `tue`, etc.
- **Minimum Qualified Agents (`min_agents_required`)**: Numerical threshold (typically `1`).
- **Required Skill FK (`required_skill_id`)**: Required competency from `skill_catalogue` (e.g. `SKL-SYS-ARCH`, `SKL-FULLSTACK`).
- **Minimum Proficiency (`min_proficiency`)**: `beginner`, `intermediate`, `advanced`, `expert`, `master`.
- **Criticality (`criticality`)**: `critical`, `high`, `medium`, `low`.
- **Continuous 24/7 Flag (`is_continuous_24_7`)**: Identifies services requiring unbroken operational coverage.

---

## 3. Real-Time Coverage Evaluation Algorithm

When `OperationalCoverageService::evaluateCoverage($date, $unitId)` evaluates coverage, it executes a four-tier validation:

```
[Requirement Active for Date?]
               │
               ▼
[Find Scheduled Working Agents in Target Unit]
               │
               ▼
[Filter Out Unqualified Agents (Missing required_skill_id)]
               │
               ▼
[Filter Out Unavailable Agents (Approved Leave without Activated Replacement)]
               │
               ▼
[Count Qualified & Available Agents vs min_agents_required]
               │
               ├── Count >= Min AND Unconfirmed Replacement Exists -> "coverage_at_risk"
               ├── Count >= Min AND No Replacement Risk             -> "covered"
               ├── Count > 0 AND Count < Min                       -> "partially_covered"
               ├── Count == 0 AND Replacement Pending              -> "pending_replacement"
               └── Count == 0                                      -> "uncovered"
```

---

## 4. Evaluated Coverage States

| State | Definition | Visual Indicator | Recommended System Action |
| :--- | :--- | :---: | :--- |
| **`covered`** | Qualified, available agent count $\ge$ required count. | 🟢 Green | Normal operation. |
| **`coverage_at_risk`** | Covered, but assigned agent has pending leave or replacement proposal. | 🟠 Amber | Expedite replacement & Wallbook review. |
| **`partially_covered`**| Active agents $> 0$ but below minimum required staffing. | 🟡 Yellow | Propose supplementary shift assignment. |
| **`pending_replacement`**| Primary agent on leave; replacement proposal pending acceptance/activation. | 🟣 Purple | Prompt incoming agent for acceptance. |
| **`uncovered`** | Zero qualified agents assigned or available for duty. | 🔴 Red | Automated escalation to Division Lead / Director AI. |

---

## 5. Coverage Gap Analysis

The endpoint `GET /api/v1/workforce/scheduling/coverage/gaps` calculates real-time operational deficiencies across all divisions. Gaps are filtered by criticality, enabling immediate supervisory dispatch before operational blackouts occur.
