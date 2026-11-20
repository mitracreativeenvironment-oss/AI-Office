<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class WelfareFacilityService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * List all cross-division welfare facilities with live occupancy.
     */
    public function listFacilities(): array
    {
        $facilities = $this->db->table('welfare_facilities f')
            ->select('f.*, b.name as building_name, b.building_code, r.name as room_name, r.room_code')
            ->join('hq_buildings b', 'b.id = f.building_id')
            ->join('hq_rooms r', 'r.id = f.room_id')
            ->where('f.is_active', 1)
            ->orderBy('f.id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($facilities as &$f) {
            $f['id'] = (int)$f['id'];
            $f['capacity'] = (int)$f['capacity'];

            $activeCount = $this->db->table('welfare_facility_usage_logs')
                ->where('facility_id', $f['id'])
                ->where('status', 'active')
                ->countAllResults();

            $f['current_occupancy'] = $activeCount;
            $f['is_full'] = ($activeCount >= $f['capacity']);
        }

        return $facilities;
    }

    /**
     * Check an agent into a welfare facility session (Rest recovery, study, etc.).
     */
    public function checkInFacility(int $facilityId, int $agentId, string $sessionType = 'duty_rest_recovery', ?int $workstationId = null, ?string $notes = null): array
    {
        $facility = $this->db->table('welfare_facilities')->where('id', $facilityId)->get()->getRowArray();
        if (!$facility) {
            throw new \InvalidArgumentException("Facility #{$facilityId} not found.", 404);
        }

        $agent = $this->db->table('ai_agent_registry')->where('id', $agentId)->get()->getRowArray();
        if (!$agent) {
            throw new \InvalidArgumentException("Agent #{$agentId} not found.", 404);
        }

        // Check if agent already has an active session
        $existingSession = $this->db->table('welfare_facility_usage_logs')
            ->where('agent_id', $agentId)
            ->where('status', 'active')
            ->get()
            ->getRowArray();

        if ($existingSession) {
            throw new \RuntimeException("Agent #{$agentId} already has an active facility session #{$existingSession['id']}.", 422);
        }

        $now = date('Y-m-d H:i:s');
        $insert = [
            'facility_id'    => $facilityId,
            'agent_id'       => $agentId,
            'workstation_id' => $workstationId,
            'session_type'   => $sessionType,
            'start_time'     => $now,
            'end_time'       => null,
            'status'         => 'active',
            'notes'          => $notes ?? "Agent session in {$facility['name']}.",
            'created_at'     => $now,
            'updated_at'     => $now,
        ];

        $this->db->table('welfare_facility_usage_logs')->insert($insert);
        $logId = (int)$this->db->insertID();

        // Audit Trail
        $this->db->table('welfare_audit_trail')->insert([
            'event_uuid'     => \App\Services\AgentRegistryService::generateUuid(),
            'event_category' => 'facility',
            'action'         => 'facility_checkin',
            'target_type'    => 'facility_usage',
            'target_id'      => $logId,
            'actor_type'     => 'director_ai',
            'actor_id'       => $agentId,
            'details'        => json_encode(['facility_code' => $facility['facility_code'], 'session_type' => $sessionType]),
            'created_at'     => $now,
        ]);

        return $this->db->table('welfare_facility_usage_logs')->where('id', $logId)->get()->getRowArray();
    }

    /**
     * Conclude an active facility session.
     */
    public function checkOutFacility(int $usageLogId): array
    {
        $log = $this->db->table('welfare_facility_usage_logs')->where('id', $usageLogId)->get()->getRowArray();
        if (!$log) {
            throw new \InvalidArgumentException("Usage log #{$usageLogId} not found.", 404);
        }

        if ($log['status'] !== 'active') {
            throw new \RuntimeException("Session #{$usageLogId} is already {$log['status']}.", 422);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('welfare_facility_usage_logs')->where('id', $usageLogId)->update([
            'end_time'   => $now,
            'status'     => 'completed',
            'updated_at' => $now,
        ]);

        return $this->db->table('welfare_facility_usage_logs')->where('id', $usageLogId)->get()->getRowArray();
    }

    /**
     * List facility usage logs with filters.
     */
    public function listUsageLogs(array $filters = []): array
    {
        $builder = $this->db->table('welfare_facility_usage_logs l')
            ->select('l.*, f.name as facility_name, f.facility_code, ag.name as agent_name, ag.agent_code')
            ->join('welfare_facilities f', 'f.id = l.facility_id')
            ->join('ai_agent_registry ag', 'ag.id = l.agent_id');

        if (!empty($filters['facility_id'])) {
            $builder->where('l.facility_id', (int)$filters['facility_id']);
        }
        if (!empty($filters['agent_id'])) {
            $builder->where('l.agent_id', (int)$filters['agent_id']);
        }
        if (!empty($filters['status'])) {
            $builder->where('l.status', $filters['status']);
        }

        $list = $builder->orderBy('l.id', 'DESC')->limit(100)->get()->getResultArray();
        foreach ($list as &$item) {
            $item['id'] = (int)$item['id'];
            $item['facility_id'] = (int)$item['facility_id'];
            $item['agent_id'] = (int)$item['agent_id'];
        }

        return $list;
    }
}
