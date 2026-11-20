/**
 * MCE AI Office — Three.js Procedural Campus & Architecture Renderer (Prompt 23)
 * Builds the 6-building campus: Main Executive Tower, 4 Division Pavilions,
 * and the AI Agent Welfare Commons (BLD-WELFARE).
 */

import {
  BoxGeometry,
  Color,
  CylinderGeometry,
  DoubleSide,
  Group,
  Mesh,
  MeshLambertMaterial,
  MeshStandardMaterial,
  PlaneGeometry,
  RingGeometry,
  Vector3,
} from 'three';
import type { HqBuilding, HqRoom, HqWorkstation } from '../../types/vhq';

export class CampusRenderer {
  public root: Group;
  private geometries: Array<{ dispose: () => void }> = [];
  private materials: Array<{ dispose: () => void }> = [];
  private interactiveMeshes: Mesh[] = [];

  constructor() {
    this.root = new Group();
    this.root.name = 'CampusRoot';
  }

  public getInteractiveMeshes(): Mesh[] {
    return this.interactiveMeshes;
  }

  /**
   * Build the entire campus environment from database data.
   */
  public buildCampus(
    buildings: HqBuilding[],
    rooms: HqRoom[],
    workstations: HqWorkstation[]
  ): Group {
    this.clear();

    // 1. Terrain, Ground Grid & Campus Plazas
    this.buildTerrain();

    // 2. Multi-Building Structures
    for (const b of buildings) {
      this.buildBuildingStructure(b);
    }

    // 3. Functional Rooms & Glass Partitions
    for (const r of rooms) {
      this.buildRoomZone(r);
    }

    // 4. Workstations & Pods
    for (const ws of workstations) {
      this.buildWorkstationFixture(ws);
    }

    return this.root;
  }

  /**
   * Procedural Campus Terrain, Grass Lawns, and Pathways
   */
  private buildTerrain(): void {
    // Main Ground / Slate Campus Base
    const groundGeo = new PlaneGeometry(160, 140);
    groundGeo.rotateX(-Math.PI / 2);
    const groundMat = new MeshStandardMaterial({
      color: 0x090d16,
      roughness: 0.85,
      metalness: 0.1,
    });
    const ground = new Mesh(groundGeo, groundMat);
    ground.position.y = -0.05;
    ground.receiveShadow = true;
    this.root.add(ground);
    this.track(groundGeo, groundMat);

    // Central Campus Plaza (Cobblestone / Dark Quartz)
    const plazaGeo = new PlaneGeometry(80, 70);
    plazaGeo.rotateX(-Math.PI / 2);
    const plazaMat = new MeshStandardMaterial({
      color: 0x131d2e,
      roughness: 0.7,
      metalness: 0.2,
    });
    const plaza = new Mesh(plazaGeo, plazaMat);
    plaza.position.set(0, -0.02, 0);
    plaza.receiveShadow = true;
    this.root.add(plaza);
    this.track(plazaGeo, plazaMat);

    // Courtyard Central Circle / Water Feature Ring
    const fountainRingGeo = new RingGeometry(4.5, 5.0, 32);
    fountainRingGeo.rotateX(-Math.PI / 2);
    const ringMat = new MeshLambertMaterial({
      color: 0x38bdf8,
      side: DoubleSide,
    });
    const fountainRing = new Mesh(fountainRingGeo, ringMat);
    fountainRing.position.set(0, 0.01, 0);
    this.root.add(fountainRing);
    this.track(fountainRingGeo, ringMat);

    const waterGeo = new CylinderGeometry(4.4, 4.4, 0.2, 32);
    const waterMat = new MeshStandardMaterial({
      color: 0x0284c7,
      roughness: 0.1,
      metalness: 0.8,
    });
    const water = new Mesh(waterGeo, waterMat);
    water.position.set(0, 0.05, 0);
    this.root.add(water);
    this.track(waterGeo, waterMat);

    // Connecting Pathways between Pavilions
    this.buildPathways();
  }

