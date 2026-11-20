/**
 * MCE — Konfigurasi Identitas, Ruangan, dan Struktur Agen
 * AI Company Operating System & 3D Virtual Office
 */

import laporanAgen from './laporan.json';
import type { GayaId } from './gaya/daftar';
import type { MCEAgent, RoomKey } from './types/mce';

export type { RoomKey };
export type Agen = MCEAgent;

export const KANTOR = {
  nama: 'MCE Virtual Office',
  subjudul: 'Mitra Creative Environment — AI Company OS',
  tagline: 'Hybrid AI Automation with Human-in-the-Loop',
};

/**
 * Pilihan gaya visual 3D bawaan (bisa diubah via URL ?gaya=<id> atau pemilih di layar)
 */
export const GAYA: GayaId = 'asli';
export const TAMPIL_PEMILIH_GAYA = true;

/**
 * Pemetaan unit bisnis MCE pada visual ruang 3D:
 * - technology: MCE Technology & Digital Solutions (KBLI 62900, 62199, 62209)
 * - operations: MCE Corporate Operations (Executive, HR, Finance, Growth)
 * - services: MCE Technical Services (95101) & MCE Academy (85592)
 */
export const RUANGAN = [
  { key: 'technology', label: 'Tech & Solutions', x0: -7, x1: -1.8, warna: '#e9d9c4', karpet: '#e4572e' },
  { key: 'operations', label: 'Corporate Operations', x0: -1.8, x1: 2, warna: '#efe3cf', karpet: '#f2b134' },
  { key: 'services', label: 'Technical & Academy', x0: 2, x1: 7, warna: '#e3d6c6', karpet: '#3a86ff' },
] as const;

/**
 * Agen Representatif MCE (Director AI, Division Heads & Key Specialists)
 */
export const AGEN: MCEAgent[] = [
  // Technology Lab
  { key: 'tech_head', nama: 'Tech Head AI', tugas: 'Arsitektur Software & Solusi', ruang: 'technology', warna: '#e4572e', level: 'division_head', x: -5.2, z: -1.4 },
  { key: 'dev_ai', nama: 'Dev Specialist AI', tugas: 'Pengembangan Web & Sistem', ruang: 'technology', warna: '#2a9d8f', level: 'specialist', x: -3.2, z: -1.4 },
  { key: 'qa_ai', nama: 'QA Engineer AI', tugas: 'Audit & Pengujian Sistem', ruang: 'technology', warna: '#8e6bd8', level: 'specialist', x: -4.2, z: 1.2 },

  // Corporate Operations & Executive
  { key: 'director', nama: 'Director AI', tugas: 'Pusat Koordinasi Operasional', ruang: 'operations', warna: '#6c5ce7', level: 'director', x: 0.1, z: -0.2 },

  // Technical Services & Academy
  { key: 'service_head', nama: 'Service Head AI', tugas: 'Layanan Perbaikan & Maintenance', ruang: 'services', warna: '#3a86ff', level: 'division_head', x: 3.6, z: -1.2 },
  { key: 'academy_head', nama: 'Academy Head AI', tugas: 'Kurikulum & Pelatihan Komputer', ruang: 'services', warna: '#ff5d8f', level: 'division_head', x: 5.4, z: 0.8 },
];

export type Aktivitas = 'kerja' | 'laporan' | 'belajar';

/** Jadwal kerja operasional (WIB). agen: '*' = seluruh agen. */
export const JADWAL: { agen: string[] | '*'; mulai: string; menit: number; hari: number[]; aktivitas: Aktivitas; label: string }[] = [
  { agen: ['director'], mulai: '07:30', menit: 30, hari: [1, 2, 3, 4, 5], aktivitas: 'kerja', label: 'evaluasi target harian…' },
  { agen: ['tech_head', 'dev_ai'], mulai: '08:00', menit: 45, hari: [1, 2, 3, 4, 5], aktivitas: 'kerja', label: 'sprint development web & api…' },
  { agen: ['service_head'], mulai: '08:15', menit: 40, hari: [1, 2, 3, 4, 5], aktivitas: 'kerja', label: 'tinjau tiket perbaikan komputer…' },
  { agen: ['academy_head'], mulai: '08:30', menit: 35, hari: [1, 2, 3, 4, 5], aktivitas: 'kerja', label: 'susun silabus materi kursus…' },
  { agen: ['qa_ai'], mulai: '09:00', menit: 40, hari: [1, 2, 3, 4, 5], aktivitas: 'kerja', label: 'automated regression testing…' },
  { agen: '*', mulai: '16:00', menit: 30, hari: [1, 2, 3, 4, 5], aktivitas: 'laporan', label: 'konsolidasi laporan divisi…' },
  { agen: '*', mulai: '20:00', menit: 120, hari: [1, 2, 3, 4, 5, 6, 7], aktivitas: 'belajar', label: 'knowledge base ingestion…' },
];

export const JAM_KANTOR = { buka: '07:30', tutup: '18:00' };

export const LAPORAN = laporanAgen as Record<string, { ringkasan?: string; menunggu?: string[]; mendesak?: string[]; angka?: { label: string; nilai: number | string }[] }>;

export const BABAK = [
  { judul: 'Pagi: Koordinasi & Sprint', dari: '07:28', sampai: '12:00', detik: 12 },
  { judul: 'Sore: Eksekusi & Laporan', dari: '12:00', sampai: '17:00', detik: 10 },
  { judul: 'Malam: Review & Learning', dari: '17:00', sampai: '22:00', detik: 8 },
];
