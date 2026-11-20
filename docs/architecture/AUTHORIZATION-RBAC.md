# ROLE-BASED ACCESS CONTROL (RBAC) & PERMISSION ENGINE
## MCE AI Office — Authorization Model
**Document Code:** `MCE-ARC-RBAC-2026-V1`  
**Status:** Implemented (Prompt 17)

---

## 1. Relational RBAC Model

MCE AI Office employs a normalized relational Role-Based Access Control architecture:

```text
┌──────────────┐         ┌──────────────┐         ┌──────────────┐
│    users     │◄───────►│  user_roles  │◄───────►│    roles     │
│ (id, email)  │         │(user, role)  │         │ (id, slug)   │
└──────────────┘         └──────────────┘         └──────┬───────┘
                                                         │
                                                         ▼
┌──────────────┐         ┌──────────────────┐            │
│ permissions  │◄───────►│ role_permissions │◄───────────┘
│  (id, slug)  │         │ (role, perm)     │
└──────────────┘         └──────────────────┘
```

---

## 2. Standardized Roles Catalogue

| Role Slug | Display Name | Authority Tier | System Responsibilities |
| :--- | :--- | :---: | :--- |
| **`owner`** | Human Owner | Level A | Full veto power, final financial signatory (> IDR 5M), governance override. |
| **`executive`** | Executive Board / Direksi | Level B | Strategic governance, corporate metrics review, project approval. |
| **`director_ai`** | Director AI | Level C | Operational task coordination, workforce dispatch, report aggregation. |
| **`division_head`**| Division Head | Level C | Divisional sprint review, task creation, agent monitoring. |
| **`admin`** | System Administrator | Operations | Account provisioning, role assignments, system diagnostics. |
| **`operator`** | Operations Staff | Operations | Day-to-day task monitoring and interactive assistance. |
| **`auditor`** | Compliance Auditor | Oversight | Read-only compliance oversight and security event analysis. |

---

## 3. Effective Permission Evaluation

Permissions are defined by granular domain slugs:
- User Management: `users.view`, `users.create`, `users.update`, `users.disable`
- Role Management: `roles.view`, `roles.assign`, `permissions.view`, `permissions.manage`
- Audit & Security: `audit.view`
- Corporate & Governance: `company.view`, `company.update`, `governance.view`, `governance.manage`
- AI Workforce: `agents.view`, `agents.manage`
- Task Operations: `tasks.view`, `tasks.create`, `tasks.update`
- Approvals: `approvals.view`, `approvals.decide`
- System Settings: `system.settings.view`

### Calculation Rule:
1. When a user requests a protected endpoint, `PermissionFilter` extracts the user's role set.
2. If the user holds the **`owner`** role, access is automatically granted (implicit total authority).
3. Otherwise, the effective permissions are aggregated across all of the user's assigned roles via a distinct join between `user_roles` and `role_permissions`.
4. If the required permission is absent, the request is immediately rejected with `HTTP 403 Forbidden` and a security event `access_denied` is logged.
