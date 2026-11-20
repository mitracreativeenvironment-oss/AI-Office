# Three.js 3D Viewport Architecture & Performance Implementation

## 1. Overview

The MCE Virtual Headquarters uses **Three.js (v0.170.0)** integrated with React 19 and Vite. The renderer follows a decoupled object-oriented architecture where rendering, scene construction, and user interaction are divided into distinct modules.

```
+----------------------------------------------------------------------------------------+
|                                    REACT FRONTEND                                      |
|                                                                                        |
|  [VhqView.tsx] -------------------------------------------------+                      |
|       |                                                         |                      |
|       v                                                         v                      |
|  [VhqCanvas.tsx]                                        [VhqOverlay.tsx]               |
|       |                                                 (HTML HUD & Controls)          |
|       +---> [CampusRenderer.ts] (Buildings & Rooms)             |                      |
|       |                                                         v                      |
|       +---> [AgentAvatarRenderer.ts] (Avatars & Badges) <--- [vhqService.ts]           |
|       |                                                         |                      |
|       v                                                         v                      |
|  Three.js WebGLRenderer & PerspectiveCamera             CI4 Backend REST API           |
+----------------------------------------------------------------------------------------+
```

---

## 2. Rendering Lifecycle & Memory Hygiene

To eliminate memory leaks when navigating between `/dashboard` and `/vhq`:
1. **Geometric Tracking**: All procedural geometries (`BoxGeometry`, `CylinderGeometry`, `RingGeometry`, `PlaneGeometry`) and materials (`MeshStandardMaterial`, `MeshLambertMaterial`, `MeshBasicMaterial`) are collected in internal tracker arrays.
2. **Deterministic Disposal**: Upon route unmount or view teardown:
   - All geometries execute `.dispose()`.
   - All materials execute `.dispose()`.
   - All canvas textures execute `.dispose()`.
   - WebGL render context is formally cleaned via `renderer.dispose()`.
   - Event listeners for pointer, wheel, resize, and context-loss are safely detached.

---

## 3. Camera Navigation & Smooth Cinematic Tweening

The camera supports both manual user manipulation and automated bookmark flying:
- **Orbit Mode**: Left-click drag adjusts spherical angles `theta` (azimuth) and `phi` (polar elevation) around the focal point.
- **Pan Mode**: Right-click drag pans the camera focal plane horizontally and vertically.
- **Zoom Mode**: Wheel or pinch changes orbit radius with clamping `[8, 130]`.
- **Cinematic Interpolation (`flyTo`)**:
  - Bookmarks and agent selection trigger cubic ease-out tweening (`progress = 1 - Math.pow(1 - progress, 3)`).
  - Target position and look-at point lerp smoothly across 900–1200ms without camera clipping or jarring cuts.

---

## 4. Raycaster Hit Testing

Interactive objects register their meshes in an `interactiveMeshes` array:
- When a user clicks on the canvas, raycasting prioritizes **Agent Avatars**, followed by **Workstations**, **Rooms**, and **Building Shells**.
- Selecting an entity opens its corresponding detail drawer in the HUD overlay and smoothly pivots the camera to focus on it.
