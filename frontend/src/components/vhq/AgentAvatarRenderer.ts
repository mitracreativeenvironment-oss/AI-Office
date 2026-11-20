/**
 * MCE AI Office — Three.js Agent Avatar Renderer (Prompt 23)
 * Renders stylized 3D workforce agents with dynamic operational status rings,
 * floating identity badges, and idle bobbing animation.
 */

import {
  BoxGeometry,
  CanvasTexture,
  Color,
  CylinderGeometry,
  DoubleSide,
  Group,
  Mesh,
  MeshBasicMaterial,
  MeshLambertMaterial,
  MeshStandardMaterial,
  RingGeometry,
  SphereGeometry,
  Sprite,
  SpriteMaterial,
  Vector3,
} from 'three';
import type { AgentPresence, PresenceStatus } from '../../types/vhq';

export interface AvatarNode {
  agent: AgentPresence;
  group: Group;
  statusRing: Mesh;
  badge: Sprite;
  baseY: number;
  bobPhase: number;
}

export class AgentAvatarRenderer {
  public root: Group;
  private avatars: Map<number, AvatarNode> = new Map();
  private geometries: Array<{ dispose: () => void }> = [];
  private materials: Array<{ dispose: () => void }> = [];
  private textures: Array<{ dispose: () => void }> = [];
  private interactiveMeshes: Mesh[] = [];

  constructor() {
    this.root = new Group();
    this.root.name = 'AgentAvatarsRoot';
  }

  public getInteractiveMeshes(): Mesh[] {
    return this.interactiveMeshes;
  }

  public getAvatars(): Map<number, AvatarNode> {
    return this.avatars;
  }

  /**
   * Status color resolver matching MCE operational presence rules
   */
  public static getStatusColorHex(status: PresenceStatus): number {
    switch (status) {
      case 'available':
        return 0x10b981; // Emerald Green
      case 'working':
        return 0x3b82f6; // Sapphire Blue
      case 'on_leave':
        return 0xa855f7; // Amethyst Purple
      case 'scheduled_off':
        return 0x64748b; // Slate Gray
      case 'suspended':
        return 0xea580c; // Tangerine Orange
      case 'runtime_disconnected':
        return 0xf43f5e; // Rose Red
      case 'vacant':
      default:
        return 0x334155;
    }
  }

  /**
   * Sync and render avatars from the live agent presences list
   */
  public updateAgents(presences: AgentPresence[]): void {
    this.clear();

    for (const p of presences) {
      if (!p.has_spatial_placement || !p.placement) {
        continue;
      }

      this.createAvatar(p);
    }
  }

  /**
   * Create an individual 3D stylized avatar for an agent
   */
  private createAvatar(agent: AgentPresence): void {
    const pl = agent.placement!;
    const avatarGroup = new Group();
    avatarGroup.name = `Avatar_${agent.agent_code}`;

    // Position at workstation desk / pod
    const posX = pl.pos_x;
    const posY = pl.pos_y + 0.45; // Just above room floor
    const posZ = pl.pos_z;
    avatarGroup.position.set(posX, posY, posZ);
    avatarGroup.rotation.y = pl.rotation_y || 0;

    const statusHex = AgentAvatarRenderer.getStatusColorHex(agent.presence_status);

    // 1. Dynamic Status Floor Ring (Glow indicator beneath chair/desk)
    const ringGeo = new RingGeometry(0.8, 0.95, 24);
    ringGeo.rotateX(-Math.PI / 2);
    const ringMat = new MeshBasicMaterial({
      color: statusHex,
      side: DoubleSide,
      transparent: true,
      opacity: 0.85,
    });
    const statusRing = new Mesh(ringGeo, ringMat);
    statusRing.position.y = 0.02;
    avatarGroup.add(statusRing);
    this.track(ringGeo, ringMat);

    // 2. Stylized Character Body (Modern geometric low-poly capsule / torso)
    const torsoGeo = new CylinderGeometry(0.24, 0.28, 0.65, 12);
    const torsoColor = agent.classification === 'executive' ? 0x1e1b4b : 0x0f172a;
    const torsoMat = new MeshStandardMaterial({ color: torsoColor, roughness: 0.5 });
    const torso = new Mesh(torsoGeo, torsoMat);
    torso.position.y = 0.85;
    torso.userData = { type: 'agent', agent };
    avatarGroup.add(torso);
    this.interactiveMeshes.push(torso);
    this.track(torsoGeo, torsoMat);

    // 3. Head & Visor
    const headGeo = new SphereGeometry(0.22, 16, 16);
    const headMat = new MeshStandardMaterial({ color: 0x38bdf8, roughness: 0.2, metalness: 0.8 });
    const head = new Mesh(headGeo, headMat);
    head.position.y = 1.35;
    head.userData = { type: 'agent', agent };
    avatarGroup.add(head);
    this.interactiveMeshes.push(head);
    this.track(headGeo, headMat);

    // Glow Visor band
    const visorGeo = new CylinderGeometry(0.23, 0.23, 0.08, 16, 1, false, 0, Math.PI);
    const visorMat = new MeshBasicMaterial({ color: statusHex });
    const visor = new Mesh(visorGeo, visorMat);
    visor.position.set(0, 1.35, 0);
    visor.rotation.y = Math.PI / 2;
    avatarGroup.add(visor);
    this.track(visorGeo, visorMat);

    // 4. Overhead Text Badge / Sprite
    const badge = this.createBadgeSprite(agent.agent_code, agent.presence_status);
    badge.position.set(0, 1.9, 0);
    avatarGroup.add(badge);

    this.root.add(avatarGroup);

    this.avatars.set(agent.agent_id, {
      agent,
      group: avatarGroup,
      statusRing,
      badge,
      baseY: posY,
      bobPhase: Math.random() * Math.PI * 2,
    });
  }