  /**
   * Paved walkways connecting Executive Core, Tech, Services, Academy, Hardware, and Welfare Commons
   */
  private buildPathways(): void {
    const pathMat = new MeshLambertMaterial({ color: 0x1e293b });
    this.materials.push(pathMat);

    // Path from Center to Main Tower (North)
    const p1Geo = new PlaneGeometry(6, 12);
    p1Geo.rotateX(-Math.PI / 2);
    const p1 = new Mesh(p1Geo, pathMat);
    p1.position.set(0, 0.005, -5);
    this.root.add(p1);
    this.track(p1Geo);

    // Path from Center to Welfare Commons (South)
    const p2Geo = new PlaneGeometry(6, 16);
    p2Geo.rotateX(-Math.PI / 2);
    const p2 = new Mesh(p2Geo, pathMat);
    p2.position.set(0, 0.005, 14);
    this.root.add(p2);
    this.track(p2Geo);

    // Path to West Wings (Tech & Academy)
    const p3Geo = new PlaneGeometry(26, 5);
    p3Geo.rotateX(-Math.PI / 2);
    const p3 = new Mesh(p3Geo, pathMat);
    p3.position.set(-16, 0.005, 0);
    this.root.add(p3);
    this.track(p3Geo);

    // Path to East Wings (Services & Hardware)
    const p4Geo = new PlaneGeometry(26, 5);
    p4Geo.rotateX(-Math.PI / 2);
    const p4 = new Mesh(p4Geo, pathMat);
    p4.position.set(16, 0.005, 0);
    this.root.add(p4);
    this.track(p4Geo);
  }

  /**
   * Procedural Building Geometry with Theme Color, Accent Roof Trims, and Columns
   */
  private buildBuildingStructure(b: HqBuilding): void {
    const buildingGroup = new Group();
    buildingGroup.name = `Building_${b.building_code}`;
    buildingGroup.position.set(b.pos_x, b.pos_y, b.pos_z);

    const themeHex = new Color(b.theme_color || '#1e293b').getHex();
    const accentHex = new Color(b.accent_color || '#38bdf8').getHex();

    // 1. Foundation Podium / Base Slab
    const baseGeo = new BoxGeometry(b.width + 1.2, 0.4, b.depth + 1.2);
    const baseMat = new MeshStandardMaterial({ color: 0x111827, roughness: 0.9 });
    const baseMesh = new Mesh(baseGeo, baseMat);
    baseMesh.position.y = 0.2;
    buildingGroup.add(baseMesh);
    this.track(baseGeo, baseMat);

    // 2. Main Floor Enclosure (Semi-translucent Architectural Glass & Steel)
    const wallGeo = new BoxGeometry(b.width, b.height, b.depth);
    const wallMat = new MeshStandardMaterial({
      color: themeHex,
      roughness: 0.4,
      metalness: 0.3,
      transparent: true,
      opacity: 0.82,
    });
    const wallMesh = new Mesh(wallGeo, wallMat);
    wallMesh.position.y = b.height / 2 + 0.4;
    wallMesh.userData = { type: 'building', building: b };
    buildingGroup.add(wallMesh);
    this.interactiveMeshes.push(wallMesh);
    this.track(wallGeo, wallMat);

    // 3. Roof Crown / Modern Parapet Trim
    const roofGeo = new BoxGeometry(b.width + 0.6, 0.6, b.depth + 0.6);
    const roofMat = new MeshStandardMaterial({
      color: accentHex,
      roughness: 0.3,
      metalness: 0.6,
    });
    const roofMesh = new Mesh(roofGeo, roofMat);
    roofMesh.position.y = b.height + 0.7;
    buildingGroup.add(roofMesh);
    this.track(roofGeo, roofMat);

    // 4. Special Architecture for AI Agent Welfare Building (BLD-WELFARE)
    if (b.building_code === 'BLD-WELFARE') {
      // Add Green Roof Terrace / Garden Canopy
      const gardenGeo = new BoxGeometry(b.width - 2, 0.3, b.depth - 2);
      const gardenMat = new MeshStandardMaterial({ color: 0x166534, roughness: 0.8 });
      const gardenMesh = new Mesh(gardenGeo, gardenMat);
      gardenMesh.position.y = b.height + 1.15;
      buildingGroup.add(gardenMesh);
      this.track(gardenGeo, gardenMat);

      // Soothing Emerald Light Pillar at entrance
      const pillarGeo = new CylinderGeometry(0.3, 0.3, b.height, 16);
      const pillarMat = new MeshStandardMaterial({ color: 0x34d399, roughness: 0.2 });
      const pLeft = new Mesh(pillarGeo, pillarMat);
      pLeft.position.set(-b.width / 2 + 1, b.height / 2 + 0.4, -b.depth / 2 + 0.5);
      const pRight = pLeft.clone();
      pRight.position.x = b.width / 2 - 1;
      buildingGroup.add(pLeft, pRight);
      this.track(pillarGeo, pillarMat);
    }

    // 5. Special Architecture for Main Tower (BLD-MAIN)
    if (b.building_code === 'BLD-MAIN') {
      // 2nd Tier Penthouse / Boardroom Box
      const topGeo = new BoxGeometry(b.width * 0.7, 3.5, b.depth * 0.7);
      const topMat = new MeshStandardMaterial({
        color: 0x1e1b4b,
        roughness: 0.2,
        metalness: 0.5,
        transparent: true,
        opacity: 0.9,
      });
      const topMesh = new Mesh(topGeo, topMat);
      topMesh.position.y = b.height + 2.45;
      buildingGroup.add(topMesh);
      this.track(topGeo, topMat);

      // Top Spire / Antenna
      const spireGeo = new CylinderGeometry(0.08, 0.2, 5.0, 12);
      const spireMat = new MeshStandardMaterial({ color: 0x38bdf8, metalness: 0.9 });
      const spireMesh = new Mesh(spireGeo, spireMat);
      spireMesh.position.y = b.height + 6.7;
      buildingGroup.add(spireMesh);
      this.track(spireGeo, spireMat);
    }

    this.root.add(buildingGroup);
  }

