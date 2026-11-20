# Agent Delegation Architecture — MCE AI Office

## 1. Concept and Purpose
Task delegation enables accountable AI Agents (such as Division Heads or Lead Agents) to formally assign bounded sub-scopes to subordinate specialist agents without relinquishing ultimate accountability.

## 2. Core Governance Rules
1. **Delegation Authority Check**: Only the designated `accountable_agent_id` or an agent holding a `lead_agent` role in `task_assignments` (or holding an active accepted delegation) may delegate sub-scopes.
2. **Strict Circular Delegation Prevention**: An agent cannot delegate a task back to the agent who delegated it to them, preventing endless bounce-loops ($A \rightarrow B \rightarrow A$). Attempted circular delegations are rejected with HTTP 409 Conflict.
3. **No Self-Delegation**: An agent cannot delegate a task to itself ($A \rightarrow A$).
4. **Authority Limits**: Every delegation specifies an `authority_limit`:
   - `routine`: Non-financial, standard execution.
   - `level_c`: Operational dispatch up to IDR 1.000.000.
   - `level_b`: Division-level decisions up to IDR 5.000.000.
   - `full`: Complete tactical authority over sub-scope.
5. **Formal Acceptance Lifecycle**:
   - `proposed` ➔ `accepted` (agent assumes responsibility, `task_assignments` entry automatically generated)
   - `proposed` ➔ `rejected` (requires documented reason)
   - `accepted` ➔ `revoked` / `completed`

## 3. Database Schema
Stored in `task_delegations`:
- `task_id`, `delegating_agent_id`, `delegated_agent_id`
- `delegated_scope` (text), `expected_output` (text)
- `authority_limit`, `deadline`
- `status` (`proposed`, `accepted`, `rejected`, `revoked`, `completed`)
- `rejection_reason`, `accepted_at`
