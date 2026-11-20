<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class ResourceRegistryService
{
    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    public function getResources(array $filters = []): array
    {
        $builder = $this->db->table('resource_registry r')
            ->select('r.*, u.name as owner_unit_name, u.code as owner_unit_code')
            ->join('organization_units u', 'u.id = r.owner_unit_id', 'left')
            ->orderBy('r.category', 'ASC')
            ->orderBy('r.name', 'ASC');

        if (!empty($filters['category'])) {
            $builder->where('r.category', $filters['category']);
        }
        if (!empty($filters['status'])) {
            $builder->where('r.status', $filters['status']);
        }
        if (!empty($filters['owner_unit_id'])) {
            $builder->where('r.owner_unit_id', (int)$filters['owner_unit_id']);
        }
        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('r.name', $filters['search'])
                ->orLike('r.resource_code', $filters['search'])
                ->orLike('r.description', $filters['search'])
                ->groupEnd();
        }

        $results = $builder->get()->getResultArray();
        foreach ($results as &$row) {
            $row['specifications'] = !empty($row['specifications_json']) ? json_decode($row['specifications_json'], true) : [];
            $row['available_capacity'] = max(0, (float)$row['total_capacity'] - (float)$row['allocated_capacity']);
            $row['utilization_percent'] = (float)$row['total_capacity'] > 0 
                ? round(((float)$row['allocated_capacity'] / (float)$row['total_capacity']) * 100, 2) 
                : 0.00;
        }

        return $results;
    }

    public function getResourceById(int $id): ?array
    {
        $row = $this->db->table('resource_registry r')
            ->select('r.*, u.name as owner_unit_name, u.code as owner_unit_code')
            ->join('organization_units u', 'u.id = r.owner_unit_id', 'left')
            ->where('r.id', $id)
            ->get()->getRowArray();

        if ($row) {
            $row['specifications'] = !empty($row['specifications_json']) ? json_decode($row['specifications_json'], true) : [];
            $row['available_capacity'] = max(0, (float)$row['total_capacity'] - (float)$row['allocated_capacity']);
            $row['utilization_percent'] = (float)$row['total_capacity'] > 0 
                ? round(((float)$row['allocated_capacity'] / (float)$row['total_capacity']) * 100, 2) 
                : 0.00;
        }

        return $row;
    }

    public function getResourceByCode(string $code): ?array
    {
        $row = $this->db->table('resource_registry r')
            ->select('r.*, u.name as owner_unit_name, u.code as owner_unit_code')
            ->join('organization_units u', 'u.id = r.owner_unit_id', 'left')
            ->where('r.resource_code', $code)
            ->get()->getRowArray();

        if ($row) {
            $row['specifications'] = !empty($row['specifications_json']) ? json_decode($row['specifications_json'], true) : [];
            $row['available_capacity'] = max(0, (float)$row['total_capacity'] - (float)$row['allocated_capacity']);
            $row['utilization_percent'] = (float)$row['total_capacity'] > 0 
                ? round(((float)$row['allocated_capacity'] / (float)$row['total_capacity']) * 100, 2) 
                : 0.00;
        }

        return $row;
    }

    public function createResource(array $data, ?array $actor = null): array
    {
        $now = date('Y-m-d H:i:s');
        $code = $data['resource_code'] ?? 'RES-' . strtoupper(substr(uniqid(), -6));

        $insertData = [
            'resource_uuid'       => $data['resource_uuid'] ?? sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
            'resource_code'       => $code,
            'name'                => $data['name'],
            'category'            => $data['category'] ?? 'software_tool',
            'description'         => $data['description'] ?? null,
            'unit_of_measure'     => $data['unit_of_measure'] ?? 'hours',
            'total_capacity'      => (float)($data['total_capacity'] ?? 100.00),
            'allocated_capacity'  => (float)($data['allocated_capacity'] ?? 0.00),
            'unit_cost_idr'       => (float)($data['unit_cost_idr'] ?? 0.00),
            'owner_unit_id'       => !empty($data['owner_unit_id']) ? (int)$data['owner_unit_id'] : null,
            'status'              => $data['status'] ?? 'active',
            'specifications_json' => isset($data['specifications']) ? json_encode($data['specifications']) : (isset($data['specifications_json']) ? $data['specifications_json'] : null),
            'created_at'          => $now,
            'updated_at'          => $now,
        ];

        $this->db->table('resource_registry')->insert($insertData);
        $id = (int)$this->db->insertID();

        $this->logAuditEvent('resource_created', 'resource', $id, $insertData, $actor);

        return $this->getResourceById($id);
    }

    public function updateResource(int $id, array $data, ?array $actor = null): ?array
    {
        $now = date('Y-m-d H:i:s');
        $updateData = ['updated_at' => $now];

        $fields = ['name', 'category', 'description', 'unit_of_measure', 'total_capacity', 'allocated_capacity', 'unit_cost_idr', 'owner_unit_id', 'status'];
        foreach ($fields as $f) {
            if (isset($data[$f])) {
                $updateData[$f] = in_array($f, ['total_capacity', 'allocated_capacity', 'unit_cost_idr']) ? (float)$data[$f] : $data[$f];
            }
        }

        if (isset($data['specifications'])) {
            $updateData['specifications_json'] = json_encode($data['specifications']);
        }

        $this->db->table('resource_registry')->where('id', $id)->update($updateData);

        $this->logAuditEvent('resource_updated', 'resource', $id, $updateData, $actor);

        return $this->getResourceById($id);
    }

    public function deleteResource(int $id, ?array $actor = null): bool
    {
        $res = $this->getResourceById($id);
        if (!$res) {
            return false;
        }

        $this->db->table('resource_registry')->where('id', $id)->delete();
        $this->logAuditEvent('resource_deleted', 'resource', $id, ['resource_code' => $res['resource_code']], $actor);

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
