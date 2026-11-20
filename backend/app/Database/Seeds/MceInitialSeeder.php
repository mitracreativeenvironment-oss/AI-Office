<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MceInitialSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // 1. Company
        $this->db->table('companies')->emptyTable();
        $this->db->table('companies')->insert([
            'name'        => 'Mitra Creative Environment (MCE)',
            'slug'        => 'mce',
            'tagline'     => 'AI Company Operating System & 3D Virtual Office',
            'description' => 'Perusahaan teknologi yang mengoperasikan sebagian besar aktivitas bisnis melalui sistem AI terintegrasi.',
            'email'       => 'contact@mce.local',
            'phone'       => '+62 812-3456-7890',
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        // 2. Divisions (6 Unit Bisnis)
        $this->db->table('divisions')->emptyTable();
        $divisions = [
            [
                'company_id'  => 1,
                'key'         => 'technology',
                'name'        => 'MCE Technology & Digital Solutions',
                'label'       => 'Tech & Solutions',
                'kbli_codes'  => '62900, 62199, 62209',
                'color'       => '#e4572e',
                'rug_color'   => '#e4572e',
                'x0'          => -7.0,
                'x1'          => -1.8,
                'description' => 'Pengembangan software, website, sistem informasi, dan konsultansi IT.',
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'company_id'  => 1,
                'key'         => 'operations',
                'name'        => 'MCE Corporate Operations',
                'label'       => 'Corporate Operations',
                'kbli_codes'  => 'Pendukung Bersama',
                'color'       => '#f2b134',
                'rug_color'   => '#f2b134',
                'x0'          => -1.8,
                'x1'          => 2.0,
                'description' => 'Fungsi eksekutif, koordinasi, HR, keuangan, legal, dan Business Growth Center.',
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'company_id'  => 1,
                'key'         => 'services',
                'name'        => 'MCE Technical Services',
                'label'       => 'Technical Services',
                'kbli_codes'  => '95101, 95400',
                'color'       => '#3a86ff',
                'rug_color'   => '#3a86ff',
                'x0'          => 2.0,
                'x1'          => 7.0,
                'description' => 'Layanan reparasi, pemeliharaan, dan intermediasi perawatan komputer.',
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'company_id'  => 1,
                'key'         => 'hardware',
                'name'        => 'MCE Hardware & Manufacturing',
                'label'       => 'Hardware Lab',
                'kbli_codes'  => '26210',
                'color'       => '#2a9d8f',
                'rug_color'   => '#2a9d8f',
                'x0'          => -7.0,
                'x1'          => -1.8,
                'description' => 'Riset hardware, desain spesifikasi, dan perakitan komputer.',
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'company_id'  => 1,
                'key'         => 'commerce',
                'name'        => 'MCE Commerce & Distribution',
                'label'       => 'Commerce & Store',
                'kbli_codes'  => '46511, 47401',
                'color'       => '#8e6bd8',
                'rug_color'   => '#8e6bd8',
                'x0'          => 2.0,
                'x1'          => 7.0,
                'description' => 'Perdagangan besar dan eceran komputer serta perlengkapannya.',
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'company_id'  => 1,
                'key'         => 'academy',
                'name'        => 'MCE Academy',
                'label'       => 'Academy & Training',
                'kbli_codes'  => '85592',
                'color'       => '#ff5d8f',
                'rug_color'   => '#ff5d8f',
                'x0'          => 2.0,
                'x1'          => 7.0,
                'description' => 'Pendidikan komputer swasta dan pelatihan teknologi informasi.',
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];
        $this->db->table('divisions')->insertBatch($divisions);

        // 3. Agents
        $this->db->table('agents')->emptyTable();
        $agents = [
            [
                'key'            => 'director',
                'name'           => 'Director AI',
                'role_title'     => 'Pusat Koordinasi Operasional',
                'division_key'   => 'operations',
                'level'          => 'director',
                'color'          => '#6c5ce7',
                'pos_x'          => 0.1,
                'pos_z'          => -0.2,
                'status'         => 'working',
                'current_task'   => 'Koordinasi harian & supervisi alokasi approval Level C',
                'approval_level' => 'B',
                'is_online'      => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'key'            => 'tech_head',
                'name'           => 'Tech Head AI',
                'role_title'     => 'Kepala Divisi Teknologi & Solusi',
                'division_key'   => 'technology',
                'level'          => 'division_head',
                'color'          => '#e4572e',
                'pos_x'          => -5.2,
                'pos_z'          => -1.4,
                'status'         => 'working',
                'current_task'   => 'Review arsitektur REST API & Three.js',
                'approval_level' => 'A',
                'is_online'      => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'key'            => 'dev_ai',
                'name'           => 'Dev Specialist AI',
                'role_title'     => 'Pengembangan Sistem & Web',
                'division_key'   => 'technology',
                'level'          => 'specialist',
                'color'          => '#2a9d8f',
                'pos_x'          => -3.2,
                'pos_z'          => -1.4,
                'status'         => 'working',
                'current_task'   => 'Pembangunan API Controller & Model CI4',
                'approval_level' => 'A',
                'is_online'      => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'key'            => 'qa_ai',
                'name'           => 'QA Engineer AI',
                'role_title'     => 'Quality Assurance & Audit',
                'division_key'   => 'technology',
                'level'          => 'specialist',
                'color'          => '#8e6bd8',
                'pos_x'          => -4.2,
                'pos_z'          => 1.2,
                'status'         => 'idle',
                'current_task'   => 'Siaga untuk automated test suite API',
                'approval_level' => 'A',
                'is_online'      => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'key'            => 'service_head',
                'name'           => 'Service Head AI',
                'role_title'     => 'Kepala Layanan Teknis & Reparasi',
                'division_key'   => 'services',
                'level'          => 'division_head',
                'color'          => '#3a86ff',
                'pos_x'          => 3.6,
                'pos_z'          => -1.2,
                'status'         => 'working',
                'current_task'   => 'Analisis tiket layanan pemeliharaan perangkat klien',
                'approval_level' => 'A',
                'is_online'      => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'key'            => 'academy_head',
                'name'           => 'Academy Head AI',
                'role_title'     => 'Kepala Kurikulum & Edukasi TIK',
                'division_key'   => 'academy',
                'level'          => 'division_head',
                'color'          => '#ff5d8f',
                'pos_x'          => 5.4,
                'pos_z'          => 0.8,
                'status'         => 'working',
                'current_task'   => 'Penyusunan materi ajar kursus pemrograman web',
                'approval_level' => 'A',
                'is_online'      => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
        ];
        $this->db->table('agents')->insertBatch($agents);

        // 4. Initial Tasks
        $this->db->table('tasks')->emptyTable();
        $tasks = [
            [
                'task_code'      => 'TSK-MCE-001',
                'title'          => 'Inisialisasi REST API CodeIgniter 4 & Database MySQL',
                'description'    => 'Penyusunan migration, seeder, dan endpoint REST API Fase 2.',
                'division_key'   => 'technology',
                'agent_key'      => 'dev_ai',
                'track'          => 'project_delivery',
                'priority'       => 'critical',
                'approval_level' => 'A',
                'status'         => 'working',
                'progress'       => 85,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'task_code'      => 'TSK-MCE-002',
                'title'          => 'Pengajuan Alokasi Server Staging VPS MCE',
                'description'    => 'Penyewaan server hosting VPS cloud untuk staging environment produksi.',
                'division_key'   => 'operations',
                'agent_key'      => 'director',
                'track'          => 'project_delivery',
                'priority'       => 'critical',
                'approval_level' => 'C',
                'status'         => 'waiting_approval',
                'progress'       => 50,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'task_code'      => 'TSK-MCE-003',
                'title'          => 'Riset Kata Kunci & Optimasi SEO Organik Bisnis MCE',
                'description'    => 'Analisis search trends untuk jasa pembuatan website & reparasi komputer regional.',
                'division_key'   => 'operations',
                'agent_key'      => 'director',
                'track'          => 'business_growth',
                'priority'       => 'growth',
                'approval_level' => 'A',
                'status'         => 'working',
                'progress'       => 35,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
        ];
        $this->db->table('tasks')->insertBatch($tasks);

        // 5. Initial Approval Requests
        $this->db->table('approval_requests')->emptyTable();
        $approvals = [
            [
                'task_id'          => 2,
                'requester_agent'  => 'director',
                'title'            => 'Persetujuan Pembayaran Sewa Cloud VPS Staging (1 Tahun)',
                'description'      => 'Server VPS dibutuhkan untuk deploy backend CI4 dan worker background Hermes.',
                'reason'           => 'Menunjang pengujian staging Fase 2 s/d Fase 10 MCE.',
                'risk_level'       => 'medium',
                'financial_amount' => 3500000.00,
                'status'           => 'pending',
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
        ];
        $this->db->table('approval_requests')->insertBatch($approvals);

        // 6. Users
        $this->db->table('users')->emptyTable();
        $this->db->table('users')->insert([
            'username'      => 'owner',
            'email'         => 'owner@mce.local',
            'password_hash' => password_hash('admin123', PASSWORD_BCRYPT),
            'full_name'     => 'Human Owner & Executive Management',
            'role'          => 'owner',
            'is_active'     => 1,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        // 7. Initial Audit Log
        $this->db->table('audit_logs')->emptyTable();
        $this->db->table('audit_logs')->insert([
            'user_id'     => 1,
            'agent_key'   => 'director',
            'action'      => 'INITIALIZE_SYSTEM',
            'entity_type' => 'company',
            'entity_id'   => '1',
            'details'     => 'Initial seeding of MCE system data: 6 divisions, 6 agents, initial tasks, and approval requests.',
            'ip_address'  => '127.0.0.1',
            'created_at'  => $now,
        ]);
    }
}
