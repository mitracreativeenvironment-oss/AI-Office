# MCE AI Office — Agent Welfare & Welfare Tickets API Reference

This document specifies the 24 REST API endpoints under `/api/v1/welfare/*` implemented in Prompt 24.

---

## 1. Overview & Authentication
All welfare endpoints return standard JSON envelopes (`status`, `code`, `message`, `data`) and require Bearer Token authentication (`AuthFilter` / `PermissionFilter`).

Base URI: `/api/v1/welfare`

---

## 2. Endpoints Summary

### 2.1 Overview & Campus Metrics
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/overview` | Campus welfare metrics, active policy, agent balance roster, and facilities |

### 2.2 Policies (`/policies`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/policies` | List all historical and active welfare policies |
| `GET` | `/policies/active` | Get currently effective approved welfare policy |
| `POST` | `/policies` | Propose a new welfare policy draft |
| `POST` | `/policies/:id/approve` | Approve and activate policy (deactivates older versions) |

### 2.3 Periodic Base Compensation (`/periods`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/periods` | List compensation calculation periods |
| `GET` | `/periods/:id` | Get period details including individual agent duty evaluations |
| `POST` | `/periods` | Create a new period cycle |
| `POST` | `/periods/:id/calculate` | Evaluate 5/2 duty roster fulfillment across agents |
| `POST` | `/periods/:id/approve` | Human executive approval of calculated period |
| `POST` | `/periods/:id/distribute` | Batch emit base compensation tickets into immutable ledger |

### 2.4 Verified Task Bonuses (`/task-bonuses`, `/tasks`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/task-bonuses` | List task bonus evaluations and verification status |
| `POST` | `/tasks/:id/evaluate-bonus` | Evaluate task for verified completion bonus |
| `POST` | `/task-bonuses/:id/approve` | Approve task bonus, credit ledger, and update performance history |

### 2.5 Special Recognitions (`/recognitions`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/recognitions` | List special recognition nominations |
| `POST` | `/recognitions` | Nominate an agent for special merit recognition |
| `POST` | `/recognitions/:id/approve` | Approve recognition (enforcing DoA level rules) |

### 2.6 Immutable Ledger & Balances (`/ledger`, `/agents`, `/adjustments`, `/transactions`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/ledger` | Paginated immutable ticket transaction records |
| `GET` | `/agents/:id/balance` | Retrieve cached balance and reconciliation status |
| `POST` | `/agents/:id/reconcile` | Perform mathematical ledger proof against transaction sums |
| `POST` | `/adjustments` | Post administrative credit/debit adjustment with audit reason |
| `POST` | `/transactions/:id/reverse` | Formal transaction reversal referencing original ID |

### 2.7 Facilities & Sessions (`/facilities`, `/facility-sessions`, `/facility-logs`)
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/facilities` | List VHQ welfare facilities with live occupancy |
| `POST` | `/facilities/:id/check-in` | Check an agent into a welfare facility session |
| `POST` | `/facility-sessions/:id/check-out` | Conclude an active facility recovery session |
| `GET` | `/facility-logs` | Query facility usage logs and session history |
