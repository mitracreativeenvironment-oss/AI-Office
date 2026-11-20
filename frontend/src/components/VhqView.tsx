/**
 * MCE AI Office — Virtual Headquarters (VHQ) View Container (Prompt 23)
 * Connects Three.js 3D viewport (VhqCanvas), HUD overlay (VhqOverlay),
 * and interactive 2D schematic fallback (VhqFallback2D) with real-time operational data.
 */

import React, { useEffect, useState, useCallback } from 'react';
import { vhqService } from '../services/vhqService';
import { MceKantorScene } from './vhq/MceKantorScene';
import { VhqOverlay } from './vhq/VhqOverlay';
import { VhqFallback2D } from './vhq/VhqFallback2D';
import type {
  CampusOverview,
  HqBuilding,
  HqRoom,
  HqWorkstation,
  AgentPresence,
  CameraBookmark,
} from '../types/vhq';

export interface VhqViewProps {
  onBackToDashboard: () => void;
}

export const VhqView: React.FC<VhqViewProps> = ({ onBackToDashboard }) => {
  const [campus, setCampus] = useState<CampusOverview | null>(null);
  const [buildings, setBuildings] = useState<HqBuilding[]>([]);
  const [rooms, setRooms] = useState<HqRoom[]>([]);
  const [workstations, setWorkstations] = useState<HqWorkstation[]>([]);
  const [presences, setPresences] = useState<AgentPresence[]>([]);
  const [bookmarks, setBookmarks] = useState<CameraBookmark[]>([]);
  const [activeBookmark, setActiveBookmark] = useState<CameraBookmark | null>(null);

  // Selection states
  const [selectedAgent, setSelectedAgent] = useState<AgentPresence | null>(null);
  const [selectedBuilding, setSelectedBuilding] = useState<HqBuilding | null>(null);
  const [selectedRoom, setSelectedRoom] = useState<HqRoom | null>(null);

  // Rendering modes & flags
  const [is2DMode, setIs2DMode] = useState<boolean>(false);
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);

  // 1. Initial Load of Campus Spatial Entities
  useEffect(() => {
    let isMounted = true;

    async function loadCampusData() {
      try {
        setLoading(true);
        setError(null);

        const [campusData, roomsData, wsData, presencesData, bookmarksData] = await Promise.all([
          vhqService.getCampusOverview(),
          vhqService.listRooms(),
          vhqService.listWorkstations(),
          vhqService.listPresences(),
          vhqService.getBookmarks(),
        ]);

        if (isMounted) {
          setCampus(campusData);
          setBuildings(campusData.buildings || []);
          setRooms(roomsData);
          setWorkstations(wsData);
          setPresences(presencesData);
          setBookmarks(bookmarksData);

          const defaultBm = bookmarksData.find((b) => b.is_default === 1) || bookmarksData[0];
          setActiveBookmark(defaultBm || null);
          setLoading(false);
        }
      } catch (err) {
        if (isMounted) {
          console.error('Failed to load VHQ spatial data:', err);
          setError('Gagal memuat data spasial Virtual Headquarters.');
          setIs2DMode(true);
          setLoading(false);
        }
      }
    }

    loadCampusData();

    // Periodic refresh of real-time presence (every 8 seconds)
    const interval = setInterval(async () => {
      try {
        const freshPresences = await vhqService.listPresences();
        if (isMounted) {
          setPresences(freshPresences);
        }
      } catch {
        // Silently tolerate transient polling glitch
      }
    }, 8000);

    return () => {
      isMounted = false;
      clearInterval(interval);
    };
  }, []);

  const handleSelectBookmark = useCallback((bm: CameraBookmark) => {
    setActiveBookmark(bm);
    setSelectedAgent(null);
  }, []);

  const handleSelectAgent = useCallback((agent: AgentPresence | null) => {
    setSelectedAgent(agent);
    if (agent) {
      setSelectedBuilding(null);
      setSelectedRoom(null);
    }
  }, []);

  const handleSelectBuilding = useCallback((b: HqBuilding | null) => {
    setSelectedBuilding(b);
    setSelectedAgent(null);
    setSelectedRoom(null);
  }, []);

  const handleSelectRoom = useCallback((r: HqRoom | null) => {
    setSelectedRoom(r);
    setSelectedAgent(null);
  }, []);

  const handleSelectWorkstation = useCallback(
    (ws: HqWorkstation) => {
      if (ws.assigned_agent_id) {
        const agent = presences.find((p) => p.agent_id === ws.assigned_agent_id);
        if (agent) {
          setSelectedAgent(agent);
          return;
        }
      }
      // If unoccupied, show room info
      const room = rooms.find((r) => r.id === ws.room_id);
      if (room) {
        setSelectedRoom(room);
      }
    },
    [presences, rooms]
  );

  const handleFallback2D = useCallback(() => {
    setIs2DMode(true);
  }, []);

  if (loading) {
    return (
      <div
        style={{
          width: '100%',
          height: '100vh',
          display: 'flex',
          flexDirection: 'column',
          justifyContent: 'center',
          alignItems: 'center',
          background: '#090d16',
          color: '#f8fafc',
          fontFamily: 'Inter, system-ui, sans-serif',
          gap: '16px',
        }}
      >
        <div
          style={{
            width: '48px',
            height: '48px',
            borderRadius: '50%',
            border: '3px solid #1e293b',
            borderTopColor: '#38bdf8',
            animation: 'spin 1s linear infinite',
          }}
        />
        <style>{`@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }`}</style>
        <b style={{ fontSize: '16px', color: '#38bdf8' }}>Menyiapkan Virtual Headquarters MCE…</b>
        <p style={{ margin: 0, fontSize: '13px', color: '#94a3b8' }}>
          Menginisialisasi kampus 3D, memetakan workstation, dan menyinkronkan status agen real-time.
        </p>
      </div>
    );
  }

  if (is2DMode) {
    return (
      <VhqFallback2D
        campus={campus}
        buildings={buildings}
        rooms={rooms}
        workstations={workstations}
        presences={presences}
        onSelectAgent={handleSelectAgent}
        onRetry3D={() => setIs2DMode(false)}
        onBackToDashboard={onBackToDashboard}
      />
    );
  }

  return (
    <div
      style={{
        position: 'relative',
        width: '100vw',
        height: '100vh',
        overflow: 'hidden',
        background: '#090d16',
      }}
    >
      <MceKantorScene campus={campus} presences={presences} workstations={workstations} rooms={rooms} />

      <VhqOverlay
        campus={campus}
        buildings={buildings}
        rooms={rooms}
        workstations={workstations}
        presences={presences}
        bookmarks={bookmarks}
        activeBookmark={activeBookmark}
        selectedAgent={selectedAgent}
        selectedBuilding={selectedBuilding}
        selectedRoom={selectedRoom}
        is2DMode={is2DMode}
        onSelectBookmark={handleSelectBookmark}
        onSelectAgent={handleSelectAgent}
        onSelectBuilding={handleSelectBuilding}
        onSelectRoom={handleSelectRoom}
        onToggle2DMode={() => setIs2DMode(true)}
        onBackToDashboard={onBackToDashboard}
      />
    </div>
  );
};
export default VhqView;
