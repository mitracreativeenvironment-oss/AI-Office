# Notification System Architecture & Alert Escalation Workflow

## 1. Unified Notification Center

The MCE Notification Center provides a unified, cross-workforce alerting subsystem servicing both Human Personnel and AI Workforce Agents. Stored in `collaboration_notifications`, it bridges asynchronous events from the business control plane, executive board, and runtime execution infrastructure.

```mermaid
flowchart LR
    subgraph Event Producers
        ExecCoord["Executive Board (Directives & Decisions)"]
        MeetingsSys["Meeting Scheduler (Invites & MoM)"]
        ChatEngine["Channels & Direct Chat (Mentions & Messages)"]
        TasksPlane["Task Management (Assignments & Blockers)"]
        Telemetry["Hermes & 9Router Telemetry Alerts"]
    end

    subgraph Notification Engine
        NotifService["CollaborationNotificationService"]
        Filter["Recipient & Clearance Gate"]
        Persist[("collaboration_notifications")]
    end

    subgraph Recipients
        HumanInbox["Human Web Client / Toast Inbox"]
        AgentInbox["AI Agent Active Context Stream"]
    end

    Event Producers --> NotifService
    NotifService --> Filter
    Filter --> Persist
    Persist -->|user_id| HumanInbox
    Persist -->|agent_id| AgentInbox
```

---

## 2. Notification Types & Priority Matrix

| Type Code | Trigger Source | Default Priority | Delivery Behavior |
| :--- | :--- | :--- | :--- |
| `message` | Direct message received or channel subscription | `medium` | Inbox unread badge update |
| `mention` | `@user` or `@agent` tag in channel conversation | `high` | High-visibility pill & context injection |
| `meeting_invite` | Scheduled meeting invitation | `high` | Includes room link and time slot reservation |
| `meeting_reminder` | 15-minute pre-meeting alert | `urgent` | Prompt to occupy VHQ workstation or room |
| `decision_published`| Formal executive decision ratified | `medium` | Broadcast to relevant unit or company-wide |
| `directive_issued` | New strategic instruction from Human Owner | `urgent` | Forces acknowledgement workflow in Board |
| `approval_requested`| Task approval or financial sign-off required | `urgent` | Gated on DoA profile of recipient |
| `action_item_assigned`| Action item extracted from meeting | `high` | Automatic link to `workforce_tasks` |
| `announcement` | New broadcast corporate announcement | `routine` / `important` | Dismissible banner & inbox entry |
| `alert` | Operational anomaly or gateway failover | `urgent` / `critical` | Real-time banner with sound/visual cue |

---

## 3. Delivery States & Lifecycle

1. **Generation:** Dispatched via `sendNotification($recipientType, $recipientId, $title, $message, $type, $priority, $resourceUri)`.
2. **Badge Tracking:** Unread count is queried via real-time counter endpoint `/collaboration/notifications/unread-count`.
3. **Consumption:** Recipients review details or navigate directly to the linked entity via `resource_uri`.
4. **Mark Read:** Single-item mark read (`/notifications/{id}/read`) or batch resolution (`/notifications/read-all`).
