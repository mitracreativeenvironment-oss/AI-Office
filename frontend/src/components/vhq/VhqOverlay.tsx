/**
 * MCE AI Office — Virtual Headquarters HUD Overlay (Prompt 23)
 * Provides camera bookmark pills, search & locate agent drawer,
 * mini-map / room selector, and agent detail drawer.
 */

import React, { useState } from 'react';
import type {
  HqBuilding,
  HqRoom,
  HqWorkstation,
  AgentPresence,
  CameraBookmark,
  CampusOverview,
} from '../../types/vhq';

export interface VhqOverlayProps {
  campus: CampusOverview | null;
  buildings: HqBuilding[];
  rooms: HqRoom[];
  workstations: HqWorkstation[];
  presences: AgentPresence[];
  bookmarks: CameraBookmark[];
  activeBookmark: CameraBookmark | null;
  selectedAgent: AgentPresence | null;
  selectedBuilding: HqBuilding | null;
  selectedRoom: HqRoom | null;
  is2DMode: boolean;
  onSelectBookmark: (bm: CameraBookmark) => void;
  onSelectAgent: (agent: AgentPresence | null) => void;
  onSelectBuilding: (building: HqBuilding | null) => void;
  onSelectRoom: (room: HqRoom | null) => void;
  onToggle2DMode: () => void;
  onBackToDashboard: () => void;
}

