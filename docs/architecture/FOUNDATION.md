# ARCHITECTURAL FOUNDATION & SYSTEM BOUNDARIES
## MCE AI Office — Core Technical Baseline
**Document Code:** `MCE-ARC-FOUND-2026-V1`  
**Author:** Full-Stack Architecture & Engineering Team  
**Status:** Baseline Established (Prompt 16)

---

## 1. System Overview & Core Principles

The **MCE AI Office** operates as an enterprise operating system and immersive virtual headquarters for Mitra Creative Environment. The architecture is founded upon three immutable pillars:

1. **Single Source of Truth & Authoritative Policy Engine**:
   CodeIgniter 4 (CI4) on PHP 8.2+ acts as the authoritative backend. All corporate governance, DoA checks, state machines, and business policies execute in CI4 before any data is mutated. The frontend and external runtimes NEVER bypass CI4.
2. **System of Record**:
   MySQL / MariaDB (InnoDB, UTF8mb4) is the authoritative persistence store. Data integrity is guaranteed via foreign key constraints, UUID v4 primary keys, and transaction isolation.
3. **Dual Client Viewport**:
   The frontend is a unified Single-Page Application (SPA) in React 19 + TypeScript + Vite 8 providing two coordinated operational views:
   - **Administrative Dashboard**: Operational governance, agent monitoring, approvals, tasks, and system metrics.
   - **Virtual Headquarters (VHQ)**: Immersive 3D interactive campus built in native Three.js reflecting agent physical states and district operations.

---

## 2. Layer Topology & Separation of Concerns

```text
┌─────────────────────────────────────────────────────────────┐
│                    CLIENT VIEWPORT (SPA)                    │
│    React 19 + TypeScript + Vite 8 + Native Three.js (r170)  │
│         /dashboard ──► Administrative Dashboard Shell       │
│         /vhq       ──► 3D Virtual Headquarters Scene        │
└──────────────────────────────┬──────────────────────────────┘
                               │ HTTPS / JSON REST & Cookies
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                 BACKEND ENGINE (REST API)                   │
│           CodeIgniter 4.7.6 (Modular Monolith)              │
│   • Thin Controllers (Api\*)                                │
│   • Domain Services & Policy Enforcement Filters            │
│   • Centralized Error Handling & Universal Envelope         │
└──────────────┬──────────────────────────────┬───────────────┘
               │                              │
               ▼                              ▼
┌──────────────────────────────┐┌──────────────────────────────┐
│       DATABASE SYSTEM        ││     PLANNED AI RUNTIME       │
│    MySQL / MariaDB (Port 3306││   • Hermes Docker Sandbox    │
│   28 Tables Normalized Schema││   • 9Router Model Gateway    │
│    (InnoDB Row-Level Locking)││   (Mocks / Stubs in Wave 0–6)│
└──────────────────────────────┘└──────────────────────────────┘
```

---

## 3. API Communication Standards

All REST API endpoints conform to a standardized JSON response envelope:

### Success Envelope
```json
{
  "status": "success",
  "code": 200,
  "message": "Human readable confirmation",
  "data": { ... },
  "meta": { ... },
  "timestamp": "2026-11-20T17:00:00+08:00"
}
```

### Error Envelope
```json
{
  "status": "error",
  "code": 400,
  "message": "Safe human-readable error description",
  "errors": { ... },
  "meta": { ... },
  "timestamp": "2026-11-20T17:00:00+08:00"
}
```

### Security Boundary Controls:
- **No Stack Trace Leakage**: Internal PHP exceptions and SQL error traces are logged privately in `backend/writable/logs/` and never leaked in client responses.
- **Strict Origin Filtering**: CORS filters explicitly match the configured `mce.frontendURL` (`http://localhost:5173`). Wildcard `*` origins are rejected.
- **Request Cancellation & Timeouts**: The frontend `apiClient` enforces an 8000ms timeout with standard `AbortController` cancellation.

---

## 4. Planned Runtime Integration Boundaries

### Hermes AI Agent Runtime
- **Planned Execution**: Agents execute reasoning loops in isolated, ephemeral Docker containers.
- **Subtask Recursion**: Strictly hard-capped at 3 levels (`Parent Task` $\rightarrow$ `Subtask` $\rightarrow$ `Microtask`).
- **Foundation Status**: Hermes is not required or installed during Foundation (Prompt 16). CI4 employs mock/stub adapters for all runtime calls until Wave 7.

### 9Router Model Gateway
- **Planned Routing**: Central gateway for Claude 3.5 Sonnet, GPT-4o, and Gemini 1.5 Pro with token usage and rate-limiting enforcement.
- **Dual-Boundary Defense**: Prompt injection barriers and strict stripping of Chain-of-Thought (CoT) tokens prior to client dispatch.
- **Foundation Status**: 9Router is currently stubbed and will be integrated during live runtime deployment.
