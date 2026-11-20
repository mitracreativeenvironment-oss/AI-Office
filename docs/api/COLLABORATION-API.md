# MCE Internal Communication & Meetings REST API Specification

## 1. Overview & Authentication

All API endpoints are hosted under `/api/v1/collaboration/*`. Authenticated endpoints require an `Authorization: Bearer <token>` header issued via `/api/v1/auth/login`.

Standard API response format:
```json
{
  "status": "success",
  "data": {},
  "message": "Human readable confirmation message"
}
```

---

## 2. Executive Coordination Endpoints

### 2.1 `GET /api/v1/collaboration/executive/overview`
- **Description:** Returns aggregate executive metrics, active directives, recent board decisions, and active DoA profiles.
- **Access:** Internal / Executive
- **Response:** `HTTP 200 OK`

### 2.2 `GET /api/v1/collaboration/executive/directives`
- **Description:** Lists owner directions with acknowledgement status joins.
- **Parameters:** `direction_type`, `status`, `search`, `limit`, `offset`
- **Response:** `HTTP 200 OK`

### 2.3 `POST /api/v1/collaboration/executive/directives`
- **Description:** Issues a new strategic instruction or advisory guidance directive.
- **Access:** Human Owner / Superadmin only
- **Payload:**
  ```json
  {
    "direction_code": "DIR-OWNER-2026-002",
    "direction_type": "strategic_instruction",
    "subject": "Mandatory Task Synchronization",
    "description": "Align all operational tasks with DoA Level B boundaries.",
    "requires_acknowledgment": 1,
    "due_date": "2026-12-31"
  }
  ```
- **Response:** `HTTP 201 Created`

### 2.4 `POST /api/v1/collaboration/executive/directives/{id}/acknowledge`
- **Description:** Updates acknowledgement status and submits operational execution notes.
- **Payload:**
  ```json
  {
    "actor_type": "agent",
    "agent_id": 1,
    "status": "in_execution",
    "notes": "Director AI acknowledged and scheduled Boardroom sync."
  }
  ```
- **Response:** `HTTP 200 OK`

### 2.5 `GET /api/v1/collaboration/executive/decisions`
- **Description:** Lists formal executive decisions ratified by the Continuous Executive Board.
- **Response:** `HTTP 200 OK`

### 2.6 `POST /api/v1/collaboration/executive/decisions`
- **Description:** Records a proposed or ratified executive decision within DoA Level B/C boundaries.
- **Response:** `HTTP 201 Created`

---

## 3. Channels & Messaging Endpoints

### 3.1 `GET /api/v1/collaboration/channels`
- **Description:** Lists accessible organizational channels filtered by domain and classification.
- **Response:** `HTTP 200 OK`

### 3.2 `POST /api/v1/collaboration/channels`
- **Description:** Creates a new organizational channel.
- **Payload:**
  ```json
  {
    "name": "Software Engineering Pod",
    "description": "Technical channel for software architecture and Hermes runtime.",
    "domain": "technical",
    "classification": "internal",
    "is_private": 0
  }
  ```
- **Response:** `HTTP 201 Created`

### 3.3 `POST /api/v1/collaboration/channels/{id}/join`
- **Description:** Adds user or agent to channel membership roster.
- **Response:** `HTTP 200 OK`

### 3.4 `POST /api/v1/collaboration/channels/{id}/leave`
- **Description:** Removes user or agent from channel membership.
- **Response:** `HTTP 200 OK`

### 3.5 `GET /api/v1/collaboration/channels/{id}/messages`
- **Description:** Retrieves message stream for channel with threaded replies and references.
- **Response:** `HTTP 200 OK`

### 3.6 `POST /api/v1/collaboration/channels/{id}/messages`
- **Description:** Transmits message to channel. Validates sender identity to prevent impersonation.
- **Payload:**
  ```json
  {
    "sender_type": "human",
    "content": "Status update on integration tests.",
    "content_format": "markdown",
    "references": [
      { "reference_type": "task", "reference_id": 1, "reference_code": "TSK-001" }
    ]
  }
  ```
- **Response:** `HTTP 201 Created`

---

## 4. Direct & Group Conversations

### 4.1 `GET /api/v1/collaboration/conversations`
- **Description:** Lists 1-on-1 and executive session conversations for current user/agent.
- **Response:** `HTTP 200 OK`

### 4.2 `POST /api/v1/collaboration/conversations`
- **Description:** Initiates new conversation with participant roster.
- **Response:** `HTTP 201 Created`

### 4.3 `GET /api/v1/collaboration/conversations/{id}/messages`
- **Description:** Retrieves direct conversation messages.
- **Response:** `HTTP 200 OK`