  /**
   * Room demarcation zone with perimeter lines & soft ambient glow
   */
  private buildRoomZone(r: HqRoom): void {
    const roomGroup = new Group();
    roomGroup.name = `Room_${r.room_code}`;
    roomGroup.position.set(r.pos_x, r.pos_y + 0.45, r.pos_z);

    const floorHex = new Color(r.floor_color || '#1e293b').getHex();
    const accentHex = new Color(r.accent_color || '#38bdf8').getHex();

    // Floor Carpet Tile
    const tileGeo = new BoxGeometry(r.width - 0.4, 0.08, r.depth - 0.4);
    const tileMat = new MeshStandardMaterial({
      color: floorHex,
      roughness: 0.8,
      metalness: 0.1,
    });
    const tileMesh = new Mesh(tileGeo, tileMat);
    tileMesh.userData = { type: 'room', room: r };
    roomGroup.add(tileMesh);
    this.interactiveMeshes.push(tileMesh);
    this.track(tileGeo, tileMat);

    // Accent Perimeter Border
    const borderGeo = new BoxGeometry(r.width, 0.05, r.depth);
    const borderMat = new MeshLambertMaterial({
      color: accentHex,
      transparent: true,
      opacity: 0.4,
    });
    const borderMesh = new Mesh(borderGeo, borderMat);
    borderMesh.position.y = -0.02;
    roomGroup.add(borderMesh);
    this.track(borderGeo, borderMat);

    this.root.add(roomGroup);
  }

