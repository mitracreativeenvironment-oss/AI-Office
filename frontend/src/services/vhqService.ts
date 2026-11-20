// Virtual Headquarters (VHQ) 3D Spatial API Client Service (Prompt 23)

import { apiClient } from './apiClient';
import type {
  CampusOverview,
  BuildingDetail,
  HqRoom,
  HqWorkstation,
  AgentPresence,
  CameraBookmark,
} from '../types/vhq';

class VhqService {
  /**
   * Campus-wide overview with buildings, summary metrics, and active agent counts.
   */
  public async getCampusOverview(): Promise<CampusOverview> {
    const res = await apiClient.request<CampusOverview>('/vhq/campus');
    return res.data;
  }

  /**
   * Building details including floors, rooms, workstations, and occupants.
   */
  public async getBuildingDetail(buildingId: number): Promise<BuildingDetail> {
    const res = await apiClient.request<BuildingDetail>(`/vhq/buildings/${buildingId}`);
    return res.data;
  }

  /**
   * List campus rooms with optional filters.
   */
  public async listRooms(filters?: { building_id?: number; category?: string; unit_id?: number }): Promise<HqRoom[]> {
    const params = new URLSearchParams();
    if (filters?.building_id) params.append('building_id', String(filters.building_id));
    if (filters?.category) params.append('category', filters.category);
    if (filters?.unit_id) params.append('unit_id', String(filters.unit_id));

    const endpoint = `/vhq/rooms${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<HqRoom[]>(endpoint);
    return res.data;
  }

  /**
   * List campus workstations with assigned agents and real-time presence.
   */
  public async listWorkstations(filters?: {
    building_id?: number;
    room_id?: number;
    is_occupied?: boolean;
  }): Promise<HqWorkstation[]> {
    const params = new URLSearchParams();
    if (filters?.building_id) params.append('building_id', String(filters.building_id));
    if (filters?.room_id) params.append('room_id', String(filters.room_id));
    if (filters?.is_occupied !== undefined) params.append('is_occupied', filters.is_occupied ? '1' : '0');

    const endpoint = `/vhq/workstations${params.toString() ? `?${params.toString()}` : ''}`;
    const res = await apiClient.request<HqWorkstation[]>(endpoint);
    return res.data;
  }

  /**
   * List operational presences for all active AI workforce agents.
   */
  public async listPresences(): Promise<AgentPresence[]> {
    const res = await apiClient.request<AgentPresence[]>('/vhq/presence');
    return res.data;
  }

  /**
   * Get operational presence and spatial placement of a specific agent.
   */
  public async getAgentPresence(agentId: number): Promise<AgentPresence> {
    const res = await apiClient.request<AgentPresence>(`/vhq/agents/${agentId}/presence`);
    return res.data;
  }

  /**
   * List camera navigation bookmarks.
   */
  public async getBookmarks(): Promise<CameraBookmark[]> {
    const res = await apiClient.request<CameraBookmark[]>('/vhq/bookmarks');
    return res.data;
  }

  /**
   * Reassign or vacate a workstation.
   */
  public async assignWorkstation(workstationId: number, agentId: number | null): Promise<HqWorkstation> {
    const res = await apiClient.request<HqWorkstation>(`/vhq/workstations/${workstationId}/assign`, {
      method: 'POST',
      body: JSON.stringify({ agent_id: agentId }),
    });
    return res.data;
  }
}

export const vhqService = new VhqService();
