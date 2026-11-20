/**
 * MCE — Type Definitions
 * AI Company Operating System & 3D Virtual Office
 */

export type ApprovalLevel = 'A' | 'B' | 'C';

export type AgentLevel = 'owner' | 'director' | 'division_head' | 'specialist';

export type AgentActivityType =
  | 'kerja'
  | 'laporan'
  | 'belajar'
  | 'siaga'
  | 'pulang'
  | 'waiting_approval'
  | 'review';

export type RoomKey = 'technology' | 'operations' | 'services';

export interface MCEDivision {
  key: RoomKey;
  label: string;
  kbli: string[];
  x0: number;
  x1: number;
  warna: string;
  karpet: string;
}

export interface MCEAgent {
  key: string;
  nama: string;
  tugas: string;
  ruang: RoomKey;
  warna: string;
  level: AgentLevel;
  approvalLevelDefault?: ApprovalLevel;
  x: number;
  z: number;
}

export interface AgentReportItem {
  ringkasan?: string;
  menunggu?: string[];
  mendesak?: string[];
  angka?: { label: string; nilai: number | string }[];
}

export interface TaskItem {
  id: string;
  title: string;
  agentKey: string;
  division: string;
  priority: 'critical' | 'project' | 'growth' | 'maintenance' | 'research';
  approvalLevel: ApprovalLevel;
  status: 'pending' | 'in_progress' | 'review' | 'waiting_approval' | 'completed';
}
