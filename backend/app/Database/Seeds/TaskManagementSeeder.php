<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Services\AgentRegistryService;

class TaskManagementSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');

        // Fetch agents and units
        $agents = [];
        $agentRows = $this->db->table('ai_agent_registry')->get()->getResultArray();
        foreach ($agentRows as $row) {
            $key = $row['legacy_agent_key'] ?? $row['agent_code'];
            $agents[$key] = (int)$row['id'];
        }

        $units = [];
        $unitRows = $this->db->table('organization_units')->get()->getResultArray();
        foreach ($unitRows as $row) {
            $units[$row['code']] = (int)$row['id'];
        }

        $skills = [];
        $skillRows = $this->db->table('skill_catalogue')->get()->getResultArray();
        foreach ($skillRows as $row) {
            $skills[$row['code']] = (int)$row['id'];
        }

        // 1. Initial Verified Workforce Tasks
        $tasksData = [
            [
                'task_uuid'              => AgentRegistryService::generateUuid(),
                'task_code'              => 'TSK-2026-001',
                'title'                  => 'Perumusan Strategi Q4 & Rapat Direksi Berkelanjutan',
                'description'            => 'Koordinasi perumusan rencana pertumbuhan operasional, tata kelola DoA, dan alignment lintas divisi MCE.',
                'objective'              => 'Menghasilkan panduan operasional terpadu untuk kuartal berjalan.',
                'expected_outcome'       => 'Dokumen arahan strategis yang diratifikasi oleh Continuous Executive Board.',
                'parent_task_id'         => null,
                'unit_id'                => $units['ORG-MCE'] ?? 1,
                'category'               => 'executive',
                'priority'               => 'high',
                'status'                 => 'in_progress',
                'origin_type'            => 'board_decision',
                'origin_reference_id'    => 'DEC-BOARD-2026-001',
                'is_binding_instruction' => 0,
                'accountable_agent_id'   => $agents['director'] ?? 1,
                'creator_user_id'        => 1,
                'creator_agent_id'       => null,
                'required_skill_id'      => $skills['SKL-CORP-GOV'] ?? null,
                'min_skill_proficiency'  => 'expert',
                'approval_level'         => 'B',
                'financial_amount'       => 0.00,
                'approved_by_user_id'    => 1,
                'approved_at'            => $now,
                'start_date'             => $today,
                'due_date'               => date('Y-m-d', strtotime('+14 days')),
                'progress_percent'       => 45,
                'confidentiality_level'  => 'internal',
                'created_at'             => $now,
                'updated_at'             => $now,
            ],
            [
                'task_uuid'              => AgentRegistryService::generateUuid(),
                'task_code'              => 'TSK-2026-002',
                'title'                  => 'Implementasi & Pengujian Task Control Plane CI4',
                'description'            => 'Membangun arsitektur domain task management, multi-agent collaboration, review deliverables, dan integrasi audit.',
                'objective'              => 'Menyediakan business control plane yang aman sebelum runtime Hermes diaktifkan.',
                'expected_outcome'       => 'REST API, database schema Batch 6, dan UI React Task Management terverifikasi 100%.',
                'parent_task_id'         => null,
                'unit_id'                => $units['DIV-TECH'] ?? 2,
                'category'               => 'development',
                'priority'               => 'critical',
                'status'                 => 'in_progress',
                'origin_type'            => 'director_directive',
                'origin_reference_id'    => 'DIR-EXEC-2026-001',
                'is_binding_instruction' => 1,
                'accountable_agent_id'   => $agents['tech_head'] ?? 2,
                'creator_user_id'        => 1,
                'creator_agent_id'       => $agents['director'] ?? 1,
                'required_skill_id'      => $skills['SKL-SYS-ARCH'] ?? null,
                'min_skill_proficiency'  => 'expert',
                'approval_level'         => 'routine',
                'financial_amount'       => 0.00,
                'approved_by_user_id'    => null,
                'approved_at'            => null,
                'start_date'             => $today,
                'due_date'               => date('Y-m-d', strtotime('+7 days')),
                'progress_percent'       => 70,
                'confidentiality_level'  => 'internal',
                'created_at'             => $now,
                'updated_at'             => $now,
            ],
            [
                'task_uuid'              => AgentRegistryService::generateUuid(),
                'task_code'              => 'TSK-2026-003',
                'title'                  => 'Pembuatan Adapter Eksekusi & Review Deliverable',
                'description'            => 'Subtask teknis untuk menyusun kontrak adapter antarmuka runtime execution tracking dan penyimpanan deliverable terverifikasi.',
                'objective'              => 'Memastikan isolasi antara task business control plane dan live model execution boundary.',
                'expected_outcome'       => 'Service interface dan model tracking tervalidasi.',
                'parent_task_id'         => null, // Will link to TSK-2026-002
                'unit_id'                => $units['DIV-TECH'] ?? 2,
                'category'               => 'development',
                'priority'               => 'high',
                'status'                 => 'accepted',
                'origin_type'            => 'division_initiative',
                'origin_reference_id'    => 'TSK-2026-002',
                'is_binding_instruction' => 0,
                'accountable_agent_id'   => $agents['dev_ai'] ?? 6,
                'creator_user_id'        => null,
                'creator_agent_id'       => $agents['tech_head'] ?? 2,
                'required_skill_id'      => $skills['SKL-FULLSTACK'] ?? null,
                'min_skill_proficiency'  => 'advanced',
                'approval_level'         => 'routine',
                'financial_amount'       => 0.00,
                'approved_by_user_id'    => null,
                'approved_at'            => null,
                'start_date'             => $today,
                'due_date'               => date('Y-m-d', strtotime('+5 days')),
                'progress_percent'       => 30,
                'confidentiality_level'  => 'internal',
                'created_at'             => $now,
                'updated_at'             => $now,
            ],
            [
                'task_uuid'              => AgentRegistryService::generateUuid(),
                'task_code'              => 'TSK-2026-004',
                'title'                  => 'Penyusunan Paket Layanan Cloud & Managed AI MCE',
                'description'            => 'Merancang penawaran paket layanan konsultasi, integrasi AI, dan continuous support untuk klien korporat.',
                'objective'              => 'Menghadirkan proposal portofolio layanan komersial MCE.',
                'expected_outcome'       => 'Katalog penawaran paket layanan B2B.',
                'parent_task_id'         => null,
                'unit_id'                => $units['DIV-SRV'] ?? 4,
                'category'               => 'customer_project',
                'priority'               => 'medium',
                'status'                 => 'assigned',
                'origin_type'            => 'owner_instruction',
                'origin_reference_id'    => 'DIR-OWNER-2026-001',
                'is_binding_instruction' => 0, // Advisory
                'accountable_agent_id'   => $agents['services_head'] ?? 3,
                'creator_user_id'        => 1,
                'creator_agent_id'       => null,
                'required_skill_id'      => $skills['SKL-AGILE-COORD'] ?? null,
                'min_skill_proficiency'  => 'intermediate',
                'approval_level'         => 'routine',
                'financial_amount'       => 0.00,
                'approved_by_user_id'    => null,
                'approved_at'            => null,
                'start_date'             => $today,
                'due_date'               => date('Y-m-d', strtotime('+21 days')),
                'progress_percent'       => 15,
                'confidentiality_level'  => 'internal',
                'created_at'             => $now,
                'updated_at'             => $now,
            ],
            [
                'task_uuid'              => AgentRegistryService::generateUuid(),
                'task_code'              => 'TSK-2026-005',
                'title'                  => 'Penyusunan Kurikulum AI Workforce Engineering',
                'description'            => 'Penyusunan silabus pembelajaran, praktikum hands-on agentic workflows, dan modul etika AI untuk MCE Academy.',
                'objective'              => 'Menyediakan kurikulum pelatihan standar enterprise.',
                'expected_outcome'       => 'Silabus 8 modul lengkap dengan rubrik evaluasi.',
                'parent_task_id'         => null,
                'unit_id'                => $units['DIV-ACD'] ?? 7,
                'category'               => 'creative',
                'priority'               => 'medium',
                'status'                 => 'in_progress',
                'origin_type'            => 'board_decision',
                'origin_reference_id'    => 'DEC-BOARD-2026-001',
                'is_binding_instruction' => 0,
                'accountable_agent_id'   => $agents['academy_head'] ?? 4,
                'creator_user_id'        => 1,
                'creator_agent_id'       => null,
                'required_skill_id'      => $skills['SKL-CURR-DES'] ?? null,
                'min_skill_proficiency'  => 'advanced',
                'approval_level'         => 'routine',
                'financial_amount'       => 0.00,
                'approved_by_user_id'    => null,
                'approved_at'            => null,
                'start_date'             => $today,
                'due_date'               => date('Y-m-d', strtotime('+30 days')),
                'progress_percent'       => 50,
                'confidentiality_level'  => 'public',
                'created_at'             => $now,
                'updated_at'             => $now,
            ],
            [
                'task_uuid'              => AgentRegistryService::generateUuid(),
                'task_code'              => 'TSK-2026-006',
                'title'                  => 'Audit & Spesifikasi Server Hermes On-Premise',
                'description'            => 'Pemeriksaan kesiapan perangkat keras, GPU clustering, alokasi memori, dan keamanan jaringan untuk runtime sandbox AI.',
                'objective'              => 'Memastikan spesifikasi server on-premise siap sebelum containerization Prompt 23.',
                'expected_outcome'       => 'Laporan audit spesifikasi server on-premise v1.0.',
                'parent_task_id'         => null,
                'unit_id'                => $units['DIV-HDW'] ?? 5,
                'category'               => 'maintenance',
                'priority'               => 'high',
                'status'                 => 'under_review',
                'origin_type'            => 'board_decision',
                'origin_reference_id'    => 'DEC-BOARD-2026-001',
                'is_binding_instruction' => 0,
                'accountable_agent_id'   => $agents['hardware_head'] ?? 5,
                'creator_user_id'        => 1,
                'creator_agent_id'       => null,
                'required_skill_id'      => $skills['SKL-IOT-EMBED'] ?? null,
                'min_skill_proficiency'  => 'advanced',
                'approval_level'         => 'routine',
                'financial_amount'       => 0.00,
                'approved_by_user_id'    => null,
                'approved_at'            => null,
                'start_date'             => $today,
                'due_date'               => date('Y-m-d', strtotime('+3 days')),
                'progress_percent'       => 95,
                'confidentiality_level'  => 'restricted',
                'created_at'             => $now,
                'updated_at'             => $now,
            ],
        ];

        $taskMap = [];

        foreach ($tasksData as $t) {
            $exists = $this->db->table('workforce_tasks')->where('task_code', $t['task_code'])->get()->getRowArray();
            if (!$exists) {
                $this->db->table('workforce_tasks')->insert($t);
                $taskId = (int)$this->db->insertID();
            } else {
                $taskId = (int)$exists['id'];
            }
            $taskMap[$t['task_code']] = $taskId;

            // Seed primary assignment
            $assignExists = $this->db->table('task_assignments')
                ->where('task_id', $taskId)
                ->where('agent_id', $t['accountable_agent_id'])
                ->countAllResults();

            if ($assignExists === 0) {
                $this->db->table('task_assignments')->insert([
                    'task_id'              => $taskId,
                    'agent_id'             => $t['accountable_agent_id'],
                    'assignment_role'      => 'lead_agent',
                    'responsibility_scope' => 'Accountable lead for full task lifecycle execution and deliverable verification.',
                    'assigned_by_user_id'  => 1,
                    'assigned_by_agent_id' => null,
                    'acceptance_status'    => 'accepted',
                    'rejection_reason'     => null,
                    'accepted_at'          => $now,
                    'status'               => 'active',
                    'duty_assignment_id'   => null,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ]);
            }

            // Seed initial task event audit
            $this->db->table('task_events_audit')->insert([
                'task_id'     => $taskId,
                'event_type'  => 'task_created',
                'from_status' => null,
                'to_status'   => $t['status'],
                'actor_type'  => 'human',
                'actor_id'    => 1,
                'actor_name'  => 'System Architect',
                'details'     => "Task {$t['task_code']} ({$t['title']}) registered in Task Control Plane.",
                'created_at'  => $now,
            ]);
        }

        // Link parent-child: TSK-2026-003 is subtask of TSK-2026-002
        if (isset($taskMap['TSK-2026-003']) && isset($taskMap['TSK-2026-002'])) {
            $this->db->table('workforce_tasks')->where('id', $taskMap['TSK-2026-003'])->update([
                'parent_task_id' => $taskMap['TSK-2026-002'],
            ]);

            // Add dependency: TSK-2026-003 depends on TSK-2026-002
            $depExists = $this->db->table('task_dependencies')
                ->where('task_id', $taskMap['TSK-2026-003'])
                ->where('depends_on_task_id', $taskMap['TSK-2026-002'])
                ->countAllResults();

            if ($depExists === 0) {
                $this->db->table('task_dependencies')->insert([
                    'task_id'            => $taskMap['TSK-2026-003'],
                    'depends_on_task_id' => $taskMap['TSK-2026-002'],
                    'dependency_type'    => 'finish_to_start',
                    'is_satisfied'       => 0,
                    'satisfied_at'       => null,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);
            }
        }

        // 2. Multi-Agent Collaboration Group
        // Task TSK-2026-002 has collaboration group COL-2026-001 with Tech Head (Lead), Dev AI, and Hardware Head
        if (isset($taskMap['TSK-2026-002'])) {
            $collabCode = 'COL-2026-001';
            $collabExists = $this->db->table('task_collaboration_groups')->where('collaboration_code', $collabCode)->get()->getRowArray();
            if (!$collabExists) {
                $this->db->table('task_collaboration_groups')->insert([
                    'collaboration_code' => $collabCode,
                    'task_id'            => $taskMap['TSK-2026-002'],
                    'lead_agent_id'      => $agents['tech_head'] ?? 2,
                    'title'              => 'Satuan Tugas Arsitektur Hermes & 9Router',
                    'shared_objective'   => 'Kolaborasi lintas spesialisasi untuk mempersiapkan adapter runtime, skema database, dan spesifikasi server AI.',
                    'status'             => 'active',
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);
                $collabGroupId = (int)$this->db->insertID();

                // Members
                $members = [
                    [
                        'group_id'                  => $collabGroupId,
                        'agent_id'                  => $agents['tech_head'] ?? 2,
                        'role'                      => 'lead',
                        'assigned_responsibilities' => 'Memimpin koordinasi teknis, validasi arsitektur, dan persetujuan deliverable subtask.',
                        'status'                    => 'active',
                        'created_at'                => $now,
                        'updated_at'                => $now,
                    ],
                    [
                        'group_id'                  => $collabGroupId,
                        'agent_id'                  => $agents['dev_ai'] ?? 6,
                        'role'                      => 'developer',
                        'assigned_responsibilities' => 'Mengembangkan domain services, CI4 controller, dan komponen React dashboard.',
                        'status'                    => 'active',
                        'created_at'                => $now,
                        'updated_at'                => $now,
                    ],
                    [
                        'group_id'                  => $collabGroupId,
                        'agent_id'                  => $agents['hardware_head'] ?? 5,
                        'role'                      => 'specialist',
                        'assigned_responsibilities' => 'Memberikan telaah kebutuhan throughput GPU, alokasi resource, dan kesiapan hardware.',
                        'status'                    => 'active',
                        'created_at'                => $now,
                        'updated_at'                => $now,
                    ],
                ];

                foreach ($members as $m) {
                    $this->db->table('task_collaboration_members')->insert($m);
                }
            }
        }

        // 3. Task Delegation Record
        // Tech Head delegates sub-scope of TSK-2026-002 to Dev AI
        if (isset($taskMap['TSK-2026-002']) && isset($agents['tech_head']) && isset($agents['dev_ai'])) {
            $delegExists = $this->db->table('task_delegations')
                ->where('task_id', $taskMap['TSK-2026-002'])
                ->where('delegated_agent_id', $agents['dev_ai'])
                ->countAllResults();

            if ($delegExists === 0) {
                $this->db->table('task_delegations')->insert([
                    'task_id'             => $taskMap['TSK-2026-002'],
                    'delegating_agent_id' => $agents['tech_head'],
                    'delegated_agent_id'  => $agents['dev_ai'],
                    'delegated_scope'     => 'Implementasi REST controller endpoint, penanganan CORS, dan penulisan unit test assertions.',
                    'expected_output'     => 'Unit test SchedulingTest dan TaskManagementTest lulus 100%.',
                    'authority_limit'     => 'routine',
                    'deadline'            => date('Y-m-d H:i:s', strtotime('+5 days')),
                    'status'              => 'accepted',
                    'rejection_reason'    => null,
                    'accepted_at'         => $now,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]);
            }
        }

        // 4. Deliverable, Review & Verified Contribution on TSK-2026-006
        if (isset($taskMap['TSK-2026-006']) && isset($agents['hardware_head']) && isset($agents['tech_head'])) {
            $delivExists = $this->db->table('task_deliverables')->where('task_id', $taskMap['TSK-2026-006'])->get()->getRowArray();
            if (!$delivExists) {
                $this->db->table('task_deliverables')->insert([
                    'task_id'             => $taskMap['TSK-2026-006'],
                    'submitting_agent_id' => $agents['hardware_head'],
                    'deliverable_type'    => 'report',
                    'title'               => 'Laporan Audit Spesifikasi Server On-Premise v1.0',
                    'description'         => 'Rincian audit arsitektur hardware: 64 vCPU, 256GB RAM, dual NVIDIA RTX A6000, 4TB NVMe SSD untuk sandbox Hermes.',
                    'artifact_uri'        => 'docs/architecture/HARDWARE-AUDIT-REPORT.md',
                    'version'             => 1,
                    'review_status'       => 'accepted',
                    'is_current_accepted' => 1,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]);
                $deliverableId = (int)$this->db->insertID();

                // Review record by Tech Head AI
                $this->db->table('task_reviews')->insert([
                    'task_id'                  => $taskMap['TSK-2026-006'],
                    'deliverable_id'           => $deliverableId,
                    'reviewer_user_id'         => null,
                    'reviewer_agent_id'        => $agents['tech_head'],
                    'review_outcome'           => 'accepted',
                    'evaluation_comments'      => 'Spesifikasi hardware telah memenuhi seluruh prasyarat runtime Prompt 23. Sangat komprehensif.',
                    'criteria_checklist'       => json_encode(['gpu_adequate' => true, 'memory_adequate' => true, 'nvme_fast_storage' => true]),
                    'is_verified_contribution' => 1,
                    'created_at'               => $now,
                ]);

                // Record in Individual Agent Performance History for Hardware Head AI!
                $hardwareUuid = $this->db->table('ai_agent_registry')->where('id', $agents['hardware_head'])->get()->getRowArray()['agent_uuid'] ?? '';
                $this->db->table('ai_agent_performance_history')->insert([
                    'agent_id'           => $agents['hardware_head'],
                    'agent_uuid'         => $hardwareUuid,
                    'category'           => 'task_completion',
                    'title'              => 'Penyelesaian Audit Spesifikasi Server On-Premise',
                    'description'        => 'Deliverable Laporan Audit Spesifikasi Server On-Premise v1.0 berhasil diverifikasi dan disetujui oleh Tech Head AI.',
                    'task_ref'           => 'TSK-2026-006',
                    'output_ref'         => 'docs/architecture/HARDWARE-AUDIT-REPORT.md',
                    'evidence_ref'       => 'SHA256:hardware-audit-v1.0-verified',
                    'evidence_status'    => 'verified',
                    'evaluator_identity' => 'Tech Head AI (Reviewer)',
                    'evaluation_notes'   => 'Verified via Task Review #1 by Tech Head AI.',
                    'superseded_by_id'   => null,
                    'created_at'         => $now,
                ]);
            }
        }
    }
}
