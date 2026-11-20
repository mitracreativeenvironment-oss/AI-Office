/**
 * MCE AI Office — Virtual Headquarters (VHQ) 3D Spatial Types (Prompt 23)
 */

export type PresenceStatus =
  | 'available'
  | 'working'
  | 'on_leave'
  | 'scheduled_off'
  | 'suspended'
  | 'runtime_disconnected'
  | 'vacant';

export type RoomCategory =
  | 'reception'
  | 'executive'
  | 'workspace'
  | 'collaboration'
  | 'project_ops'
  | 'monitoring'
  | 'welfare_rest'
  | 'welfare_learning';

export type BuildingType =
  | 'tower'
  | 'pavilion'
  | 'hall'
  | 'facility'
  | 'welfare_center';

export interface HqBuilding {
  id: number;
  building_code: string;
  name: string;
  description?: string;
  campus_zone: string;
  building_type: BuildingType;
  pos_x: number;
  pos_y: number;
  pos_z: number;
  width: number;
  depth: number;
  height: number;
  theme_color: string;
  accent_color: string;
  total_floors: number;
  is_active: number;
  sort_order: number;
  rooms_count?: number;
  workstations_count?: number;
  occupied_workstations_count?: number;
}

export interface HqFloor {
  id: number;
  building_id: number;
  floor_number: number;
  floor_code: string;
  name: string;
  elevation_y: number;
  width?: number;
  depth?: number;
  rooms?: HqRoom[];
}

export interface HqRoom {
  id: number;
  building_id: number;
  floor_id: number;
  unit_id: number | null;
  room_code: string;
  name: string;
  category: RoomCategory;
  description?: string;
  pos_x: number;
  pos_y: number;
  pos_z: number;
  width: number;
  depth: number;
  height: number;
  floor_color: string;
  accent_color: string;
  max_capacity: number;
  unit_name?: string;
  unit_code?: string;
  building_name?: string;
  building_code?: string;
  campus_zone?: string;
  workstations?: HqWorkstation[];
}

export interface HqWorkstation {
  id: number;
  room_id: number;
  workstation_code: string;
  label?: string;
  assigned_agent_id: number | null;
  pos_x: number;
  pos_y: number;
  pos_z: number;
  rotation_y: number;
  equipment_type: string;
  room_code?: string;
  room_name?: string;
  room_category?: RoomCategory;
  building_id?: number;
  building_name?: string;
  building_code?: string;
  agent_name?: string;
  agent_code?: string;
  agent_classification?: string;
  presence_status?: PresenceStatus;
  active_task?: {
    task_id: number;
    task_code: string;
    title: string;
    status: string;
    priority: string;
  } | null;
}

export interface SpatialPlacement {
  building_id: number;
  building_code: string;
  building_name: string;
  room_id: number;
  room_code: string;
  room_name: string;
  room_category: RoomCategory;
  workstation_id: number;
  workstation_code: string;
  pos_x: number;
  pos_y: number;
  pos_z: number;
  rotation_y: number;
  equipment_type: string;
}

export interface AgentPresence {
  agent_id: number;
  agent_uuid: string;
  agent_code: string;
  name: string;
  display_name: string;
  classification: string;
  lifecycle_state: string;
  unit_name?: string;
  unit_code?: string;
  position_title?: string;
  presence_status: PresenceStatus;
  status_reason: string;
  has_spatial_placement: boolean;
  placement: SpatialPlacement | null;
  active_task: {
    task_id: number;
    task_code: string;
    title: string;
    status: string;
    priority: string;
  } | null;
  updated_at: string;
}

export interface CameraBookmark {
  id: number;
  bookmark_code: string;
  title: string;
  category: 'overview' | 'building' | 'room' | 'facility';
  target_building_id?: number | null;
  target_room_id?: number | null;
  camera_pos_x: number;
  camera_pos_y: number;
  camera_pos_z: number;
  look_at_x: number;
  look_at_y: number;
  look_at_z: number;
  is_default: number;
  sort_order: number;
}

export interface CampusOverview {
  campus_name: string;
  tagline: string;
  summary: {
    total_buildings: number;
    total_rooms: number;
    total_workstations: number;
    occupied_workstations: number;
    total_active_agents: number;
    unplaced_agents_count: number;
  };
  buildings: HqBuilding[];
}

export interface BuildingDetail extends HqBuilding {
  floors: HqFloor[];
}