  /**
   * Workstation fixtures: executive desks, dual monitor coding setups, and welfare pods
   */
  private buildWorkstationFixture(ws: HqWorkstation): void {
    const wsGroup = new Group();
    wsGroup.name = `Workstation_${ws.workstation_code}`;
    wsGroup.position.set(ws.pos_x, ws.pos_y + 0.45, ws.pos_z);
    wsGroup.rotation.y = ws.rotation_y || 0;

    if (ws.equipment_type === 'welfare_pod') {
      // Soothing curved recovery capsule
      const podBaseGeo = new CylinderGeometry(1.2, 1.2, 0.4, 24);
      const podMat = new MeshStandardMaterial({ color: 0x14532d, roughness: 0.4 });
      const podBase = new Mesh(podBaseGeo, podMat);
      podBase.position.y = 0.2;
      podBase.userData = { type: 'workstation', workstation: ws };
      wsGroup.add(podBase);
      this.interactiveMeshes.push(podBase);
      this.track(podBaseGeo, podMat);

      // Resting lounger cushion
      const cushionGeo = new BoxGeometry(1.6, 0.25, 0.9);
      const cushionMat = new MeshLambertMaterial({ color: 0x34d399 });
      const cushion = new Mesh(cushionGeo, cushionMat);
      cushion.position.y = 0.45;
      wsGroup.add(cushion);
      this.track(cushionGeo, cushionMat);
    } else {
      // Office Table Surface
      const isExecutive = ws.equipment_type === 'executive_desk';
      const deskWidth = isExecutive ? 2.4 : 1.8;
      const deskDepth = isExecutive ? 1.2 : 0.9;
      const deskColor = isExecutive ? 0x2e1065 : 0x1e293b;

      const topGeo = new BoxGeometry(deskWidth, 0.1, deskDepth);
      const topMat = new MeshStandardMaterial({ color: deskColor, roughness: 0.5 });
      const deskTop = new Mesh(topGeo, topMat);
      deskTop.position.y = 0.75;
      deskTop.userData = { type: 'workstation', workstation: ws };
      wsGroup.add(deskTop);
      this.interactiveMeshes.push(deskTop);
      this.track(topGeo, topMat);

      // Desk Legs (Metallic Steel)
      const legGeo = new CylinderGeometry(0.04, 0.04, 0.75, 8);
      const legMat = new MeshStandardMaterial({ color: 0x94a3b8, metalness: 0.8 });
      const offsets = [
        [-deskWidth / 2 + 0.1, -deskDepth / 2 + 0.1],
        [deskWidth / 2 - 0.1, -deskDepth / 2 + 0.1],
        [-deskWidth / 2 + 0.1, deskDepth / 2 - 0.1],
        [deskWidth / 2 - 0.1, deskDepth / 2 - 0.1],
      ];
      for (const [ox, oz] of offsets) {
        const leg = new Mesh(legGeo, legMat);
        leg.position.set(ox, 0.375, oz);
        wsGroup.add(leg);
      }
      this.track(legGeo, legMat);

      // Workstation Display Monitors
      const screenCount = ws.equipment_type === 'dual_monitor_station' ? 2 : 1;
      const monMat = new MeshStandardMaterial({ color: 0x0f172a, roughness: 0.2 });
      this.materials.push(monMat);

      for (let i = 0; i < screenCount; i++) {
        const monGeo = new BoxGeometry(0.7, 0.45, 0.05);
        const mon = new Mesh(monGeo, monMat);
        const shiftX = screenCount === 2 ? (i === 0 ? -0.4 : 0.4) : 0;
        mon.position.set(shiftX, 1.1, -deskDepth / 4);
        wsGroup.add(mon);
        this.track(monGeo);
      }
    }

    this.root.add(wsGroup);
  }

  /**
   * Memory management: track geometry and material for disposal
   */
  private track(geo: { dispose: () => void }, mat?: { dispose: () => void }): void {
    this.geometries.push(geo);
    if (mat) {
      this.materials.push(mat);
    }
  }

  /**
   * Dispose all GPU allocations and clear scene graph
   */
  public dispose(): void {
    this.clear();
  }

  private clear(): void {
    for (const g of this.geometries) {
      g.dispose();
    }
    for (const m of this.materials) {
      m.dispose();
    }
    this.geometries = [];
    this.materials = [];
    this.interactiveMeshes = [];

    while (this.root.children.length > 0) {
      this.root.remove(this.root.children[0]);
    }
  }
}
