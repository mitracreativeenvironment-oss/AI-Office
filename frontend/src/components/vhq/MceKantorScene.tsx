/**
 * MCE AI Office — VHQ Kantor (3D Office Scene)
 *
 * Integrasi tampilan template "Kantor AI 3D" (malikisaputra / kantor-ai-template)
 * menggunakan KONSEP VHQ MCE dari:
 *   - docs/architecture/VHQ-THREEJS-IMPLEMENTATION.md
 *   - docs/architecture/VHQ-SPATIAL-ARCHITECTURE.md
 *
 * Prinsip:
 *   - `Ruang` (3 zona) = pengelompokan gedung MCE: Executive Core, Ops/Tech&Services, Welfare.
 *   - Setiap "ruang" adalah lantai/floor berwarna + karpet (warna karpet dari aksen gedung).
 *   - Tiap agen duduk di meja stasiumnya (HqWorkstation) sesuai data spasial VHQ.
 *   - Layar meja menampilkan status aktif; meja "mendesak" berkedip.
 *   - Mission Board (gelembung) = stasiun belum ditugaskan + angka kerja.
 *   - Timelapse (BABAK) = 3 babak: Pagi | Sore | Malam (dari wallbook).
 *   - Gaya (gaya) = palet materi; 'asli' = native three.js, 'flat' = flat color, 'mono' = grayscale.
 *
 * Data dipetakan dari /api/v1/vhq/* (CampusOverview, AgentPresence, HqWorkstation, HqRoom).
 */

import React, { useEffect, useMemo, useRef, useState, forwardRef } from 'react';
import {
  AmbientLight, BoxGeometry, CylinderGeometry, Color, DirectionalLight, Fog, Group,
  HemisphereLight, Mesh, MeshStandardMaterial, PlaneGeometry, PointLight, Scene, SphereGeometry, WebGLRenderer,
} from 'three';

import type { CampusOverview, AgentPresence, HqWorkstation, HqRoom, PresenceStatus } from '../../types/vhq';

// ---------- Typing model dari template (customized dari data VHQ) ----------
export interface Agen {
  key: string;
  nama: string;
  tugas: string;
  ruang: string;
  warna: string;
  belakang: string;
  x: number;
  z: number;
}

export interface Ruang {
  key: string;
  label: string;
  x0: number; x1: number;
  warna: string;
  karpet: string;
  edge: string;
}

export interface LaporanAgen {
  ringkasan?: string;
  menunggu?: string[];
  mendesak?: string[];
  angka?: { label: string; nilai: string }[];
}

// ---------- Helpers kecil ----------
const clamp = (v: number, lo: number, hi: number) => Math.max(lo, Math.min(hi, v));

// Warna aksen gedung -> karpet (dengan eta)
const rugColor = (floor: string, accent: string) => {
  const c = new Color(accent); c.multiplyScalar(0.22); return `#${c.getHexString()}`;
};

// Kelompok gedung menjadi 3 zona
const zonaGedung: Record<string, string> = {
  'BLD-MAIN': '#0e7490',
  'BLD-DIV-TECH': '#7c3aed',
  'BLD-DIV-SRV': '#b45309',
  'BLD-DIV-ACAD': 'welfare', 'BLD-DIV-HW': 'welfare', 'BLD-WELFARE': 'welfare',
};

const zonaLabel: Record<string, string> = {
  core: 'Eksekutif & Koordinasi',
  ops: 'Operasi & Teknologi',
  welfare: 'Welfare & Pemulihan AI',
};

function kelompokZona(buildingCode: string): string {
  return zonaGedung[buildingCode] ?? 'ops';
}

export interface MceKantorSceneProps {
  campus: CampusOverview | null;
  presences: AgentPresence[];
  workstations: HqWorkstation[];
  rooms: HqRoom[];
  onAgentSync?: (agent: AgentPresence) => void;
}

