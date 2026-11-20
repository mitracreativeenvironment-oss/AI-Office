# Virtual Headquarters (VHQ) REST API Specification

## Base URL
`/api/v1/vhq`

---

## 1. Endpoints

### 1.1 Campus Overview
- **Method**: `GET`
- **Path**: `/api/v1/vhq/campus`
- **Description**: Returns campus metadata, summary metrics, and list of all 6 active buildings.
- **Response (200 OK)**:
```json
{
  "status": "success",
  "code": 200,
  "message": "Virtual Headquarters campus overview retrieved.",
  "data": {
    "campus_name": "MCE AI Office Virtual Headquarters",
    "tagline": "Mitra Creative Environment — AI Workforce Spatial Operations",
    "summary": {
      "total_buildings": 6,
      "total_rooms": 10,
      "total_workstations": 8,
      "occupied_workstations": 6,
      "total_active_agents": 6,
      "unplaced_agents_count": 0
    },
    "buildings": [ ... ]
  }
}
```

---

### 1.2 Building Detail
- **Method**: `GET`
- **Path**: `/api/v1/vhq/buildings/{id}`
- **Description**: Returns building details with floors, rooms, workstations, and assigned agent occupants.
- **Response (200 OK)**:
```json
{
  "status": "success",
  "code": 200,
  "message": "Building #1 details retrieved.",
  "data": {
    "id": 1,
    "building_code": "BLD-MAIN",
    "name": "MCE Main Campus & Executive Tower",
    "floors": [
      {
        "id": 1,
        "floor_code": "FL-MAIN-01",
        "rooms": [ ... ]
      }
    ]
  }
}
```

---

### 1.3 List Rooms
- **Method**: `GET`
- **Path**: `/api/v1/vhq/rooms`
- **Query Parameters**:
  - `building_id` *(optional int)*
  - `category` *(optional string: `reception`, `executive`, `workspace`, `monitoring`, `welfare_rest`, `welfare_learning`)*
  - `unit_id` *(optional int)*
- **Response (200 OK)**: Array of room spatial objects with boundaries and unit links.

---

### 1.4 List Workstations
- **Method**: `GET`
- **Path**: `/api/v1/vhq/workstations`
- **Query Parameters**:
  - `building_id` *(optional int)*
  - `room_id` *(optional int)*
  - `is_occupied` *(optional boolean: `1` or `0`)*
- **Response (200 OK)**: Array of workstation objects with presence status and active tasks.

---

### 1.5 List All Agent Presences
- **Method**: `GET`
- **Path**: `/api/v1/vhq/presence`
- **Description**: Returns real-time operational presences across the entire active workforce.
- **Response (200 OK)**:
```json
{
  "status": "success",
  "code": 200,
  "message": "All agent presences retrieved.",
  "data": [
    {
      "agent_id": 1,
      "agent_uuid": "...",
      "agent_code": "AGT-DIR-001",
      "name": "Director AI",
      "presence_status": "available",
      "status_reason": "Agent is online, active on duty, and ready for work.",
      "has_spatial_placement": true,
      "placement": {
        "building_code": "BLD-MAIN",
        "room_code": "ROOM-DIR-SUITE",
        "workstation_code": "WS-DIR-01",
        "pos_x": 6.0,
        "pos_y": 5.5,
        "pos_z": -5.0
      },
      "active_task": null
    }
  ]
}
```

---

### 1.6 Single Agent Presence
- **Method**: `GET`
- **Path**: `/api/v1/vhq/agents/{id}/presence`
- **Description**: Returns presence, status reason, and spatial workstation placement for a specific agent.

---

### 1.7 Camera Navigation Bookmarks
- **Method**: `GET`
- **Path**: `/api/v1/vhq/bookmarks`
- **Description**: Returns camera navigation presets (`BM-CAMPUS`, `BM-LOBBY`, `BM-EXEC`, `BM-TECH`, `BM-WELFARE`, `BM-HW`).

---

### 1.8 Reassign Workstation
- **Method**: `POST`
- **Path**: `/api/v1/vhq/workstations/{id}/assign`
- **Authentication**: Bearer Token (`mce_auth`)
- **Request Body**:
```json
{
  "agent_id": 5
}
```
- **Description**: Assigns or vacates (`agent_id: null`) a workstation. Automatically removes the agent from any prior workstation to maintain single-desk integrity.
