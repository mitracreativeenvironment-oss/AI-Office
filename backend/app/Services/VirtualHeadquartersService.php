<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class VirtualHeadquartersService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Get Campus-wide overview including all buildings, total capacities, and occupancy.
     */
    public function getCampusOverview(): array
    {
        $buildings = $this->db->table('hq_buildings')
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getResultArray();

        $totalRooms = 0;
        $totalWorkstations = 0;
        $occupiedWorkstations = 0;

        foreach ($buildings as &$b) {
            $b['id'] = (int)$b['id'];
            $b['pos_x'] = (float)$b['pos_x'];
            $b['pos_y'] = (float)$b['pos_y'];
            $b['pos_z'] = (float)$b['pos_z'];
            $b['width'] = (float)$b['width'];
            $b['depth'] = (float)$b['depth'];
            $b['height'] = (float)$b['height'];
            $b['total_floors'] = (int)$b['total_floors'];

            $roomCount = $this->db->table('hq_rooms')
                ->where('building_id', $b['id'])
                ->where('is_active', 1)
                ->countAllResults();

            $workstationCount = $this->db->table('hq_workstations ws')
                ->join('hq_rooms r', 'r.id = ws.room_id')
                ->where('r.building_id', $b['id'])
                ->where('ws.is_active', 1)
                ->countAllResults();

            $occupiedCount = $this->db->table('hq_workstations ws')
                ->join('hq_rooms r', 'r.id = ws.room_id')
                ->where('r.building_id', $b['id'])
                ->where('ws.is_active', 1)
                ->where('ws.assigned_agent_id IS NOT NULL', null, false)
                ->countAllResults();

            $b['rooms_count'] = $roomCount;
            $b['workstations_count'] = $workstationCount;
            $b['occupied_workstations_count'] = $occupiedCount;

            $totalRooms += $roomCount;
            $totalWorkstations += $workstationCount;
            $occupiedWorkstations += $occupiedCount;
        }

        $totalActiveAgents = $this->db->table('ai_agent_registry')
            ->where('lifecycle_state', 'active')
            ->countAllResults();

        return [
            'campus_name' => 'MCE AI Office Virtual Headquarters',
            'tagline'     => 'Mitra Creative Environment — AI Workforce Spatial Operations',
            'summary'     => [
                'total_buildings'       => count($buildings),
                'total_rooms'           => $totalRooms,
                'total_workstations'    => $totalWorkstations,
                'occupied_workstations' => $occupiedWorkstations,
                'total_active_agents'   => $totalActiveAgents,
                'unplaced_agents_count' => max(0, $totalActiveAgents - $occupiedWorkstations),
            ],
            'buildings'   => $buildings,
        ];
    }

    /**
     * Get specific building details with floors, rooms, and workstations.
     */
    public function getBuildingDetail(int $buildingId): ?array
    {
        $building = $this->db->table('hq_buildings')->where('id', $buildingId)->get()->getRowArray();
        if (!$building) {
            return null;
        }

        $building['id'] = (int)$building['id'];
        $building['pos_x'] = (float)$building['pos_x'];
        $building['pos_y'] = (float)$building['pos_y'];
        $building['pos_z'] = (float)$building['pos_z'];
        $building['width'] = (float)$building['width'];
        $building['depth'] = (float)$building['depth'];
        $building['height'] = (float)$building['height'];

        $floors = $this->db->table('hq_floors')
            ->where('building_id', $buildingId)
            ->where('is_active', 1)
            ->orderBy('floor_number', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($floors as &$fl) {
            $fl['id'] = (int)$fl['id'];
            $fl['floor_number'] = (int)$fl['floor_number'];
            $fl['elevation_y'] = (float)$fl['elevation_y'];

            $rooms = $this->db->table('hq_rooms r')
                ->select('r.*, u.name as unit_name, u.code as unit_code')
                ->join('organization_units u', 'u.id = r.unit_id', 'left')
                ->where('r.floor_id', $fl['id'])
                ->where('r.is_active', 1)
                ->get()
                ->getResultArray();

            foreach ($rooms as &$rm) {
                $rm['id'] = (int)$rm['id'];
                $rm['pos_x'] = (float)$rm['pos_x'];
                $rm['pos_y'] = (float)$rm['pos_y'];
                $rm['pos_z'] = (float)$rm['pos_z'];
                $rm['width'] = (float)$rm['width'];
                $rm['depth'] = (float)$rm['depth'];
                $rm['height'] = (float)$rm['height'];
                $rm['max_capacity'] = (int)$rm['max_capacity'];

                // Workstations in this room
                $wsList = $this->db->table('hq_workstations ws')
                    ->select('ws.*, ag.name as agent_name, ag.agent_code, ag.classification as agent_classification')
                    ->join('ai_agent_registry ag', 'ag.id = ws.assigned_agent_id', 'left')
                    ->where('ws.room_id', $rm['id'])
                    ->where('ws.is_active', 1)
                    ->get()
                    ->getResultArray();

                foreach ($wsList as &$ws) {
                    $ws['id'] = (int)$ws['id'];
                    $ws['pos_x'] = (float)$ws['pos_x'];
                    $ws['pos_y'] = (float)$ws['pos_y'];
                    $ws['pos_z'] = (float)$ws['pos_z'];
                    $ws['rotation_y'] = (float)$ws['rotation_y'];
                    if (!empty($ws['assigned_agent_id'])) {
                        $presence = $this->getAgentPresence((int)$ws['assigned_agent_id']);
                        $ws['presence_status'] = $presence['presence_status'];
                        $ws['active_task'] = $presence['active_task'];
                    } else {
                        $ws['presence_status'] = 'vacant';
                        $ws['active_task'] = null;
                    }
                }
                $rm['workstations'] = $wsList;
            }
            $fl['rooms'] = $rooms;
        }

        $building['floors'] = $floors;
        return $building;
    }

    /**
     * List rooms with optional filters.
     */
    public function listRooms(array $filters = []): array
    {
        $builder = $this->db->table('hq_rooms r')
            ->select('r.*, b.name as building_name, b.building_code, b.campus_zone, u.name as unit_name, u.code as unit_code')
            ->join('hq_buildings b', 'b.id = r.building_id')
            ->join('organization_units u', 'u.id = r.unit_id', 'left')
            ->where('r.is_active', 1);

        if (!empty($filters['building_id'])) {
            $builder->where('r.building_id', (int)$filters['building_id']);
        }
        if (!empty($filters['category'])) {
            $builder->where('r.category', $filters['category']);
        }
        if (!empty($filters['unit_id'])) {
            $builder->where('r.unit_id', (int)$filters['unit_id']);
        }

        $rooms = $builder->orderBy('b.sort_order', 'ASC')->orderBy('r.id', 'ASC')->get()->getResultArray();
        foreach ($rooms as &$r) {
            $r['id'] = (int)$r['id'];
            $r['pos_x'] = (float)$r['pos_x'];
            $r['pos_y'] = (float)$r['pos_y'];
            $r['pos_z'] = (float)$r['pos_z'];
            $r['width'] = (float)$r['width'];
            $r['depth'] = (float)$r['depth'];
            $r['height'] = (float)$r['height'];
            $r['max_capacity'] = (int)$r['max_capacity'];
        }

        return $rooms;
    }

    /**
     * List workstations with assigned agents and resolved operational presence.
     */
    public function listWorkstations(array $filters = []): array
    {
        $builder = $this->db->table('hq_workstations ws')
            ->select('ws.*, r.room_code, r.name as room_name, r.category as room_category, b.id as building_id, b.name as building_name, b.building_code,
                      ag.name as agent_name, ag.agent_code, ag.classification as agent_classification')
            ->join('hq_rooms r', 'r.id = ws.room_id')
            ->join('hq_buildings b', 'b.id = r.building_id')
            ->join('ai_agent_registry ag', 'ag.id = ws.assigned_agent_id', 'left')
            ->where('ws.is_active', 1);

        if (!empty($filters['building_id'])) {
            $builder->where('b.id', (int)$filters['building_id']);
        }
        if (!empty($filters['room_id'])) {
            $builder->where('ws.room_id', (int)$filters['room_id']);
        }
        if (isset($filters['is_occupied'])) {
            if ($filters['is_occupied']) {
                $builder->where('ws.assigned_agent_id IS NOT NULL', null, false);
            } else {
                $builder->where('ws.assigned_agent_id IS NULL', null, false);
            }
        }

        $workstations = $builder->orderBy('ws.id', 'ASC')->get()->getResultArray();
        foreach ($workstations as &$ws) {
            $ws['id'] = (int)$ws['id'];
            $ws['pos_x'] = (float)$ws['pos_x'];
            $ws['pos_y'] = (float)$ws['pos_y'];
            $ws['pos_z'] = (float)$ws['pos_z'];
            $ws['rotation_y'] = (float)$ws['rotation_y'];

            if (!empty($ws['assigned_agent_id'])) {
                $presence = $this->getAgentPresence((int)$ws['assigned_agent_id']);
                $ws['presence_status'] = $presence['presence_status'];
                $ws['active_task'] = $presence['active_task'];
            } else {
                $ws['presence_status'] = 'vacant';
                $ws['active_task'] = null;
            }
        }

        return $workstations;
    }

    /**
     * Real-time Agent Presence calculation:
     * Discerning availability, 5/2 shift schedule, active tasks, and runtime mapping.
     */
    public function getAgentPresence(int $agentId): array
    {
        $agent = $this->db->table('ai_agent_registry ag')
            ->select('ag.*, u.name as unit_name, u.code as unit_code, pos.title as position_title')
            ->join('organization_units u', 'u.id = ag.primary_unit_id', 'left')
            ->join('organization_positions pos', 'pos.id = ag.primary_position_id', 'left')
            ->where('ag.id', $agentId)
            ->get()
            ->getRowArray();

        if (!$agent) {
            throw new \InvalidArgumentException("Agent #{$agentId} not found.", 404);
        }

        // 1. Spatial Placement lookup
        $ws = $this->db->table('hq_workstations ws')
            ->select('ws.*, r.room_code, r.name as room_name, r.category as room_category, b.id as building_id, b.building_code, b.name as building_name')
            ->join('hq_rooms r', 'r.id = ws.room_id')
            ->join('hq_buildings b', 'b.id = r.building_id')
            ->where('ws.assigned_agent_id', $agentId)
            ->where('ws.is_active', 1)
            ->get()
            ->getRowArray();

        $spatialPlacement = null;
        if ($ws) {
            $spatialPlacement = [
                'building_id'      => (int)$ws['building_id'],
                'building_code'    => $ws['building_code'],
                'building_name'    => $ws['building_name'],
                'room_id'          => (int)$ws['room_id'],
                'room_code'        => $ws['room_code'],
                'room_name'        => $ws['room_name'],
                'room_category'    => $ws['room_category'],
                'workstation_id'   => (int)$ws['id'],
                'workstation_code' => $ws['workstation_code'],
                'pos_x'            => (float)$ws['pos_x'],
                'pos_y'            => (float)$ws['pos_y'],
                'pos_z'            => (float)$ws['pos_z'],
                'rotation_y'       => (float)$ws['rotation_y'],
                'equipment_type'   => $ws['equipment_type'],
            ];
        }

        // 2. Determine presence status with high data fidelity
        $presenceStatus = 'available';
        $statusReason = 'Agent is online, active on duty, and ready for work.';

        $today = date('Y-m-d');
        $nowTime = date('Y-m-d H:i:s');

        // Check Approved Leave
        $leave = $this->db->table('workforce_agent_availability')
            ->where('agent_id', $agentId)
            ->whereIn('availability_type', ['planned_leave', 'temporary_unavailability'])
            ->where('approval_status', 'approved')
            ->where('start_time <=', $nowTime)
            ->groupStart()
                ->where('end_time >=', $nowTime)
                ->orWhere('end_time IS NULL', null, false)
            ->groupEnd()
            ->get()
            ->getRowArray();

        if ($leave) {
            $presenceStatus = 'on_leave';
            $statusReason = 'Agent is currently on approved off-duty leave: ' . ($leave['reason'] ?? '');
        } else {
            // Check 5/2 Roster Rest Day
            $duty = $this->db->table('workforce_duty_assignments')
                ->where('agent_id', $agentId)
                ->where('assignment_date', $today)
                ->get()
                ->getRowArray();

            if ($duty && $duty['duty_type'] === 'rest') {
                $presenceStatus = 'scheduled_off';
                $statusReason = 'Agent is on scheduled rest day under 5/2 staggered roster policy.';
            } else {
                // Check Active In-Progress Task
                $activeTask = $this->db->table('workforce_tasks')
                    ->where('accountable_agent_id', $agentId)
                    ->whereIn('status', ['in_progress', 'under_review'])
                    ->where('deleted_at IS NULL')
                    ->orderBy('id', 'DESC')
                    ->get()
                    ->getRowArray();

                if ($activeTask) {
                    $presenceStatus = 'working';
                    $statusReason = "Executing task '{$activeTask['task_code']}': {$activeTask['title']}";
                } else {
                    // Check Runtime Connectivity
                    $runtime = $this->db->table('ai_agent_runtime_mappings')
                        ->where('agent_id', $agentId)
                        ->get()
                        ->getRowArray();

                    if ($runtime && $runtime['integration_status'] === 'suspended') {
                        $presenceStatus = 'suspended';
                        $statusReason = 'Agent runtime execution is suspended: ' . ($runtime['suspension_reason'] ?? '');
                    } else if (!$runtime || $runtime['integration_status'] !== 'ready') {
                        $presenceStatus = 'runtime_disconnected';
                        $statusReason = 'Hermes runtime mapping pending or disconnected.';
                    }
                }
            }
        }

        // Active task details if any
        $taskSummary = null;
        if (!empty($activeTask)) {
            $taskSummary = [
                'task_id'   => (int)$activeTask['id'],
                'task_code' => $activeTask['task_code'],
                'title'     => $activeTask['title'],
                'status'    => $activeTask['status'],
                'priority'  => $activeTask['priority'],
            ];
        }

        return [
            'agent_id'              => (int)$agent['id'],
            'agent_uuid'            => $agent['agent_uuid'],
            'agent_code'            => $agent['agent_code'],
            'name'                  => $agent['name'],
            'display_name'          => $agent['display_name'],
            'classification'        => $agent['classification'],
            'lifecycle_state'       => $agent['lifecycle_state'],
            'unit_name'             => $agent['unit_name'],
            'unit_code'             => $agent['unit_code'],
            'position_title'        => $agent['position_title'],
            'presence_status'       => $presenceStatus,
            'status_reason'         => $statusReason,
            'has_spatial_placement' => ($spatialPlacement !== null),
            'placement'             => $spatialPlacement,
            'active_task'           => $taskSummary,
            'updated_at'            => $nowTime,
        ];
    }

    /**
     * List all agent presences across the entire workforce.
     */
    public function listAllAgentPresences(): array
    {
        $agents = $this->db->table('ai_agent_registry')
            ->where('lifecycle_state', 'active')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $presences = [];
        foreach ($agents as $ag) {
            $presences[] = $this->getAgentPresence((int)$ag['id']);
        }

        return $presences;
    }

    /**
     * List camera navigation bookmarks.
     */
    public function getCameraBookmarks(): array
    {
        $bookmarks = $this->db->table('hq_camera_bookmarks')
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($bookmarks as &$bm) {
            $bm['id'] = (int)$bm['id'];
            $bm['camera_pos_x'] = (float)$bm['camera_pos_x'];
            $bm['camera_pos_y'] = (float)$bm['camera_pos_y'];
            $bm['camera_pos_z'] = (float)$bm['camera_pos_z'];
            $bm['look_at_x'] = (float)$bm['look_at_x'];
            $bm['look_at_y'] = (float)$bm['look_at_y'];
            $bm['look_at_z'] = (float)$bm['look_at_z'];
            $bm['is_default'] = (int)$bm['is_default'];
            $bm['sort_order'] = (int)$bm['sort_order'];
        }

        return $bookmarks;
    }

    /**
     * Reassign or vacate a workstation.
     */
    public function assignWorkstation(int $workstationId, ?int $agentId = null): array
    {
        $ws = $this->db->table('hq_workstations')->where('id', $workstationId)->get()->getRowArray();
        if (!$ws) {
            throw new \InvalidArgumentException("Workstation #{$workstationId} not found.", 404);
        }

        if ($agentId !== null) {
            $agent = $this->db->table('ai_agent_registry')->where('id', $agentId)->get()->getRowArray();
            if (!$agent) {
                throw new \InvalidArgumentException("Agent #{$agentId} not found.", 404);
            }

            // Remove agent from any previous workstation
            $this->db->table('hq_workstations')
                ->where('assigned_agent_id', $agentId)
                ->where('id !=', $workstationId)
                ->update(['assigned_agent_id' => null, 'updated_at' => date('Y-m-d H:i:s')]);
        }

        $this->db->table('hq_workstations')
            ->where('id', $workstationId)
            ->update([
                'assigned_agent_id' => $agentId,
                'updated_at'        => date('Y-m-d H:i:s')
            ]);

        $updatedWs = $this->db->table('hq_workstations')->where('id', $workstationId)->get()->getRowArray();
        $updatedWs['id'] = (int)$updatedWs['id'];
        $updatedWs['assigned_agent_id'] = $updatedWs['assigned_agent_id'] ? (int)$updatedWs['assigned_agent_id'] : null;

        return $updatedWs;
    }
}
