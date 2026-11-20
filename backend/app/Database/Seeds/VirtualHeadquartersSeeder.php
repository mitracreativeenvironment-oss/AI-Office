<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class VirtualHeadquartersSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        // Fetch organization units map
        $units = [];
        $unitRows = $db->table('organization_units')->get()->getResultArray();
        foreach ($unitRows as $u) {
            $units[$u['code']] = (int)$u['id'];
        }

        // Fetch agents map
        $agents = [];
        $agentRows = $db->table('ai_agent_registry')->get()->getResultArray();
        foreach ($agentRows as $a) {
            $agents[$a['agent_code']] = (int)$a['id'];
        }

        // 1. Seed HQ Buildings
        $buildingsData = [
            [
                'building_code' => 'BLD-MAIN',
                'name'          => 'MCE Main Campus & Executive Tower',
                'description'   => 'Headquarters core hosting the Main Reception, Continuous Executive Boardroom, Director AI Suite, and Central Operations Monitoring.',
                'campus_zone'   => 'executive_core',
                'building_type' => 'tower',
                'pos_x'         => 0.0,
                'pos_y'         => 0.0,
                'pos_z'         => -10.0,
                'width'         => 24.0,
                'depth'         => 18.0,
                'height'        => 14.0,
                'theme_color'   => '#0f172a',
                'accent_color'  => '#38bdf8',
                'total_floors'  => 2,
                'is_active'     => 1,
                'sort_order'    => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'building_code' => 'BLD-DIV-TECH',
                'name'          => 'Technology & AI Solutions Pavilion',
                'description'   => 'Home of DIV-TECH and DIV-DEV. Houses core software engineering, agentic architecture workspaces, and code generation pods.',
                'campus_zone'   => 'technology_wing',
                'building_type' => 'pavilion',
                'pos_x'         => -28.0,
                'pos_y'         => 0.0,
                'pos_z'         => 6.0,
                'width'         => 20.0,
                'depth'         => 16.0,
                'height'        => 8.0,
                'theme_color'   => '#1e1b4b',
                'accent_color'  => '#6366f1',
                'total_floors'  => 1,
                'is_active'     => 1,
                'sort_order'    => 2,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'building_code' => 'BLD-DIV-SRV',
                'name'          => 'Creative & Enterprise Services Pavilion',
                'description'   => 'Operational center for DIV-SRV. Houses digital solutions, business automation, and media design collaborative workstations.',
                'campus_zone'   => 'services_pavilion',
                'building_type' => 'pavilion',
                'pos_x'         => 28.0,
                'pos_y'         => 0.0,
                'pos_z'         => 6.0,
                'width'         => 20.0,
                'depth'         => 16.0,
                'height'        => 8.0,
                'theme_color'   => '#064e3b',
                'accent_color'  => '#10b981',
                'total_floors'  => 1,
                'is_active'     => 1,
                'sort_order'    => 3,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'building_code' => 'BLD-DIV-ACAD',
                'name'          => 'Training & Academy Innovation Center',
                'description'   => 'Center for DIV-ACAD. Houses agent curriculum engineering, benchmark testing, and workforce skill evaluation arenas.',
                'campus_zone'   => 'academy_center',
                'building_type' => 'hall',
                'pos_x'         => -28.0,
                'pos_y'         => 0.0,
                'pos_z'         => -26.0,
                'width'         => 18.0,
                'depth'         => 15.0,
                'height'        => 7.5,
                'theme_color'   => '#451a03',
                'accent_color'  => '#f59e0b',
                'total_floors'  => 1,
                'is_active'     => 1,
                'sort_order'    => 4,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'building_code' => 'BLD-DIV-HW',
                'name'          => 'Advanced Hardware & Infrastructure Facility',
                'description'   => 'Center for DIV-HW. Houses server telemetry monitoring, edge node validation, and physical/virtualized GPU cluster management.',
                'campus_zone'   => 'hardware_labs',
                'building_type' => 'facility',
                'pos_x'         => 28.0,
                'pos_y'         => 0.0,
                'pos_z'         => -26.0,
                'width'         => 18.0,
                'depth'         => 15.0,
                'height'        => 7.5,
                'theme_color'   => '#3f3f46',
                'accent_color'  => '#ef4444',
                'total_floors'  => 1,
                'is_active'     => 1,
                'sort_order'    => 5,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'building_code' => 'BLD-WELFARE',
                'name'          => 'MCE AI Agent Welfare Commons',
                'description'   => 'Mandatory cross-division facility dedicated to AI workforce rest scheduling, quiet operational recovery, and knowledge enrichment.',
                'campus_zone'   => 'welfare_commons',
                'building_type' => 'welfare_center',
                'pos_x'         => 0.0,
                'pos_y'         => 0.0,
                'pos_z'         => 24.0,
                'width'         => 22.0,
                'depth'         => 16.0,
                'height'        => 7.0,
                'theme_color'   => '#14532d',
                'accent_color'  => '#34d399',
                'total_floors'  => 1,
                'is_active'     => 1,
                'sort_order'    => 6,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ];

        $buildingMap = [];
        foreach ($buildingsData as $b) {
            $existing = $db->table('hq_buildings')->where('building_code', $b['building_code'])->get()->getRowArray();
            if (!$existing) {
                $db->table('hq_buildings')->insert($b);
                $buildingMap[$b['building_code']] = (int)$db->insertID();
            } else {
                $buildingMap[$b['building_code']] = (int)$existing['id'];
            }
        }

        // 2. Seed HQ Floors
        $floorsData = [
            ['building_id' => $buildingMap['BLD-MAIN'], 'floor_number' => 1, 'floor_code' => 'FL-MAIN-01', 'name' => 'Ground Floor: Atrium & Operations', 'elevation_y' => 0.0],
            ['building_id' => $buildingMap['BLD-MAIN'], 'floor_number' => 2, 'floor_code' => 'FL-MAIN-02', 'name' => 'Executive Level: Boardroom & Governance', 'elevation_y' => 5.5],
            ['building_id' => $buildingMap['BLD-DIV-TECH'], 'floor_number' => 1, 'floor_code' => 'FL-TECH-01', 'name' => 'Technology Workspace Floor', 'elevation_y' => 0.0],
            ['building_id' => $buildingMap['BLD-DIV-SRV'], 'floor_number' => 1, 'floor_code' => 'FL-SRV-01', 'name' => 'Services Studio Floor', 'elevation_y' => 0.0],
            ['building_id' => $buildingMap['BLD-DIV-ACAD'], 'floor_number' => 1, 'floor_code' => 'FL-ACAD-01', 'name' => 'Academy Lab Floor', 'elevation_y' => 0.0],
            ['building_id' => $buildingMap['BLD-DIV-HW'], 'floor_number' => 1, 'floor_code' => 'FL-HW-01', 'name' => 'Hardware Telemetry Lab Floor', 'elevation_y' => 0.0],
            ['building_id' => $buildingMap['BLD-WELFARE'], 'floor_number' => 1, 'floor_code' => 'FL-WLF-01', 'name' => 'Welfare Commons Main Floor', 'elevation_y' => 0.0],
        ];

        $floorMap = [];
        foreach ($floorsData as $f) {
            $existing = $db->table('hq_floors')
                ->where('building_id', $f['building_id'])
                ->where('floor_number', $f['floor_number'])
                ->get()
                ->getRowArray();
            if (!$existing) {
                $db->table('hq_floors')->insert(array_merge($f, ['width' => 20.0, 'depth' => 16.0, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]));
                $floorMap[$f['floor_code']] = (int)$db->insertID();
            } else {
                $floorMap[$f['floor_code']] = (int)$existing['id'];
            }
        }

        // 3. Seed HQ Rooms & Functional Zones
        $roomsData = [
            // Main Building Ground Floor
            [
                'building_id'  => $buildingMap['BLD-MAIN'],
                'floor_id'     => $floorMap['FL-MAIN-01'],
                'unit_id'      => $units['ORG-MCE'] ?? null,
                'room_code'    => 'ROOM-MAIN-LOBBY',
                'name'         => 'Main Reception & Welcome Atrium',
                'category'     => 'reception',
                'description'  => 'Central arrival point for visitors and workforce members. Features live company profile and campus directory.',
                'pos_x'        => -5.0,
                'pos_y'        => 0.0,
                'pos_z'        => -5.0,
                'width'        => 10.0,
                'depth'        => 8.0,
                'height'       => 4.5,
                'floor_color'  => '#1e293b',
                'accent_color' => '#38bdf8',
                'max_capacity' => 12,
            ],
            [
                'building_id'  => $buildingMap['BLD-MAIN'],
                'floor_id'     => $floorMap['FL-MAIN-01'],
                'unit_id'      => $units['DIV-TECH'] ?? null,
                'room_code'    => 'ROOM-MAIN-MONITOR',
                'name'         => 'Central Operations & Telemetry Center',
                'category'     => 'monitoring',
                'description'  => 'Live telemetry screens for Hermes runtime nodes, 9Router gateway traffic, and agent operational status.',
                'pos_x'        => 6.0,
                'pos_y'        => 0.0,
                'pos_z'        => -5.0,
                'width'        => 9.0,
                'depth'        => 8.0,
                'height'       => 4.5,
                'floor_color'  => '#0f172a',
                'accent_color' => '#10b981',
                'max_capacity' => 8,
            ],
            // Main Building Executive Floor
            [
                'building_id'  => $buildingMap['BLD-MAIN'],
                'floor_id'     => $floorMap['FL-MAIN-02'],
                'unit_id'      => $units['GOV-BOARD'] ?? ($units['ORG-MCE'] ?? null),
                'room_code'    => 'ROOM-MAIN-BOARD',
                'name'         => 'Continuous Executive Boardroom',
                'category'     => 'executive',
                'description'  => 'Deliberation suite for Human Owner and Executive Board. Used for formal DoA ratifications and strategic planning.',
                'pos_x'        => -5.0,
                'pos_y'        => 5.5,
                'pos_z'        => -5.0,
                'width'        => 10.0,
                'depth'        => 8.0,
                'height'       => 4.5,
                'floor_color'  => '#1e1b4b',
                'accent_color' => '#818cf8',
                'max_capacity' => 10,
            ],
            [
                'building_id'  => $buildingMap['BLD-MAIN'],
                'floor_id'     => $floorMap['FL-MAIN-02'],
                'unit_id'      => $units['ORG-MCE'] ?? null,
                'room_code'    => 'ROOM-DIR-SUITE',
                'name'         => 'Director AI Executive Suite',
                'category'     => 'executive',
                'description'  => 'Dedicated operational suite for AGT-DIR-001. Equipped with executive policy terminals and corporate communication feeds.',
                'pos_x'        => 6.0,
                'pos_y'        => 5.5,
                'pos_z'        => -5.0,
                'width'        => 9.0,
                'depth'        => 8.0,
                'height'       => 4.5,
                'floor_color'  => '#1e293b',
                'accent_color' => '#38bdf8',
                'max_capacity' => 4,
            ],
            // Technology Pavilion
            [
                'building_id'  => $buildingMap['BLD-DIV-TECH'],
                'floor_id'     => $floorMap['FL-TECH-01'],
                'unit_id'      => $units['DIV-TECH'] ?? null,
                'room_code'    => 'ROOM-TECH-DEV',
                'name'         => 'Core Software Engineering Lab',
                'category'     => 'workspace',
                'description'  => 'Primary engineering workspace for Tech Head AI and Dev AI. High-performance dual-monitor coding stations.',
                'pos_x'        => -28.0,
                'pos_y'        => 0.0,
                'pos_z'        => 6.0,
                'width'        => 16.0,
                'depth'        => 12.0,
                'height'       => 4.0,
                'floor_color'  => '#1e1b4b',
                'accent_color' => '#6366f1',
                'max_capacity' => 8,
            ],
            // Services Pavilion
            [
                'building_id'  => $buildingMap['BLD-DIV-SRV'],
                'floor_id'     => $floorMap['FL-SRV-01'],
                'unit_id'      => $units['DIV-SRV'] ?? null,
                'room_code'    => 'ROOM-SRV-STUDIO',
                'name'         => 'Creative & Enterprise Solutions Studio',
                'category'     => 'workspace',
                'description'  => 'Workspace for Services Head AI. Layout configured for client project delivery, workflow design, and document automation.',
                'pos_x'        => 28.0,
                'pos_y'        => 0.0,
                'pos_z'        => 6.0,
                'width'        => 16.0,
                'depth'        => 12.0,
                'height'       => 4.0,
                'floor_color'  => '#064e3b',
                'accent_color' => '#10b981',
                'max_capacity' => 6,
            ],
            // Academy Center
            [
                'building_id'  => $buildingMap['BLD-DIV-ACAD'],
                'floor_id'     => $floorMap['FL-ACAD-01'],
                'unit_id'      => $units['DIV-ACAD'] ?? null,
                'room_code'    => 'ROOM-ACAD-HALL',
                'name'         => 'Workforce Training & Benchmark Arena',
                'category'     => 'workspace',
                'description'  => 'Workspace for Academy Head AI. Designed for model evaluation, curriculum generation, and skill proficiency audits.',
                'pos_x'        => -28.0,
                'pos_y'        => 0.0,
                'pos_z'        => -26.0,
                'width'        => 14.0,
                'depth'        => 12.0,
                'height'       => 4.0,
                'floor_color'  => '#451a03',
                'accent_color' => '#f59e0b',
                'max_capacity' => 6,
            ],
            // Hardware Labs
            [
                'building_id'  => $buildingMap['BLD-DIV-HW'],
                'floor_id'     => $floorMap['FL-HW-01'],
                'unit_id'      => $units['DIV-HW'] ?? null,
                'room_code'    => 'ROOM-HW-LAB',
                'name'         => 'GPU Infrastructure & Edge Testing Lab',
                'category'     => 'workspace',
                'description'  => 'Workspace for Hardware Head AI. Equipped with physical/virtualized hardware health diagnostics and telemetry feeds.',
                'pos_x'        => 28.0,
                'pos_y'        => 0.0,
                'pos_z'        => -26.0,
                'width'        => 14.0,
                'depth'        => 12.0,
                'height'       => 4.0,
                'floor_color'  => '#3f3f46',
                'accent_color' => '#ef4444',
                'max_capacity' => 6,
            ],
            // Agent Welfare Building Rooms
            [
                'building_id'  => $buildingMap['BLD-WELFARE'],
                'floor_id'     => $floorMap['FL-WLF-01'],
                'unit_id'      => $units['ORG-MCE'] ?? null,
                'room_code'    => 'ROOM-WLF-REST',
                'name'         => 'Workforce Recovery & Quiet Zone',
                'category'     => 'welfare_rest',
                'description'  => 'Mandatory cross-division recovery environment. Features low-energy standby pods and zero-task scheduling zones.',
                'pos_x'        => -4.5,
                'pos_y'        => 0.0,
                'pos_z'        => 24.0,
                'width'        => 9.0,
                'depth'        => 12.0,
                'height'       => 4.0,
                'floor_color'  => '#14532d',
                'accent_color' => '#34d399',
                'max_capacity' => 10,
            ],
            [
                'building_id'  => $buildingMap['BLD-WELFARE'],
                'floor_id'     => $floorMap['FL-WLF-01'],
                'unit_id'      => $units['ORG-MCE'] ?? null,
                'room_code'    => 'ROOM-WLF-LEARN',
                'name'         => 'Knowledge Enrichment & Capability Lounge',
                'category'     => 'welfare_learning',
                'description'  => 'Self-guided capability enhancement area where off-duty agents can index reference literature and benchmark historical tasks.',
                'pos_x'        => 5.5,
                'pos_y'        => 0.0,
                'pos_z'        => 24.0,
                'width'        => 9.0,
                'depth'        => 12.0,
                'height'       => 4.0,
                'floor_color'  => '#064e3b',
                'accent_color' => '#6ee7b7',
                'max_capacity' => 8,
            ],
        ];

        $roomMap = [];
        foreach ($roomsData as $r) {
            $existing = $db->table('hq_rooms')->where('room_code', $r['room_code'])->get()->getRowArray();
            if (!$existing) {
                $db->table('hq_rooms')->insert(array_merge($r, ['is_active' => 1, 'created_at' => $now, 'updated_at' => $now]));
                $roomMap[$r['room_code']] = (int)$db->insertID();
            } else {
                $roomMap[$r['room_code']] = (int)$existing['id'];
            }
        }

        // 4. Seed HQ Workstations (Linked to Verified Workforce Agents)
        $workstationsData = [
            [
                'room_id'           => $roomMap['ROOM-DIR-SUITE'],
                'workstation_code'  => 'WS-DIR-01',
                'label'             => 'Director AI Executive Terminal',
                'assigned_agent_id' => $agents['AGT-DIR-001'] ?? null,
                'pos_x'             => 6.0,
                'pos_y'             => 5.5,
                'pos_z'             => -5.0,
                'rotation_y'        => 0.0,
                'equipment_type'    => 'executive_desk',
            ],
            [
                'room_id'           => $roomMap['ROOM-TECH-DEV'],
                'workstation_code'  => 'WS-TECH-LEAD',
                'label'             => 'Technology Head Lead Desk',
                'assigned_agent_id' => $agents['AGT-TECH-001'] ?? null,
                'pos_x'             => -30.0,
                'pos_y'             => 0.0,
                'pos_z'             => 4.0,
                'rotation_y'        => 0.0,
                'equipment_type'    => 'dual_monitor_station',
            ],
            [
                'room_id'           => $roomMap['ROOM-TECH-DEV'],
                'workstation_code'  => 'WS-TECH-DEV01',
                'label'             => 'Full-Stack Developer Station',
                'assigned_agent_id' => $agents['AGT-DEV-001'] ?? null,
                'pos_x'             => -26.0,
                'pos_y'             => 0.0,
                'pos_z'             => 8.0,
                'rotation_y'        => 0.0,
                'equipment_type'    => 'dual_monitor_station',
            ],
            [
                'room_id'           => $roomMap['ROOM-SRV-STUDIO'],
                'workstation_code'  => 'WS-SRV-LEAD',
                'label'             => 'Services Head Operations Desk',
                'assigned_agent_id' => $agents['AGT-SRV-001'] ?? null,
                'pos_x'             => 28.0,
                'pos_y'             => 0.0,
                'pos_z'             => 6.0,
                'rotation_y'        => 0.0,
                'equipment_type'    => 'standard_desk',
            ],
            [
                'room_id'           => $roomMap['ROOM-ACAD-HALL'],
                'workstation_code'  => 'WS-ACAD-LEAD',
                'label'             => 'Academy Head Curriculum Terminal',
                'assigned_agent_id' => $agents['AGT-ACD-001'] ?? ($agents['AGT-ACAD-001'] ?? null),
                'pos_x'             => -28.0,
                'pos_y'             => 0.0,
                'pos_z'             => -26.0,
                'rotation_y'        => 0.0,
                'equipment_type'    => 'standard_desk',
            ],
            [
                'room_id'           => $roomMap['ROOM-HW-LAB'],
                'workstation_code'  => 'WS-HW-LEAD',
                'label'             => 'Hardware Head Telemetry Console',
                'assigned_agent_id' => $agents['AGT-HDW-001'] ?? ($agents['AGT-HW-001'] ?? null),
                'pos_x'             => 28.0,
                'pos_y'             => 0.0,
                'pos_z'             => -26.0,
                'rotation_y'        => 0.0,
                'equipment_type'    => 'monitoring_console',
            ],
            // Welfare recovery pods
            [
                'room_id'           => $roomMap['ROOM-WLF-REST'],
                'workstation_code'  => 'WS-WLF-POD01',
                'label'             => 'Workforce Standby Recovery Pod Alpha',
                'assigned_agent_id' => null,
                'pos_x'             => -5.0,
                'pos_y'             => 0.0,
                'pos_z'             => 22.0,
                'rotation_y'        => 1.57,
                'equipment_type'    => 'welfare_pod',
            ],
            [
                'room_id'           => $roomMap['ROOM-WLF-REST'],
                'workstation_code'  => 'WS-WLF-POD02',
                'label'             => 'Workforce Standby Recovery Pod Beta',
                'assigned_agent_id' => null,
                'pos_x'             => -5.0,
                'pos_y'             => 0.0,
                'pos_z'             => 26.0,
                'rotation_y'        => 1.57,
                'equipment_type'    => 'welfare_pod',
            ],
        ];

        foreach ($workstationsData as $ws) {
            $existing = $db->table('hq_workstations')->where('workstation_code', $ws['workstation_code'])->get()->getRowArray();
            if (!$existing) {
                $db->table('hq_workstations')->insert(array_merge($ws, ['is_active' => 1, 'created_at' => $now, 'updated_at' => $now]));
            } else if ($existing['assigned_agent_id'] === null && $ws['assigned_agent_id'] !== null) {
                $db->table('hq_workstations')->where('id', $existing['id'])->update(['assigned_agent_id' => $ws['assigned_agent_id'], 'updated_at' => $now]);
            }
        }

        // 5. Seed HQ Camera Navigation Bookmarks
        $bookmarksData = [
            [
                'bookmark_code' => 'BM-CAMPUS',
                'title'         => 'Campus Bird\'s-Eye Overview',
                'category'      => 'overview',
                'camera_pos_x'  => 0.0,
                'camera_pos_y'  => 45.0,
                'camera_pos_z'  => 55.0,
                'look_at_x'     => 0.0,
                'look_at_y'     => 0.0,
                'look_at_z'     => -2.0,
                'is_default'    => 1,
                'sort_order'    => 1,
            ],
            [
                'bookmark_code' => 'BM-LOBBY',
                'title'         => 'Main Reception & Atrium',
                'category'      => 'room',
                'camera_pos_x'  => -5.0,
                'camera_pos_y'  => 6.0,
                'camera_pos_z'  => 6.0,
                'look_at_x'     => -5.0,
                'look_at_y'     => 1.5,
                'look_at_z'     => -5.0,
                'is_default'    => 0,
                'sort_order'    => 2,
            ],
            [
                'bookmark_code' => 'BM-EXEC',
                'title'         => 'Executive Boardroom & Director Suite',
                'category'      => 'building',
                'camera_pos_x'  => 0.0,
                'camera_pos_y'  => 14.0,
                'camera_pos_z'  => 8.0,
                'look_at_x'     => 0.0,
                'look_at_y'     => 6.0,
                'look_at_z'     => -5.0,
                'is_default'    => 0,
                'sort_order'    => 3,
            ],
            [
                'bookmark_code' => 'BM-TECH',
                'title'         => 'Technology & Dev AI Workspace',
                'category'      => 'building',
                'camera_pos_x'  => -28.0,
                'camera_pos_y'  => 12.0,
                'camera_pos_z'  => 22.0,
                'look_at_x'     => -28.0,
                'look_at_y'     => 1.5,
                'look_at_z'     => 6.0,
                'is_default'    => 0,
                'sort_order'    => 4,
            ],
            [
                'bookmark_code' => 'BM-WELFARE',
                'title'         => 'AI Agent Welfare Commons',
                'category'      => 'facility',
                'camera_pos_x'  => 0.0,
                'camera_pos_y'  => 14.0,
                'camera_pos_z'  => 40.0,
                'look_at_x'     => 0.0,
                'look_at_y'     => 1.5,
                'look_at_z'     => 24.0,
                'is_default'    => 0,
                'sort_order'    => 5,
            ],
            [
                'bookmark_code' => 'BM-HW',
                'title'         => 'Hardware Labs & Telemetry',
                'category'      => 'building',
                'camera_pos_x'  => 28.0,
                'camera_pos_y'  => 12.0,
                'camera_pos_z'  => -10.0,
                'look_at_x'     => 28.0,
                'look_at_y'     => 1.5,
                'look_at_z'     => -26.0,
                'is_default'    => 0,
                'sort_order'    => 6,
            ],
        ];

        foreach ($bookmarksData as $bm) {
            $existing = $db->table('hq_camera_bookmarks')->where('bookmark_code', $bm['bookmark_code'])->get()->getRowArray();
            if (!$existing) {
                $db->table('hq_camera_bookmarks')->insert(array_merge($bm, ['created_at' => $now, 'updated_at' => $now]));
            }
        }
    }
}
