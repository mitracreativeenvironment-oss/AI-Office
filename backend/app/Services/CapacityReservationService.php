<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class CapacityReservationService
{
    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    public function getReservations(array $filters = []): array
    {
        $builder = $this->db->table('resource_capacity_reservations r')
            ->select('r.*, u.name as unit_name, u.code as unit_code, ag.name as agent_name, ag.agent_code, res.name as resource_name, res.resource_code, usr.username as creator_username')
            ->join('organization_units u', 'u.id = r.scope_unit_id', 'left')
            ->join('ai_agent_registry ag', 'ag.id = r.target_agent_id', 'left')
            ->join('resource_registry res', 'res.id = r.target_resource_id', 'left')
            ->join('users usr', 'usr.id = r.created_by_user_id', 'left')
            ->orderBy('r.id', 'DESC');

        if (!empty($filters['status'])) {
            $builder->where('r.status', $filters['status']);
        }
        if (!empty($filters['reservation_type'])) {
            $builder->where('r.reservation_type', $filters['reservation_type']);
        }
        if (!empty($filters['scope_unit_id'])) {
            $builder->where('r.scope_unit_id', (int)$filters['scope_unit_id']);
        }

        return $builder->get()->getResultArray();
    }

    public function getReservationById(int $id): ?array
    {
        return $this->db->table('resource_capacity_reservations r')
            ->select('r.*, u.name as unit_name, u.code as unit_code, ag.name as agent_name, ag.agent_code, res.name as resource_name, res.resource_code, usr.username as creator_username')
            ->join('organization_units u', 'u.id = r.scope_unit_id', 'left')
            ->join('ai_agent_registry ag', 'ag.id = r.target_agent_id', 'left')
            ->join('resource_registry res', 'res.id = r.target_resource_id', 'left')
            ->join('users usr', 'usr.id = r.created_by_user_id', 'left')
            ->where('r.id', $id)
            ->get()->getRowArray();
    }

    public function createReservation(array $data, ?array $actor = null): array
    {
        $now = date('Y-m-d H:i:s');
        $code = $data['reservation_code'] ?? 'RSV-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));

        $insertData = [
            'reservation_code'        => $code,
            'title'                   => $data['title'],
            'reservation_type'        => $data['reservation_type'] ?? 'internal_operations',
            'scope_unit_id'           => !empty($data['scope_unit_id']) ? (int)$data['scope_unit_id'] : null,
            'target_agent_id'         => !empty($data['target_agent_id']) ? (int)$data['target_agent_id'] : null,
            'target_resource_id'      => !empty($data['target_resource_id']) ? (int)$data['target_resource_id'] : null,
            'reserved_percentage'     => (int)($data['reserved_percentage'] ?? 20),
            'reserved_hours_per_week' => (float)($data['reserved_hours_per_week'] ?? 8.00),
            'start_date'              => $data['start_date'] ?? date('Y-m-d'),
            'end_date'                => $data['end_date'] ?? null,
            'sponsoring_authority'    => $data['sponsoring_authority'] ?? 'continuous_executive_board',
            'sponsoring_decision_id'  => !empty($data['sponsoring_decision_id']) ? (int)$data['sponsoring_decision_id'] : null,
            'sponsoring_direction_id' => !empty($data['sponsoring_direction_id']) ? (int)$data['sponsoring_direction_id'] : null,
            'status'                  => $data['status'] ?? 'active',
            'justification'           => $data['justification'] ?? null,
            'created_by_user_id'      => $actor['id'] ?? 1,
            'created_at'              => $now,
            'updated_at'              => $now,
        ];

        $this->db->table('resource_capacity_reservations')->insert($insertData);
        $id = (int)$this->db->insertID();

        $this->logAuditEvent('reservation_created', 'reservation', $id, $insertData, $actor);

        return $this->getReservationById($id);
    }

    public function updateReservation(int $id, array $data, ?array $actor = null): ?array
    {
        $now = date('Y-m-d H:i:s');
        $updateData = ['updated_at' => $now];

        $fields = ['title', 'reservation_type', 'scope_unit_id', 'target_agent_id', 'target_resource_id', 'reserved_percentage', 'reserved_hours_per_week', 'start_date', 'end_date', 'status', 'justification'];
        foreach ($fields as $f) {
            if (isset($data[$f])) {
                $updateData[$f] = in_array($f, ['scope_unit_id', 'target_agent_id', 'target_resource_id', 'reserved_percentage']) 
                    ? ($data[$f] !== null ? (int)$data[$f] : null)
                    : ($f === 'reserved_hours_per_week' ? (float)$data[$f] : $data[$f]);
            }
        }

        $this->db->table('resource_capacity_reservations')->where('id', $id)->update($updateData);

        $this->logAuditEvent('reservation_updated', 'reservation', $id, $updateData, $actor);

        return $this->getReservationById($id);
    }

    public function deactivateReservation(int $id, ?array $actor = null): bool
    {
        $res = $this->getReservationById($id);
        if (!$res) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('resource_capacity_reservations')->where('id', $id)->update([
            'status'     => 'concluded',
            'updated_at' => $now,
        ]);

        $this->logAuditEvent('reservation_concluded', 'reservation', $id, ['status' => 'concluded'], $actor);

        return true;
    }

    protected function logAuditEvent(string $eventType, string $targetType, int $targetId, array $metadata, ?array $actor = null): void
    {
        $this->db->table('resource_audit_events')->insert([
            'event_type'    => $eventType,
            'actor_type'    => $actor['type'] ?? 'user',
            'actor_id'      => $actor['id'] ?? null,
            'actor_name'    => $actor['name'] ?? 'System',
            'target_type'   => $targetType,
            'target_id'     => $targetId,
            'metadata_json' => json_encode($metadata),
            'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }
}