### 4.4 `POST /api/v1/collaboration/conversations/{id}/messages`
- **Description:** Transmits message within direct conversation.
- **Response:** `HTTP 201 Created`

### 4.5 `POST /api/v1/collaboration/messages/{id}/pin`
- **Description:** Pins or unpins message in stream.
- **Response:** `HTTP 200 OK`

### 4.6 `POST /api/v1/collaboration/messages/read`
- **Description:** Acknowledges message receipt and updates `last_read_message_id`.
- **Response:** `HTTP 200 OK`

---

## 5. Official Announcements

### 5.1 `GET /api/v1/collaboration/announcements`
- **Description:** Lists broadcast announcements filtered by audience scope and priority.
- **Response:** `HTTP 200 OK`

### 5.2 `POST /api/v1/collaboration/announcements`
- **Description:** Publishes an official announcement.
- **Response:** `HTTP 201 Created`

### 5.3 `GET /api/v1/collaboration/announcements/{id}`
- **Response:** `HTTP 200 OK`

### 5.4 `PUT /api/v1/collaboration/announcements/{id}`
- **Response:** `HTTP 200 OK`

### 5.5 `DELETE /api/v1/collaboration/announcements/{id}`
- **Response:** `HTTP 200 OK`

---

## 6. Meeting Management & MoM

### 6.1 `GET /api/v1/collaboration/meetings`
- **Description:** Lists scheduled meetings with spatial VHQ room joins.
- **Response:** `HTTP 200 OK`

### 6.2 `POST /api/v1/collaboration/meetings`
- **Description:** Schedules a formal meeting with room binding, agendas, and participant roster.
- **Response:** `HTTP 201 Created`

### 6.3 `GET /api/v1/collaboration/meetings/{id}`
- **Description:** Retrieves meeting details with agendas, participants, MoM, and action items.
- **Response:** `HTTP 200 OK`

### 6.4 `PATCH /api/v1/collaboration/meetings/{id}/status`
- **Description:** Transitions meeting status (`in_progress`, `completed`, `cancelled`).
- **Response:** `HTTP 200 OK`

### 6.5 `POST /api/v1/collaboration/meetings/{id}/participants`
- **Description:** Appends participant to meeting.
- **Response:** `HTTP 201 Created`

### 6.6 `PATCH /api/v1/collaboration/meetings/{id}/participants/{participantId}`
- **Description:** Records roll call and attendance status.
- **Response:** `HTTP 200 OK`

### 6.7 `POST /api/v1/collaboration/meetings/{id}/agendas`
- **Description:** Adds dynamic agenda item.
- **Response:** `HTTP 201 Created`

### 6.8 `GET /api/v1/collaboration/meetings/{id}/minutes`
- **Description:** Retrieves Minutes of Meeting (MoM).
- **Response:** `HTTP 200 OK`

### 6.9 `POST /api/v1/collaboration/meetings/{id}/minutes`
- **Description:** Drafts or updates MoM notes.
- **Response:** `HTTP 200 OK`

### 6.10 `POST /api/v1/collaboration/meetings/{id}/minutes/approve`
- **Description:** Formally approves MoM and transitions meeting status to `minutes_finalized`.
- **Response:** `HTTP 200 OK`

### 6.11 `GET /api/v1/collaboration/action-items`
- **Description:** Lists meeting action items across all meetings.
- **Response:** `HTTP 200 OK`

### 6.12 `POST /api/v1/collaboration/meetings/{id}/action-items`
- **Description:** Creates action item with optional automated synchronization to `workforce_tasks`.
- **Response:** `HTTP 201 Created`

### 6.13 `PATCH /api/v1/collaboration/action-items/{id}`
- **Description:** Updates action item completion status.
- **Response:** `HTTP 200 OK`

---

## 7. Notifications & Unified Search

### 7.1 `GET /api/v1/collaboration/notifications`
- **Description:** Lists user or agent notifications.
- **Response:** `HTTP 200 OK`

### 7.2 `GET /api/v1/collaboration/notifications/unread-count`
- **Description:** Returns real-time integer count of unread notifications.
- **Response:** `HTTP 200 OK`

### 7.3 `POST /api/v1/collaboration/notifications/{id}/read`
- **Description:** Marks single notification read.
- **Response:** `HTTP 200 OK`

### 7.4 `POST /api/v1/collaboration/notifications/read-all`
- **Description:** Marks all recipient notifications read.
- **Response:** `HTTP 200 OK`

### 7.5 `GET /api/v1/collaboration/search?q={query}`
- **Description:** Multi-entity cross-domain collaboration search across channels, messages, meetings, announcements, and action items with clearance filtering.
- **Response:** `HTTP 200 OK`
