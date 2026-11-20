# Hermes Runtime & 9Router Model Gateway REST API Specification

## 1. Overview

This document specifies the REST endpoints provided by the CodeIgniter 4 backend for Hermes runtime integration and 9Router model gateway operations. All requests require standard `mce_auth` Bearer JWT authentication unless specified.

Base URL: `/api/v1`

---

## 2. Infrastructure Health & Environments

### `GET /runtime/health`
Retrieves aggregate health diagnostic across Hermes runtime and 9Router model gateway.
- **Response**: `HTTP 200 OK`
- **Envelope**:
```json
{
  "status": "success",
  "data": {
    "overall_status": "healthy",
    "hermes_runtime": {
      "component": "hermes_runtime",
      "status": "connected",
      "is_live": false,
      "endpoint": "http://127.0.0.1:8686/v1",
      "latency_ms": 2,
      "active_environments_count": 2,
      "mode": "simulation_fallback"
    },
    "nine_router_gateway": {
      "component": "9router_gateway",
      "status": "connected",
      "is_live": false,
      "endpoint": "http://127.0.0.1:9090/v1",
      "latency_ms": 2,
      "active_routes_count": 5,
      "sole_gateway_enforced": true,
      "direct_bypass_policy": "BLOCKED",
      "mode": "simulation_fallback"
    },
    "ready_agents_count": 6,
    "sessions": { "active": 0, "completed": 5, "failed": 0, "total": 5 },
    "sole_gateway_enforced": true,
    "direct_bypass_policy": "BLOCKED"
  }
}
```

### `GET /runtime/environments`
Lists configured Hermes runtime execution node environments.

---

## 3. Agent Runtime Profiles & Provisioning

### `GET /runtime/profiles`
Lists Hermes agent runtime profiles with tool policies, memory limits, and sandbox rules.

### `GET /runtime/agents/{id}`
Retrieves agent runtime identity mapping.

### `POST /runtime/agents/{id}/validate`
Evaluates agent readiness across all 12 pre-dispatch checks.

### `POST /runtime/agents/{id}/provision`
Provisions an active agent into Hermes execution environment.

### `POST /runtime/agents/{id}/suspend`
Suspends an agent's runtime execution capability with an audit reason.

---

## 4. Execution Sessions & Dispatch

### `GET /runtime/executions`
Lists execution sessions (supports `task_id`, `agent_id`, `status`, `limit`, `offset`).

### `GET /runtime/executions/{id}`
Retrieves execution session detail with chronological events, tool invocations, and model usage.

### `POST /runtime/executions`
Dispatches an authorized task execution to Hermes. Evaluates 15 preconditions before dispatch.
- **Request Body**:
```json
{
  "task_id": 2,
  "agent_id": 6,
  "nonce": "exec_nonce_12345"
}
```
- **Response**: `HTTP 201 Created`

### `POST /runtime/executions/{id}/cancel`
Cancels an active or queued execution session.

---

## 5. Controlled Tool Registry

### `GET /runtime/tools`
Lists tools registered in the controlled sandbox tool registry.

### `POST /runtime/tools`
Registers a new tool in the catalog.

### `POST /runtime/tools/invoke`
Authorizes and executes a tool within the Hermes sandbox. Critical risk tools require `approval_token`.

---

## 6. 9Router Model Gateway

### `GET /model-gateway/status`
Checks 9Router health and verifies sole gateway policy enforcement.

### `GET /model-gateway/routes`
Lists model routing profiles (`ROUTE-FAST-REASONING`, `ROUTE-DEEP-RESEARCH`, etc.).

### `GET /model-gateway/usage`
Retrieves authoritative token consumption and cost accounting in IDR.

### `POST /model-gateway/completions`
Dispatches a completion request strictly through 9Router.