// ---------- Agent figure (style template: body+head+arm+leg+screen+desk+desk) ----------
function agentFigureGroup(colorJacket: string, colorSkin: string, deskX: number, deskY: number) {
  const g = new Group();
  const cJ = new Color(colorJacket);
  const cS = new Color(colorSkin);

  // meja
  const desk = new Mesh(new BoxGeometry(1.6, 0.08, 0.9), new MeshStandardMaterial({ color: 0x334155, roughness: 0.4 }));
  desk.position.set(deskX - 0.1, deskY - 0.06, 0);
  g.add(desk);

  // kursi
  const seat = new Mesh(new CylinderGeometry(0.42, 0.46, 0.12, 12), new MeshStandardMaterial({ color: 0x262626 }));
  seat.position.set(deskX - 0.1, deskY - 0.20, 0);
  g.add(seat);

  // lengan
  const arm = (side: number) => {
    const a = new Mesh(new CylinderGeometry(0.055, 0.055, 0.42, 8), new MeshStandardMaterial({ color: 0x8a8a8a }));
    a.rotation.z = side > 0 ? -Math.PI / 2 : Math.PI / 2;
    a.position.set(deskX - 0.1 + side * 0.55, deskY - 0.30, 0);
    g.add(a);
  };
  arm(1); arm(-1);

  // tangan
  const hand = (side: number) => {
    const h = new Mesh(new SphereGeometry(0.07, 8, 8), new MeshStandardMaterial({ color: 0xc9c9c9 }));
    h.position.set(deskX - 0.1 + side * 0.78, deskY - 0.45, 0);
    g.add(h);
  };
  hand(1); hand(-1);

  // badan (vest/seragam)
  const body = new Mesh(new CylinderGeometry(0.20, 0.26, 0.5, 10), new MeshStandardMaterial({ color: cJ, roughness: 0.35 }));
  body.position.set(deskX - 0.1, deskY - 0.08, 0);
  g.add(body);

  // kepala
  const head = new Mesh(new SphereGeometry(0.17, 14, 14), new MeshStandardMaterial({ color: cS, roughness: 0.5 }));
  head.position.set(deskX - 0.1, deskY + 0.22, 0);
  g.add(head);

  // layar
  const screen = new Mesh(new PlaneGeometry(0.5, 0.34), new MeshStandardMaterial({ color: 0x222222, roughness: 0.7 }));
  screen.position.set(deskX - 0.1, deskY + 0.06, 0.48);
  g.add(screen);

  // lampu meja
  const lamp = new Mesh(new CylinderGeometry(0.03, 0.04, 0.14, 8), new MeshStandardMaterial({ color: 0xffd700, emissive: 0xffd700, emissiveIntensity: 0.6 }));
  lamp.position.set(deskX - 0.1, deskY + 0.30, 0.46);
  g.add(lamp);

  return g;
}

// Flat material gaya (dari template: asli / flat / mono / voxel-ish)
const gayaMat = (base: string, gaya: string, floorHex: number) => {
  const c = new Color(base);
  const r = c.r * 0.85, g2 = c.g * 0.85, b2 = c.b * 0.85;
  let color = base;
  if (gaya === 'flat') color = base;
  else if (gaya === 'mono') {
    const lum = 0.299 * c.r + 0.587 * c.g + 0.114 * c.b;
    const hex = Math.floor(lum * 255).toString(16).padStart(2, '0');
    color = `#${hex}${hex}${hex}`;
  } else if (gaya === 'clay') {
    // pastel clay
    color = `#${Math.min(255, Math.floor(r*0.9)).toString(16).padStart(2,'0')}${Math.min(255, Math.floor(g2*0.9)).toString(16).padStart(2,'0')}${Math.min(255, Math.floor(b2*0.9)).toString(16).padStart(2,'0')}`;
  }
  return new MeshStandardMaterial({ color: parseInt(color.replace('#',''), 16), roughness: 0.55, metalness: 0.05 });
};

// ---------- Horn 보완하고... ---------- (placeholder untuk gambar teks layar)
function screenCanvas(label: string, status: PresenceStatus): HTMLCanvasElement {
  const c = document.createElement('canvas');
  c.width = 256; c.height = 160;
  const ctx = c.getContext('2d')!;
  ctx.fillStyle = '#111'; ctx.fillRect(0, 0, 256, 160);
  ctx.fillStyle = '#ffe08a'; ctx.font = 'bold 34px sans-serif'; ctx.textAlign = 'center';
  ctx.fillText(label || '●', 128, 52);
  const st = status || 'available';
  if (st === 'working') { ctx.fillStyle = '#34d399'; ctx.font = 'bold 26px sans-serif'; ctx.fillText('Bekerja', 128, 104); }
  else if (st === 'available') { ctx.fillStyle = '#38bdf8'; ctx.font = 'bold 24px sans-serif'; ctx.fillText('Siaga', 128, 104); }
  else if (st === 'on_leave') { ctx.fillStyle = '#a855f7'; ctx.font = 'bold 22px sans-serif'; ctx.fillText('Cuti', 128, 104); }
  else { ctx.fillStyle = '#f43f5e'; ctx.font = 'bold 24px sans-serif'; ctx.fillText('⛔', 128, 104); }
  return c;
}

