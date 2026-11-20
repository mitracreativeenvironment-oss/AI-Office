<?php

namespace App\Services;

use Config\Database;

class OrganizationService
{
    protected $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Retrieve hierarchical tree of organizational units.
     */
    public function getTree(): array
    {
        $allUnits = $this->db->table('organization_units')
            ->select('id, parent_id, code, name, type, description, mission, division_key, status, sort_order')
            ->where('status !=', 'retired')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        // Index by ID
        $indexed = [];
        foreach ($allUnits as $u) {
            $u['id'] = (int)$u['id'];
            $u['parent_id'] = $u['parent_id'] !== null ? (int)$u['parent_id'] : null;
            $u['children'] = [];
            $indexed[$u['id']] = $u;
        }

        $tree = [];
        foreach ($indexed as $id => &$node) {
            if ($node['parent_id'] && isset($indexed[$node['parent_id']])) {
                $indexed[$node['parent_id']]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }

        return $tree;
    }

    /**
     * List all organizational units flat.
     */
    public function getUnits(?string $status = null): array
    {
        $builder = $this->db->table('organization_units');
        if ($status) {
            $builder->where('status', $status);
        }
        $builder->orderBy('sort_order', 'ASC');
        $units = $builder->get()->getResultArray();

        foreach ($units as &$u) {
            $u['id'] = (int)$u['id'];
            $u['parent_id'] = $u['parent_id'] ? (int)$u['parent_id'] : null;
            $u['positions_count'] = $this->db->table('organization_positions')
                ->where('unit_id', $u['id'])
                ->where('is_active', 1)
                ->countAllResults();
            $u['subunits_count'] = $this->db->table('organization_units')
                ->where('parent_id', $u['id'])
                ->countAllResults();
        }

        return $units;
    }

    /**
     * Get single unit with detail, parent, children, and positions.
     */
    public function getUnit(int $id): ?array
    {
        $unit = $this->db->table('organization_units')->where('id', $id)->get()->getRowArray();
        if (!$unit) {
            return null;
        }

        $unit['id'] = (int)$unit['id'];
        $unit['parent_id'] = $unit['parent_id'] ? (int)$unit['parent_id'] : null;

        // Parent detail
        $unit['parent'] = $unit['parent_id']
            ? $this->db->table('organization_units')->select('id, code, name, type')->where('id', $unit['parent_id'])->get()->getRowArray()
            : null;

        // Subunits
        $unit['subunits'] = $this->db->table('organization_units')
            ->select('id, code, name, type, status, sort_order')
            ->where('parent_id', $unit['id'])
            ->get()
            ->getResultArray();

        // Positions in this unit
        $positions = $this->db->table('organization_positions')
            ->where('unit_id', $unit['id'])
            ->where('is_active', 1)
            ->get()
            ->getResultArray();

        foreach ($positions as &$p) {
            $p['id'] = (int)$p['id'];
            $p['active_appointment'] = $this->db->table('organization_appointments')
                ->where('position_id', $p['id'])
                ->where('status', 'active')
                ->get()
                ->getRowArray();
        }

        $unit['positions'] = $positions;

        return $unit;
    }

    /**
     * Check if assigning $newParentId as parent of $unitId would cause a circular cycle.
     */
    public function isCircularParent(int $unitId, int $newParentId): bool
    {
        if ($unitId === $newParentId) {
            return true;
        }

        $currentParentId = $newParentId;
        $visited = [$unitId];

        while ($currentParentId !== null) {
            if ($currentParentId === $unitId || in_array($currentParentId, $visited, true)) {
                return true;
            }
            $visited[] = $currentParentId;

            $row = $this->db->table('organization_units')
                ->select('parent_id')
                ->where('id', $currentParentId)
                ->get()
                ->getRow();

            $currentParentId = $row && $row->parent_id !== null ? (int)$row->parent_id : null;
        }

        return false;
    }

    /**
     * Create an organizational unit safely.
     */
    public function createUnit(array $data, ?int $actorUserId = null): array
    {
        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');

        if (empty($code) || empty($name)) {
            throw new \InvalidArgumentException('Unit code and name are required.', 422);
        }

        // Check unique code
        $exists = $this->db->table('organization_units')->where('code', $code)->countAllResults();
        if ($exists > 0) {
            throw new \RuntimeException("Organizational unit code '{$code}' already exists.", 409);
        }

        $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
        if ($parentId !== null) {
            $parentExists = $this->db->table('organization_units')->where('id', $parentId)->countAllResults();
            if ($parentExists === 0) {
                throw new \InvalidArgumentException("Parent unit ID '{$parentId}' not found.", 404);
            }
        }

        $now = date('Y-m-d H:i:s');
        $unitData = [
            'parent_id'            => $parentId,
            'code'                 => $code,
            'name'                 => $name,
            'type'                 => $data['type'] ?? 'unit',
            'description'          => $data['description'] ?? null,
            'mission'              => $data['mission'] ?? null,
            'division_key'         => $data['division_key'] ?? null,
            'status'               => $data['status'] ?? 'active',
            'sort_order'           => (int)($data['sort_order'] ?? 0),
            'effective_start_date' => $data['effective_start_date'] ?? date('Y-m-d'),
            'created_at'           => $now,
            'updated_at'           => $now,
        ];

        $this->db->table('organization_units')->insert($unitData);
        $insertId = $this->db->insertID();

        $this->logAudit('unit', (string)$insertId, 'create', $actorUserId, null, $unitData, "Created unit {$code}");

        return $this->getUnit($insertId);
    }

    /**
     * Update an organizational unit with cycle detection.
     */
    public function updateUnit(int $id, array $data, ?int $actorUserId = null): array
    {
        $unit = $this->db->table('organization_units')->where('id', $id)->get()->getRowArray();
        if (!$unit) {
            throw new \RuntimeException('Unit not found.', 404);
        }

        $newParentId = isset($data['parent_id']) ? ($data['parent_id'] !== null ? (int)$data['parent_id'] : null) : (int)$unit['parent_id'];

        if ($newParentId !== null && $this->isCircularParent($id, $newParentId)) {
            throw new \InvalidArgumentException('Circular hierarchy detected: a unit cannot be a descendant of itself.', 400);
        }

        $updateData = array_filter([
            'name'                 => $data['name'] ?? null,
            'parent_id'            => array_key_exists('parent_id', $data) ? $newParentId : null,
            'type'                 => $data['type'] ?? null,
            'description'          => $data['description'] ?? null,
            'mission'              => $data['mission'] ?? null,
            'division_key'         => $data['division_key'] ?? null,
            'status'               => $data['status'] ?? null,
            'sort_order'           => isset($data['sort_order']) ? (int)$data['sort_order'] : null,
            'effective_end_date'   => $data['effective_end_date'] ?? null,
            'updated_at'           => date('Y-m-d H:i:s'),
        ], fn($val) => $val !== null);

        $this->db->table('organization_units')->where('id', $id)->update($updateData);

        $this->logAudit('unit', (string)$id, 'update', $actorUserId, $unit, $updateData, "Updated unit {$unit['code']}");

        return $this->getUnit($id);
    }

    /**
     * List positions.
     */
    public function getPositions(?int $unitId = null): array
    {
        $builder = $this->db->table('organization_positions op');
        $builder->select('op.*, ou.name as unit_name, ou.code as unit_code, ap.name as authority_profile_name, ap.authority_level');
        $builder->join('organization_units ou', 'ou.id = op.unit_id');
        $builder->join('authority_profiles ap', 'ap.id = op.authority_profile_id', 'left');

        if ($unitId) {
            $builder->where('op.unit_id', $unitId);
        }
        $builder->where('op.is_active', 1);
        $builder->orderBy('op.level', 'ASC');
        $positions = $builder->get()->getResultArray();

        foreach ($positions as &$p) {
            $p['id'] = (int)$p['id'];
            $p['unit_id'] = (int)$p['unit_id'];
            $p['active_appointment'] = $this->db->table('organization_appointments')
                ->where('position_id', $p['id'])
                ->where('status', 'active')
                ->get()
                ->getRowArray();
        }

        return $positions;
    }

    public function getPosition(int $id): ?array
    {
        $builder = $this->db->table('organization_positions op');
        $builder->select('op.*, ou.name as unit_name, ou.code as unit_code, ap.name as authority_profile_name, ap.authority_level');
        $builder->join('organization_units ou', 'ou.id = op.unit_id');
        $builder->join('authority_profiles ap', 'ap.id = op.authority_profile_id', 'left');
        $builder->where('op.id', $id);

        $pos = $builder->get()->getRowArray();
        if (!$pos) {
            return null;
        }

        $pos['id'] = (int)$pos['id'];
        $pos['unit_id'] = (int)$pos['unit_id'];
        $pos['active_appointment'] = $this->db->table('organization_appointments')
            ->where('position_id', $id)
            ->where('status', 'active')
            ->get()
            ->getRowArray();

        $pos['appointments_history'] = $this->db->table('organization_appointments')
            ->where('position_id', $id)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        return $pos;
    }

    public function createPosition(array $data, ?int $actorUserId = null): array
    {
        $code = trim($data['code'] ?? '');
        $title = trim($data['title'] ?? '');
        $unitId = (int)($data['unit_id'] ?? 0);

        if (empty($code) || empty($title) || !$unitId) {
            throw new \InvalidArgumentException('code, title, and unit_id are required.', 422);
        }

        $exists = $this->db->table('organization_positions')->where('code', $code)->countAllResults();
        if ($exists > 0) {
            throw new \RuntimeException("Position code '{$code}' already exists.", 409);
        }

        $now = date('Y-m-d H:i:s');
        $insert = [
            'unit_id'              => $unitId,
            'code'                 => $code,
            'title'                => $title,
            'description'          => $data['description'] ?? null,
            'level'                => $data['level'] ?? 'specialist',
            'authority_profile_id' => !empty($data['authority_profile_id']) ? (int)$data['authority_profile_id'] : null,
            'is_leadership'        => isset($data['is_leadership']) ? (int)$data['is_leadership'] : 0,
            'is_active'            => 1,
            'created_at'           => $now,
            'updated_at'           => $now,
        ];

        $this->db->table('organization_positions')->insert($insert);
        $insertId = $this->db->insertID();

        $this->logAudit('position', (string)$insertId, 'create', $actorUserId, null, $insert, "Created position {$code} ({$title})");

        return $this->getPosition($insertId);
    }

    /**
     * List appointments (active and historical).
     */
    public function getAppointments(?int $unitId = null, ?int $positionId = null, ?string $status = null): array
    {
        $builder = $this->db->table('organization_appointments oa');
        $builder->select('oa.*, op.title as position_title, op.code as position_code, ou.name as unit_name');
        $builder->join('organization_positions op', 'op.id = oa.position_id');
        $builder->join('organization_units ou', 'ou.id = oa.unit_id');

        if ($unitId) {
            $builder->where('oa.unit_id', $unitId);
        }
        if ($positionId) {
            $builder->where('oa.position_id', $positionId);
        }
        if ($status) {
            $builder->where('oa.status', $status);
        }

        $builder->orderBy('oa.id', 'DESC');

        return $builder->get()->getResultArray();
    }

    /**
     * Create appointment, safely marking prior appointment as ended.
     */
    public function createAppointment(array $data, ?int $actorUserId = null): array
    {
        $positionId = (int)($data['position_id'] ?? 0);
        $appointeeId = trim((string)($data['appointee_id'] ?? ''));
        $appointeeName = trim($data['appointee_name'] ?? '');

        if (!$positionId || empty($appointeeId) || empty($appointeeName)) {
            throw new \InvalidArgumentException('Position ID and Appointee details are required.', 422);
        }

        $pos = $this->db->table('organization_positions')->where('id', $positionId)->get()->getRowArray();
        if (!$pos) {
            throw new \RuntimeException("Position #{$positionId} not found.", 404);
        }

        $unitId = (int)($data['unit_id'] ?? $pos['unit_id']);
        $now = date('Y-m-d H:i:s');

        // End any currently active appointment on this position
        $this->db->table('organization_appointments')
            ->where('position_id', $positionId)
            ->where('status', 'active')
            ->update([
                'status'     => 'ended',
                'end_date'   => $now,
                'updated_at' => $now,
                'notes'      => 'Replaced by subsequent appointment.',
            ]);

        $apptData = [
            'position_id'    => $positionId,
            'unit_id'        => $unitId,
            'appointee_type' => $data['appointee_type'] ?? 'human',
            'appointee_id'   => $appointeeId,
            'appointee_name' => $appointeeName,
            'status'         => 'active',
            'start_date'     => $data['start_date'] ?? $now,
            'decision_ref'   => $data['decision_ref'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'created_at'     => $now,
            'updated_at'     => $now,
        ];

        $this->db->table('organization_appointments')->insert($apptData);
        $insertId = $this->db->insertID();

        $this->logAudit('appointment', (string)$insertId, 'appoint', $actorUserId, null, $apptData, "Appointed {$appointeeName} to position #{$positionId}");

        $created = $this->db->table('organization_appointments')->where('id', $insertId)->get()->getRowArray();
        return $created ?: $apptData;
    }

    /**
     * Terminate or end an appointment, preserving historical record.
     */
    public function endAppointment(int $id, string $reason, ?int $actorUserId = null): array
    {
        $now = date('Y-m-d H:i:s');
        $appt = $this->db->table('organization_appointments')->where('id', $id)->get()->getRowArray();
        if (!$appt) {
            throw new \RuntimeException('Appointment record not found.', 404);
        }

        $this->db->table('organization_appointments')->where('id', $id)->update([
            'status'     => 'ended',
            'end_date'   => $now,
            'notes'      => trim(($appt['notes'] ?? '') . "\nEnded: {$reason}"),
            'updated_at' => $now,
        ]);

        $this->logAudit('appointment', (string)$id, 'end_appointment', $actorUserId, $appt, ['status' => 'ended', 'end_date' => $now], $reason);

        return $this->db->table('organization_appointments')->where('id', $id)->get()->getRowArray();
    }

    /**
     * List reporting relationships.
     */
    public function getReportingRelationships(?int $positionId = null): array
    {
        $builder = $this->db->table('reporting_relationships rr');
        $builder->select('rr.*, sp.title as subordinate_title, sp.code as subordinate_code, sup.title as supervisor_title, sup.code as supervisor_code');
        $builder->join('organization_positions sp', 'sp.id = rr.subordinate_position_id');
        $builder->join('organization_positions sup', 'sup.id = rr.supervisor_position_id');

        if ($positionId) {
            $builder->groupStart()
                ->where('rr.subordinate_position_id', $positionId)
                ->orWhere('rr.supervisor_position_id', $positionId)
                ->groupEnd();
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Add a reporting relationship.
     */
    public function addReportingRelationship(int $subordinateId, int $supervisorId, string $type = 'direct', ?string $desc = null, ?int $actorUserId = null): array
    {
        if ($subordinateId === $supervisorId) {
            throw new \InvalidArgumentException('A position cannot report to itself.', 400);
        }

        $now = date('Y-m-d H:i:s');
        $data = [
            'subordinate_position_id' => $subordinateId,
            'supervisor_position_id'  => $supervisorId,
            'relationship_type'       => $type,
            'description'             => $desc,
            'created_at'              => $now,
        ];

        $this->db->table('reporting_relationships')->insert($data);
        $insertId = $this->db->insertID();

        $this->logAudit('reporting', (string)$insertId, 'create', $actorUserId, null, $data, "Created reporting relationship #{$insertId}");

        $created = $this->db->table('reporting_relationships')->where('id', $insertId)->get()->getRowArray();
        return $created ?: $data;
    }

    protected function logAudit(string $entityType, string $entityId, string $action, ?int $actorUserId, ?array $old, ?array $new, ?string $notes = null): void
    {
        try {
            $actorName = 'System';
            if ($actorUserId) {
                $user = $this->db->table('users')->select('username')->where('id', $actorUserId)->get()->getRow();
                $actorName = $user ? $user->username : "User #{$actorUserId}";
            }

            $ip = '127.0.0.1';
            try {
                if (function_exists('service')) {
                    $req = service('request');
                    if ($req) {
                        $ip = $req->getIPAddress() ?: '127.0.0.1';
                    }
                }
            } catch (\Throwable $e) {}

            $this->db->table('governance_audit_logs')->insert([
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'action'      => $action,
                'actor_type'  => $actorUserId ? 'user' : 'system',
                'actor_id'    => (string)($actorUserId ?? 'system'),
                'actor_name'  => $actorName,
                'old_state'   => $old ? json_encode($old) : null,
                'new_state'   => $new ? json_encode($new) : null,
                'notes'       => $notes,
                'ip_address'  => $ip,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Governance audit log failed: ' . $e->getMessage());
        }
    }
}