  /**
   * Dynamic Canvas Sprite for overhead agent nameplates
   */
  private createBadgeSprite(agentCode: string, status: PresenceStatus): Sprite {
    const canvas = document.createElement('canvas');
    canvas.width = 256;
    canvas.height = 72;
    const ctx = canvas.getContext('2d');

    if (ctx) {
      // Rounded background pill
      ctx.fillStyle = 'rgba(15, 23, 42, 0.9)';
      ctx.beginPath();
      ctx.roundRect(8, 8, 240, 56, 28);
      ctx.fill();

      // Border matching status color
      const colorStyle = `#${AgentAvatarRenderer.getStatusColorHex(status).toString(16).padStart(6, '0')}`;
      ctx.lineWidth = 4;
      ctx.strokeStyle = colorStyle;
      ctx.stroke();

      // Status Dot
      ctx.fillStyle = colorStyle;
      ctx.beginPath();
      ctx.arc(36, 36, 8, 0, Math.PI * 2);
      ctx.fill();

      // Agent Code Text
      ctx.font = 'bold 24px Inter, system-ui, sans-serif';
      ctx.fillStyle = '#f8fafc';
      ctx.fillText(agentCode, 58, 44);
    }

    const texture = new CanvasTexture(canvas);
    this.textures.push(texture);

    const mat = new SpriteMaterial({ map: texture, transparent: true });
    this.materials.push(mat);

    const sprite = new Sprite(mat);
    sprite.scale.set(1.4, 0.42, 1);
    return sprite;
  }

  /**
   * Update subtle idle bobbing animation on each frame
   */
  public animate(elapsedTime: number): void {
    for (const [, node] of this.avatars) {
      // Subtle vertical breathing / floating loop
      const bob = Math.sin(elapsedTime * 2.5 + node.bobPhase) * 0.04;
      node.group.position.y = node.baseY + bob;

      // Pulse status ring scale slightly for 'working' agents
      if (node.agent.presence_status === 'working') {
        const pulse = 1.0 + Math.sin(elapsedTime * 4.0 + node.bobPhase) * 0.08;
        node.statusRing.scale.set(pulse, pulse, 1);
      }
    }
  }

  private track(geo: { dispose: () => void }, mat?: { dispose: () => void }): void {
    this.geometries.push(geo);
    if (mat) {
      this.materials.push(mat);
    }
  }

  public dispose(): void {
    this.clear();
  }

  private clear(): void {
    for (const g of this.geometries) g.dispose();
    for (const m of this.materials) m.dispose();
    for (const t of this.textures) t.dispose();
    this.geometries = [];
    this.materials = [];
    this.textures = [];
    this.interactiveMeshes = [];
    this.avatars.clear();

    while (this.root.children.length > 0) {
      this.root.remove(this.root.children[0]);
    }
  }
}
