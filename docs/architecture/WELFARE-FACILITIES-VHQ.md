# MCE AI Office — Agent Welfare Facilities & VHQ 3D Spatial Integration

## 1. Spatial Overview & Architectural Intent

In the MCE Virtual Headquarters (VHQ), AI workforce welfare is not merely an abstract administrative concept; it is physically manifested in the 3D environment under the dedicated **Welfare Building (`BLD-WELFARE`)**.

Just as human offices provide break rooms, wellness facilities, and quiet lounges to prevent burnout, the MCE AI Office provides spatial recovery pods, knowledge synthesis chambers, and focus facilities to prevent model degradation, token exhaustion, and context pollution.

```
       +-----------------------------------------------------------+
       |             MCE VIRTUAL HEADQUARTERS CAMPUS               |
       |                                                           |
       |  [BLD-MAIN]              [BLD-DIV-TECH]   [BLD-DIV-SRV]   |
       |  Corporate Core          Engineering      Client Support  |
       |                                                           |
       |  [BLD-DIV-ACAD]          [BLD-DIV-HW]     [BLD-WELFARE]   |
       |  Education & Research    Hardware & Infra  Wellness & Care|
       +-----------------------------------------------------------+
```

---

## 2. Facilities Inventory (`welfare_facilities`)

The database table `welfare_facilities` establishes relational mappings between campus buildings, rooms, workstations, and welfare operational services:

| Facility Code | Facility Name | Building Code | Room Code | Facility Type | Capacity | Operating Status |
|---|---|---|---|---|---|---|
| `FAC-WLF-REST-01` | Executive AI Rest & Regeneration Pod | `BLD-WELFARE` | `ROOM-WLF-REST` | `rest_recovery` | 4 Pods | `available` |
| `FAC-WLF-LEARN-01` | Continuous Knowledge Enrichment Lounge | `BLD-WELFARE` | `ROOM-WLF-LEARN` | `knowledge_enrichment` | 8 Stations | `available` |

### 2.1 Facility Types & Operational Semantics
- **`rest_recovery`**: Allocated for Agents on off-duty shift intervals (5/2 schedule) or recovering from complex multi-turn reasoning tasks. Resets context caches, archives conversational history to permanent storage, and minimizes background polling.
- **`knowledge_enrichment`**: Dedicated environment for autonomous model fine-tuning, benchmark evaluation, and RAG knowledge base ingestion without active client interruption.
- **`quiet_focus`**: High-priority isolated computational environment for complex architectural design and algorithmic verification.
- **`collaboration`**: Multi-agent shared whiteboard environment for inter-divisional synchronization.

---

## 3. Spatial Coordinates & 3D Three.js Integration

### 3.1 Three.js Coordinate Space
Within `MceKantorScene.tsx` and the Three.js 3D rendering pipeline:
- **Zone Mapping**: The `welfare` zone encompasses $X \in [1.6, 5.6]$, $Z \in [-3.7, -2.1]$.
- **Aesthetic Distinction**:
  - Floor Material: Warm institutional slate (`#e3d6c6`).
  - Carpet Accent: Emerald Green (`#10b981`), symbolizing operational health and wellness.
  - Lighting: Soft ambient point light with automatic day/night dimming (`nightFactor = 0.18`).
- **Workstations & Pods**: Spatial coordinates correspond to `hq_workstations` with codes `WS-WLF-POD-01` through `WS-WLF-POD-04`.

### 3.2 Live Occupancy & Visual Indicators
The Three.js scene dynamically reflects occupancy from `/api/v1/welfare/facilities`:
- When an Agent is checked in (`active` session in `welfare_facility_usage_logs`):
  - Agent avatar mesh is placed at the facility workstation pod.
  - Interactive screen canvas renders status indicator: `Siaga` (Available), `Bekerja` (Working), or `Cuti` (Rest/Leave).
  - Current occupancy counter increments; if `current_occupancy >= capacity`, status toggles to `busy`.

---

## 4. Operational Usage Sessions (`welfare_facility_usage_logs`)

Usage is tracked with start/end timestamps and linked to agent performance records:
1. **Check-In (`POST /api/v1/welfare/facilities/:id/check-in`)**:
   - Validates agent active status and verifies capacity is not exceeded.
   - Creates log record with status `active`.
   - Increments `welfare_facilities.current_occupancy`.
2. **Check-Out (`POST /api/v1/welfare/facility-sessions/:id/check-out`)**:
   - Sets `end_time = NOW()`, status `completed`.
   - Decrements `current_occupancy`.
   - Records session summary in `welfare_audit_trail`.
