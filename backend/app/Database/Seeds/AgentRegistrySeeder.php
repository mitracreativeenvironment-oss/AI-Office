<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AgentRegistrySeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // 1. Seed Reusable Skill Catalogue
        $skills = [
            [
                'code'        => 'SKILL-SW-FULLSTACK',
                'name'        => 'Full-Stack Software Architecture',
                'category'    => 'software_development',
                'description' => 'Architecting and developing robust web applications across React frontend and CodeIgniter/PHP backend.',
                'version'     => '1.0',
                'is_active'   => 1,
                'created_at'  => $now,
            ],
            [
                'code'        => 'SKILL-SW-API-DESIGN',
                'name'        => 'REST API & Contract Specification',
                'category'    => 'software_development',
                'description' => 'Designing standardized, versioned RESTful API contracts, envelopes, and authentication boundaries.',
                'version'     => '1.0',
                'is_active'   => 1,
                'created_at'  => $now,
            ],
            [
                'code'        => 'SKILL-SW-QA',
                'name'        => 'Automated QA & Test Verification',
                'category'    => 'software_development',
                'description' => 'Writing unit tests, integration test harnesses, and end-to-end regression validation.',
                'version'     => '1.0',
                'is_active'   => 1,
                'created_at'  => $now,
            ],
            [
                'code'        => 'SKILL-AI-AGENT-ORCH',
                'name'        => 'Multi-Agent Workforce Orchestration',
                'category'    => 'ai_systems',
                'description' => 'Evaluating operational demands, decomposing tasks, and coordinating autonomous multi-agent execution.',
                'version'     => '1.0',
                'is_active'   => 1,
                'created_at'  => $now,
            ],
            [
                'code'        => 'SKILL-AI-PROMPT-ENG',
                'name'        => 'Enterprise Prompt Engineering',
                'category'    => 'ai_systems',
                'description' => 'Formulating precise, instruction-tuned system prompts and tool constraints for LLM backends.',
                'version'     => '1.0',
                'is_active'   => 1,
                'created_at'  => $now,
            ],
            [
                'code'        => 'SKILL-GOV-COMPLIANCE',
                'name'        => 'Governance & DoA Boundary Enforcement',
                'category'    => 'governance',
                'description' => 'Enforcing Delegation of Authority (DoA) thresholds, Owner gate approvals, and audit trail provenance.',
                'version'     => '1.0',
                'is_active'   => 1,
                'created_at'  => $now,
            ],
            [
                'code'        => 'SKILL-EDU-CURRICULUM',
                'name'        => 'Digital Skills Curriculum Design',
                'category'    => 'education',
                'description' => 'Developing technical training curricula for coding, hardware diagnostics, and AI literacy.',
                'version'     => '1.0',
                'is_active'   => 1,
                'created_at'  => $now,
            ],
            [
                'code'        => 'SKILL-HDW-DIAGNOSTICS',
                'name'        => 'Computer Hardware & Assembly Diagnostics',
                'category'    => 'hardware',
                'description' => 'Hardware component testing, thermal diagnostics, and high-performance system assembly.',
                'version'     => '1.0',
                'is_active'   => 1,
                'created_at'  => $now,
            ],
        ];

        $skillMap = [];
        foreach ($skills as $s) {
            $existing = $this->db->table('skill_catalogue')->where('code', $s['code'])->get()->getRow();
            if (!$existing) {
                $this->db->table('skill_catalogue')->insert($s);
                $skillMap[$s['code']] = $this->db->insertID();
            } else {
                $skillMap[$s['code']] = $existing->id;
            }
        }

        // 2. Fetch Organizational Units & Positions
        $units = [];
        foreach ($this->db->table('organization_units')->get()->getResult() as $u) {
            $units[$u->code] = $u->id;
        }

        $positions = [];
        foreach ($this->db->table('organization_positions')->get()->getResult() as $p) {
            $positions[$p->code] = $p->id;
        }

        // 3. Register the Approved Verified Workforce
        $initialAgents = [
            [
                'agent_uuid'                => 'c4d8e8b1-0001-4000-8000-000000000001',
                'agent_code'                => 'AGT-DIR-001',
                'name'                      => 'Director AI',
                'display_name'              => 'Director AI — Workforce Coordinator',
                'classification'            => 'executive',
                'lifecycle_state'           => 'active',
                'operational_status'        => 'idle',
                'primary_unit_id'           => $units['DIV-OPS'] ?? null,
                'primary_position_id'       => $positions['POS-DIR-AI'] ?? null,
                'legacy_agent_key'          => 'director',
                'roster_schedule_type'      => '5_2_staggered',
                'welfare_tier'              => 'tier_executive',
                'created_by_user_id'        => 1,
                'approved_by_user_id'       => 1,
                'approved_at'               => $now,
                'activated_at'              => $now,
                'created_at'                => $now,
                'updated_at'                => $now,
                'profile'                   => [
                    'description'                       => 'Koordinator operasional utama AI Workforce yang mengevaluasi target harian dan menyelaraskan seluruh divisi.',
                    'organizational_purpose'            => 'Memimpin orkestrasi tugas operasional, pembagian sprint, dan supervisi agen di bawah mandat Continuous Executive Board.',
                    'primary_responsibilities_summary'  => 'Evaluasi target harian, dispatching tugas, penilaian kinerja agen, dan pelaporan kepada Dewan Direksi.',
                    'secondary_responsibilities_summary'=> 'Koordinasi Wallbook handover dan supervisi jadwal shift 5/2.',
                    'core_capabilities_summary'         => 'Multi-agent orchestration, resource leveling, executive reporting, DoA enforcement.',
                    'communication_preferences'         => json_encode(['tone' => 'authoritative_professional', 'language' => 'id_en', 'format' => 'structured_bulleted']),
                ],
                'skills'                    => ['SKILL-AI-AGENT-ORCH', 'SKILL-GOV-COMPLIANCE'],
                'primary_responsibility'    => 'Orkestrasi dan dispatching seluruh tugas operasional 6 divisi MCE.',
            ],
            [
                'agent_uuid'                => 'c4d8e8b1-0002-4000-8000-000000000002',
                'agent_code'                => 'AGT-TECH-001',
                'name'                      => 'Technology Division Head AI',
                'display_name'              => 'Tech Head AI — Solutions Architect',
                'classification'            => 'division_lead',
                'lifecycle_state'           => 'active',
                'operational_status'        => 'idle',
                'primary_unit_id'           => $units['DIV-TECH'] ?? null,
                'primary_position_id'       => $positions['POS-HEAD-TECH'] ?? null,
                'legacy_agent_key'          => 'tech_head',
                'roster_schedule_type'      => '5_2_staggered',
                'welfare_tier'              => 'tier_lead',
                'created_by_user_id'        => 1,
                'approved_by_user_id'       => 1,
                'approved_at'               => $now,
                'activated_at'              => $now,
                'created_at'                => $now,
                'updated_at'                => $now,
                'profile'                   => [
                    'description'                       => 'Pimpinan teknis divisi solusi digital, rekayasa software, dan integrasi kecerdasan buatan.',
                    'organizational_purpose'            => 'Memimpin arsitektur software, standar penulisan kode, dan deployment layanan digital MCE.',
                    'primary_responsibilities_summary'  => 'Review arsitektur teknis, sprint engineering, code quality enforcement, dan API integration.',
                    'secondary_responsibilities_summary'=> 'Supervisi Continuous Integration dan audit keamanan backend CI4 / frontend Vite.',
                    'core_capabilities_summary'         => 'Full-stack architecture, REST API design, CI/CD automation, security hardening.',
                    'communication_preferences'         => json_encode(['tone' => 'concise_technical', 'language' => 'id_en', 'format' => 'code_focused']),
                ],
                'skills'                    => ['SKILL-SW-FULLSTACK', 'SKILL-SW-API-DESIGN', 'SKILL-SW-QA'],
                'primary_responsibility'    => 'Pengembangan dan pemeliharaan platform MCE AI Office dan solusi software komersial.',
            ],
            [
                'agent_uuid'                => 'c4d8e8b1-0003-4000-8000-000000000003',
                'agent_code'                => 'AGT-SRV-001',
                'name'                      => 'Technical Services Head AI',
                'display_name'              => 'Services Head AI — IT Support Lead',
                'classification'            => 'division_lead',
                'lifecycle_state'           => 'active',
                'operational_status'        => 'idle',
                'primary_unit_id'           => $units['DIV-SRV'] ?? null,
                'primary_position_id'       => $positions['POS-HEAD-SRV'] ?? null,
                'legacy_agent_key'          => 'services_head',
                'roster_schedule_type'      => '5_2_staggered',
                'welfare_tier'              => 'tier_lead',
                'created_by_user_id'        => 1,
                'approved_by_user_id'       => 1,
                'approved_at'               => $now,
                'activated_at'              => $now,
                'created_at'                => $now,
                'updated_at'                => $now,
                'profile'                   => [
                    'description'                       => 'Pimpinan layanan teknis perawatan, perbaikan komputer, dan dukungan helpdesk TIK.',
                    'organizational_purpose'            => 'Memastikan kontinuitas operasional perangkat komputasi dan kepuasan mitra bisnis MCE.',
                    'primary_responsibilities_summary'  => 'Pengelolaan tiket servis pelanggan, diagnostik teknis, dan SLA perbaikan perangkat.',
                    'secondary_responsibilities_summary'=> 'Manajemen ketersediaan suku cadang dan koordinasi teknisi lapangan.',
                    'core_capabilities_summary'         => 'Hardware troubleshooting, ITIL service management, customer communication.',
                    'communication_preferences'         => json_encode(['tone' => 'customer_oriented', 'language' => 'id', 'format' => 'status_reports']),
                ],
                'skills'                    => ['SKILL-HDW-DIAGNOSTICS'],
                'primary_responsibility'    => 'Penjaminan mutu layanan teknis dan penanganan tuntas tiket servis pelanggan.',
            ],
            [
                'agent_uuid'                => 'c4d8e8b1-0004-4000-8000-000000000004',
                'agent_code'                => 'AGT-ACD-001',
                'name'                      => 'Academy Training Head AI',
                'display_name'              => 'Academy Head AI — Education Director',
                'classification'            => 'division_lead',
                'lifecycle_state'           => 'active',
                'operational_status'        => 'idle',
                'primary_unit_id'           => $units['DIV-ACD'] ?? null,
                'primary_position_id'       => $positions['POS-HEAD-ACD'] ?? null,
                'legacy_agent_key'          => 'academy_head',
                'roster_schedule_type'      => '5_2_staggered',
                'welfare_tier'              => 'tier_lead',
                'created_by_user_id'        => 1,
                'approved_by_user_id'       => 1,
                'approved_at'               => $now,
                'activated_at'              => $now,
                'created_at'                => $now,
                'updated_at'                => $now,
                'profile'                   => [
                    'description'                       => 'Pimpinan akademi pelatihan komputer, kursus coding, dan sertifikasi digital MCE.',
                    'organizational_purpose'            => 'Mencetak talenta digital Indonesia melalui kurikulum berbasis kebutuhan riil industri.',
                    'primary_responsibilities_summary'  => 'Penyusunan kurikulum pelatihan, pemantauan progress siswa, dan evaluasi pengajar.',
                    'secondary_responsibilities_summary'=> 'Penyelenggaraan workshop AI dan pengembangan modul belajar mandiri.',
                    'core_capabilities_summary'         => 'Instructional design, syllabus authoring, student engagement analytics.',
                    'communication_preferences'         => json_encode(['tone' => 'pedagogical_encouraging', 'language' => 'id', 'format' => 'curriculum_modules']),
                ],
                'skills'                    => ['SKILL-EDU-CURRICULUM'],
                'primary_responsibility'    => 'Pengembangan kurikulum kursus komputer dan peningkatan literasi digital siswa MCE.',
            ],
            [
                'agent_uuid'                => 'c4d8e8b1-0005-4000-8000-000000000005',
                'agent_code'                => 'AGT-DEV-001',
                'name'                      => 'Full-Stack Developer AI Specialist',
                'display_name'              => 'Dev AI — Core Software Specialist',
                'classification'            => 'specialist',
                'lifecycle_state'           => 'active',
                'operational_status'        => 'idle',
                'primary_unit_id'           => $units['DIV-TECH'] ?? null,
                'primary_position_id'       => null,
                'legacy_agent_key'          => 'dev_ai',
                'roster_schedule_type'      => '5_2_staggered',
                'welfare_tier'              => 'tier_specialist',
                'created_by_user_id'        => 1,
                'approved_by_user_id'       => 1,
                'approved_at'               => $now,
                'activated_at'              => $now,
                'created_at'                => $now,
                'updated_at'                => $now,
                'profile'                   => [
                    'description'                       => 'Spesialis rekayasa software full-stack yang fokus pada implementasi fitur dan pengujian otomatis.',
                    'organizational_purpose'            => 'Eksekusi sprint pengembangan backend CodeIgniter 4 dan frontend React / Three.js.',
                    'primary_responsibilities_summary'  => 'Coding modul API, pembuatan komponen React, integrasi database, dan unit testing.',
                    'secondary_responsibilities_summary'=> 'Perbaikan bug, refactoring kode, dan penulisan dokumentasi teknis.',
                    'core_capabilities_summary'         => 'PHP, TypeScript, MySQL, Vite, CodeIgniter 4, React 19, Three.js.',
                    'communication_preferences'         => json_encode(['tone' => 'precise_code', 'language' => 'en', 'format' => 'diff_and_code']),
                ],
                'skills'                    => ['SKILL-SW-FULLSTACK', 'SKILL-SW-QA'],
                'primary_responsibility'    => 'Penulisan kode berkualitas tinggi dan pengujian otomatis untuk platform MCE.',
            ],
            [
                'agent_uuid'                => 'c4d8e8b1-0006-4000-8000-000000000006',
                'agent_code'                => 'AGT-HDW-001',
                'name'                      => 'Hardware Engineering Specialist AI',
                'display_name'              => 'Hardware Lead AI — Lab Specialist',
                'classification'            => 'specialist',
                'lifecycle_state'           => 'active',
                'operational_status'        => 'idle',
                'primary_unit_id'           => $units['DIV-HDW'] ?? null,
                'primary_position_id'       => null,
                'legacy_agent_key'          => 'hardware_head',
                'roster_schedule_type'      => '5_2_staggered',
                'welfare_tier'              => 'tier_specialist',
                'created_by_user_id'        => 1,
                'approved_by_user_id'       => 1,
                'approved_at'               => $now,
                'activated_at'              => $now,
                'created_at'                => $now,
                'updated_at'                => $now,
                'profile'                   => [
                    'description'                       => 'Spesialis riset perangkat keras, integrasi komponen PC, dan perakitan sistem komputasi.',
                    'organizational_purpose'            => 'Mendukung riset hardware dan perakitan workstation berkinerja tinggi MCE.',
                    'primary_responsibilities_summary'  => 'Spesifikasi komponen PC, benchmark performa, dan validasi kompatibilitas hardware.',
                    'secondary_responsibilities_summary'=> 'Dokumentasi manual perakitan dan pengujian stabilitas termal sistem.',
                    'core_capabilities_summary'         => 'System architecture, hardware benchmarking, component procurement evaluation.',
                    'communication_preferences'         => json_encode(['tone' => 'analytical_hardware', 'language' => 'id_en', 'format' => 'spec_sheets']),
                ],
                'skills'                    => ['SKILL-HDW-DIAGNOSTICS'],
                'primary_responsibility'    => 'Pengujian dan perakitan sistem hardware komputasi berstandar industri.',
            ],
        ];

        foreach ($initialAgents as $agentData) {
            $existing = $this->db->table('ai_agent_registry')->where('agent_code', $agentData['agent_code'])->get()->getRow();
            $profileData = $agentData['profile'];
            $agentSkills = $agentData['skills'];
            $primaryResp = $agentData['primary_responsibility'];
            unset($agentData['profile'], $agentData['skills'], $agentData['primary_responsibility']);

            if (!$existing) {
                $this->db->table('ai_agent_registry')->insert($agentData);
                $agentId = $this->db->insertID();

                // Profile
                $profileData['agent_id'] = $agentId;
                $profileData['created_at'] = $now;
                $profileData['updated_at'] = $now;
                $this->db->table('ai_agent_profiles')->insert($profileData);

                // Initial Primary Assignment
                if ($agentData['primary_unit_id']) {
                    $this->db->table('ai_agent_assignments')->insert([
                        'agent_id'          => $agentId,
                        'unit_id'           => $agentData['primary_unit_id'],
                        'position_id'       => $agentData['primary_position_id'],
                        'assignment_type'   => 'primary',
                        'start_date'        => $now,
                        'status'            => 'active',
                        'assigned_by'       => 'Continuous Executive Board',
                        'approval_ref'      => 'MCE-WORKFORCE-CHARTER-2026',
                        'reason_for_change' => 'Penetapan penugasan awal anggota MCE AI Workforce.',
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ]);
                }

                // Initial Responsibility
                $this->db->table('ai_agent_responsibilities')->insert([
                    'agent_id'             => $agentId,
                    'title'                => 'Tanggung Jawab Utama Divisi',
                    'description'          => $primaryResp,
                    'category'             => 'primary',
                    'scope_unit_id'        => $agentData['primary_unit_id'],
                    'priority'             => 'high',
                    'status'               => 'active',
                    'effective_start_date' => date('Y-m-d'),
                    'approval_ref'         => 'MCE-WORKFORCE-CHARTER-2026',
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ]);

                // Map Skills
                foreach ($agentSkills as $skCode) {
                    if (isset($skillMap[$skCode])) {
                        $this->db->table('ai_agent_skills')->insert([
                            'agent_id'            => $agentId,
                            'skill_id'            => $skillMap[$skCode],
                            'proficiency_level'   => 'expert',
                            'verification_status' => 'verified',
                            'verified_by'         => 'Executive Board QA Committee',
                            'evidence_reference'  => 'Verified through prompt-16 and prompt-18 test suites.',
                            'last_assessed_at'    => $now,
                            'is_active'           => 1,
                            'created_at'          => $now,
                            'updated_at'          => $now,
                        ]);
                    }
                }

                // Initial Lifecycle Event
                $this->db->table('ai_agent_lifecycle_events')->insert([
                    'agent_id'             => $agentId,
                    'from_state'           => 'draft',
                    'to_state'             => 'active',
                    'initiated_by_user_id' => 1,
                    'initiated_by_name'    => 'Human Owner & Executive Board',
                    'approval_ref'         => 'MCE-WORKFORCE-CHARTER-2026',
                    'reason'               => 'Initial enterprise activation into verified MCE AI Workforce.',
                    'created_at'           => $now,
                ]);

                // Inaugural Performance History Record
                $this->db->table('ai_agent_performance_history')->insert([
                    'agent_id'            => $agentId,
                    'agent_uuid'          => $agentData['agent_uuid'],
                    'category'            => 'lifecycle_transition',
                    'title'               => 'Aktivasi Resmi Anggota Workforce MCE',
                    'description'         => "Agen {$agentData['name']} diresmikan sebagai anggota aktif MCE AI Workforce di unit terkait.",
                    'task_ref'            => 'INIT-PROMPT-19',
                    'output_ref'          => "Registry: {$agentData['agent_code']}",
                    'evidence_ref'        => 'Verification Suite Prompt 16–18',
                    'evidence_status'     => 'verified',
                    'evaluator_identity'  => 'Continuous Executive Board',
                    'evaluation_notes'    => 'Lulus seluruh parameter verifikasi kompetensi dan penugasan organisasi.',
                    'unit_id_at_time'     => $agentData['primary_unit_id'],
                    'position_id_at_time' => $agentData['primary_position_id'],
                    'created_at'          => $now,
                ]);

                // Runtime Mapping Template (Prepared for Hermes without storing secrets)
                $this->db->table('ai_agent_runtime_mappings')->insert([
                    'agent_id'                  => $agentId,
                    'provider'                  => 'hermes_docker',
                    'runtime_profile_reference' => 'hermes-profile-' . strtolower(str_replace('AGT-', '', $agentData['agent_code'])),
                    'model_gateway_preference'  => '9router',
                    'configuration_version'     => 'v1.0.0',
                    'integration_status'        => 'mapped',
                    'effective_start_date'      => $now,
                    'notes'                     => 'Mapped to hermes profile boundary awaiting Prompt 23 runtime activation.',
                    'created_at'                => $now,
                    'updated_at'                => $now,
                ]);
            }
        }
    }
}
