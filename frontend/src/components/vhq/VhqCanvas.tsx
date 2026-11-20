/**
 * MCE AI Office — Three.js Virtual Headquarters Canvas Controller (Prompt 23)
 * Full interactive 3D viewport with smooth camera navigation, raycaster selection,
 * and reliable WebGL lifecycle management.
 */

import React, { useEffect, useRef } from 'react';
import {
  AmbientLight,
  DirectionalLight,
  Fog,
  HemisphereLight,
  PerspectiveCamera,
  Raycaster,
  Scene,
  Vector2,
  Vector3,
  WebGLRenderer,
} from 'three';
import { CampusRenderer } from './CampusRenderer';
import { AgentAvatarRenderer } from './AgentAvatarRenderer';
import type {
  HqBuilding,
  HqRoom,
  HqWorkstation,
  AgentPresence,
  CameraBookmark,
} from '../../types/vhq';

export interface VhqCanvasProps {
  buildings: HqBuilding[];
  rooms: HqRoom[];
  workstations: HqWorkstation[];
  presences: AgentPresence[];
  activeBookmark: CameraBookmark | null;
  selectedAgentId: number | null;
  onSelectAgent: (agent: AgentPresence) => void;
  onSelectBuilding: (building: HqBuilding) => void;
  onSelectRoom: (room: HqRoom) => void;
  onSelectWorkstation: (ws: HqWorkstation) => void;
  onFallback2D: () => void;
}

