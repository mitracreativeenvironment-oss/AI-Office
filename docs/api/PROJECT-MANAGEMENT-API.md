# MCE AI Office — Project Management REST API Reference

**Document Version:** 1.0  
**Status:** Approved & Implemented  
**Base URL:** `/api/v1/projects`  
**Authentication:** HTTP Authorization Bearer Token (`mce_auth`)

---

## 1. Endpoints Overview

| Method | Endpoint | Description | Clearance / Auth |
| :--- | :--- | :--- | :--- |
| `GET` | `/projects/portfolio-metrics` | Summary metrics across entire project portfolio | Public / Internal |
| `GET` | `/projects` | List projects with filters (`project_type`, `category`, etc.) | Classification-based |
| `GET` | `/projects/{id}` | Detailed project workspace with brief, scopes, milestones | Classification-based |
| `POST` | `/projects` | Create new customer project or internal initiative | Authenticated |
| `PUT` | `/projects/{id}` | Update project metadata and health status | Authenticated |
| `DELETE`| `/projects/{id}` | Cancel / soft delete project | Authenticated |
| `POST` | `/projects/{id}/transition` | Advance lifecycle stage with governance validation | Authenticated |
| `GET` | `/projects/{id}/status-history`| Audit trail of lifecycle transitions | Authenticated |
| `GET` | `/projects/{id}/tasks` | List linked workforce tasks | Authenticated |
| `POST` | `/projects/{id}/tasks/link` | Link workforce_task to project milestone | Authenticated |
| `POST` | `/projects/{id}/tasks/unlink` | Unlink workforce_task from project | Authenticated |
| `POST` | `/projects/{id}/recalculate-progress` | Trigger mathematical progress recalculation | Authenticated |
| `GET` | `/projects/clients` | List customer profiles with engagement statistics | Authenticated |
| `GET` | `/projects/clients/{id}` | Customer detail with contacts and projects list | Authenticated |
| `POST` | `/projects/clients` | Register new customer client profile | Authenticated |
| `PUT` | `/projects/clients/{id}` | Update client profile metadata | Authenticated |
| `GET` | `/projects/clients/{id}/contacts` | List client stakeholder contacts | Authenticated |
| `POST` | `/projects/clients/{id}/contacts` | Register client contact person | Authenticated |
| `PUT` | `/projects/clients/contacts/{id}` | Update contact details | Authenticated |
| `DELETE`| `/projects/clients/contacts/{id}`| Remove client contact | Authenticated |
| `GET` | `/projects/{id}/brief` | Fetch project brief and objectives | Authenticated |
| `POST` | `/projects/{id}/brief` | Create or update project brief | Authenticated |
| `GET` | `/projects/{id}/scopes` | List versioned scope baselines | Authenticated |
| `POST` | `/projects/{id}/scopes/baseline`| Create new authorized scope baseline | Authenticated |
| `GET` | `/projects/{id}/milestones` | List milestones with critical path indicators | Authenticated |
| `POST` | `/projects/{id}/milestones` | Create project milestone | Authenticated |
| `PUT` | `/projects/milestones/{id}` | Update milestone target date or status | Authenticated |
| `DELETE`| `/projects/milestones/{id}` | Delete milestone | Authenticated |
| `GET` | `/projects/{id}/deliverables` | List project deliverables | Authenticated |
| `POST` | `/projects/{id}/deliverables` | Register deliverable tied to milestone | Authenticated |
| `PUT` | `/projects/deliverables/{id}` | Update deliverable metadata | Authenticated |
| `POST` | `/projects/deliverables/{id}/submit` | Submit deliverable for review | Authenticated |
| `POST` | `/projects/deliverables/{id}/review` | Complete technical review of deliverable | Authenticated |
| `POST` | `/projects/deliverables/{id}/client-accept` | Record external client acceptance sign-off | Authenticated |
| `GET` | `/projects/{id}/risks` | List registered project risks | Authenticated |
| `POST` | `/projects/{id}/risks` | Register project risk and mitigation plan | Authenticated |
| `PUT` | `/projects/risks/{id}` | Update risk status or mitigation strategy | Authenticated |
| `GET` | `/projects/{id}/issues` | List operational blockers and issues | Authenticated |
| `POST` | `/projects/{id}/issues` | Log issue requiring resolution | Authenticated |
| `PUT` | `/projects/issues/{id}` | Update issue attributes | Authenticated |
| `POST` | `/projects/issues/{id}/resolve`| Record issue resolution summary | Authenticated |
| `GET` | `/projects/{id}/change-requests` | List change requests with DoA levels | Authenticated |
| `POST` | `/projects/{id}/change-requests` | Submit scope/budget change request | Authenticated |
| `POST` | `/projects/change-requests/{id}/evaluate` | Authorize or reject change request (DoA Level A/B) | Authenticated |
| `GET` | `/projects/{id}/team` | List assigned project workforce team members | Authenticated |
| `POST` | `/projects/{id}/team` | Assign human or AI agent to project role | Authenticated |
| `PUT` | `/projects/team/{id}` | Update team member allocation or status | Authenticated |
| `DELETE`| `/projects/team/{id}` | Release team member from project | Authenticated |
| `GET` | `/projects/{id}/resources` | List compute and infrastructure allocations | Authenticated |
| `POST` | `/projects/{id}/resources` | Register resource allocation | Authenticated |
| `POST` | `/projects/resources/{id}/utilization` | Record resource usage quantity | Authenticated |
| `GET` | `/projects/{id}/budget` | Get budget governance summary | Authenticated |
| `PUT` | `/projects/{id}/budget` | Modify budget limits under DoA | Authenticated |