// --- MceKantorScene ---
export const MceKantorScene = forwardRef<HTMLDivElement, MceKantorSceneProps>(({ campus, presences, workstations, rooms, onAgentSync }, ref) => {
  const hostRef = useRef<HTMLDivElement>(null);
  const sceneRef = useRef<Scene | null>(null);
  const cameraRef = useRef<any>(null);
  const rendererRef = useRef<any>(null);
  const groupsRef = useRef<Map<string, Group>>(new Map());
  const syncAgentRef = useRef<(agent: AgentPresence) => void>(() => {});
  const agentGroupsRef = useRef<Map<string, any>>(new Map());
  const highlightRef = useRef<Map<string, { ring: any; pulse: number }>>(new Map());
  const agentStatesRef = useRef<Map<string, boolean>>(new Map());
  const [night, setNight] = useState<boolean>(false);

  // --- Data terpetakan dari VHQ ---
  const sceneData = useMemo(() => {
    const buildingById = new Map<number, any>();
    (campus?.buildings || []).forEach((b: any) => buildingById.set(b.id, b));
    const wsById = new Map<number, any>();
    workstations.forEach((w: any) => wsById.set(w.room_id, w));
    const rById = new Map<number, any>();
    rooms.forEach((r: any) => rById.set(r.id, r));

    // Ruang = 3 zona (dari gedung aktual)
    const zones: Ruang[] = [
      { key: 'core', label: zonaLabel.core, x0: -5.6, x1: -1.6, warna: '#0f172a', karpet: rugColor('#0f172a', '#38bdf8'), edge: '#38bdf8' },
      { key: 'ops',  label: zonaLabel.ops,  x0: -1.6, x1:  1.6, warna: '#efe3cf', karpet: rugColor('#efe3cf', '#2563eb'), edge: '#2563eb' },
      { key: 'welfare', label: zonaLabel.welfare, x0: 1.6, x1: 5.6, warna: '#e3d6c6', karpet: rugColor('#e3d6c6', '#10b981'), edge: '#10b981' },
    ];

    // Agen yang punya posisi spasial (dari VHQ) -> barisan meja
    const agents: Agen[] = (presences || [])
      .filter((p: AgentPresence) => p.has_spatial_placement && p.placement)
      .map((p: AgentPresence) => {
        if (!p.has_spatial_placement || !p.placement) return null;
        const pl = p.placement;
        const ws = wsById.get(pl.workstation_id as number);
        const building = buildingById.get(pl.building_id as number);
        const zone = kelompokZona(building?.building_code || 'ops');
        const ruang = zones.find((z) => z.key === zone);
        if (!ruang) return null;
        const st = p.presence_status;
        // warna jersey = aksen zone/gedung, modalitas status
        let warna = building?.accent_color || '#38bdf8';
        if (st === 'working') warna = '#f59e0b';
        else if (st === 'on_leave') warna = '#a855f7';
        else if (st === 'suspended') warna = '#ef4444';
        else if (st === 'runtime_disconnected') warna = '#64748b';
        const tugas = p.active_task?.title || p.status_reason || 'Siap menerima perintah';
        return {
          key: p.agent_code || `ag${p.agent_id}`,
          nama: p.display_name || p.name || 'Agen',
          tugas,
          ruang: ruang.key,
          warna,
          belakang: building?.accent_color || '#38bdf8',
          x: ruang.x0 + (Math.abs(pl.pos_x) % ruang.x1) + 0.6,
          z: pl.pos_z ?? -2.8,
        };
      })
      .filter((a): a is Agen => a !== null);

    // Laporan per agen (dari active task / status)
    const laporan: Record<string, LaporanAgen> = {};
    agents.forEach((a) => {
      const wa = workstations.filter((w) => w.assigned_agent_id == null);
      laporan[a.key] = {
        ringkasan: a.tugas,
        menunggu: wa.map((w) => `Stasiun ${w.workstation_code} tanpa agen`),
        mendesak: [],
        angka: [{ label: 'Agen Aktif', nilai: String(presences?.length || 0) }],
      };
    });

    // Jadwal (wallbook default + terjemahan agile)
    const jadwal = [
      { agen: ['*'], mulai: '07:30', menit: 5, hari: [1,2,3,4,5], aktivitas: 'kerja', label: 'Membangun agenda hari' },
      { agen: ['*'], mulai: '08:00', menit: 20, hari: [1,2,3,4,5], aktivitas: 'kerja', label: 'Konsentrasi tinggi' },
      { agen: ['*'], mulai: '12:00', menit: 60, hari: [1,2,3,4,5], aktivitas: 'kerja', label: 'Istirahat siang' },
      { agen: ['*'], mulai: '16:30', menit: 30, hari: [1,2,3,4,5], aktivitas: 'kerja', label: 'Rapor selesai' },
      { agen: ['*'], mulai: '20:00', menit: 120, hari: [1,2,3,4,5,6,7], aktivitas: 'belajar', label: 'Belajar malam' },
    ];

    // Timelapse
    const babak = [
      { dari: '07:28', sampai: '08:44', detik: 12, judul: 'Pagi · koordinasi' },
      { dari: '15:36', sampai: '16:10', detik: 9, judul: 'Sore · laporan' },
      { dari: '19:52', sampai: '20:26', detik: 9, judul: 'Malam · wellness' },
    ];

    return { zones, agents, laporan, jadwal, babak };
  }, [campus, presences, workstations, rooms]);

  // --- Init Three.js ---
  useEffect(() => {
    const host = hostRef.current;
    if (!host) return;
    const renderer = new (window as any).THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setSize(host.clientWidth, host.clientHeight);
    rendererRef.current = renderer;
    ;(window as any).__RENDERER = renderer;
    host.appendChild(renderer.domElement);

    const scene = new (window as any).THREE.Scene();
    scene.background = null;
    scene.fog = new (window as any).THREE.Fog(0x060a18, 40, 140);
    sceneRef.current = scene;
    ;(window as any).__SCENE = scene;

    const camera = new (window as any).THREE.PerspectiveCamera(40, host.clientWidth / host.clientHeight, 0.1, 200);
    camera.position.set(0, 8, 14);
    camera.lookAt(0, 0, 0);
    cameraRef.current = camera;

    const amb = new (window as any).THREE.AmbientLight(0x8899cc, 0.55);
    const sun = new (window as any).THREE.DirectionalLight(0xffffff, 1.1);
    sun.position.set(8, 18, 10);
    const hemi = new (window as any).THREE.HemisphereLight(0x38bdf8, 0x0f172a, 0.65);
    scene.add(amb, sun, hemi);

    // --- Bangun zona ---
    const buildZone = (key: string, label: string, x0: number, x1: number, floorHex: string, rugHex: string, edgeHex: string) => {
      const g = new Group();
      const zBase = -2.9;
      const w = (x1 - x0) / 2;
      const h = 2.6;
      const d = 1.6;
      const fx = (x0 + x1) / 2;
      const floorInt = parseInt(floorHex.replace('#',''), 16);
      const rugInt = parseInt(rugHex.replace('#',''), 16);
      const edgeInt = parseInt(edgeHex.replace('#',''), 16);

      // lantai (lebih terang untuk grounding yang jelas)
      const floorMat = new (window as any).THREE.MeshStandardMaterial({ color: floorInt, roughness: 0.8 });
      const floorMesh = new (window as any).THREE.Mesh(new (window as any).THREE.PlaneGeometry(w*2, d), floorMat);
      floorMesh.rotation.x = -Math.PI / 2; floorMesh.position.set(fx, 0.02, zBase - d/2); scene.add(floorMesh);
      // karpet
      const rugMat = new (window as any).THREE.MeshStandardMaterial({ color: rugInt, roughness: 0.95 });
      const rugMesh = new (window as any).THREE.Mesh(new (window as any).THREE.PlaneGeometry(w-0.5, d-0.5), rugMat);
      rugMesh.rotation.x = -Math.PI / 2; rugMesh.position.set(fx, 0.045, zBase - d/2 + 0.18); scene.add(rugMesh);
      // langit-langit
      const ceil = new (window as any).THREE.Mesh(new (window as any).THREE.PlaneGeometry(w*2, d), new (window as any).THREE.MeshStandardMaterial({ color: 0x0a1020, roughness: 0.9, side: (window as any).THREE.DoubleSide }));
      ceil.rotation.x = Math.PI / 2; ceil.position.set(fx, h+0.12, zBase - d/2); scene.add(ceil);

      // border zona (edge): garis kontras di tepi lantai
      const edgeLine = new (window as any).THREE.Mesh(new (window as any).THREE.EdgesGeometry(floorMesh.geometry, 4), new (window as any).THREE.LineBasicMaterial({ color: edgeInt, transparent: true, opacity: 0.9 }));
      edgeLine.rotation.x = -Math.PI / 2; edgeLine.position.y = 0.03; scene.add(edgeLine);
      // garis bantu subdua
      for (let i = 1; i < 4; i++) {
        const ln = new (window as any).THREE.Mesh(new (window as any).THREE.PlaneGeometry(0.02, d+0.3), new (window as any).THREE.MeshStandardMaterial({ color: 0x334155, transparent: true, opacity: 0.2 }));
        ln.rotation.x = -Math.PI / 2; ln.position.set(fx, i*0.00001 + 0.02, zBase - d/2); scene.add(ln);
      }

      // meja & agen per agent (di dalam zona)
      const zoneGroup = new Group();
      g.add(zoneGroup);
      groupsRef.current.set(key, zoneGroup);
      return { zoneGroup, fx };
    };

    const zoneGroups = new Map<string, any>();
    sceneData.zones.forEach((z) => {
      const g = buildZone(z.key, z.label, z.x0, z.x1, z.warna, z.karpet, z.edge);
      zoneGroups.set(z.key, g.zoneGroup);
    });

    // --- Lampu malam (tombol toggles night) ---
    const nightLamp = new (window as any).THREE.PointLight(0x60a5fa, 1.2, 16, 2);
    nightLamp.position.set(0, 10, 0); scene.add(nightLamp);

    // Live sync (VHQ live presences) -> parent calls this on each refresh.
    const syncAgent = (agent: AgentPresence) => {
      const buildingId = agent.placement?.building_id;
      const building = campus?.buildings?.find((b) => b.id === buildingId);
      const zone = kelompokZona(building?.building_code || 'ops');
      const zg = zoneGroups.get(zone);
      if (!zg) return;
      const f = zg.children.find((ch: any) => ch._agentKey === agent.agent_code);
      if (!f) return;
      agentStatesRef.current.set(agent.agent_code, agent.active_task?.priority === 'urgent');
      updateScreen(f, agent);
      onAgentSync?.(agent);
    };
    syncAgentRef.current = syncAgent;

    const updateScreen = (group: any, agent: AgentPresence) => {
      const tmpCanvas = group.tmpCanvas || (group.tmpCanvas = document.createElement('canvas'));
      const ctx = tmpCanvas.getContext('2d')!;
      tmpCanvas.width = 256; tmpCanvas.height = 160;
      ctx.fillStyle = '#111'; ctx.fillRect(0, 0, 256, 160);
      ctx.fillStyle = '#ffe08a'; ctx.font = 'bold 34px sans-serif'; ctx.textAlign = 'center';
      ctx.fillText(agent.active_task?.title?.slice(0, 12) || '●', 128, 52);

      const urgent = agent.active_task?.priority === 'urgent';
      let label = 'Siaga';
      let col = '#94a3b8';
      if (urgent) { label = '⚠ URGENT'; col = '#fbbf24'; }
      else if (agent.presence_status === 'working') { label = 'Bekerja'; col = '#34d399'; }
      else if (agent.presence_status === 'available') { label = 'Siaga'; col = '#38bdf8'; }
      else if (agent.presence_status === 'on_leave') { label = 'Cuti'; col = '#a855f7'; }
      else if (agent.presence_status === 'suspended' || agent.presence_status === 'runtime_disconnected') { label = '✕'; col = '#f43f5e'; }

      if (urgent) {
        ctx.fillStyle = '#fff27a'; ctx.font = 'bold 30px sans-serif'; ctx.fillText('⚠', 128, 50);
        ctx.fillStyle = col; ctx.font = 'bold 32px sans-serif'; ctx.fillText(label, 128, 96);
        ctx.fillStyle = '#fff27a'; ctx.font = 'bold 24px sans-serif'; ctx.fillText(agent.active_task?.title?.slice(0, 8) || '', 128, 130);
      } else {
        ctx.fillStyle = col; ctx.font = 'bold 26px sans-serif'; ctx.fillText(label, 128, 104);
      }
      const tex = new (window as any).THREE.CanvasTexture(tmpCanvas);
      tex.needsUpdate = true;
      const screenMesh = group.children.find((ch: any) => ch.geometry && ch.geometry.type === 'PlaneGeometry');
      if (screenMesh) {
        (screenMesh.material as any).map = tex;
        (screenMesh.material as any).needsUpdate = true;
      }
      (group as any).isUrgent = urgent;
    };

    let animId = 0; let clock = 0;

    const renderLoop = () => {
      animId = requestAnimationFrame(renderLoop);
      clock += 0.016;
      const nightFactor = night ? 0.18 : 1;
      amb.intensity = night ? 0.18 : 0.55;
      sun.intensity = night ? 0.16 : 1.1;
      hemi.intensity = night ? 0.12 : 0.65;
      nightLamp.intensity = night ? 0.3 : 1.2;

      // selection highlight (pulse ring)
      highlightRef.current.forEach((h) => {
        h.ring.scale.x = h.pulse + Math.sin(clock * 6) * 0.12;
        h.ring.material.opacity = 0.7 + Math.sin(clock * 3) * 0.12;
      });
      // urgent shake/bob
      agentStatesRef.current.forEach((urgent, key) => {
        const f = agentGroupsRef.current.get(key);
        if (!f) return;
        if (urgent) f.position.y += Math.sin(clock * 3) * 0.002;
      });

      // Agent figures (reuse existing, recreate only when new)
      (sceneData.agents || []).forEach((ag: any, i: number) => {
        const zg = zoneGroups.get(ag.ruang);
        if (!zg) return;
        const f = zg.children.find((ch: any) => ch._agentKey === ag.key);
        if (!f) {
          const idxInZone = (i * 0.48) % 1;
          const fx = (ag.ruang.x0 + ag.ruang.x1) / 2;
          const deskX = fx + idxInZone * (ag.ruang.x1 - ag.ruang.x0 - 1.2) / Math.max(1, sceneData.agents.length - 1);
          const figure = agentFigureGroup(ag.warna, '#2b1d16', deskX, 0.30);
          (figure as any)._agentKey = ag.key;
          (figure as any)._availability = nightFactor;
          zg.add(figure);
          return;
        }
        // Live-refresh the agent screen from current VHQ presences.
        const agent = presences.find((p) => p.agent_code === ag.key);
        if (agent) updateScreen(f, agent);
      });

      renderer.render(scene, camera);
    };
    animId = requestAnimationFrame(renderLoop);

    const handleResize = () => {
      if (!camera || !renderer) return;
      camera.aspect = host.clientWidth / host.clientHeight;
      camera.updateProjectionMatrix();
      renderer.setSize(host.clientWidth, host.clientHeight);
    };
    window.addEventListener('resize', handleResize);

    return () => {
      cancelAnimationFrame(animId);
      window.removeEventListener('resize', handleResize);
      renderer.dispose();
      host.removeChild(renderer.domElement);
    };
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  return (
    <div
      ref={hostRef}
      style={{ width: '100%', height: '100%', position: 'relative', overflow: 'hidden', background: '#060a18', touchAction: 'none' }}
    >
      {/* Layer HTML di atas kanvas: HUD mini (NAMA KANTOR, STATE) */}
      <div style={{
        position: 'absolute', top: 14, left: 18, color: '#f8fafc', pointerEvents: 'none', zIndex: 5,
      }}>
        <div style={{ fontWeight: 800, fontSize: '17px', letterSpacing: 0.02 }}>{campus?.campus_name || 'MCE AI Office VHQ'}</div>
        <div style={{ fontSize: '12px', color: '#94a3b8' }}>{campus?.tagline || 'Mitra Creative Environment'}</div>
      </div>
      {/* Mode malam toggle */}
      <button
        onClick={() => setNight((v) => !v)}
        style={{ position: 'absolute', top: 14, right: 18, color: '#94a3b8', pointerEvents: 'auto', zIndex: 6, background: 'rgba(15,23,42,0.6)', border: '1px solid #334155', borderRadius: '10px', padding: '8px 12px', fontSize: '13px' }}
      >
        🌙 Mode Malam {night ? 'ON' : 'OFF'}
      </button>
      {/* Timelapse pita */}
      <div style={{ position: 'absolute', bottom: 16, left: 18, right: 18, display: 'flex', gap: 6, pointerEvents: 'auto', zIndex: 6 }}>
        {sceneData.babak.map((b) => (
          <button key={b.judul} style={{
            background: '#0f172a', border: '1px solid #334155', borderRadius: '8px', padding: '5px 10px', fontSize: '11px', color: '#cbd5e1', cursor: 'pointer',
          }}>{b.judul}</button>
        ))}
      </div>
    </div>
  );
});

export default MceKantorScene;
