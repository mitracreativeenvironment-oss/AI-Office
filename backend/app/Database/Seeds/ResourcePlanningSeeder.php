<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ResourcePlanningSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        $db = $this->db;

        // Fetch user reference
        $userRow = $db->table('users')->orderBy('id', 'ASC')->get(1)->getRowArray();
        $userId = $userRow ? (int)$userRow['id'] : 1;

        // Fetch agents
        $agentRows = $db->table('ai_agent_registry')->orderBy('id', 'ASC')->get()->getResultArray();
        $agents = [];
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
        $creatUnit = $db->table('organization_units')->where('code', 'DIV-CREAT')->get()->getRowArray();
        $creatUnitId = $creatUnit ? (int)$creatUnit['id'] : null;
        $corpUnit = $db->table('organization_units')->where('code', 'ORG-MCE')->get()->getRowArray();
        $corpUnitId = $corpUnit ? (int)$corpUnit['id'] : null;

        // Fetch Skills
        $skills = [];
        $skillRows = $db->table('skill_catalogue')->get()->getResultArray();
        foreach ($skillRows as $s) {
            $skills[$s['code']] = (int)$s['id'];
        }
        $skillTsId = $skills['SKILL-DEV-TS'] ?? (isset($skillRows[0]) ? (int)$skillRows[0]['id'] : null);
        $skillQaId = $skills['SKILL-QA-AUTO'] ?? (isset($skillRows[1]) ? (int)$skillRows[1]['id'] : null);
        $skillAiId = $skills['SKILL-AI-PROMPT'] ?? (isset($skillRows[2]) ? (int)$skillRows[2]['id'] : null);

        // Fetch Projects
        $projectRows = $db->table('projects')->orderBy('id', 'ASC')->get()->getResultArray();
        $prj1Id = isset($projectRows[0]) ? (int)$projectRows[0]['id'] : null;
        $prj2Id = isset($projectRows[1]) ? (int)$projectRows[1]['id'] : $prj1Id;

        // Clean tables in reverse order
        $tables = [
            'resource_audit_events',
            'capacity_gap_analyses',
            'capacity_forecast_snapshots',
            'portfolio_priorities',
            'resource_conflicts',
            'resource_allocations_master',
            'resource_requests',
            'resource_capacity_reservations',
            'resource_registry',
        ];

        foreach ($tables as $t) {
            if ($db->tableExists($t)) {
                $db->table($t)->emptyTable();
            }
        }

        // 1. Seed Resource Registry
        $resources = [
            [
                'resource_uuid'       => '550e8400-e29b-41d4-a716-446655440001',
                'resource_code'       => 'RES-9ROUTER-MAIN',
                'name'                => '9Router Model Gateway High-Throughput Pool',
                'category'            => 'ai_model_service',
                'description'         => 'Authoritative enterprise AI model routing gateway providing Anthropic Claude, OpenAI, and Gemini models with rate limiting and quota controls.',
                'unit_of_measure'     => 'tokens',
                'total_capacity'      => 10000000.00,
                'allocated_capacity'  => 3500000.00,
                'unit_cost_idr'       => 150.00,
                'owner_unit_id'       => $techUnitId,
                'status'              => 'active',
                'specifications_json' => json_encode(['latency_sla_ms' => 450, 'concurrency_limit' => 64, 'supported_protocols' => ['HTTP/2', 'gRPC']]),
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'resource_uuid'       => '550e8400-e29b-41d4-a716-446655440002',
                'resource_code'       => 'RES-HERMES-CLUSTER',
                'name'                => 'Hermes Agentic Execution Node Pool',
                'category'            => 'compute_infrastructure',
                'description'         => 'Dedicated Docker/Kubernetes containerized runtime cluster powering autonomous AI Agent workers, multi-agent pipelines, and sandbox execution.',
                'unit_of_measure'     => 'instances',
                'total_capacity'      => 16.00,
                'allocated_capacity'  => 8.00,
                'unit_cost_idr'       => 50000.00,
                'owner_unit_id'       => $techUnitId,
                'status'              => 'active',
                'specifications_json' => json_encode(['vcpus_per_node' => 4, 'ram_gb_per_node' => 16, 'isolation_level' => 'strict_sandbox']),
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'resource_uuid'       => '550e8400-e29b-41d4-a716-446655440003',
                'resource_code'       => 'RES-STAGE-ENV',
                'name'                => 'Staging & Integration Testing Sandbox',
                'category'            => 'development_environment',
                'description'         => 'Mirror environment of MCE production used for full-stack regression verification, load testing, and client pre-delivery demonstrations.',
                'unit_of_measure'     => 'hours',
                'total_capacity'      => 160.00,
                'allocated_capacity'  => 60.00,
                'unit_cost_idr'       => 25000.00,
                'owner_unit_id'       => $techUnitId,
                'status'              => 'active',
                'specifications_json' => json_encode(['database_engine' => 'MariaDB 11', 'cache_engine' => 'Redis 7', 'ssl_enabled' => true]),
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'resource_uuid'       => '550e8400-e29b-41d4-a716-446655440004',
                'resource_code'       => 'RES-FIGMA-DEV',
                'name'                => 'Figma Organization Shared Design Seats',
                'category'            => 'software_tool',
                'description'         => 'Enterprise design seats for UI/UX prototypes, 3D mockups, design tokens, and frontend component alignment.',
                'unit_of_measure'     => 'seats',
                'total_capacity'      => 10.00,
                'allocated_capacity'  => 5.00,
                'unit_cost_idr'       => 250000.00,
                'owner_unit_id'       => $creatUnitId,
                'status'              => 'active',
                'specifications_json' => json_encode(['features' => ['Dev Mode', 'Component Analytics', 'REST API Access']]),
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'resource_uuid'       => '550e8400-e29b-41d4-a716-446655440005',
                'resource_code'       => 'RES-AUDIO-LAB',
                'name'                => 'Neural Audio & Media Synthesis Cluster',
                'category'            => 'compute_infrastructure',
                'description'         => 'GPU acceleration cluster for generating high-fidelity voice synthesis, soundscapes, and procedural 3D assets for the VHQ.',
                'unit_of_measure'     => 'hours',
                'total_capacity'      => 80.00,
                'allocated_capacity'  => 20.00,
                'unit_cost_idr'       => 75000.00,
                'owner_unit_id'       => $creatUnitId,
                'status'              => 'active',
                'specifications_json' => json_encode(['gpu_type' => 'NVIDIA A100 80GB', 'cuda_cores' => 6912]),
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'resource_uuid'       => '550e8400-e29b-41d4-a716-446655440006',
                'resource_code'       => 'RES-VHQ-ROOM-CONF',
                'name'                => 'Executive Boardroom VHQ Spatial Facility',
                'category'            => 'shared_facility',
                'description'         => '3D virtual headquarters executive boardroom room-01 with real-time audio streams, whiteboards, and MoM recording bots.',
                'unit_of_measure'     => 'hours',
                'total_capacity'      => 40.00,
                'allocated_capacity'  => 12.00,
                'unit_cost_idr'       => 0.00,
                'owner_unit_id'       => $corpUnitId,
                'status'              => 'active',
                'specifications_json' => json_encode(['capacity_persons' => 25, 'recording_enabled' => true, '3d_room_id' => 'ROOM-CONF-01']),
                'created_at'          => $now,
                'updated_at'          => $now,
            ]
        ];

        foreach ($resources as $res) {
            $db->table('resource_registry')->insert($res);
        }

        // Fetch inserted resource IDs
        $resMap = [];
        $resRows = $db->table('resource_registry')->get()->getResultArray();
        foreach ($resRows as $rr) {
            $resMap[$rr['resource_code']] = (int)$rr['id'];
        }

        // 2. Seed Resource Capacity Reservations (Internal Operations Protection)
        $reservations = [
            [
                'reservation_code'        => 'RSV-2026-INT-OPS',
                'title'                   => 'Continuous Executive Governance & Board Operations',
                'reservation_type'        => 'internal_operations',
                'scope_unit_id'           => $corpUnitId,
                'target_agent_id'         => $agentDirId,
                'target_resource_id'      => null,
                'reserved_percentage'     => 20,
                'reserved_hours_per_week' => 8.00,
                'start_date'              => '2026-01-01',
                'end_date'                => '2026-12-31',
                'sponsoring_authority'    => 'continuous_executive_board',
                'sponsoring_decision_id'  => null,
                'sponsoring_direction_id' => null,
                'status'                  => 'active',
                'justification'           => 'Mandatory ring-fenced time for the Director AI to oversee company governance, review Wallbook handovers, and align with Owner directions.',
                'created_by_user_id'      => $userId,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
            [
                'reservation_code'        => 'RSV-2026-TECH-RND',
                'title'                   => 'MCE AI Platform Architecture & 9Router Tuning R&D',
                'reservation_type'        => 'platform_rnd',
                'scope_unit_id'           => $techUnitId,
                'target_agent_id'         => $agentTechId,
                'target_resource_id'      => $resMap['RES-HERMES-CLUSTER'] ?? null,
                'reserved_percentage'     => 25,
                'reserved_hours_per_week' => 10.00,
                'start_date'              => '2026-01-01',
                'end_date'                => '2026-12-31',
                'sponsoring_authority'    => 'continuous_executive_board',
                'sponsoring_decision_id'  => null,
                'sponsoring_direction_id' => null,
                'status'                  => 'active',
                'justification'           => 'Ensures engineering leadership dedicates 25% of operational time to core platform optimization, latency reduction, and runtime tooling.',
                'created_by_user_id'      => $userId,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
            [
                'reservation_code'        => 'RSV-2026-WELFARE-BUF',
                'title'                   => 'Agent Welfare Handover Buffer & Recovery Hours',
                'reservation_type'        => 'agent_welfare_buffer',
                'scope_unit_id'           => $opsUnitId,
                'target_agent_id'         => $agentOpsId,
                'target_resource_id'      => null,
                'reserved_percentage'     => 10,
                'reserved_hours_per_week' => 4.00,
                'start_date'              => '2026-01-01',
                'end_date'                => '2026-12-31',
                'sponsoring_authority'    => 'continuous_executive_board',
                'sponsoring_decision_id'  => null,
                'sponsoring_direction_id' => null,
                'status'                  => 'active',
                'justification'           => 'Protects mandatory Wallbook handover periods and cooldown cycles between shifts to prevent operational burnout and maintain knowledge continuity.',
                'created_by_user_id'      => $userId,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
            [
                'reservation_code'        => 'RSV-2026-BIZDEV-GROWTH',
                'title'                   => 'Client Solutioning & Commercial Proposal Engineering',
                'reservation_type'        => 'bizdev_growth',
                'scope_unit_id'           => $corpUnitId,
                'target_agent_id'         => null,
                'target_resource_id'      => null,
                'reserved_percentage'     => 15,
                'reserved_hours_per_week' => 6.00,
                'start_date'              => '2026-01-01',
                'end_date'                => '2026-12-31',
                'sponsoring_authority'    => 'continuous_executive_board',
                'sponsoring_decision_id'  => null,
                'sponsoring_direction_id' => null,
                'status'                  => 'active',
                'justification'           => 'Autonomous capacity pool to scope new client inquiries and author technical delivery proposals within DoA Level B constraints.',
                'created_by_user_id'      => $userId,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
        ];

        foreach ($reservations as $rsv) {
            $db->table('resource_capacity_reservations')->insert($rsv);
        }

        // 3. Seed Resource Requests
        $requests = [
            [
                'request_code'              => 'REQ-2026-001',
                'project_id'                => $prj1Id,
                'requesting_unit_id'        => $techUnitId,
                'requester_user_id'         => $userId,
                'requester_agent_id'        => $agentTechId,
                'resource_category'         => 'workforce_agent',
                'title'                     => 'Senior Full-Stack TypeScript Lead for Client Portal',
                'purpose'                   => 'Implement modern responsive client portal UI, integrate GraphQL endpoints, and verify end-to-end user journeys.',
                'required_skill_id'         => $skillTsId,
                'required_proficiency'      => 'expert',
                'quantity_requested'        => 1.00,
                'hours_per_week'            => 20.00,
                'start_date'                => '2026-11-20',
                'end_date'                  => '2026-12-31',
                'priority'                  => 'high',
                'estimated_cost_idr'        => 3500000.00,
                'lifecycle_status'          => 'allocated',
                'capacity_assessment_notes' => 'Capacity verified: Agent DEV has 20 hours available without violating 5/2 shift schedule or welfare limits.',
                'is_capacity_sufficient'    => 1,
                'approval_authority_level'  => 'B',
                'approved_by_user_id'       => $userId,
                'approved_at'               => $now,
                'rejection_reason'          => null,
                'created_at'                => $now,
                'updated_at'                => $now,
            ],
            [
                'request_code'              => 'REQ-2026-002',
                'project_id'                => $prj1Id,
                'requesting_unit_id'        => $techUnitId,
                'requester_user_id'         => $userId,
                'requester_agent_id'        => $agentTechId,
                'resource_category'         => 'workforce_agent',
                'title'                     => 'QA Automation Specialist for Test Pipeline',
                'purpose'                   => 'Develop automated test coverage in PHPUnit and Playwright for client portal milestone deliverables.',
                'required_skill_id'         => $skillQaId,
                'required_proficiency'      => 'proficient',
                'quantity_requested'        => 1.00,
                'hours_per_week'            => 16.00,
                'start_date'                => '2026-11-20',
                'end_date'                  => '2026-12-25',
                'priority'                  => 'high',
                'estimated_cost_idr'        => 2800000.00,
                'lifecycle_status'          => 'allocated',
                'capacity_assessment_notes' => 'Agent QA scheduled capacity allows 16 hours allocation; note off-duty days must be strictly observed.',
                'is_capacity_sufficient'    => 1,
                'approval_authority_level'  => 'B',
                'approved_by_user_id'       => $userId,
                'approved_at'               => $now,
                'rejection_reason'          => null,
                'created_at'                => $now,
                'updated_at'                => $now,
            ],
            [
                'request_code'              => 'REQ-2026-003',
                'project_id'                => $prj2Id,
                'requesting_unit_id'        => $techUnitId,
                'requester_user_id'         => $userId,
                'requester_agent_id'        => $agentTechId,
                'resource_category'         => 'operational_tool',
                'title'                     => 'Hermes Cluster Dedicated Node Allocation',
                'purpose'                   => 'Dedicated Hermes sandbox node for continuous regression and 9Router stress benchmarking.',
                'required_skill_id'         => null,
                'required_proficiency'      => null,
                'quantity_requested'        => 2.00,
                'hours_per_week'            => 40.00,
                'start_date'                => '2026-11-25',
                'end_date'                  => '2026-12-31',
                'priority'                  => 'medium',
                'estimated_cost_idr'        => 4000000.00,
                'lifecycle_status'          => 'capacity_assessed',
                'capacity_assessment_notes' => 'Hermes cluster currently has 8 unallocated nodes. Request feasible under DoA Level B.',
                'is_capacity_sufficient'    => 1,
                'approval_authority_level'  => 'B',
                'approved_by_user_id'       => null,
                'approved_at'               => null,
                'rejection_reason'          => null,
                'created_at'                => $now,
                'updated_at'                => $now,
            ],
            [
                'request_code'              => 'REQ-2026-004',
                'project_id'                => $prj2Id,
                'requesting_unit_id'        => $creatUnitId,
                'requester_user_id'         => $userId,
                'requester_agent_id'        => $agentCreatId,
                'resource_category'         => 'workforce_agent',
                'title'                     => 'UI/UX Visual Identity Specialist for VHQ 3D Assets',
                'purpose'                   => 'Model procedural 3D assets, textures, and lighting rigs for VHQ Pavilion 3 update.',
                'required_skill_id'         => $skillTsId,
                'required_proficiency'      => 'competent',
                'quantity_requested'        => 1.00,
                'hours_per_week'            => 15.00,
                'start_date'                => '2026-12-01',
                'end_date'                  => '2026-12-31',
                'priority'                  => 'medium',
                'estimated_cost_idr'        => 2200000.00,
                'lifecycle_status'          => 'draft',
                'capacity_assessment_notes' => null,
                'is_capacity_sufficient'    => 1,
                'approval_authority_level'  => 'B',
                'approved_by_user_id'       => null,
                'approved_at'               => null,
                'rejection_reason'          => null,
                'created_at'                => $now,
                'updated_at'                => $now,
            ],
        ];

        foreach ($requests as $req) {
            $db->table('resource_requests')->insert($req);
        }

        // Fetch inserted request IDs
        $reqMap = [];
        $reqRows = $db->table('resource_requests')->get()->getResultArray();
        foreach ($reqRows as $rq) {
            $reqMap[$rq['request_code']] = (int)$rq['id'];
        }

        // 4. Seed Resource Allocations Master
        $allocations = [
            [
                'allocation_code'          => 'ALC-2026-001',
                'request_id'               => $reqMap['REQ-2026-001'] ?? null,
                'project_id'               => $prj1Id,
                'allocation_type'          => 'workforce_agent',
                'agent_id'                 => $agentDevId,
                'user_id'                  => null,
                'resource_id'              => null,
                'unit_id'                  => $techUnitId,
                'allocated_percentage'     => 50,
                'allocated_hours_per_week' => 20.00,
                'start_date'               => '2026-11-20',
                'end_date'                 => '2026-12-31',
                'status'                   => 'active',
                'unit_cost_idr'            => 175000.00,
                'total_cost_idr'           => 3500000.00,
                'notes'                    => 'Primary full-stack frontend engineer committed to PRJ-2026-001 customer sprint.',
                'created_at'               => $now,
                'updated_at'               => $now,
            ],
            [
                'allocation_code'          => 'ALC-2026-002',
                'request_id'               => $reqMap['REQ-2026-002'] ?? null,
                'project_id'               => $prj1Id,
                'allocation_type'          => 'workforce_agent',
                'agent_id'                 => $agentQaId,
                'user_id'                  => null,
                'resource_id'              => null,
                'unit_id'                  => $techUnitId,
                'allocated_percentage'     => 40,
                'allocated_hours_per_week' => 16.00,
                'start_date'               => '2026-11-20',
                'end_date'                 => '2026-12-25',
                'status'                   => 'active',
                'unit_cost_idr'            => 175000.00,
                'total_cost_idr'           => 2800000.00,
                'notes'                    => 'Automated test suite maintenance and PR verification for customer deliverables.',
                'created_at'               => $now,
                'updated_at'               => $now,
            ],
            [
                'allocation_code'          => 'ALC-2026-003',
                'request_id'               => null,
                'project_id'               => $prj2Id,
                'allocation_type'          => 'workforce_agent',
                'agent_id'                 => $agentTechId,
                'user_id'                  => null,
                'resource_id'              => null,
                'unit_id'                  => $techUnitId,
                'allocated_percentage'     => 30,
                'allocated_hours_per_week' => 12.00,
                'start_date'               => '2026-11-20',
                'end_date'                 => '2026-12-31',
                'status'                   => 'active',
                'unit_cost_idr'            => 250000.00,
                'total_cost_idr'           => 3000000.00,
                'notes'                    => 'Technical design lead on internal AI platform initiatives.',
                'created_at'               => $now,
                'updated_at'               => $now,
            ],
            [
                'allocation_code'          => 'ALC-2026-004',
                'request_id'               => null,
                'project_id'               => $prj1Id,
                'allocation_type'          => 'operational_resource',
                'agent_id'                 => null,
                'user_id'                  => null,
                'resource_id'              => $resMap['RES-STAGE-ENV'] ?? null,
                'unit_id'                  => $techUnitId,
                'allocated_percentage'     => 25,
                'allocated_hours_per_week' => 10.00,
                'start_date'               => '2026-11-20',
                'end_date'                 => '2026-12-31',
                'status'                   => 'active',
                'unit_cost_idr'            => 25000.00,
                'total_cost_idr'           => 250000.00,
                'notes'                    => 'Staging server environment allocated for customer acceptance demonstrations.',
                'created_at'               => $now,
                'updated_at'               => $now,
            ],
        ];

        foreach ($allocations as $alc) {
            $db->table('resource_allocations_master')->insert($alc);
        }

        // Fetch inserted allocation IDs
        $alcMap = [];
        $alcRows = $db->table('resource_allocations_master')->get()->getResultArray();
        foreach ($alcRows as $ar) {
            $alcMap[$ar['allocation_code']] = (int)$ar['id'];
        }

        // 5. Seed Resource Conflicts (Realistic simulated operational clashes)
        $conflicts = [
            [
                'conflict_code'          => 'CNF-2026-001',
                'conflict_type'          => 'overallocation',
                'severity'               => 'warning',
                'entity_type'            => 'agent',
                'agent_id'               => $agentTechId,
                'resource_id'            => null,
                'project_id'             => $prj2Id,
                'allocation_id'          => $alcMap['ALC-2026-003'] ?? null,
                'title'                  => 'Agent High Utilization / Potential Overallocation Alert',
                'description'            => 'Agent AGT-TECH-001 has 30% project allocation + 25% platform R&D reservation + 20% operational leadership duties (75% total committed hours). Additional task assignments risk exceeding the 40h standard threshold.',
                'impact_summary'         => 'Potential burnout, delayed PR code reviews, and slower architectural guidance on internal initiatives.',
                'recommended_resolution' => 'Rebalance routine operational tasks or designate Agent DEV as interim technical reviewer for customer project pull requests.',
                'resolution_status'      => 'acknowledged',
                'resolved_by_user_id'    => null,
                'resolution_notes'       => null,
                'resolved_at'            => null,
                'detected_at'            => $now,
                'created_at'             => $now,
                'updated_at'             => $now,
            ],
            [
                'conflict_code'          => 'CNF-2026-002',
                'conflict_type'          => 'rest_day_conflict',
                'severity'               => 'critical',
                'entity_type'            => 'agent',
                'agent_id'               => $agentQaId,
                'resource_id'            => null,
                'project_id'             => $prj1Id,
                'allocation_id'          => $alcMap['ALC-2026-002'] ?? null,
                'title'                  => 'Scheduled Off-Duty Shift Integrity Breach Detected',
                'description'            => 'Customer milestone sprint delivery date coincides with Agent AGT-QA-001 scheduled 5/2 rest cycle day without designated replacement activation.',
                'impact_summary'         => 'Direct violation of MCE AI Workforce Policy (5 working days / 2 rest days). Lack of Wallbook handover could compromise release testing quality.',
                'recommended_resolution' => 'Activate secondary replacement agent AGT-OPS-001 and mandate Wallbook Handover checklist completion before off-duty cycle begins.',
                'resolution_status'      => 'detected',
                'resolved_by_user_id'    => null,
                'resolution_notes'       => null,
                'resolved_at'            => null,
                'detected_at'            => $now,
                'created_at'             => $now,
                'updated_at'             => $now,
            ],
            [
                'conflict_code'          => 'CNF-2026-003',
                'conflict_type'          => 'skill_mismatch',
                'severity'               => 'warning',
                'entity_type'            => 'agent',
                'agent_id'               => $agentDevId,
                'resource_id'            => null,
                'project_id'             => $prj2Id,
                'allocation_id'          => null,
                'title'                  => 'Competency Proficiency Threshold Deficit',
                'description'            => 'Internal task requires Master-level prompt tuning and LLM router benchmarking; assigned agent is currently certified at Competent level in SKILL-AI-PROMPT.',
                'impact_summary'         => 'Possible inefficient token consumption or sub-optimal inference routing in 9Router gateway setup.',
                'recommended_resolution' => 'Pair Agent DEV with Agent TECH for peer evaluation review or complete AI Prompt Engineering advanced verification assessment.',
                'resolution_status'      => 'resolving',
                'resolved_by_user_id'    => $userId,
                'resolution_notes'       => 'Peer review pairing protocol initiated under TaskCollaborationService.',
                'resolved_at'            => null,
                'detected_at'            => $now,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]
        ];

        foreach ($conflicts as $cnf) {
            $db->table('resource_conflicts')->insert($cnf);
        }

        // 6. Seed Portfolio Priorities (Multi-criteria project evaluations)
        if ($prj1Id) {
            $db->table('portfolio_priorities')->insert([
                'project_id'                => $prj1Id,
                'evaluation_period'         => '2026-Q4',
                'strategic_alignment_score' => 88.00,
                'customer_commitment_score' => 95.00,
                'business_value_score'      => 92.00,
                'urgency_score'             => 85.00,
                'feasibility_score'         => 90.00,
                'risk_penalty_score'        => 18.00,
                'composite_priority_score'  => 89.20,
                'calculated_rank'           => 1,
                'priority_tier'             => 'tier_1_critical',
                'evaluation_rationale'      => 'Key external customer delivery with contractual milestones and SLA revenue penalties. High strategic and financial priority.',
                'evaluated_by_user_id'      => $userId,
                'evaluated_at'              => $now,
                'is_overridden'             => 0,
                'override_reason'           => null,
                'created_at'                => $now,
                'updated_at'                => $now,
            ]);
        }

        if ($prj2Id && $prj2Id !== $prj1Id) {
            $db->table('portfolio_priorities')->insert([
                'project_id'                => $prj2Id,
                'evaluation_period'         => '2026-Q4',
                'strategic_alignment_score' => 96.00,
                'customer_commitment_score' => 40.00,
                'business_value_score'      => 84.00,
                'urgency_score'             => 70.00,
                'feasibility_score'         => 88.00,
                'risk_penalty_score'        => 12.00,
                'composite_priority_score'  => 81.40,
                'calculated_rank'           => 2,
                'priority_tier'             => 'tier_1_critical',
                'evaluation_rationale'      => 'Internal foundation initiative driving MCE AI Office autonomous capabilities. Essential long-term infrastructure.',
                'evaluated_by_user_id'      => $userId,
                'evaluated_at'              => $now,
                'is_overridden'             => 0,
                'override_reason'           => null,
                'created_at'                => $now,
                'updated_at'                => $now,
            ]);
        }

        // 7. Seed Capacity Forecast Snapshots (Next 4 weeks)
        $forecastWeeks = [
            [
                'snapshot_code'                 => 'FCS-2026-W47',
                'period_type'                   => 'weekly',
                'period_start_date'             => '2026-11-16',
                'period_end_date'               => '2026-11-22',
                'unit_id'                       => $techUnitId,
                'total_agents_headcount'        => 3,
                'gross_capacity_hours'          => 120.00,
                'scheduled_capacity_hours'      => 120.00,
                'reserved_capacity_hours'       => 28.00,
                'projected_demand_hours'        => 68.00,
                'net_available_capacity_hours'  => 24.00,
                'utilization_projected_percent' => 80.00,
                'status'                        => 'balanced',
                'forecast_assumptions_json'     => json_encode(['headcount' => 3, 'standard_shift_hours' => 40, 'reserved_pct' => 23.3]),
                'is_forecast_data'              => 1,
                'created_at'                    => $now,
            ],
            [
                'snapshot_code'                 => 'FCS-2026-W48',
                'period_type'                   => 'weekly',
                'period_start_date'             => '2026-11-23',
                'period_end_date'               => '2026-11-29',
                'unit_id'                       => $techUnitId,
                'total_agents_headcount'        => 3,
                'gross_capacity_hours'          => 120.00,
                'scheduled_capacity_hours'      => 120.00,
                'reserved_capacity_hours'       => 28.00,
                'projected_demand_hours'        => 82.00,
                'net_available_capacity_hours'  => 10.00,
                'utilization_projected_percent' => 91.67,
                'status'                        => 'balanced',
                'forecast_assumptions_json'     => json_encode(['headcount' => 3, 'high_sprint_load' => true]),
                'is_forecast_data'              => 1,
                'created_at'                    => $now,
            ],
            [
                'snapshot_code'                 => 'FCS-2026-W49',
                'period_type'                   => 'weekly',
                'period_start_date'             => '2026-11-30',
                'period_end_date'               => '2026-12-06',
                'unit_id'                       => $techUnitId,
                'total_agents_headcount'        => 3,
                'gross_capacity_hours'          => 120.00,
                'scheduled_capacity_hours'      => 120.00,
                'reserved_capacity_hours'       => 28.00,
                'projected_demand_hours'        => 95.00,
                'net_available_capacity_hours'  => -3.00,
                'utilization_projected_percent' => 102.50,
                'status'                        => 'deficit',
                'forecast_assumptions_json'     => json_encode(['headcount' => 3, 'bottleneck_detected' => true]),
                'is_forecast_data'              => 1,
                'created_at'                    => $now,
            ],
            [
                'snapshot_code'                 => 'FCS-2026-W50',
                'period_type'                   => 'weekly',
                'period_start_date'             => '2026-12-07',
                'period_end_date'               => '2026-12-13',
                'unit_id'                       => $techUnitId,
                'total_agents_headcount'        => 3,
                'gross_capacity_hours'          => 120.00,
                'scheduled_capacity_hours'      => 120.00,
                'reserved_capacity_hours'       => 28.00,
                'projected_demand_hours'        => 65.00,
                'net_available_capacity_hours'  => 27.00,
                'utilization_projected_percent' => 77.50,
                'status'                        => 'balanced',
                'forecast_assumptions_json'     => json_encode(['headcount' => 3, 'post_release_normalization' => true]),
                'is_forecast_data'              => 1,
                'created_at'                    => $now,
            ]
        ];

        foreach ($forecastWeeks as $fw) {
            $db->table('capacity_forecast_snapshots')->insert($fw);
        }

        // 8. Seed Capacity Gap Analyses
        $gaps = [
            [
                'gap_code'                 => 'GAP-2026-001',
                'unit_id'                  => $techUnitId,
                'skill_id'                 => $skillTsId,
                'gap_category'             => 'concurrency_bottleneck',
                'severity'                 => 'high',
                'required_capacity_hours'  => 45.00,
                'available_capacity_hours' => 20.00,
                'deficit_hours'            => 25.00,
                'impacted_projects_json'   => json_encode(['PRJ-2026-001', 'PRJ-2026-002']),
                'recommended_action'       => 'train_existing_agent',
                'action_plan_description'  => 'Upskill Agent QA in frontend component verification to offload basic TypeScript review tasks from Agent DEV.',
                'status'                   => 'action_initiated',
                'created_at'               => $now,
                'updated_at'               => $now,
            ],
            [
                'gap_code'                 => 'GAP-2026-002',
                'unit_id'                  => $techUnitId,
                'skill_id'                 => $skillAiId,
                'gap_category'             => 'skill_shortage',
                'severity'                 => 'medium',
                'required_capacity_hours'  => 30.00,
                'available_capacity_hours' => 15.00,
                'deficit_hours'            => 15.00,
                'impacted_projects_json'   => json_encode(['PRJ-2026-002']),
                'recommended_action'       => 'shift_reallocation',
                'action_plan_description'  => 'Adjust duty roster to align Agent TECH shift schedules with Hermes benchmarking execution windows.',
                'status'                   => 'identified',
                'created_at'               => $now,
                'updated_at'               => $now,
            ]
        ];

        foreach ($gaps as $gap) {
            $db->table('capacity_gap_analyses')->insert($gap);
        }

        // 9. Seed Resource Audit Events
        $auditEvents = [
            [
                'event_type'    => 'resource_created',
                'actor_type'    => 'user',
                'actor_id'      => $userId,
                'actor_name'    => 'Human Owner / Lead Architect',
                'target_type'   => 'resource',
                'target_id'     => $resMap['RES-9ROUTER-MAIN'] ?? 1,
                'metadata_json' => json_encode(['code' => 'RES-9ROUTER-MAIN', 'capacity' => 10000000]),
                'ip_address'    => '127.0.0.1',
                'created_at'    => $now,
            ],
            [
                'event_type'    => 'reservation_activated',
                'actor_type'    => 'system',
                'actor_id'      => null,
                'actor_name'    => 'Continuous Executive Board',
                'target_type'   => 'reservation',
                'target_id'     => 1,
                'metadata_json' => json_encode(['reservation_code' => 'RSV-2026-INT-OPS', 'reserved_pct' => 20]),
                'ip_address'    => '127.0.0.1',
                'created_at'    => $now,
            ],
            [
                'event_type'    => 'allocation_approved',
                'actor_type'    => 'user',
                'actor_id'      => $userId,
                'actor_name'    => 'Human Owner / Lead Architect',
                'target_type'   => 'allocation',
                'target_id'     => $alcMap['ALC-2026-001'] ?? 1,
                'metadata_json' => json_encode(['allocation_code' => 'ALC-2026-001', 'hours_per_week' => 20]),
                'ip_address'    => '127.0.0.1',
                'created_at'    => $now,
            ],
            [
                'event_type'    => 'conflict_detected',
                'actor_type'    => 'system',
                'actor_id'      => null,
                'actor_name'    => 'Capacity Conflict Engine',
                'target_type'   => 'conflict',
                'target_id'     => 1,
                'metadata_json' => json_encode(['conflict_code' => 'CNF-2026-001', 'type' => 'overallocation']),
                'ip_address'    => '127.0.0.1',
                'created_at'    => $now,
            ]
        ];

        foreach ($auditEvents as $ae) {
            $db->table('resource_audit_events')->insert($ae);
        }
    }
}
