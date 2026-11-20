/**
 * MCE AI Office — Virtual Headquarters 2D WebGL Fallback & Campus Directory (Prompt 23)
 * Provides a reliable, high-fidelity 2D interactive campus layout and agent directory
 * if WebGL is unavailable or when 2D schematic mode is enabled.
 */

import React, { useState } from 'react';
import type {
  HqBuilding,
  HqRoom,
  HqWorkstation,
  AgentPresence,
  CampusOverview,
} from '../../types/vhq';

export interface VhqFallback2DProps {
  campus: CampusOverview | null;
  buildings: HqBuilding[];
  rooms: HqRoom[];
  workstations: HqWorkstation[];
  presences: AgentPresence[];
  onSelectAgent: (agent: AgentPresence) => void;
  onRetry3D?: () => void;
  onBackToDashboard: () => void;
}

export const VhqFallback2D: React.FC<VhqFallback2DProps> = ({
  campus,
  buildings,
  rooms,
  workstations,
  presences,
  onSelectAgent,
  onRetry3D,
  onBackToDashboard,
}) => {
  const [filterZone, setFilterZone] = useState<string>('all');
  const [selectedAgentDetail, setSelectedAgentDetail] = useState<AgentPresence | null>(null);

  const getStatusColor = (status: string) => {
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

  const filteredBuildings =
    filterZone === 'all'
      ? buildings
      : filterZone === 'welfare'
      ? buildings.filter((b) => b.building_code === 'BLD-WELFARE')
      : buildings.filter((b) => b.campus_zone === filterZone);

  return (
    <div
      style={{
        width: '100%',
        minHeight: '100vh',
        background: '#090d16',
        color: '#f8fafc',
        fontFamily: 'Inter, system-ui, sans-serif',
        padding: '24px',
        boxSizing: 'border-box',
      }}
    >
      {/* Top Header */}
      <header
        style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          borderBottom: '1px solid #1e293b',
          paddingBottom: '16px',
          marginBottom: '20px',
          flexWrap: 'wrap',
          gap: '12px',
        }}
      >
        <div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <h1 style={{ margin: 0, fontSize: '20px', fontWeight: 800, color: '#f8fafc' }}>
              {campus?.campus_name || 'MCE AI Office Virtual Headquarters'}
            </h1>
            <span
              style={{
                fontSize: '11px',
                background: '#1e293b',
                color: '#38bdf8',
                padding: '3px 8px',
                borderRadius: '6px',
                border: '1px solid #334155',
                fontWeight: 600,
              }}
            >
              Mode Skematik 2D
            </span>
          </div>
          <p style={{ margin: '4px 0 0', fontSize: '13px', color: '#94a3b8' }}>
            {campus?.tagline} · Tampilan cadangan operasional interaktif
          </p>
        </div>

        <div style={{ display: 'flex', gap: '10px' }}>
          {onRetry3D && (
            <button
              onClick={onRetry3D}
              style={{
                background: '#0284c7',
                color: '#ffffff',
                border: 'none',
                borderRadius: '8px',
                padding: '8px 16px',
                fontSize: '13px',
                fontWeight: 600,
                cursor: 'pointer',
              }}
            >
              🌐 Buka Ruang 3D
            </button>
          )}
          <button
            onClick={onBackToDashboard}
            style={{
              background: '#2563eb',
              color: '#ffffff',
              border: 'none',
              borderRadius: '8px',
              padding: '8px 16px',
              fontSize: '13px',
              fontWeight: 600,
              cursor: 'pointer',
            }}
          >
            ← Kembali ke Dashboard
          </button>
        </div>
      </header>

      {/* Summary Metrics Bar */}
      <div
        style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))',
          gap: '12px',
          marginBottom: '24px',
        }}
      >
        <div style={{ background: '#0f172a', border: '1px solid #1e293b', borderRadius: '12px', padding: '14px' }}>
          <div style={{ fontSize: '12px', color: '#94a3b8' }}>Total Bangunan Kampus</div>
          <div style={{ fontSize: '24px', fontWeight: 800, color: '#38bdf8', marginTop: '4px' }}>
            {campus?.summary.total_buildings || buildings.length}
          </div>
        </div>
        <div style={{ background: '#0f172a', border: '1px solid #1e293b', borderRadius: '12px', padding: '14px' }}>
          <div style={{ fontSize: '12px', color: '#94a3b8' }}>Total Ruangan Operasional</div>
          <div style={{ fontSize: '24px', fontWeight: 800, color: '#a855f7', marginTop: '4px' }}>
            {campus?.summary.total_rooms || rooms.length}
          </div>
        </div>
        <div style={{ background: '#0f172a', border: '1px solid #1e293b', borderRadius: '12px', padding: '14px' }}>
          <div style={{ fontSize: '12px', color: '#94a3b8' }}>Workstation / Pods</div>
          <div style={{ fontSize: '24px', fontWeight: 800, color: '#34d399', marginTop: '4px' }}>
            {campus?.summary.occupied_workstations || 0} / {campus?.summary.total_workstations || workstations.length}
          </div>
        </div>
        <div style={{ background: '#0f172a', border: '1px solid #1e293b', borderRadius: '12px', padding: '14px' }}>
          <div style={{ fontSize: '12px', color: '#94a3b8' }}>Tenaga Kerja AI Aktif</div>
          <div style={{ fontSize: '24px', fontWeight: 800, color: '#f59e0b', marginTop: '4px' }}>
            {presences.length}
          </div>
        </div>
      </div>

      {/* Campus Zone Filter Tabs */}
      <div style={{ display: 'flex', gap: '8px', marginBottom: '20px', flexWrap: 'wrap' }}>
        {[
          { key: 'all', label: 'Semua Bangunan' },
          { key: 'executive_core', label: 'Executive Core (Tower)' },
          { key: 'technology_wing', label: 'Tech Pavilion' },
          { key: 'services_pavilion', label: 'Services Pavilion' },
          { key: 'academy_center', label: 'Academy Center' },
          { key: 'hardware_labs', label: 'Hardware Labs' },
          { key: 'welfare', label: '🌿 AI Agent Welfare' },
        ].map((tab) => (
          <button
            key={tab.key}
            onClick={() => setFilterZone(tab.key)}
            style={{
              background: filterZone === tab.key ? '#38bdf8' : '#0f172a',
              color: filterZone === tab.key ? '#0f172a' : '#cbd5e1',
              border: '1px solid #334155',
              borderRadius: '8px',
              padding: '6px 14px',
              fontSize: '12px',
              fontWeight: filterZone === tab.key ? 700 : 500,
              cursor: 'pointer',
            }}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {/* Buildings & Rooms Grid */}
      <div
        style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fill, minmax(360px, 1fr))',
          gap: '20px',
        }}
      >
        {filteredBuildings.map((b) => {
          const bRooms = rooms.filter((r) => r.building_id === b.id);
          const isWelfare = b.building_code === 'BLD-WELFARE';

          return (
            <div
              key={b.id}
              style={{
                background: isWelfare ? 'rgba(20, 83, 45, 0.25)' : '#0f172a',
                border: `1px solid ${isWelfare ? '#10b981' : '#1e293b'}`,
                borderRadius: '16px',
                padding: '20px',
                display: 'flex',
                flexDirection: 'column',
                gap: '16px',
              }}
            >
              <div>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <span
                    style={{
                      fontSize: '12px',
                      fontWeight: 700,
                      color: b.accent_color || '#38bdf8',
                      letterSpacing: '0.05em',
                    }}
                  >
                    {b.building_code}
                  </span>
                  <span
                    style={{
                      fontSize: '11px',
                      background: '#1e293b',
                      color: '#94a3b8',
                      padding: '2px 8px',
                      borderRadius: '4px',
                    }}
                  >
                    {b.total_floors} Lantai
                  </span>
                </div>
                <h3 style={{ margin: '4px 0 0', fontSize: '17px', color: '#f8fafc' }}>{b.name}</h3>
                <p style={{ margin: '6px 0 0', fontSize: '12px', color: '#94a3b8', lineHeight: 1.4 }}>
                  {b.description}
                </p>
              </div>

              {/* Rooms in this Building */}
              <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                <b style={{ fontSize: '12px', color: '#cbd5e1' }}>Ruangan & Workstation:</b>
                {bRooms.map((rm) => {
                  const rmWs = workstations.filter((ws) => ws.room_id === rm.id);

                  return (
                    <div
                      key={rm.id}
                      style={{
                        background: '#1e293b',
                        border: '1px solid #334155',
                        borderRadius: '10px',
                        padding: '12px',
                      }}
                    >
                      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <div>
                          <b style={{ fontSize: '13px', color: '#f8fafc' }}>{rm.name}</b>
                          <div style={{ fontSize: '11px', color: '#94a3b8' }}>
                            {rm.room_code} · Kapasitas: {rm.max_capacity}
                          </div>
                        </div>
                        <span
                          style={{
                            fontSize: '10px',
                            background: '#0f172a',
                            color: rm.accent_color || '#38bdf8',
                            padding: '2px 6px',
                            borderRadius: '4px',
                            textTransform: 'uppercase',
                          }}
                        >
                          {rm.category}
                        </span>
                      </div>

                      {/* Workstations / Agents inside this room */}
                      {rmWs.length > 0 && (
                        <div style={{ marginTop: '10px', display: 'flex', flexDirection: 'column', gap: '6px' }}>
                          {rmWs.map((ws) => {
                            const assignedAgent = presences.find((p) => p.agent_id === ws.assigned_agent_id);
                            const statusInfo = assignedAgent
                              ? getStatusColor(assignedAgent.presence_status)
                              : null;

                            return (
                              <div
                                key={ws.id}
                                onClick={() => {
                                  if (assignedAgent) {
                                    setSelectedAgentDetail(assignedAgent);
                                  }
                                }}
                                style={{
                                  background: assignedAgent ? '#0f172a' : 'transparent',
                                  border: `1px solid ${assignedAgent ? '#334155' : 'rgba(255,255,255,0.06)'}`,
                                  borderRadius: '8px',
                                  padding: '8px 10px',
                                  display: 'flex',
                                  justifyContent: 'space-between',
                                  alignItems: 'center',
                                  cursor: assignedAgent ? 'pointer' : 'default',
                                }}
                              >
                                <div>
                                  <span style={{ fontSize: '11px', color: '#94a3b8' }}>
                                    {ws.workstation_code} · {ws.label || ws.equipment_type}
                                  </span>
                                  {assignedAgent && (
                                    <div style={{ fontSize: '12px', color: '#f8fafc', fontWeight: 600 }}>
                                      {assignedAgent.name} ({assignedAgent.agent_code})
                                    </div>
                                  )}
                                </div>

                                {assignedAgent ? (
                                  <span
                                    style={{
                                      fontSize: '10px',
                                      padding: '2px 8px',
                                      borderRadius: '6px',
                                      backgroundColor: statusInfo?.bg,
                                      color: statusInfo?.text,
                                      border: `1px solid ${statusInfo?.border}`,
                                    }}
                                  >
                                    ● {statusInfo?.label}
                                  </span>
                                ) : (
                                  <span style={{ fontSize: '10px', color: '#64748b' }}>Kosong</span>
                                )}
                              </div>
                            );
                          })}
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>
            </div>
          );
        })}
      </div>

      {/* Selected Agent Modal in 2D View */}
      {selectedAgentDetail && (
        <div
          onClick={() => setSelectedAgentDetail(null)}
          style={{
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            background: 'rgba(0, 0, 0, 0.75)',
            display: 'flex',
            justifyContent: 'center',
            alignItems: 'center',
            zIndex: 100,
          }}
        >
          <div
            onClick={(e) => e.stopPropagation()}
            style={{
              background: '#0f172a',
              border: '1px solid #38bdf8',
              borderRadius: '16px',
              width: '420px',
              maxWidth: '90%',
              padding: '24px',
              boxShadow: '0 20px 50px rgba(0,0,0,0.8)',
            }}
          >
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
              <div>
                <b style={{ color: '#38bdf8', fontSize: '12px' }}>{selectedAgentDetail.agent_code}</b>
                <h2 style={{ margin: '4px 0 0', fontSize: '20px', color: '#f8fafc' }}>
                  {selectedAgentDetail.name}
                </h2>
                <div style={{ fontSize: '12px', color: '#94a3b8' }}>
                  {selectedAgentDetail.position_title}
                </div>
              </div>
              <button
                onClick={() => setSelectedAgentDetail(null)}
                style={{
                  background: 'transparent',
                  border: 'none',
                  color: '#94a3b8',
                  fontSize: '20px',
                  cursor: 'pointer',
                }}
              >
                ✕
              </button>
            </div>

            <div
              style={{
                marginTop: '16px',
                padding: '12px',
                borderRadius: '10px',
                backgroundColor: getStatusColor(selectedAgentDetail.presence_status).bg,
                border: `1px solid ${getStatusColor(selectedAgentDetail.presence_status).border}`,
              }}
            >
              <b style={{ color: getStatusColor(selectedAgentDetail.presence_status).text, fontSize: '13px' }}>
                ● {getStatusColor(selectedAgentDetail.presence_status).label}
              </b>
              <div style={{ fontSize: '12px', color: '#cbd5e1', marginTop: '4px' }}>
                {selectedAgentDetail.status_reason}
              </div>
            </div>

            <div style={{ marginTop: '16px', fontSize: '13px', display: 'flex', flexDirection: 'column', gap: '8px' }}>
              <div>
                <span style={{ color: '#94a3b8' }}>Gedung:</span>{' '}
                <b>{selectedAgentDetail.placement?.building_name || 'Roaming'}</b>
              </div>
              <div>
                <span style={{ color: '#94a3b8' }}>Ruangan:</span>{' '}
                <b>{selectedAgentDetail.placement?.room_name || 'N/A'}</b>
              </div>
              <div>
                <span style={{ color: '#94a3b8' }}>Workstation:</span>{' '}
                <b style={{ color: '#38bdf8' }}>{selectedAgentDetail.placement?.workstation_code || 'N/A'}</b>
              </div>
            </div>

            {selectedAgentDetail.active_task && (
              <div style={{ marginTop: '16px', background: '#1e293b', borderRadius: '10px', padding: '12px' }}>
                <span style={{ fontSize: '11px', color: '#3b82f6', fontWeight: 700 }}>TUGAS AKTIF</span>
                <div style={{ color: '#f8fafc', fontWeight: 600, fontSize: '13px', marginTop: '4px' }}>
                  {selectedAgentDetail.active_task.title}
                </div>
                <div style={{ fontSize: '11px', color: '#94a3b8', marginTop: '4px' }}>
                  {selectedAgentDetail.active_task.task_code} · Prioritas: {selectedAgentDetail.active_task.priority}
                </div>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
};
