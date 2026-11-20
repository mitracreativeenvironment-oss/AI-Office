<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class OrganizationGovernanceSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // 1. Seed 7 Core Values (Controlled Reference Data)
        $coreValues = [
            [
                'code'          => 'integrity',
                'name_en'       => 'Integrity',
                'name_id'       => 'Integritas',
                'description'   => 'Menjunjung tinggi kejujuran, etika profesional, kepatuhan tata kelola, dan transparansi dalam seluruh tindakan dan keputusan.',
                'display_order' => 1,
                'is_active'     => 1,
                'created_at'    => $now,
            ],
            [
                'code'          => 'innovation',
                'name_en'       => 'Innovation',
                'name_id'       => 'Inovasi',
                'description'   => 'Terus mengembangkan solusi baru yang kreatif, adaptif, dan berdaya guna melalui integrasi kecerdasan buatan terdepan.',
                'display_order' => 2,
                'is_active'     => 1,
                'created_at'    => $now,
            ],
            [
                'code'          => 'collaboration',
                'name_en'       => 'Collaboration',
                'name_id'       => 'Kolaborasi',
                'description'   => 'Membangun sinergi harmonis antara kecerdasan manusia (Human-in-the-Loop) dan otonomi tenaga kerja AI.',
                'display_order' => 3,
                'is_active'     => 1,
                'created_at'    => $now,
            ],
            [
                'code'          => 'excellence',
                'name_en'       => 'Excellence',
                'name_id'       => 'Keunggulan',
                'description'   => 'Berkomitmen memberikan standar kualitas tertinggi dalam setiap produk software, layanan teknis, dan operasional bisnis.',
                'display_order' => 4,
                'is_active'     => 1,
                'created_at'    => $now,
            ],
            [
                'code'          => 'adaptability',
                'name_en'       => 'Adaptability',
                'name_id'       => 'Adaptabilitas',
                'description'   => 'Tangguh dan responsif terhadap perubahan dinamika pasar teknologi, kebutuhan pelanggan, dan perkembangan industri.',
                'display_order' => 5,
                'is_active'     => 1,
                'created_at'    => $now,
            ],
            [
                'code'          => 'customer_centric',
                'name_en'       => 'Customer-Centric',
                'name_id'       => 'Berorientasi pada Pelanggan',
                'description'   => 'Menempatkan keberhasilan, kepuasan, dan dampak nyata bagi mitra serta pelanggan sebagai pusat inovasi.',
                'display_order' => 6,
                'is_active'     => 1,
                'created_at'    => $now,
            ],
            [
                'code'          => 'sustainability',
                'name_en'       => 'Sustainability',
                'name_id'       => 'Keberlanjutan',
                'description'   => 'Membangun model bisnis dan tata kelola organisasi yang bertanggung jawab, efisien secara biaya, dan berkelanjutan jangka panjang.',
                'display_order' => 7,
                'is_active'     => 1,
                'created_at'    => $now,
            ],
        ];

        foreach ($coreValues as $cv) {
            $existing = $this->db->table('core_values')->where('code', $cv['code'])->get()->getRow();
            if (!$existing) {
                $this->db->table('core_values')->insert($cv);
            }
        }

        // 2. Seed Authority Profiles (DoA Framework)
        $authorityProfiles = [
            [
                'code'                => 'DOA-LEVEL-A',
                'name'                => 'Level A: Owner Strategic & Capital Authority',
                'authority_level'     => 'A',
                'domain'              => 'strategic',
                'financial_limit'     => 0.00, // Unlimited
                'permitted_actions'   => json_encode(['capital_investment', 'restructuring', 'policy_veto', 'high_outlay_approval']),
                'approval_conditions' => 'Pengeluaran finansial > IDR 5.000.000 mewajibkan otorisasi manual Human Owner.',
                'can_subdelegate'     => 0,
                'is_active'           => 1,
                'created_at'          => $now,
            ],
            [
                'code'                => 'DOA-LEVEL-B',
                'name'                => 'Level B: Continuous Executive Board Authority',
                'authority_level'     => 'B',
                'domain'              => 'operational',
                'financial_limit'     => 5000000.00,
                'permitted_actions'   => json_encode(['internal_operations', 'business_growth', 'marketing', 'campaigns', 'task_dispatch']),
                'approval_conditions' => 'Otoritas mandiri Dewan Direksi untuk operasional internal hingga batas IDR 5.000.000.',
                'can_subdelegate'     => 1,
                'is_active'           => 1,
                'created_at'          => $now,
            ],
            [
                'code'                => 'DOA-LEVEL-C',
                'name'                => 'Level C: Operational Task & Workforce Dispatch Authority',
                'authority_level'     => 'C',
                'domain'              => 'operational',
                'financial_limit'     => 1000000.00,
                'permitted_actions'   => json_encode(['task_decomposition', 'agent_assignment', 'shift_handover', 'routine_spend']),
                'approval_conditions' => 'Otoritas koordinasi harian Director AI dan Division Heads untuk pengeluaran hingga IDR 1.000.000.',
                'can_subdelegate'     => 0,
                'is_active'           => 1,
                'created_at'          => $now,
            ],
            [
                'code'                => 'DOA-OPS-ROUTINE',
                'name'                => 'Level Operational: Routine Non-Financial Authority',
                'authority_level'     => 'OPERATIONAL',
                'domain'              => 'technical',
                'financial_limit'     => 0.00,
                'permitted_actions'   => json_encode(['task_execution', 'status_update', 'report_filing']),
                'approval_conditions' => 'Pelaksanaan tugas teknis dan operasional tanpa kewenangan pengeluaran anggaran.',
                'can_subdelegate'     => 0,
                'is_active'           => 1,
                'created_at'          => $now,
            ],
        ];

        foreach ($authorityProfiles as $ap) {
            $existing = $this->db->table('authority_profiles')->where('code', $ap['code'])->get()->getRow();
            if (!$existing) {
                $this->db->table('authority_profiles')->insert($ap);
            }
        }

        // 3. Seed Governance Bodies
        $governanceBodies = [
            [
                'code'          => 'GOV-OWNER',
                'name'          => 'Human Owner Authority',
                'type'          => 'owner_authority',
                'description'   => 'Badan kepemimpinan tertinggi dan pemegang hak veto final perusahaan.',
                'mandate'       => 'Pemberian arahan strategis, persetujuan modal/finansial Level A, dan penugasan mandat eksekutif.',
                'decision_rule' => 'sole_owner',
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'code'          => 'GOV-BOARD',
                'name'          => 'Continuous Executive Board (Rapat Direksi)',
                'type'          => 'executive_board',
                'description'   => 'Dewan eksekutif otonom yang memimpin operasional internal dan business growth.',
                'mandate'       => 'Perencanaan operasional, promosi perusahaan, pemasaran, branding, kampanye, dan koordinasi divisi.',
                'decision_rule' => 'board_consensus',
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'code'          => 'GOV-DIR-AI',
                'name'          => 'Director AI Operational Authority',
                'type'          => 'director_authority',
                'description'   => 'Pusat komando dan koordinasi operasional AI Workforce harian.',
                'mandate'       => 'Dekomposisi tugas, pemantauan shift agen, validasi Wallbook, dan pelaporan rutin ke Dewan Direksi.',
                'decision_rule' => 'delegated_operational',
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ];

        $bodyMap = [];
        foreach ($governanceBodies as $gb) {
            $existing = $this->db->table('governance_bodies')->where('code', $gb['code'])->get()->getRow();
            if (!$existing) {
                $this->db->table('governance_bodies')->insert($gb);
                $bodyMap[$gb['code']] = $this->db->insertID();
            } else {
                $bodyMap[$gb['code']] = $existing->id;
            }
        }

        // 4. Seed Governance Memberships
        if (isset($bodyMap['GOV-OWNER'])) {
            $exists = $this->db->table('governance_memberships')->where('body_id', $bodyMap['GOV-OWNER'])->countAllResults();
            if ($exists === 0) {
                $this->db->table('governance_memberships')->insert([
                    'body_id'         => $bodyMap['GOV-OWNER'],
                    'member_type'     => 'human',
                    'member_id'       => '1',
                    'member_name'     => 'Human Owner',
                    'governance_role' => 'chair',
                    'voting_weight'   => 1,
                    'status'          => 'active',
                    'start_date'      => $now,
                    'created_at'      => $now,
                ]);
            }
        }

        if (isset($bodyMap['GOV-BOARD'])) {
            $exists = $this->db->table('governance_memberships')->where('body_id', $bodyMap['GOV-BOARD'])->countAllResults();
            if ($exists === 0) {
                $this->db->table('governance_memberships')->insertBatch([
                    [
                        'body_id'         => $bodyMap['GOV-BOARD'],
                        'member_type'     => 'human',
                        'member_id'       => '1',
                        'member_name'     => 'Human Owner',
                        'governance_role' => 'advisor',
                        'voting_weight'   => 1,
                        'status'          => 'active',
                        'start_date'      => $now,
                        'created_at'      => $now,
                    ],
                    [
                        'body_id'         => $bodyMap['GOV-BOARD'],
                        'member_type'     => 'agent',
                        'member_id'       => 'director',
                        'member_name'     => 'Director AI',
                        'governance_role' => 'operational_lead',
                        'voting_weight'   => 1,
                        'status'          => 'active',
                        'start_date'      => $now,
                        'created_at'      => $now,
                    ],
                ]);
            }
        }

        // 5. Seed Root Organization & 6 Initial Divisions
        $rootUnit = $this->db->table('organization_units')->where('code', 'ORG-MCE')->get()->getRow();
        if (!$rootUnit) {
            $this->db->table('organization_units')->insert([
                'parent_id'            => null,
                'code'                 => 'ORG-MCE',
                'name'                 => 'Mitra Creative Environment (Root Organization)',
                'type'                 => 'root',
                'description'          => 'Induk entitas korporat Mitra Creative Environment.',
                'mission'              => 'Membangun ekosistem teknologi AI terintegrasi dengan tata kelola profesional dan etika kolaborasi human-AI.',
                'status'               => 'active',
                'sort_order'           => 1,
                'effective_start_date' => date('Y-01-01'),
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);
            $rootId = $this->db->insertID();
        } else {
            $rootId = $rootUnit->id;
        }

        $initialDivisions = [
            [
                'parent_id'            => $rootId,
                'code'                 => 'DIV-TECH',
                'name'                 => 'MCE Technology & Digital Solutions',
                'type'                 => 'division',
                'description'          => 'Pengembangan software, website, sistem informasi, dan konsultansi IT.',
                'mission'              => 'Menghasilkan arsitektur digital dan solusi perangkat lunak enterprise berstandar industri tinggi.',
                'division_key'         => 'technology',
                'status'               => 'active',
                'sort_order'           => 10,
                'effective_start_date' => date('Y-01-01'),
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            [
                'parent_id'            => $rootId,
                'code'                 => 'DIV-OPS',
                'name'                 => 'MCE Corporate Operations',
                'type'                 => 'division',
                'description'          => 'Fungsi eksekutif, koordinasi, HR, keuangan, legal, dan Business Growth Center.',
                'mission'              => 'Menjamin kelancaran administrasi korporat, kepatuhan governance, dan pertumbuhan bisnis berkelanjutan.',
                'division_key'         => 'operations',
                'status'               => 'active',
                'sort_order'           => 20,
                'effective_start_date' => date('Y-01-01'),
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            [
                'parent_id'            => $rootId,
                'code'                 => 'DIV-SRV',
                'name'                 => 'MCE Technical Services',
                'type'                 => 'division',
                'description'          => 'Layanan reparasi, pemeliharaan, dan intermediasi perawatan komputer.',
                'mission'              => 'Memberikan layanan teknis pemeliharaan perangkat keras andal dan berkualitas.',
                'division_key'         => 'services',
                'status'               => 'active',
                'sort_order'           => 30,
                'effective_start_date' => date('Y-01-01'),
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            [
                'parent_id'            => $rootId,
                'code'                 => 'DIV-HDW',
                'name'                 => 'MCE Hardware & Manufacturing',
                'type'                 => 'division',
                'description'          => 'Riset hardware, perakitan komputer, dan integrasi perangkat keras.',
                'mission'              => 'Riset dan perakitan sistem komputasi berkinerja tinggi sesuai kebutuhan industri.',
                'division_key'         => 'hardware',
                'status'               => 'active',
                'sort_order'           => 40,
                'effective_start_date' => date('Y-01-01'),
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            [
                'parent_id'            => $rootId,
                'code'                 => 'DIV-COM',
                'name'                 => 'MCE Commerce & Distribution',
                'type'                 => 'division',
                'description'          => 'Perdagangan besar dan eceran komputer serta perlengkapan TIK.',
                'mission'              => 'Distribusi produk teknologi dan aksesoris berkualitas ke pasar konsumen dan bisnis.',
                'division_key'         => 'commerce',
                'status'               => 'active',
                'sort_order'           => 50,
                'effective_start_date' => date('Y-01-01'),
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            [
                'parent_id'            => $rootId,
                'code'                 => 'DIV-ACD',
                'name'                 => 'MCE Academy & Training Center',
                'type'                 => 'division',
                'description'          => 'Penyelenggaraan kursus komputer, coding, dan literasi AI.',
                'mission'              => 'Mencetak talenta digital kompeten dan adaptif melalui kurikulum praktis dan terkini.',
                'division_key'         => 'academy',
                'status'               => 'active',
                'sort_order'           => 60,
                'effective_start_date' => date('Y-01-01'),
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
        ];

        $unitMap = ['ORG-MCE' => $rootId];
        foreach ($initialDivisions as $div) {
            $existing = $this->db->table('organization_units')->where('code', $div['code'])->get()->getRow();
            if (!$existing) {
                $this->db->table('organization_units')->insert($div);
                $unitMap[$div['code']] = $this->db->insertID();
            } else {
                $unitMap[$div['code']] = $existing->id;
            }
        }

        // 6. Seed Key Positions
        $positions = [
            [
                'unit_id'       => $rootId,
                'code'          => 'POS-OWNER',
                'title'         => 'Human Owner & Executive Chair',
                'description'   => 'Pemilik perusahaan dan ketua otoritas tata kelola tertinggi MCE.',
                'level'         => 'owner',
                'is_leadership' => 1,
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'unit_id'       => $unitMap['DIV-OPS'] ?? $rootId,
                'code'          => 'POS-DIR-AI',
                'title'         => 'Director AI Operational Coordinator',
                'description'   => 'Koordinator operasional utama AI Workforce di bawah arahan Dewan Direksi.',
                'level'         => 'director',
                'is_leadership' => 1,
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'unit_id'       => $unitMap['DIV-TECH'] ?? $rootId,
                'code'          => 'POS-HEAD-TECH',
                'title'         => 'Head of Technology & Solutions',
                'description'   => 'Pimpinan teknis divisi solusi digital & pengembangan software.',
                'level'         => 'division_head',
                'is_leadership' => 1,
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'unit_id'       => $unitMap['DIV-SRV'] ?? $rootId,
                'code'          => 'POS-HEAD-SRV',
                'title'         => 'Head of Technical Services',
                'description'   => 'Pimpinan operasional layanan teknis dan perawatan komputer.',
                'level'         => 'division_head',
                'is_leadership' => 1,
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'unit_id'       => $unitMap['DIV-ACD'] ?? $rootId,
                'code'          => 'POS-HEAD-ACD',
                'title'         => 'Head of Academy & Training Center',
                'description'   => 'Pimpinan kurikulum dan edukasi TIK MCE Academy.',
                'level'         => 'division_head',
                'is_leadership' => 1,
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ];

        $posMap = [];
        foreach ($positions as $pos) {
            $existing = $this->db->table('organization_positions')->where('code', $pos['code'])->get()->getRow();
            if (!$existing) {
                $this->db->table('organization_positions')->insert($pos);
                $posMap[$pos['code']] = $this->db->insertID();
            } else {
                $posMap[$pos['code']] = $existing->id;
            }
        }

        // 7. Seed Appointments
        if (isset($posMap['POS-OWNER'])) {
            $exists = $this->db->table('organization_appointments')->where('position_id', $posMap['POS-OWNER'])->countAllResults();
            if ($exists === 0) {
                $this->db->table('organization_appointments')->insert([
                    'position_id'    => $posMap['POS-OWNER'],
                    'unit_id'        => $rootId,
                    'appointee_type' => 'human',
                    'appointee_id'   => '1',
                    'appointee_name' => 'Human Owner & Executive Management',
                    'status'         => 'active',
                    'start_date'     => $now,
                    'decision_ref'   => 'FOUNDING-CHARTER-2026',
                    'notes'          => 'Penetapan kepemimpinan definitif pendiri perusahaan.',
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }
        }

        if (isset($posMap['POS-DIR-AI'])) {
            $exists = $this->db->table('organization_appointments')->where('position_id', $posMap['POS-DIR-AI'])->countAllResults();
            if ($exists === 0) {
                $this->db->table('organization_appointments')->insert([
                    'position_id'    => $posMap['POS-DIR-AI'],
                    'unit_id'        => $unitMap['DIV-OPS'] ?? $rootId,
                    'appointee_type' => 'agent',
                    'appointee_id'   => 'director',
                    'appointee_name' => 'Director AI',
                    'status'         => 'active',
                    'start_date'     => $now,
                    'decision_ref'   => 'MCE-APPT-2026-001',
                    'notes'          => 'Penunjukan Director AI sebagai koordinator operasional utama AI Workforce.',
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }
        }

        // 8. Seed Initial Owner Direction
        $ownerDir = $this->db->table('owner_directions')->where('direction_code', 'DIR-OWNER-2026-001')->get()->getRow();
        if (!$ownerDir) {
            $this->db->table('owner_directions')->insert([
                'direction_code'          => 'DIR-OWNER-2026-001',
                'direction_type'          => 'strategic_instruction',
                'subject'                 => 'Akselerasi Operasional MCE AI Office & Transformasi Digital',
                'description'             => 'Dewan Direksi (Continuous Executive Board) diinstruksikan untuk memimpin perencanaan operasional internal, penyiapan layanan TIK, serta penguatan sinergi human-AI dengan tetap berpegang pada 7 Core Values MCE.',
                'target_body_id'          => $bodyMap['GOV-BOARD'] ?? null,
                'target_unit_id'          => $rootId,
                'issued_by_id'            => 1,
                'issued_by_name'          => 'Human Owner',
                'requires_acknowledgment' => 1,
                'acknowledged_at'         => $now,
                'acknowledged_by'         => 'Director AI',
                'due_date'                => date('Y-12-31'),
                'status'                  => 'acknowledged',
                'created_at'              => $now,
                'updated_at'              => $now,
            ]);
            $dirId = $this->db->insertID();
        } else {
            $dirId = $ownerDir->id;
        }

        // 9. Seed Initial Executive Decision
        $execDec = $this->db->table('executive_decisions')->where('decision_code', 'DEC-BOARD-2026-001')->get()->getRow();
        if (!$execDec && isset($bodyMap['GOV-BOARD'])) {
            $this->db->table('executive_decisions')->insert([
                'decision_code'      => 'DEC-BOARD-2026-001',
                'governance_body_id' => $bodyMap['GOV-BOARD'],
                'direction_id'       => $dirId,
                'title'              => 'Ratifikasi Rencana Kerja Sprint dan Tata Kelola AI Workforce MCE 2026',
                'summary'            => 'Rapat Direksi menetapkan jadwal kerja shift 5/2, penegakan kewajiban Wallbook, dan operasionalisasi 6 divisi awal di bawah kerangka tata kelola bertingkat (Level A/B/C).',
                'decision_category'  => 'operational',
                'decision_level'     => 'B',
                'decided_by'         => 'Continuous Executive Board (Rapat Direksi)',
                'effective_date'     => date('Y-m-d'),
                'status'             => 'ratified',
                'notes'              => 'Disepakati secara konsensus dewan.',
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);
        }
    }
}