export const VhqOverlay: React.FC<VhqOverlayProps> = ({
  campus,
  buildings,
  rooms,
  presences,
  bookmarks,
  activeBookmark,
  selectedAgent,
  selectedBuilding,
  selectedRoom,
  is2DMode,
  onSelectBookmark,
  onSelectAgent,
  onSelectBuilding,
  onSelectRoom,
  onToggle2DMode,
  onBackToDashboard,
}) => {
  const [searchQuery, setSearchQuery] = useState('');
  const [isNavOpen, setIsNavOpen] = useState(false);

  // Compute status metrics from live presences
  const workingCount = presences.filter((p) => p.presence_status === 'working').length;
  const availableCount = presences.filter((p) => p.presence_status === 'available').length;
  const leaveOrRestCount = presences.filter(
    (p) => p.presence_status === 'on_leave' || p.presence_status === 'scheduled_off'
  ).length;

  // Filter agents for search & locate
  const filteredAgents = searchQuery.trim()
    ? presences.filter((a) => {
        const q = searchQuery.toLowerCase();
        return (
          a.agent_code.toLowerCase().includes(q) ||
          a.name.toLowerCase().includes(q) ||
          a.display_name.toLowerCase().includes(q) ||
          (a.unit_name && a.unit_name.toLowerCase().includes(q))
        );
      })
    : [];

  const getStatusBadgeStyle = (status: string) => {
    switch (status) {
      case 'available':
        return { bg: '#064e3b', text: '#34d399', border: '#10b981', label: 'Tersedia' };
      case 'working':
        return { bg: '#1e3a8a', text: '#93c5fd', border: '#3b82f6', label: 'Bekerja' };
      case 'on_leave':
        return { bg: '#4c1d95', text: '#d8b4fe', border: '#a855f7', label: 'Cuti Resmi' };
      case 'scheduled_off':
        return { bg: '#334155', text: '#cbd5e1', border: '#64748b', label: 'Libur Roster 5/2' };
      case 'suspended':
        return { bg: '#7c2d12', text: '#fed7aa', border: '#ea580c', label: 'Ditangguhkan' };
      case 'runtime_disconnected':
        return { bg: '#881337', text: '#fecdd3', border: '#f43f5e', label: 'Runtime Offline' };
      default:
        return { bg: '#1e293b', text: '#94a3b8', border: '#475569', label: status };
    }
  };

  return (
    <div
      style={{
        position: 'absolute',
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        pointerEvents: 'none',
        display: 'flex',
        flexDirection: 'column',
        justifyContent: 'space-between',
        padding: '16px',
        zIndex: 10,
        fontFamily: 'Inter, system-ui, sans-serif',
      }}
    >
      {/* 1. TOP HEADER HUD */}
      <header
        style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          gap: '12px',
          flexWrap: 'wrap',
          pointerEvents: 'auto',
        }}
      >
        {/* Brand & Metrics Pill */}
        <div
          style={{
            background: 'rgba(15, 23, 42, 0.88)',
            backdropFilter: 'blur(12px)',
            border: '1px solid rgba(56, 189, 248, 0.3)',
            borderRadius: '16px',
            padding: '10px 18px',
            display: 'flex',
            alignItems: 'center',
            gap: '16px',
            boxShadow: '0 8px 32px rgba(0,0,0,0.5)',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            <span
              style={{
                width: '10px',
                height: '10px',
                borderRadius: '50%',
                backgroundColor: '#10b981',
                boxShadow: '0 0 10px #10b981',
              }}
            />
            <div>
              <div style={{ fontWeight: 700, fontSize: '15px', color: '#f8fafc' }}>
                {campus?.campus_name || 'MCE AI Office VHQ'}
              </div>
              <div style={{ fontSize: '11px', color: '#94a3b8' }}>
                {campus?.tagline || 'Mitra Creative Environment'}
              </div>
            </div>
          </div>

          <div
            style={{
              height: '24px',
              width: '1px',
              backgroundColor: 'rgba(255,255,255,0.15)',
            }}
          />

          {/* Quick Metrics */}
          <div style={{ display: 'flex', gap: '12px', fontSize: '12px' }}>
            <span style={{ color: '#3b82f6' }}>
              ● <b>{workingCount}</b> Bekerja
            </span>
            <span style={{ color: '#10b981' }}>
              ● <b>{availableCount}</b> Siaga
            </span>
            <span style={{ color: '#a855f7' }}>
              ● <b>{leaveOrRestCount}</b> Cuti/Istirahat
            </span>
          </div>
        </div>

        {/* Search & Actions Bar */}
        <div style={{ display: 'flex', gap: '10px', alignItems: 'center' }}>
          {/* Search Input */}
          <div style={{ position: 'relative' }}>
            <input
              type="text"
              placeholder="Cari Agen (DIR-001, TECH-001...)"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              style={{
                background: 'rgba(15, 23, 42, 0.9)',
                border: '1px solid #334155',
                borderRadius: '12px',
                padding: '8px 14px',
                color: '#f8fafc',
                fontSize: '13px',
                width: '220px',
                outline: 'none',
              }}
            />
            {filteredAgents.length > 0 && (
              <div
                style={{
                  position: 'absolute',
                  top: '42px',
                  left: 0,
                  right: 0,
                  background: 'rgba(15, 23, 42, 0.96)',
                  border: '1px solid #334155',
                  borderRadius: '12px',
                  maxHeight: '220px',
                  overflowY: 'auto',
                  boxShadow: '0 8px 32px rgba(0,0,0,0.6)',
                  padding: '6px',
                }}
              >
                {filteredAgents.map((ag) => (
                  <button
                    key={ag.agent_id}
                    onClick={() => {
                      onSelectAgent(ag);
                      setSearchQuery('');
                    }}
                    style={{
                      width: '100%',
                      textAlign: 'left',
                      background: 'transparent',
                      border: 'none',
                      color: '#f8fafc',
                      padding: '8px',
                      borderRadius: '8px',
                      cursor: 'pointer',
                      fontSize: '12px',
                      display: 'flex',
                      justifyContent: 'space-between',
                      alignItems: 'center',
                    }}
                    onMouseEnter={(e) => {
                      e.currentTarget.style.background = '#1e293b';
                    }}
                    onMouseLeave={(e) => {
                      e.currentTarget.style.background = 'transparent';
                    }}
                  >
                    <div>
                      <b>{ag.agent_code}</b> · {ag.name}
                    </div>
                    <span
                      style={{
                        fontSize: '10px',
                        padding: '2px 6px',
                        borderRadius: '6px',
                        backgroundColor: getStatusBadgeStyle(ag.presence_status).bg,
                        color: getStatusBadgeStyle(ag.presence_status).text,
                      }}
                    >
                      {getStatusBadgeStyle(ag.presence_status).label}
                    </span>
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* Toggle Mini-Map / Room Navigator */}
          <button
            onClick={() => setIsNavOpen(!isNavOpen)}
            style={{
              background: isNavOpen ? '#38bdf8' : 'rgba(15, 23, 42, 0.88)',
              color: isNavOpen ? '#0f172a' : '#f8fafc',
              border: '1px solid rgba(56, 189, 248, 0.4)',
              borderRadius: '12px',
              padding: '8px 14px',
              fontSize: '13px',
              fontWeight: 600,
              cursor: 'pointer',
              display: 'flex',
              alignItems: 'center',
              gap: '6px',
            }}
          >
            🗺 Direktori Kampus
          </button>

          {/* 2D / 3D Mode Switch */}
          <button
            onClick={onToggle2DMode}
            style={{
              background: 'rgba(15, 23, 42, 0.88)',
              color: is2DMode ? '#38bdf8' : '#cbd5e1',
              border: '1px solid #334155',
              borderRadius: '12px',
              padding: '8px 14px',
              fontSize: '13px',
              fontWeight: 600,
              cursor: 'pointer',
            }}
          >
            {is2DMode ? '🌐 Tampilan 3D' : '📄 Skematik 2D'}
          </button>

          {/* Back to Dashboard */}
          <button
            onClick={onBackToDashboard}
            style={{
              background: '#2563eb',
              color: '#ffffff',
              border: 'none',
              borderRadius: '12px',
              padding: '8px 16px',
              fontSize: '13px',
              fontWeight: 600,
              cursor: 'pointer',
            }}
          >
            ← Dashboard
          </button>
        </div>
      </header>

      {/* 2. CAMPUS DIRECTORY / MINI-MAP DRAWER (LEFT SIDEBAR) */}
      {isNavOpen && (
        <aside
          style={{
            position: 'absolute',
            top: '80px',
            left: '16px',
            bottom: '90px',
            width: '280px',
            background: 'rgba(15, 23, 42, 0.94)',
            backdropFilter: 'blur(16px)',
            border: '1px solid rgba(56, 189, 248, 0.3)',
            borderRadius: '16px',
            padding: '16px',
            overflowY: 'auto',
            pointerEvents: 'auto',
            boxShadow: '0 12px 40px rgba(0,0,0,0.6)',
            display: 'flex',
            flexDirection: 'column',
            gap: '12px',
          }}
        >
          <div
            style={{
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center',
              borderBottom: '1px solid #334155',
              paddingBottom: '8px',
            }}
          >
            <h3 style={{ margin: 0, fontSize: '14px', color: '#f8fafc', fontWeight: 700 }}>
              🏢 Direktori Bangunan
            </h3>
            <button
              onClick={() => setIsNavOpen(false)}
              style={{
                background: 'transparent',
                border: 'none',
                color: '#94a3b8',
                cursor: 'pointer',
                fontSize: '16px',
              }}
            >
              ✕
            </button>
          </div>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
            {buildings.map((b) => {
              const bRooms = rooms.filter((r) => r.building_id === b.id);
              const isWelfare = b.building_code === 'BLD-WELFARE';
              return (
                <div
                  key={b.id}
                  style={{
                    background: isWelfare ? 'rgba(20, 83, 45, 0.35)' : 'rgba(30, 41, 59, 0.6)',
                    border: `1px solid ${isWelfare ? '#10b981' : '#334155'}`,
                    borderRadius: '10px',
                    padding: '10px',
                  }}
                >
                  <div
                    onClick={() => onSelectBuilding(b)}
                    style={{
                      cursor: 'pointer',
                      display: 'flex',
                      justifyContent: 'space-between',
                      alignItems: 'center',
                    }}
                  >
                    <div>
                      <b style={{ color: b.accent_color || '#38bdf8', fontSize: '13px' }}>
                        {b.building_code}
                      </b>
                      <div style={{ color: '#cbd5e1', fontSize: '12px', fontWeight: 600 }}>
                        {b.name}
                      </div>
                    </div>
                    {isWelfare && (
                      <span
                        style={{
                          fontSize: '10px',
                          background: '#064e3b',
                          color: '#34d399',
                          padding: '2px 6px',
                          borderRadius: '4px',
                        }}
                      >
                        Welfare
                      </span>
                    )}
                  </div>

                  {bRooms.length > 0 && (
                    <div
                      style={{
                        marginTop: '8px',
                        paddingTop: '6px',
                        borderTop: '1px solid rgba(255,255,255,0.06)',
                        display: 'flex',
                        flexDirection: 'column',
                        gap: '4px',
                      }}
                    >
                      {bRooms.map((rm) => (
                        <button
                          key={rm.id}
                          onClick={() => onSelectRoom(rm)}
                          style={{
                            background: 'transparent',
                            border: 'none',
                            color: '#94a3b8',
                            fontSize: '11px',
                            textAlign: 'left',
                            cursor: 'pointer',
                            padding: '3px 6px',
                            borderRadius: '4px',
                          }}
                          onMouseEnter={(e) => {
                            e.currentTarget.style.color = '#38bdf8';
                            e.currentTarget.style.background = 'rgba(56, 189, 248, 0.1)';
                          }}
                          onMouseLeave={(e) => {
                            e.currentTarget.style.color = '#94a3b8';
                            e.currentTarget.style.background = 'transparent';
                          }}
                        >
                          ▸ {rm.name} ({rm.room_code})
                        </button>
                      ))}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </aside>
      )}

      {/* 3. AGENT DETAIL SLIDE-OUT DRAWER (RIGHT SIDEBAR) */}
      {selectedAgent && (
        <aside
          style={{
            position: 'absolute',
            top: '80px',
            right: '16px',
            bottom: '90px',
            width: '320px',
            background: 'rgba(15, 23, 42, 0.95)',
            backdropFilter: 'blur(16px)',
            border: '1px solid rgba(56, 189, 248, 0.4)',
            borderRadius: '16px',
            padding: '20px',
            overflowY: 'auto',
            pointerEvents: 'auto',
            boxShadow: '0 16px 48px rgba(0,0,0,0.7)',
            display: 'flex',
            flexDirection: 'column',
            gap: '16px',
          }}
        >
          {/* Header */}
          <div
            style={{
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'flex-start',
              borderBottom: '1px solid #334155',
              paddingBottom: '12px',
            }}
          >
            <div>
              <span
                style={{
                  fontSize: '11px',
                  fontWeight: 700,
                  letterSpacing: '0.05em',
                  color: '#38bdf8',
                  textTransform: 'uppercase',
                }}
              >
                {selectedAgent.classification} Agent
              </span>
              <h2 style={{ margin: '4px 0 0', fontSize: '18px', color: '#f8fafc' }}>
                {selectedAgent.name}
              </h2>
              <div style={{ fontSize: '12px', color: '#94a3b8' }}>
                {selectedAgent.agent_code} · {selectedAgent.position_title || 'Spesialis Operasional'}
              </div>
            </div>
            <button
              onClick={() => onSelectAgent(null)}
              style={{
                background: 'transparent',
                border: 'none',
                color: '#94a3b8',
                cursor: 'pointer',
                fontSize: '18px',
              }}
            >
              ✕
            </button>
          </div>

          {/* Operational Presence Badge */}
          <div
            style={{
              backgroundColor: getStatusBadgeStyle(selectedAgent.presence_status).bg,
              border: `1px solid ${getStatusBadgeStyle(selectedAgent.presence_status).border}`,
              borderRadius: '12px',
              padding: '12px',
            }}
          >
            <div
              style={{
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center',
                marginBottom: '6px',
              }}
            >
              <b
                style={{
                  color: getStatusBadgeStyle(selectedAgent.presence_status).text,
                  fontSize: '13px',
                }}
              >
                ● {getStatusBadgeStyle(selectedAgent.presence_status).label}
              </b>
              <span style={{ fontSize: '11px', color: '#94a3b8' }}>Real-Time Status</span>
            </div>
            <div style={{ fontSize: '12px', color: '#e2e8f0', lineHeight: 1.4 }}>
              {selectedAgent.status_reason}
            </div>
          </div>

          {/* Spatial Placement Details */}
          <div>
            <h4
              style={{
                margin: '0 0 8px',
                fontSize: '12px',
                color: '#94a3b8',
                textTransform: 'uppercase',
                letterSpacing: '0.05em',
              }}
            >
              📍 Penempatan Spasial
            </h4>
            {selectedAgent.placement ? (
              <div
                style={{
                  background: '#1e293b',
                  borderRadius: '10px',
                  padding: '12px',
                  fontSize: '12px',
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '6px',
                }}
              >
                <div>
                  <span style={{ color: '#94a3b8' }}>Gedung:</span>{' '}
                  <b style={{ color: '#f8fafc' }}>{selectedAgent.placement.building_name}</b>
                </div>
                <div>
                  <span style={{ color: '#94a3b8' }}>Ruangan:</span>{' '}
                  <b style={{ color: '#f8fafc' }}>{selectedAgent.placement.room_name}</b> (
                  {selectedAgent.placement.room_code})
                </div>
                <div>
                  <span style={{ color: '#94a3b8' }}>Workstation:</span>{' '}
                  <b style={{ color: '#38bdf8' }}>{selectedAgent.placement.workstation_code}</b>
                </div>
                <div>
                  <span style={{ color: '#94a3b8' }}>Peralatan:</span>{' '}
                  <span style={{ color: '#cbd5e1' }}>{selectedAgent.placement.equipment_type}</span>
                </div>
              </div>
            ) : (
              <div
                style={{
                  background: '#1e293b',
                  borderRadius: '10px',
                  padding: '12px',
                  fontSize: '12px',
                  color: '#94a3b8',
                }}
              >
                Belum memiliki workstation fisik (Roaming Agent).
              </div>
            )}
          </div>

          {/* Active Task (If Working) */}
          {selectedAgent.active_task && (
            <div>
              <h4
                style={{
                  margin: '0 0 8px',
                  fontSize: '12px',
                  color: '#3b82f6',
                  textTransform: 'uppercase',
                  letterSpacing: '0.05em',
                }}
              >
                ⚡ Tugas Sedang Berjalan
              </h4>
              <div
                style={{
                  background: 'rgba(30, 58, 138, 0.3)',
                  border: '1px solid #2563eb',
                  borderRadius: '10px',
                  padding: '12px',
                  fontSize: '12px',
                }}
              >
                <b style={{ color: '#93c5fd' }}>{selectedAgent.active_task.task_code}</b>
                <div style={{ color: '#f8fafc', marginTop: '4px', fontWeight: 600 }}>
                  {selectedAgent.active_task.title}
                </div>
                <div style={{ marginTop: '6px', color: '#94a3b8', fontSize: '11px' }}>
                  Prioritas:{' '}
                  <span style={{ color: '#f59e0b', textTransform: 'uppercase' }}>
                    {selectedAgent.active_task.priority}
                  </span>
                </div>
              </div>
            </div>
          )}

          {/* Organization Unit */}
          <div>
            <h4
              style={{
                margin: '0 0 8px',
                fontSize: '12px',
                color: '#94a3b8',
                textTransform: 'uppercase',
                letterSpacing: '0.05em',
              }}
            >
              🏛 Divisi Organisasi
            </h4>
            <div
              style={{
                background: '#1e293b',
                borderRadius: '10px',
                padding: '10px 12px',
                fontSize: '12px',
                color: '#e2e8f0',
              }}
            >
              {selectedAgent.unit_name || 'MCE Corporate Core'} ({selectedAgent.unit_code || 'ORG-MCE'})
            </div>
          </div>
        </aside>
      )}

      {/* 4. BUILDING / ROOM DETAIL MODAL */}
      {(selectedBuilding || selectedRoom) && !selectedAgent && (
        <aside
          style={{
            position: 'absolute',
            bottom: '90px',
            right: '16px',
            width: '300px',
            background: 'rgba(15, 23, 42, 0.94)',
            backdropFilter: 'blur(16px)',
            border: '1px solid #38bdf8',
            borderRadius: '16px',
            padding: '16px',
            pointerEvents: 'auto',
            boxShadow: '0 12px 36px rgba(0,0,0,0.6)',
          }}
        >
          <div
            style={{
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center',
              marginBottom: '10px',
            }}
          >
            <b style={{ color: '#38bdf8', fontSize: '13px' }}>
              {selectedRoom ? 'Ruangan Terpilih' : 'Bangunan Terpilih'}
            </b>
            <button
              onClick={() => {
                onSelectBuilding(null);
                onSelectRoom(null);
              }}
              style={{
                background: 'transparent',
                border: 'none',
                color: '#94a3b8',
                cursor: 'pointer',
              }}
            >
              ✕
            </button>
          </div>

          <h3 style={{ margin: '0 0 6px', fontSize: '16px', color: '#f8fafc' }}>
            {selectedRoom?.name || selectedBuilding?.name}
          </h3>
          <div style={{ fontSize: '12px', color: '#cbd5e1', lineHeight: 1.4 }}>
            {selectedRoom?.description || selectedBuilding?.description}
          </div>

          {selectedBuilding?.building_code === 'BLD-WELFARE' && (
            <div
              style={{
                marginTop: '10px',
                background: '#064e3b',
                color: '#34d399',
                padding: '8px 12px',
                borderRadius: '8px',
                fontSize: '11px',
                fontWeight: 600,
              }}
            >
              🌿 Fasilitas Wajib Kesejahteraan AI MCE: Zona Pemulihan Operasional & Eksplorasi Pengetahuan.
            </div>
          )}
        </aside>
      )}

      {/* 5. BOTTOM CAMERA BOOKMARKS NAVIGATION BAR */}
      <footer
        style={{
          display: 'flex',
          justifyContent: 'center',
          gap: '8px',
          flexWrap: 'wrap',
          pointerEvents: 'auto',
        }}
      >
        <div
          style={{
            background: 'rgba(15, 23, 42, 0.9)',
            backdropFilter: 'blur(16px)',
            border: '1px solid rgba(56, 189, 248, 0.25)',
            borderRadius: '20px',
            padding: '6px 12px',
            display: 'flex',
            gap: '6px',
            boxShadow: '0 10px 30px rgba(0,0,0,0.5)',
          }}
        >
          {bookmarks.map((bm) => {
            const isActive = activeBookmark?.bookmark_code === bm.bookmark_code;
            const isWelfare = bm.bookmark_code === 'BM-WELFARE';
            return (
              <button
                key={bm.id}
                onClick={() => onSelectBookmark(bm)}
                style={{
                  background: isActive
                    ? '#38bdf8'
                    : isWelfare
                    ? 'rgba(16, 185, 129, 0.15)'
                    : 'transparent',
                  color: isActive ? '#0f172a' : isWelfare ? '#34d399' : '#e2e8f0',
                  border: isWelfare && !isActive ? '1px solid #10b981' : 'none',
                  borderRadius: '14px',
                  padding: '6px 14px',
                  fontSize: '12px',
                  fontWeight: isActive ? 700 : 500,
                  cursor: 'pointer',
                  transition: 'all 0.15s ease',
                }}
              >
                {bm.title}
              </button>
            );
          })}
        </div>
      </footer>
    </div>
  );
};
