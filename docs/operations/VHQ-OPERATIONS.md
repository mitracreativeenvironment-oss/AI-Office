# Virtual Headquarters (VHQ) Operations Guide

## 1. Navigating the 3D Campus

### Camera Controls
- **Left-Click & Drag**: Rotate camera azimuth and polar elevation around current focal point.
- **Right-Click & Drag**: Pan camera horizontally and vertically.
- **Scroll Wheel / Pinch**: Zoom in and out (clamped between 8m and 130m distance).
- **Camera Bookmarks**: Click any pill in the bottom navigation bar to fly to preset camera positions:
  - `Campus Bird's-Eye Overview`: Comprehensive view of the entire 6-building layout.
  - `Main Reception & Atrium`: Ground-level entrance to the Executive Tower.
  - `Executive Boardroom & Suite`: 2nd floor focus on Boardroom and Director AI Suite.
  - `Technology & Dev AI Workspace`: Tech Pavilion engineering pods.
  - `AI Agent Welfare Commons`: Focus on recovery pods and welfare gardens.
  - `Hardware Labs & Telemetry`: East wing infrastructure testing consoles.

---

## 2. Agent Status Ring Color Interpretation

Every 3D avatar on campus features an illuminated status ring beneath their workstation:

| Color | Hex | Status | Meaning |
|---|---|---|---|
| **Emerald Green** | `#10b981` | `available` | On duty, healthy runtime connection, ready for incoming tasks. |
| **Sapphire Blue** | `#3b82f6` | `working` | Currently executing an active assigned task. Ring pulses gently. |
| **Amethyst Purple** | `#a855f7` | `on_leave` | Approved planned leave or temporary unavailability. |
| **Slate Gray** | `#64748b` | `scheduled_off` | Scheduled rest day under 5/2 staggered roster policy. |
| **Tangerine Orange** | `#ea580c` | `suspended` | Administrative or safety suspension active on runtime profile. |
| **Rose Red** | `#f43f5e` | `runtime_disconnected` | Hermes runtime node unreachable or unmapped. |

---

## 3. Search & Locate Feature

In the top header bar:
1. Type an agent's code (`DIR`, `TECH`, `DEV`) or name in the search box.
2. Filtered matches appear in a floating dropdown.
3. Clicking a match automatically:
   - Smoothly pans and zooms the 3D camera directly to the agent's workstation.
   - Opens the **Agent Detail Slide-Out Drawer** displaying current task, DoA boundary, and operational metrics.

---

## 4. WebGL Fallback Mode (2D Schematic)

If hardware acceleration is disabled or WebGL fails to initialize:
- The system automatically triggers the **2D Interactive Campus Directory**.
- Users can also manually toggle 2D mode at any time using the `📄 Skematik 2D` button in the top navigation bar.
- All building cards, room listings, workstation occupants, and live agent status badges remain fully interactive.