export const VhqCanvas: React.FC<VhqCanvasProps> = ({
  buildings,
  rooms,
  workstations,
  presences,
  activeBookmark,
  selectedAgentId,
  onSelectAgent,
  onSelectBuilding,
  onSelectRoom,
  onSelectWorkstation,
  onFallback2D,
}) => {
  const containerRef = useRef<HTMLDivElement>(null);
  const canvasRef = useRef<HTMLCanvasElement>(null);

  // References to long-lived Three.js objects
  const sceneRef = useRef<Scene | null>(null);
  const cameraRef = useRef<PerspectiveCamera | null>(null);
  const rendererRef = useRef<WebGLRenderer | null>(null);
  const campusRendererRef = useRef<CampusRenderer | null>(null);
  const avatarRendererRef = useRef<AgentAvatarRenderer | null>(null);

  // Camera Orbit State
  const orbitState = useRef({
    target: new Vector3(0, 0, 0),
    radius: 70,
    theta: Math.PI / 4, // Azimuth angle
    phi: Math.PI / 3.5, // Polar elevation angle
    isDragging: false,
    dragButton: 0,
    lastX: 0,
    lastY: 0,
    // Smooth Tweening
    isTweening: false,
    tweenStart: 0,
    tweenDuration: 1000,
    startPos: new Vector3(),
    targetPos: new Vector3(),
    startLookAt: new Vector3(),
    targetLookAt: new Vector3(),
  });

  // 1. Initialize Three.js Scene and Render Loop
  useEffect(() => {
    const container = containerRef.current;
    const canvas = canvasRef.current;
    if (!container || !canvas) return;

    let renderer: WebGLRenderer;
    try {
      renderer = new WebGLRenderer({
        canvas,
        antialias: true,
        alpha: false,
        powerPreference: 'high-performance',
      });
      renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
      renderer.setSize(container.clientWidth, container.clientHeight);
      rendererRef.current = renderer;
    } catch {
      onFallback2D();
      return;
    }

    const scene = new Scene();
    scene.background = null;
    scene.fog = new Fog(0x090d16, 50, 160);
    sceneRef.current = scene;

    const camera = new PerspectiveCamera(
      45,
      container.clientWidth / container.clientHeight,
      0.5,
      400
    );
    cameraRef.current = camera;

    // Lighting
    const ambLight = new AmbientLight(0xdbeafe, 0.7);
    const sunLight = new DirectionalLight(0xffffff, 1.3);
    sunLight.position.set(40, 70, 30);
    const hemiLight = new HemisphereLight(0x38bdf8, 0x0f172a, 0.6);
    scene.add(ambLight, sunLight, hemiLight);

    // Procedural Renderers
    const campusRenderer = new CampusRenderer();
    campusRendererRef.current = campusRenderer;
    scene.add(campusRenderer.root);

    const avatarRenderer = new AgentAvatarRenderer();
    avatarRendererRef.current = avatarRenderer;
    scene.add(avatarRenderer.root);

    // Initial camera placement
    camera.position.set(0, 45, 55);
    camera.lookAt(0, 0, -2);

    let animId: number;
    let clock = 0;

    const renderLoop = () => {
      animId = requestAnimationFrame(renderLoop);
      clock += 0.016;

      const st = orbitState.current;

      // Handle Smooth Camera Tweening
      if (st.isTweening) {
        const elapsed = performance.now() - st.tweenStart;
        const progress = Math.min(1, elapsed / st.tweenDuration);
        // Cubic ease out
        const t = 1 - Math.pow(1 - progress, 3);

        camera.position.lerpVectors(st.startPos, st.targetPos, t);
        st.target.lerpVectors(st.startLookAt, st.targetLookAt, t);
        camera.lookAt(st.target);

        if (progress >= 1) {
          st.isTweening = false;
          // Recompute spherical angles from new position
          const offset = camera.position.clone().sub(st.target);
          st.radius = offset.length();
          st.phi = Math.acos(Math.max(-1, Math.min(1, offset.y / st.radius)));
          st.theta = Math.atan2(offset.x, offset.z);
        }
      } else {
        // Normal Orbit Camera Update
        const x = st.target.x + st.radius * Math.sin(st.phi) * Math.sin(st.theta);
        const y = st.target.y + st.radius * Math.cos(st.phi);
        const z = st.target.z + st.radius * Math.sin(st.phi) * Math.cos(st.theta);
        camera.position.set(x, y, z);
        camera.lookAt(st.target);
      }

      // Animate agent bobbing and status pulses
      avatarRenderer.animate(clock);

      renderer.render(scene, camera);
    };

    animId = requestAnimationFrame(renderLoop);

    // Resize Handler
    const handleResize = () => {
      if (!container || !camera || !renderer) return;
      camera.aspect = container.clientWidth / container.clientHeight;
      camera.updateProjectionMatrix();
      renderer.setSize(container.clientWidth, container.clientHeight);
    };

    window.addEventListener('resize', handleResize);

    return () => {
      cancelAnimationFrame(animId);
      window.removeEventListener('resize', handleResize);
      campusRenderer.dispose();
      avatarRenderer.dispose();
      renderer.dispose();
    };
  }, [onFallback2D]);

  // 2. Rebuild Campus Scene Graph when spatial data arrives
  useEffect(() => {
    if (campusRendererRef.current && buildings.length > 0) {
      campusRendererRef.current.buildCampus(buildings, rooms, workstations);
    }
  }, [buildings, rooms, workstations]);

  // 3. Update Avatars when presence data changes
  useEffect(() => {
    if (avatarRendererRef.current && presences.length > 0) {
      avatarRendererRef.current.updateAgents(presences);
    }
  }, [presences]);

  // 4. Handle Camera Bookmarks Navigation
  useEffect(() => {
    if (!activeBookmark || !cameraRef.current) return;

    flyTo(
      new Vector3(
        activeBookmark.camera_pos_x,
        activeBookmark.camera_pos_y,
        activeBookmark.camera_pos_z
      ),
      new Vector3(
        activeBookmark.look_at_x,
        activeBookmark.look_at_y,
        activeBookmark.look_at_z
      ),
      1200
    );
  }, [activeBookmark]);

  // 5. Handle Focus on Selected Agent
  useEffect(() => {
    if (!selectedAgentId || !avatarRendererRef.current) return;
    const avatars = avatarRendererRef.current.getAvatars();
    const node = avatars.get(selectedAgentId);
    if (node) {
      const pl = node.agent.placement;
      if (pl) {
        flyTo(
          new Vector3(pl.pos_x + 4, pl.pos_y + 4.5, pl.pos_z + 6),
          new Vector3(pl.pos_x, pl.pos_y + 1.2, pl.pos_z),
          900
        );
      }
    }
  }, [selectedAgentId]);

  /**
   * Smooth camera transition helper
   */
  const flyTo = (targetPos: Vector3, lookAtPos: Vector3, durationMs = 1000) => {
    if (!cameraRef.current) return;
    const st = orbitState.current;
    st.isTweening = true;
    st.tweenStart = performance.now();
    st.tweenDuration = durationMs;
    st.startPos.copy(cameraRef.current.position);
    st.targetPos.copy(targetPos);
    st.startLookAt.copy(st.target);
    st.targetLookAt.copy(lookAtPos);
  };

  // 6. User Pointer Orbit, Pan, and Zoom Controls
  const handlePointerDown = (e: React.PointerEvent) => {
    orbitState.current.isDragging = true;
    orbitState.current.dragButton = e.button;
    orbitState.current.lastX = e.clientX;
    orbitState.current.lastY = e.clientY;
    orbitState.current.isTweening = false; // Cancel active tween on user input
    (e.target as HTMLElement).setPointerCapture(e.pointerId);
  };

  const handlePointerMove = (e: React.PointerEvent) => {
    const st = orbitState.current;
    if (!st.isDragging) return;

    const dx = e.clientX - st.lastX;
    const dy = e.clientY - st.lastY;
    st.lastX = e.clientX;
    st.lastY = e.clientY;

    if (st.dragButton === 0) {
      // Orbit Azimuth & Polar
      st.theta -= dx * 0.006;
      st.phi = Math.max(0.15, Math.min(Math.PI / 2 - 0.02, st.phi - dy * 0.006));
    } else if (st.dragButton === 2) {
      // Pan target
      const forward = new Vector3(
        -Math.sin(st.theta),
        0,
        -Math.cos(st.theta)
      ).normalize();
      const right = new Vector3(
        Math.cos(st.theta),
        0,
        -Math.sin(st.theta)
      ).normalize();

      st.target.addScaledVector(right, -dx * 0.05);
      st.target.addScaledVector(forward, dy * 0.05);
    }
  };

  const handlePointerUp = (e: React.PointerEvent) => {
    orbitState.current.isDragging = false;
    try {
      (e.target as HTMLElement).releasePointerCapture(e.pointerId);
    } catch {
      // Ignore
    }
  };

  const handleWheel = (e: React.WheelEvent) => {
    e.preventDefault();
    const st = orbitState.current;
    st.isTweening = false;
    st.radius = Math.max(8, Math.min(130, st.radius + e.deltaY * 0.05));
  };

  // 7. Raycasting Click Selection
  const handleClick = (e: React.MouseEvent) => {
    const container = containerRef.current;
    const camera = cameraRef.current;
    if (!container || !camera) return;

    const rect = container.getBoundingClientRect();
    const mouse = new Vector2(
      ((e.clientX - rect.left) / rect.width) * 2 - 1,
      -((e.clientY - rect.top) / rect.height) * 2 + 1
    );

    const raycaster = new Raycaster();
    raycaster.setFromCamera(mouse, camera);

    // Check Avatars first (highest priority)
    if (avatarRendererRef.current) {
      const avatarMeshes = avatarRendererRef.current.getInteractiveMeshes();
      const hits = raycaster.intersectObjects(avatarMeshes, false);
      if (hits.length > 0) {
        const agent = hits[0].object.userData?.agent;
        if (agent) {
          onSelectAgent(agent);
          return;
        }
      }
    }

    // Check Campus Fixtures & Buildings
    if (campusRendererRef.current) {
      const campusMeshes = campusRendererRef.current.getInteractiveMeshes();
      const hits = raycaster.intersectObjects(campusMeshes, false);
      if (hits.length > 0) {
        const u = hits[0].object.userData;
        if (u?.type === 'workstation') {
          onSelectWorkstation(u.workstation);
        } else if (u?.type === 'room') {
          onSelectRoom(u.room);
        } else if (u?.type === 'building') {
          onSelectBuilding(u.building);
        }
      }
    }
  };

  return (
    <div
      ref={containerRef}
      style={{
        width: '100%',
        height: '100%',
        position: 'relative',
        touchAction: 'none',
        userSelect: 'none',
        outline: 'none',
        overflow: 'hidden',
        background: 'radial-gradient(circle at 50% 40%, #0f172a 0%, #020617 100%)',
      }}
      onPointerDown={handlePointerDown}
      onPointerMove={handlePointerMove}
      onPointerUp={handlePointerUp}
      onWheel={handleWheel}
      onClick={handleClick}
      onContextMenu={(e) => e.preventDefault()}
    >
      <canvas
        ref={canvasRef}
        style={{
          width: '100%',
          height: '100%',
          display: 'block',
        }}
      />
    </div>
  );
};
