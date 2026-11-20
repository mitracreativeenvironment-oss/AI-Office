<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ProjectManagementSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $db = $this->db;

        // Fetch User reference
        $userRow = $db->table('users')->orderBy('id', 'ASC')->get(1)->getRowArray();
        $userId = $userRow ? (int)$userRow['id'] : 1;

        // Fetch Agent references
        $agents = [];
        $agentRows = $db->table('ai_agent_registry')->orderBy('id', 'ASC')->get()->getResultArray();
        foreach ($agentRows as $a) {
            $agents[$a['agent_code']] = (int)$a['id'];
        }
        $agentDirId   = $agents['AGT-DIR-001'] ?? ($agents['AGENT-MCE-DIR-001'] ?? (isset($agentRows[0]) ? (int)$agentRows[0]['id'] : null));
        $agentTechId  = $agents['AGT-TECH-001'] ?? ($agents['AGENT-MCE-TECH-001'] ?? (isset($agentRows[1]) ? (int)$agentRows[1]['id'] : $agentDirId));
        $agentOpsId   = $agents['AGT-OPS-001'] ?? ($agents['AGENT-MCE-OPS-001'] ?? (isset($agentRows[2]) ? (int)$agentRows[2]['id'] : $agentDirId));
        $agentDevId   = $agents['AGT-DEV-001'] ?? ($agents['AGENT-MCE-DEV-001'] ?? $agentTechId);
        $agentQaId    = $agents['AGT-QA-001'] ?? ($agents['AGENT-MCE-QA-001'] ?? $agentOpsId);
        $agentCreatId = $agents['AGT-CREAT-001'] ?? ($agents['AGENT-MCE-CREAT-001'] ?? $agentDirId);

        // Fetch Units
        $techUnit = $db->table('organization_units')->where('code', 'DIV-TECH')->get()->getRowArray();
        $techUnitId = $techUnit ? (int)$techUnit['id'] : null;
        $opsUnit = $db->table('organization_units')->where('code', 'DIV-OPS')->get()->getRowArray();
        $opsUnitId = $opsUnit ? (int)$opsUnit['id'] : null;

        // Clean up project management tables in reverse dependency order
        $tables = [
            'project_audit_events',
            'project_status_history',
            'project_resource_allocations',
            'project_change_requests',
            'project_issues',
            'project_risks',
            'project_deliverables',
            'project_milestones',
            'project_scopes',
            'project_briefs',
            'project_team_members',
            'project_templates',
            'projects',
            'project_client_contacts',
            'project_clients',
        ];

        foreach ($tables as $t) {
            if ($db->tableExists($t)) {
                $db->table($t)->emptyTable();
            }
        }

        // 1. Seed Clients
        $clientsData = [
            [
                'client_code'  => 'CLI-CORP-001',
                'company_name' => 'PT Digital Nusantara Mandiri',
                'industry'     => 'Financial Technology',
                'status'       => 'active',
                'address'      => 'Menara Nusantara Lt. 24, SCBD, Jakarta Selatan',
                'website'      => 'https://dnm-corp.co.id',
                'notes'        => 'Klien enterprise strategis sektor fintech. Membutuhkan portal AI Knowledge Management dan audit compliance ketat.',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'client_code'  => 'CLI-GOV-001',
                'company_name' => 'Dinas Komunikasi dan Informatika Provinsi',
                'industry'     => 'Government & Public Sector',
                'status'       => 'prospect',
                'address'      => 'Kompleks Gedung Bersama Sektor Pemerintahan, Blok C',
                'website'      => 'https://diskominfo.prov.go.id',
                'notes'        => 'Inisiatif platform integrasi layanan kota cerdas (Smart City) untuk warga provinsi.',
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
        ];
        $db->table('project_clients')->insertBatch($clientsData);
        $clientDnm = $db->table('project_clients')->where('client_code', 'CLI-CORP-001')->get()->getRowArray();
        $clientGov = $db->table('project_clients')->where('client_code', 'CLI-GOV-001')->get()->getRowArray();
        $clientDnmId = $clientDnm ? (int)$clientDnm['id'] : null;
        $clientGovId = $clientGov ? (int)$clientGov['id'] : null;

        // 2. Seed Client Contacts
        $contactsData = [
            [
                'client_id'  => $clientDnmId,
                'full_name'  => 'Ir. Budi Pratama, M.Sc.',
                'job_title'  => 'Chief Technology Officer',
                'email'      => 'budi.pratama@dnm-corp.co.id',
                'phone'      => '+62 811-2345-6789',
                'is_primary' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'client_id'  => $clientDnmId,
                'full_name'  => 'Siti Rahayu, S.Kom.',
                'job_title'  => 'VP of Engineering',
                'email'      => 'siti.rahayu@dnm-corp.co.id',
                'phone'      => '+62 812-3456-7890',
                'is_primary' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'client_id'  => $clientGovId,
                'full_name'  => 'Dr. H. Ahmad Fauzi, M.T.',
                'job_title'  => 'Kepala Bidang Infrastruktur & Aplikasi',
                'email'      => 'ahmad.fauzi@diskominfo.prov.go.id',
                'phone'      => '+62 813-9876-5432',
                'is_primary' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        $db->table('project_client_contacts')->insertBatch($contactsData);

        // Helper for UUID
        $genUuid = function() {
            return sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
        };

        // 3. Seed Projects (2 Customer, 2 Internal)
        $projectsData = [
            [
                'project_uuid'            => $genUuid(),
                'project_code'            => 'PRJ-CUST-2026-001',
                'name'                    => 'Enterprise AI-Powered Knowledge Portal',
                'project_type'            => 'customer_project',
                'category'                => 'ai_automation',
                'description'             => 'Implementasi portal manajemen pengetahuan enterprise berbasis AI untuk PT Digital Nusantara Mandiri.',
                'objectives'              => '1. Waktu pencarian dokumen turun 70%. 2. Akurasi RAG >= 92%. 3. Integrasi Single Sign-On (SSO) selesai tepat waktu.',
                'client_id'               => $clientDnmId,
                'unit_id'                 => $techUnitId,
                'project_owner_user_id'   => $userId,
                'project_manager_user_id' => null,
                'project_manager_agent_id'=> $agentTechId,
                'delivery_lead_agent_id'  => $agentDevId,
                'sponsoring_authority'    => 'human_owner',
                'lifecycle_stage'         => 'execution',
                'status'                  => 'active',
                'priority'                => 'high',
                'confidentiality_level'   => 'confidential',
                'start_date'              => '2026-11-01',
                'target_completion_date'  => '2026-12-31',
                'actual_completion_date'  => null,
                'budget_currency'         => 'IDR',
                'proposed_budget'         => 50000000.00,
                'approved_budget_limit'   => 45000000.00,
                'actual_cost_incurred'    => 18500000.00,
                'progress_percent'        => 45,
                'health_status'           => 'healthy',
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
            [
                'project_uuid'            => $genUuid(),
                'project_code'            => 'PRJ-CUST-2026-002',
                'name'                    => 'Smart City Public Service Integration Platform',
                'project_type'            => 'customer_project',
                'category'                => 'web_app',
                'description'             => 'Penyusunan proposal dan arsitektur platform integrasi layanan kota cerdas Diskominfo Provinsi.',
                'objectives'              => '1. Lolos evaluasi administrasi tender. 2. Arsitektur modular disetujui tim teknis Pemprov.',
                'client_id'               => $clientGovId,
                'unit_id'                 => $techUnitId,
                'project_owner_user_id'   => $userId,
                'project_manager_user_id' => null,
                'project_manager_agent_id'=> $agentTechId,
                'delivery_lead_agent_id'  => $agentDevId,
                'sponsoring_authority'    => 'human_owner',
                'lifecycle_stage'         => 'proposal',
                'status'                  => 'active',
                'priority'                => 'medium',
                'confidentiality_level'   => 'internal',
                'start_date'              => '2026-12-15',
                'target_completion_date'  => '2027-03-31',
                'actual_completion_date'  => null,
                'budget_currency'         => 'IDR',
                'proposed_budget'         => 85000000.00,
                'approved_budget_limit'   => 0.00,
                'actual_cost_incurred'    => 0.00,
                'progress_percent'        => 10,
                'health_status'           => 'healthy',
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
            [
                'project_uuid'            => $genUuid(),
                'project_code'            => 'PRJ-INT-2026-001',
                'name'                    => 'MCE AI Office Virtual Headquarters Phase 2',
                'project_type'            => 'internal_initiative',
                'category'                => 'internal_platform',
                'description'             => 'Pengembangan kapabilitas 3D Three.js VHQ, room avatars, audio spatial, dan visualisasi status Agent real-time.',
                'objectives'              => '1. Three.js render stabil 60fps. 2. Integrasi socket event < 50ms latency. 3. Zero console visual regressions.',
                'client_id'               => null,
                'unit_id'                 => $techUnitId,
                'project_owner_user_id'   => $userId,
                'project_manager_user_id' => null,
                'project_manager_agent_id'=> $agentDirId,
                'delivery_lead_agent_id'  => $agentTechId,
                'sponsoring_authority'    => 'continuous_executive_board',
                'lifecycle_stage'         => 'execution',
                'status'                  => 'active',
                'priority'                => 'high',
                'confidentiality_level'   => 'internal',
                'start_date'              => '2026-11-05',
                'target_completion_date'  => '2026-12-10',
                'actual_completion_date'  => null,
                'budget_currency'         => 'IDR',
                'proposed_budget'         => 4500000.00,
                'approved_budget_limit'   => 4500000.00,
                'actual_cost_incurred'    => 2700000.00,
                'progress_percent'        => 60,
                'health_status'           => 'healthy',
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
            [
                'project_uuid'            => $genUuid(),
                'project_code'            => 'PRJ-INT-2026-002',
                'name'                    => 'AI Workforce Automated Skills Benchmark',
                'project_type'            => 'internal_initiative',
                'category'                => 'rnd',
                'description'             => 'R&D kerangka kerja benchmark otomatis evaluasi kompetensi, token economy efficiency, dan error recovery AI workforce.',
                'objectives'              => '1. Dataset uji 50 skenario tugas operasional. 2. Automasi evaluasi respons 9Router.',
                'client_id'               => null,
                'unit_id'                 => $opsUnitId,
                'project_owner_user_id'   => $userId,
                'project_manager_user_id' => null,
                'project_manager_agent_id'=> $agentOpsId,
                'delivery_lead_agent_id'  => $agentQaId,
                'sponsoring_authority'    => 'continuous_executive_board',
                'lifecycle_stage'         => 'planning',
                'status'                  => 'active',
                'priority'                => 'medium',
                'confidentiality_level'   => 'internal',
                'start_date'              => '2026-11-18',
                'target_completion_date'  => '2026-12-20',
                'actual_completion_date'  => null,
                'budget_currency'         => 'IDR',
                'proposed_budget'         => 2000000.00,
                'approved_budget_limit'   => 2000000.00,
                'actual_cost_incurred'    => 400000.00,
                'progress_percent'        => 20,
                'health_status'           => 'healthy',
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
        ];
        $db->table('projects')->insertBatch($projectsData);

        // Map project ids
        $pRows = $db->table('projects')->get()->getResultArray();
        $projects = [];
        foreach ($pRows as $p) {
            $projects[$p['project_code']] = (int)$p['id'];
        }
        $prjCust1 = $projects['PRJ-CUST-2026-001'];
        $prjCust2 = $projects['PRJ-CUST-2026-002'];
        $prjInt1  = $projects['PRJ-INT-2026-001'];
        $prjInt2  = $projects['PRJ-INT-2026-002'];

        // 4. Seed Project Team Members
        $teamData = [
            // PRJ-CUST-2026-001
            [
                'project_id'            => $prjCust1,
                'member_type'           => 'human',
                'user_id'               => $userId,
                'agent_id'              => null,
                'project_role'          => 'project_sponsor',
                'allocation_percentage' => 15,
                'status'                => 'active',
                'joined_at'             => '2026-11-01 09:00:00',
                'released_at'           => null,
            ],
            [
                'project_id'            => $prjCust1,
                'member_type'           => 'agent',
                'user_id'               => null,
                'agent_id'              => $agentTechId,
                'project_role'          => 'project_manager',
                'allocation_percentage' => 50,
                'status'                => 'active',
                'joined_at'             => '2026-11-01 09:00:00',
                'released_at'           => null,
            ],
            [
                'project_id'            => $prjCust1,
                'member_type'           => 'agent',
                'user_id'               => null,
                'agent_id'              => $agentDevId,
                'project_role'          => 'delivery_lead',
                'allocation_percentage' => 100,
                'status'                => 'active',
                'joined_at'             => '2026-11-01 09:00:00',
                'released_at'           => null,
            ],
            [
                'project_id'            => $prjCust1,
                'member_type'           => 'agent',
                'user_id'               => null,
                'agent_id'              => $agentQaId,
                'project_role'          => 'reviewer',
                'allocation_percentage' => 50,
                'status'                => 'active',
                'joined_at'             => '2026-11-15 09:00:00',
                'released_at'           => null,
            ],

            // PRJ-INT-2026-001 (Internal Initiative)
            [
                'project_id'            => $prjInt1,
                'member_type'           => 'agent',
                'user_id'               => null,
                'agent_id'              => $agentDirId,
                'project_role'          => 'project_manager',
                'allocation_percentage' => 40,
                'status'                => 'active',
                'joined_at'             => '2026-11-05 09:00:00',
                'released_at'           => null,
            ],
            [
                'project_id'            => $prjInt1,
                'member_type'           => 'agent',
                'user_id'               => null,
                'agent_id'              => $agentTechId,
                'project_role'          => 'delivery_lead',
                'allocation_percentage' => 50,
                'status'                => 'active',
                'joined_at'             => '2026-11-05 09:00:00',
                'released_at'           => null,
            ],
            [
                'project_id'            => $prjInt1,
                'member_type'           => 'agent',
                'user_id'               => null,
                'agent_id'              => $agentCreatId,
                'project_role'          => 'contributor',
                'allocation_percentage' => 40,
                'status'                => 'active',
                'joined_at'             => '2026-11-05 09:00:00',
                'released_at'           => null,
            ],
        ];
        $db->table('project_team_members')->insertBatch($teamData);

        // 5. Seed Project Briefs
        $briefsData = [
            [
                'project_id'          => $prjCust1,
                'background'          => 'PT Digital Nusantara Mandiri membutuhkan portal manajemen pengetahuan perbankan terpadu dengan semantic search berbasis RAG.',
                'business_objectives' => 'Meningkatkan kecepatan temu kembali informasi regulasi 70% dan menurunkan kesalahan compliance.',
                'expected_outcomes'   => 'Portal AI Knowledge terintegrasi siap pakai dengan audit logging terverifikasi.',
                'constraints'         => 'Latensi query < 1.5 detik, kepatuhan ISO 27001.',
                'assumptions'         => 'Format dokumen sumber berupa PDF dan DOCX bersih.',
                'dependencies'        => 'Infrastruktur GPU Milvus disediakan tepat waktu.',
                'success_criteria'    => 'Search relevancy nDCG@10 >= 0.85, UAT sign-off 100%.',
                'scope_boundaries'    => 'Hanya dokumen internal DNM, tidak mengindeks data transaksi nasabah.',
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'project_id'          => $prjInt1,
                'background'          => 'Virtual Headquarters MCE perlu diperluas ke fase 2 dengan integrasi 3D Three.js dan telemetry Agent real-time.',
                'business_objectives' => 'Menyediakan visibilitas spasial interaktif bagi dewan eksekutif dan Human Owner.',
                'expected_outcomes'   => 'VHQ 3D canvas performa tinggi 60 FPS dengan binding status kerja Agent.',
                'constraints'         => 'Kompatibel dengan semua browser WebGL tanpa plugin eksternal.',
                'assumptions'         => 'Modul Three.js terisolasi dan modular.',
                'dependencies'        => 'Backend API Prompt 16-26 terhubung.',
                'success_criteria'    => 'Frame rate stabil >= 60 FPS dan latensi update socket < 50ms.',
                'scope_boundaries'    => 'Visualisasi ruangan kantor virtual dan status Agent.',
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
        ];
        $db->table('project_briefs')->insertBatch($briefsData);

        // 6. Seed Project Scopes
        $scopesData = [
            [
                'project_id'          => $prjCust1,
                'version_number'      => '1.0',
                'scope_summary'       => 'Scope baseline pengiriman Enterprise AI Knowledge Portal DNM v1.0.',
                'in_scope_items_json' => json_encode([
                    'Pipeline ingestion dokumen PDF/DOCX/XLSX',
                    'Vector database Milvus & hybrid BM25 search',
                    'Web UI responsif dengan tema korporat',
                    'Role-based access control per divisi klien',
                ]),
                'out_of_scope_json'   => json_encode([
                    'Pengadaan server hardware on-premise',
                    'Training LLM foundational baru dari scratch',
                ]),
                'is_baseline'         => 1,
                'approved_by_user_id' => $userId,
                'approved_at'         => '2026-11-02 16:30:00',
                'created_at'          => $now,
            ],
            [
                'project_id'          => $prjInt1,
                'version_number'      => '1.0',
                'scope_summary'       => 'Scope baseline inisiatif VHQ Phase 2.',
                'in_scope_items_json' => json_encode([
                    'Optimalisasi Three.js spatial canvas',
                    'Binding status kerja live agent billboard',
                    'Room navigation orbit controls',
                ]),
                'out_of_scope_json'   => json_encode([
                    'Full VR / AR headset support',
                ]),
                'is_baseline'         => 1,
                'approved_by_user_id' => $userId,
                'approved_at'         => '2026-11-05 11:00:00',
                'created_at'          => $now,
            ],
        ];
        $db->table('project_scopes')->insertBatch($scopesData);

        // 7. Seed Milestones
        $milestonesData = [
            // PRJ-CUST-2026-001
            [
                'project_id'             => $prjCust1,
                'milestone_code'         => 'MS-DNM-001',
                'title'                  => 'M1: Architecture Design & Vector DB Pipeline',
                'description'            => 'Desain arsitektur teknis, skema vector DB, dan pipeline ingestion dokumen.',
                'target_date'            => '2026-11-15',
                'actual_completion_date' => '2026-11-14',
                'is_critical_path'       => 1,
                'status'                 => 'achieved',
                'order_index'            => 1,
                'created_at'             => $now,
                'updated_at'             => $now,
            ],
            [
                'project_id'             => $prjCust1,
                'milestone_code'         => 'MS-DNM-002',
                'title'                  => 'M2: RAG Core Engine & Web Portal MVP',
                'description'            => 'Implementasi RAG inference pipeline dan user interface knowledge search.',
                'target_date'            => '2026-12-05',
                'actual_completion_date' => null,
                'is_critical_path'       => 1,
                'status'                 => 'in_progress',
                'order_index'            => 2,
                'created_at'             => $now,
                'updated_at'             => $now,
            ],
            [
                'project_id'             => $prjCust1,
                'milestone_code'         => 'MS-DNM-003',
                'title'                  => 'M3: Security Audit, UAT & Production Handover',
                'description'            => 'Uji keamanan, penetrasi, UAT klien, dan serah terima operasional akhir.',
                'target_date'            => '2026-12-31',
                'actual_completion_date' => null,
                'is_critical_path'       => 1,
                'status'                 => 'pending',
                'order_index'            => 3,
                'created_at'             => $now,
                'updated_at'             => $now,
            ],

            // PRJ-INT-2026-001
            [
                'project_id'             => $prjInt1,
                'milestone_code'         => 'MS-VHQ-001',
                'title'                  => 'M1: Three.js VHQ Spatial Engine Optimization',
                'description'            => 'Penyempurnaan viewport rendering, shader material, dan room bounding geometry.',
                'target_date'            => '2026-11-18',
                'actual_completion_date' => '2026-11-17',
                'is_critical_path'       => 1,
                'status'                 => 'achieved',
                'order_index'            => 1,
                'created_at'             => $now,
                'updated_at'             => $now,
            ],
            [
                'project_id'             => $prjInt1,
                'milestone_code'         => 'MS-VHQ-002',
                'title'                  => 'M2: Real-Time Workforce State Binding',
                'description'            => 'Koneksi state mesin AI Agent ke visual avatar dan status indicator billboard.',
                'target_date'            => '2026-12-01',
                'actual_completion_date' => null,
                'is_critical_path'       => 1,
                'status'                 => 'in_progress',
                'order_index'            => 2,
                'created_at'             => $now,
                'updated_at'             => $now,
            ],
            [
                'project_id'             => $prjInt1,
                'milestone_code'         => 'MS-VHQ-003',
                'title'                  => 'M3: Final Verification & Wallbook Archive',
                'description'            => 'Verifikasi menyeluruh, deployment internal, dan pengarsipan dokumentasi teknis.',
                'target_date'            => '2026-12-10',
                'actual_completion_date' => null,
                'is_critical_path'       => 0,
                'status'                 => 'pending',
                'order_index'            => 3,
                'created_at'             => $now,
                'updated_at'             => $now,
            ],
        ];
        $db->table('project_milestones')->insertBatch($milestonesData);

        // Fetch milestone IDs
        $msRows = $db->table('project_milestones')->get()->getResultArray();
        $milestones = [];
        foreach ($msRows as $m) {
            $milestones[$m['milestone_code']] = (int)$m['id'];
        }

        // 8. Seed Deliverables
        $deliverablesData = [
            [
                'project_id'              => $prjCust1,
                'milestone_id'            => $milestones['MS-DNM-001'] ?? null,
                'deliverable_code'        => 'DEL-DNM-001',
                'title'                   => 'System Architecture & Vector DB Specification Document',
                'description'             => 'Dokumen arsitektur lengkap termasuk diagram komponen, integrasi Milvus, dan RAG scoring model.',
                'responsible_agent_id'    => $agentTechId,
                'responsible_user_id'     => null,
                'due_date'                => '2026-11-15',
                'acceptance_criteria'     => 'Disetujui oleh CTO klien dan mencakup spesifikasi SLA.',
                'supporting_evidence_url' => '/docs/projects/PRJ-CUST-2026-001/architecture-spec-v1.pdf',
                'review_status'           => 'accepted',
                'reviewed_by_user_id'     => $userId,
                'reviewed_at'             => '2026-11-13 15:00:00',
                'client_accepted'         => 1,
                'client_accepted_at'      => '2026-11-14 11:30:00',
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
            [
                'project_id'              => $prjCust1,
                'milestone_id'            => $milestones['MS-DNM-002'] ?? null,
                'deliverable_code'        => 'DEL-DNM-002',
                'title'                   => 'RAG Core Ingestion Engine & Retrieval API',
                'description'             => 'Layanan API backend untuk pemrosesan dokumen dan semantic vector retrieval.',
                'responsible_agent_id'    => $agentDevId,
                'responsible_user_id'     => null,
                'due_date'                => '2026-12-05',
                'acceptance_criteria'     => 'Endpoint retrieval respons < 500ms untuk 10 top results.',
                'supporting_evidence_url' => '/api/v1/projects/PRJ-CUST-2026-001/deliverables/rag-engine',
                'review_status'           => 'submitted',
                'reviewed_by_user_id'     => null,
                'reviewed_at'             => null,
                'client_accepted'         => 0,
                'client_accepted_at'      => null,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
            [
                'project_id'              => $prjInt1,
                'milestone_id'            => $milestones['MS-VHQ-001'] ?? null,
                'deliverable_code'        => 'DEL-VHQ-001',
                'title'                   => 'Three.js VHQ Spatial Engine Optimization Bundle',
                'description'             => 'Kumpulan modul Three.js canvas, asset loader, dan event controller teroptimasi.',
                'responsible_agent_id'    => $agentTechId,
                'responsible_user_id'     => null,
                'due_date'                => '2026-11-18',
                'acceptance_criteria'     => 'Render performance benchmark lulus 60 FPS pada dev workstation.',
                'supporting_evidence_url' => '/frontend/src/components/ThreeCanvas.tsx',
                'review_status'           => 'accepted',
                'reviewed_by_user_id'     => $userId,
                'reviewed_at'             => '2026-11-17 16:30:00',
                'client_accepted'         => 0,
                'client_accepted_at'      => null,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
        ];
        $db->table('project_deliverables')->insertBatch($deliverablesData);

        // 9. Seed Risks
        $risksData = [
            [
                'project_id'       => $prjCust1,
                'risk_code'        => 'RSK-DNM-001',
                'title'            => 'Keterlambatan penyediaan server GPU on-premise oleh klien',
                'description'      => 'Jika hardware GPU tiba melewati timeline sprint 2, staging validation terancam molor.',
                'probability'      => 'medium',
                'impact'           => 'high',
                'risk_level'       => 'high',
                'mitigation_plan'  => 'Menyediakan staging environment sementara di sandbox MCE hingga server on-premise siap.',
                'contingency_plan' => 'Eskalasi langsung ke CTO DNM melalui meeting koordinasi mingguan.',
                'owner_user_id'    => $userId,
                'owner_agent_id'   => $agentTechId,
                'status'           => 'identified',
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'project_id'       => $prjInt1,
                'risk_code'        => 'RSK-VHQ-001',
                'title'            => 'Spike konsumsi GPU pada laptop low-end pengguna web',
                'description'      => 'Kapasitas visual Three.js dapat menyebabkan stutter pada GPU terintegrasi.',
                'probability'      => 'low',
                'impact'           => 'medium',
                'risk_level'       => 'medium',
                'mitigation_plan'  => 'Implementasi mode adaptive resolution dan fallback canvas 2D otomatis.',
                'contingency_plan' => 'Tombol manual toggle low-performance mode pada UI header.',
                'owner_user_id'    => null,
                'owner_agent_id'   => $agentTechId,
                'status'           => 'mitigated',
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
        ];
        $db->table('project_risks')->insertBatch($risksData);

        // 10. Seed Issues
        $issuesData = [
            [
                'project_id'           => $prjCust1,
                'issue_code'           => 'ISS-DNM-001',
                'title'                => 'Inkonsistensi format tabel pada dokumen PDF versi lama',
                'description'          => 'Modul OCR standard gagal mengekstrak tabel dengan cell spanning kompleks pada dokumen SOP tahun 2018.',
                'severity'             => 'moderate',
                'priority'             => 'medium',
                'resolution_plan'      => 'Integrasi layout analysis model khusus perbankan untuk parse tabel multi-baris.',
                'responsible_user_id'  => null,
                'responsible_agent_id' => $agentDevId,
                'status'               => 'investigating',
                'resolved_at'          => null,
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
        ];
        $db->table('project_issues')->insertBatch($issuesData);

        // 11. Seed Change Requests
        $crData = [
            [
                'project_id'             => $prjCust1,
                'change_code'            => 'CR-DNM-001',
                'requester_type'         => 'client',
                'requester_user_id'      => null,
                'requester_agent_id'     => null,
                'title'                  => 'Penambahan Dukungan Format Microsoft PowerPoint (.pptx)',
                'description'            => 'Permintaan divisi pelatihan klien agar slide materi presentasi direksi dapat langsung ditanyakan melalui AI portal.',
                'business_justification' => 'Memperluas kegunaan sistem knowledge portal ke materi briefing bulanan dewan komisaris.',
                'scope_impact'           => 'Menambah extractor PPTX pada pipeline ingestion dan viewer preview khusus slide.',
                'schedule_impact_days'   => 4,
                'budget_impact_amount'   => 3500000.00,
                'risk_assessment'        => 'Risiko rendah, library python python-pptx sudah matang.',
                'approval_level'         => 'A',
                'status'                 => 'approved',
                'approved_by_user_id'    => $userId,
                'approved_at'            => '2026-11-15 16:00:00',
                'created_at'             => $now,
                'updated_at'             => $now,
            ],
        ];
        $db->table('project_change_requests')->insertBatch($crData);

        // 12. Seed Resource Allocations
        $resData = [
            [
                'project_id'               => $prjCust1,
                'resource_type'            => 'ai_agent_runtime',
                'resource_name'            => 'AI Lead Tech & Dev Compute',
                'allocated_quantity'       => 160.00,
                'utilized_quantity'        => 72.00,
                'unit_cost_idr'            => 125000.00,
                'total_allocated_cost_idr' => 20000000.00,
                'notes'                    => 'Alokasi komputasi dan runtime agent development.',
                'created_at'               => $now,
            ],
            [
                'project_id'               => $prjCust1,
                'resource_type'            => 'compute_infrastructure',
                'resource_name'            => 'Infrastructure & Cloud Sandbox',
                'allocated_quantity'       => 1.00,
                'utilized_quantity'        => 0.45,
                'unit_cost_idr'            => 15000000.00,
                'total_allocated_cost_idr' => 15000000.00,
                'notes'                    => 'Alokasi cloud staging environment dan 9Router gateway balance.',
                'created_at'               => $now,
            ],
            [
                'project_id'               => $prjInt1,
                'resource_type'            => 'ai_agent_runtime',
                'resource_name'            => 'Core AI Engineering Hours',
                'allocated_quantity'       => 45.00,
                'utilized_quantity'        => 27.00,
                'unit_cost_idr'            => 100000.00,
                'total_allocated_cost_idr' => 4500000.00,
                'notes'                    => 'DoA Level B resource pool.',
                'created_at'               => $now,
            ],
        ];
        $db->table('project_resource_allocations')->insertBatch($resData);

        // 13. Seed Status History
        $historyData = [
            [
                'project_id'          => $prjCust1,
                'from_stage'          => 'contracted',
                'to_stage'            => 'planning',
                'from_status'         => 'draft',
                'to_status'           => 'active',
                'changed_by_user_id'  => $userId,
                'changed_by_agent_id' => null,
                'reason'              => 'Kontrak ditandatangani dan formal approval diberikan Human Owner.',
                'notes'               => 'Kickoff resmi PRJ-CUST-2026-001',
                'created_at'          => '2026-11-01 09:00:00',
            ],
            [
                'project_id'          => $prjCust1,
                'from_stage'          => 'planning',
                'to_stage'            => 'execution',
                'from_status'         => 'active',
                'to_status'           => 'active',
                'changed_by_user_id'  => $userId,
                'changed_by_agent_id' => null,
                'reason'              => 'Scope baselined dan sprint 1 resmi dimulai.',
                'notes'               => 'Memasuki tahap eksekusi teknis',
                'created_at'          => '2026-11-03 10:00:00',
            ],
            [
                'project_id'          => $prjInt1,
                'from_stage'          => 'authorized',
                'to_stage'            => 'planning',
                'from_status'         => 'draft',
                'to_status'           => 'active',
                'changed_by_user_id'  => null,
                'changed_by_agent_id' => $agentDirId,
                'reason'              => 'Inisiatif internal diotorisasi Continuous Executive Board.',
                'notes'               => 'DoA Level B valid',
                'created_at'          => '2026-11-04 11:00:00',
            ],
            [
                'project_id'          => $prjInt1,
                'from_stage'          => 'planning',
                'to_stage'            => 'execution',
                'from_status'         => 'active',
                'to_status'           => 'active',
                'changed_by_user_id'  => null,
                'changed_by_agent_id' => $agentDirId,
                'reason'              => 'Tim teknis memulai pekerjaan visualisasi Three.js.',
                'notes'               => 'Sprint 1 VHQ2 aktif',
                'created_at'          => '2026-11-05 08:30:00',
            ],
        ];
        $db->table('project_status_history')->insertBatch($historyData);

        // 14. Seed Project Templates
        $templatesData = [
            [
                'template_code'             => 'TPL-CUST-AGILE',
                'name'                      => 'Customer Agile Delivery Lifecycle',
                'project_type'              => 'customer_project',
                'category'                  => 'ai_automation',
                'description'               => 'Standar template lifecycle pengiriman proyek pelanggan eksternal MCE dari kualifikasi hingga garansi.',
                'default_milestones_json'   => json_encode([
                    ['code' => 'M1', 'title' => 'Architecture & Inception', 'weight' => 20],
                    ['code' => 'M2', 'title' => 'Core Delivery & Integration', 'weight' => 50],
                    ['code' => 'M3', 'title' => 'Client Acceptance & Handover', 'weight' => 30],
                ]),
                'default_deliverables_json' => json_encode([
                    ['title' => 'Architecture Specification Document', 'type' => 'document'],
                    ['title' => 'Production Software Release', 'type' => 'software_artifact'],
                    ['title' => 'Client Acceptance & Handover Sign-off', 'type' => 'acceptance_pack'],
                ]),
                'default_roles_json'        => json_encode([
                    'project_sponsor', 'project_manager', 'delivery_lead', 'contributor', 'reviewer'
                ]),
                'is_active'                 => 1,
                'created_at'                => $now,
            ],
            [
                'template_code'             => 'TPL-INT-FAST',
                'name'                      => 'Internal Initiative Fast-Track',
                'project_type'              => 'internal_initiative',
                'category'                  => 'internal_platform',
                'description'               => 'Template inisiatif internal MCE yang dieksekusi mandiri di bawah kewenangan Continuous Executive Board (DoA Level B/C).',
                'default_milestones_json'   => json_encode([
                    ['code' => 'M1', 'title' => 'Design & Proof of Concept', 'weight' => 30],
                    ['code' => 'M2', 'title' => 'Implementation & Testing', 'weight' => 50],
                    ['code' => 'M3', 'title' => 'Review & Operational Deployment', 'weight' => 20],
                ]),
                'default_deliverables_json' => json_encode([
                    ['title' => 'Technical Design RFC', 'type' => 'document'],
                    ['title' => 'Feature Implementation Module', 'type' => 'software_artifact'],
                    ['title' => 'Wallbook Knowledge Documentation', 'type' => 'document'],
                ]),
                'default_roles_json'        => json_encode([
                    'project_manager', 'delivery_lead', 'contributor', 'specialist'
                ]),
                'is_active'                 => 1,
                'created_at'                => $now,
            ],
        ];
        $db->table('project_templates')->insertBatch($templatesData);

        // 15. Link existing workforce_tasks to projects & milestones
        $tasks = $db->table('workforce_tasks')->get()->getResultArray();
        if (!empty($tasks)) {
            $m2Id = $milestones['MS-DNM-002'] ?? null;
            if (isset($tasks[0])) {
                $db->table('workforce_tasks')->where('id', $tasks[0]['id'])->update([
                    'project_id'   => $prjCust1,
                    'milestone_id' => $m2Id,
                ]);
            }
            if (isset($tasks[1])) {
                $db->table('workforce_tasks')->where('id', $tasks[1]['id'])->update([
                    'project_id'   => $prjCust1,
                    'milestone_id' => $m2Id,
                ]);
            }
            $v2Id = $milestones['MS-VHQ-002'] ?? null;
            if (isset($tasks[2])) {
                $db->table('workforce_tasks')->where('id', $tasks[2]['id'])->update([
                    'project_id'   => $prjInt1,
                    'milestone_id' => $v2Id,
                ]);
            }
        }
    }
}
