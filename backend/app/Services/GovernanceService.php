<?php

namespace App\Services;

use Config\Database;

class GovernanceService
{
    protected $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    // =========================================================================
    // 1. Core Values (Controlled Reference Data)
    // =========================================================================

    /**
     * Get all active or all core values ordered by display_order.
     */
    public function getCoreValues(bool $activeOnly = true): array
    {
        $builder = $this->db->table('core_values');
        if ($activeOnly) {
            $builder->where('is_active', 1);
        }
        $builder->orderBy('display_order', 'ASC');
        return $builder->get()->getResultArray();
    }

    public function getCoreValue(int $id): ?array
    {
        $val = $this->db->table('core_values')->where('id', $id)->get()->getRowArray();
        return $val ?: null;
    }

    // =========================================================================
    // 2. Governance Bodies & Memberships
    // =========================================================================

    /**
     * Get all governance bodies with membership counts.
     */
    public function getBodies(): array
    {
        $bodies = $this->db->table('governance_bodies')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($bodies as &$body) {
            $members = $this->db->table('governance_memberships')
                ->where('body_id', $body['id'])
                ->where('status', 'active')
                ->get()
                ->getResultArray();
            $body['members'] = $members;
            $body['member_count'] = count($members);
        }

        return $bodies;
    }

    public function getBody(int $id): ?array
    {
        $body = $this->db->table('governance_bodies')->where('id', $id)->get()->getRowArray();
        if (!$body) {
            return null;
        }

        $body['members'] = $this->db->table('governance_memberships')
            ->where('body_id', $id)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        return $body;
    }

    public function addBodyMembership(int $bodyId, array $data, ?int $actorUserId = null): array
    {
        $body = $this->getBody($bodyId);
        if (!$body) {
            throw new \InvalidArgumentException("Governance body ID {$bodyId} not found");
        }

        $now = date('Y-m-d H:i:s');
        $insert = [
            'body_id'         => $bodyId,
            'member_type'     => $data['member_type'] ?? 'human',
            'member_id'       => $data['member_id'],
            'member_name'     => $data['member_name'],
            'governance_role' => $data['governance_role'] ?? 'executive_member',
            'voting_weight'   => isset($data['voting_weight']) ? (int)$data['voting_weight'] : 1,
            'status'          => $data['status'] ?? 'active',
            'start_date'      => $data['start_date'] ?? $now,
            'end_date'        => $data['end_date'] ?? null,
            'created_at'      => $now,
        ];

        $this->db->table('governance_memberships')->insert($insert);
        $insert['id'] = $this->db->insertID();

        $this->logAudit(
            'create',
            'governance_membership',
            (string)$insert['id'],
            $actorUserId,
            null,
            null,
            $insert,
            "Added member {$insert['member_name']} to body {$body['name']}"
        );

        return $insert;
    }

    // =========================================================================
    // 3. Authority Profiles & Delegation Framework (DoA)
    // =========================================================================

    public function getAuthorityProfiles(): array
    {
        $profiles = $this->db->table('authority_profiles')
            ->where('is_active', 1)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($profiles as &$p) {
            $p['financial_limit'] = (float)$p['financial_limit'];
            $p['permitted_actions'] = !empty($p['permitted_actions']) ? json_decode($p['permitted_actions'], true) : [];
        }

        return $profiles;
    }

    public function getAuthorityProfile(int $id): ?array
    {
        $p = $this->db->table('authority_profiles')->where('id', $id)->get()->getRowArray();
        if ($p) {
            $p['financial_limit'] = (float)$p['financial_limit'];
            $p['permitted_actions'] = !empty($p['permitted_actions']) ? json_decode($p['permitted_actions'], true) : [];
        }
        return $p ?: null;
    }

    /**
     * Check if a proposed action and amount is permitted under an authority profile or level.
     * Enforces the corporate rule:
     * - Level A: Owner strategic/capital (> IDR 5.000.000 or restructuring/capital)
     * - Level B: Board autonomous ops (<= IDR 5.000.000)
     * - Level C: Operational dispatch (<= IDR 1.000.000)
     * - Routine: Non-financial only (0.00)
     */
    public function checkAuthority(string $authorityLevel, float $amount, string $action): array
    {
        $level = strtoupper($authorityLevel);
        $requiresOwner = false;
        $allowed = false;
        $reason = '';

        if ($level === 'A') {
            $allowed = true;
            $requiresOwner = false;
            $reason = 'Owner holds ultimate strategic and capital authority.';
        } elseif ($level === 'B') {
            if ($amount > 5000000.00) {
                $allowed = false;
                $requiresOwner = true;
                $reason = 'Amount exceeds Level B threshold (IDR 5.000.000). Requires Owner Level A authorization.';
            } else {
                $allowed = true;
                $reason = 'Permitted under Executive Board autonomous authority limit.';
            }
        } elseif ($level === 'C') {
            if ($amount > 1000000.00) {
                $allowed = false;
                $requiresOwner = false;
                $reason = 'Amount exceeds Level C threshold (IDR 1.000.000). Requires Board Level B or Owner Level A.';
            } else {
                $allowed = true;
                $reason = 'Permitted under Operational Dispatch authority limit.';
            }
        } else {
            // OPERATIONAL / ROUTINE
            if ($amount > 0.00) {
                $allowed = false;
                $reason = 'Routine technical role has zero financial authority limit.';
            } else {
                $allowed = true;
                $reason = 'Permitted for non-financial routine action.';
            }
        }

        return [
            'allowed'         => $allowed,
            'authority_level' => $level,
            'amount'          => $amount,
            'action'          => $action,
            'requires_owner'  => $requiresOwner,
            'reason'          => $reason,
        ];
    }

    public function getDelegations(?string $status = null): array
    {
        $builder = $this->db->table('delegation_records d')
            ->select('d.*, p.name as profile_name, p.authority_level, p.financial_limit as profile_financial_limit, u.name as unit_name')
            ->join('authority_profiles p', 'p.id = d.authority_profile_id', 'left')
            ->join('organization_units u', 'u.id = d.scope_unit_id', 'left');

        if ($status) {
            $builder->where('d.status', $status);
        }

        $builder->orderBy('d.id', 'DESC');
        $records = $builder->get()->getResultArray();

        foreach ($records as &$r) {
            $r['financial_limit'] = (float)$r['financial_limit'];
        }

        return $records;
    }

    public function getDelegation(int $id): ?array
    {
        $r = $this->db->table('delegation_records d')
            ->select('d.*, p.name as profile_name, p.authority_level, p.financial_limit as profile_financial_limit, u.name as unit_name')
            ->join('authority_profiles p', 'p.id = d.authority_profile_id', 'left')
            ->join('organization_units u', 'u.id = d.scope_unit_id', 'left')
            ->where('d.id', $id)
            ->get()
            ->getRowArray();

        if ($r) {
            $r['financial_limit'] = (float)$r['financial_limit'];
        }

        return $r ?: null;
    }

    public function createDelegation(array $data, ?int $actorUserId = null): array
    {
        $now = date('Y-m-d H:i:s');
        $code = $data['delegation_code'] ?? ('DEL-' . strtoupper(bin2hex(random_bytes(4))));

        $insert = [
            'delegation_code'      => $code,
            'authority_profile_id' => (int)$data['authority_profile_id'],
            'grantor_type'         => $data['grantor_type'] ?? 'human',
            'grantor_id'           => (string)$data['grantor_id'],
            'grantor_name'         => (string)$data['grantor_name'],
            'grantee_type'         => $data['grantee_type'] ?? 'agent',
            'grantee_id'           => (string)$data['grantee_id'],
            'grantee_name'         => (string)$data['grantee_name'],
            'scope_unit_id'        => !empty($data['scope_unit_id']) ? (int)$data['scope_unit_id'] : null,
            'delegated_scope'      => $data['delegated_scope'] ?? null,
            'financial_limit'      => isset($data['financial_limit']) ? (float)$data['financial_limit'] : 0.00,
            'status'               => $data['status'] ?? 'active',
            'start_date'           => $data['start_date'] ?? $now,
            'end_date'             => $data['end_date'] ?? null,
            'created_at'           => $now,
            'updated_at'           => $now,
        ];

        $this->db->table('delegation_records')->insert($insert);
        $insert['id'] = $this->db->insertID();

        $this->logAudit(
            'create',
            'delegation_record',
            (string)$insert['id'],
            $actorUserId,
            null,
            null,
            $insert,
            "Created delegation {$insert['delegation_code']} from {$insert['grantor_name']} to {$insert['grantee_name']}"
        );

        return $insert;
    }

    public function revokeDelegation(int $id, string $reason, ?int $actorUserId = null, ?string $actorName = null): array
    {
        $delegation = $this->getDelegation($id);
        if (!$delegation) {
            throw new \InvalidArgumentException("Delegation record ID {$id} not found");
        }

        $now = date('Y-m-d H:i:s');
        $revoker = $actorName ?? ($actorUserId ? "User #{$actorUserId}" : 'Corporate Governance Engine');

        $update = [
            'status'            => 'revoked',
            'revoked_at'        => $now,
            'revoked_by'        => $revoker,
            'revocation_reason' => $reason,
            'updated_at'        => $now,
        ];

        $this->db->table('delegation_records')->where('id', $id)->update($update);

        $this->logAudit(
            'revoke',
            'delegation_record',
            (string)$id,
            $actorUserId,
            null,
            $delegation,
            $update,
            "Revoked delegation {$delegation['delegation_code']}: {$reason}"
        );

        return $this->getDelegation($id);
    }

    // =========================================================================
    // 4. Owner Directions (Advisory vs Binding)
    // =========================================================================

    public function getOwnerDirections(?string $status = null, ?string $directionType = null): array
    {
        $builder = $this->db->table('owner_directions');
        if ($status) {
            $builder->where('status', $status);
        }
        if ($directionType) {
            $builder->where('direction_type', $directionType);
        }
        $builder->orderBy('id', 'DESC');
        return $builder->get()->getResultArray();
    }

    public function getOwnerDirection(int $id): ?array
    {
        $d = $this->db->table('owner_directions')->where('id', $id)->get()->getRowArray();
        return $d ?: null;
    }

    public function createOwnerDirection(array $data, ?int $actorUserId = null): array
    {
        $now = date('Y-m-d H:i:s');
        $code = $data['direction_code'] ?? ('DIR-OWNER-' . date('Ymd-His'));

        $insert = [
            'direction_code'          => $code,
            'direction_type'          => $data['direction_type'] ?? 'advisory',
            'subject'                 => $data['subject'],
            'description'             => $data['description'],
            'target_body_id'          => !empty($data['target_body_id']) ? (int)$data['target_body_id'] : null,
            'target_unit_id'          => !empty($data['target_unit_id']) ? (int)$data['target_unit_id'] : null,
            'issued_by_id'            => $data['issued_by_id'] ?? ($actorUserId ?? 1),
            'issued_by_name'          => $data['issued_by_name'] ?? 'MCE Human Owner',
            'requires_acknowledgment' => isset($data['requires_acknowledgment']) ? (int)$data['requires_acknowledgment'] : 0,
            'due_date'                => $data['due_date'] ?? null,
            'status'                  => 'issued',
            'created_at'              => $now,
            'updated_at'              => $now,
        ];

        $this->db->table('owner_directions')->insert($insert);
        $insert['id'] = $this->db->insertID();

        $this->logAudit(
            'create',
            'owner_direction',
            (string)$insert['id'],
            $actorUserId,
            null,
            null,
            $insert,
            "Issued Owner Direction {$insert['direction_code']} ({$insert['direction_type']}): {$insert['subject']}"
        );

        return $insert;
    }

    public function acknowledgeOwnerDirection(int $id, string $acknowledgedBy, ?string $notes = null, ?int $actorUserId = null): array
    {
        $direction = $this->getOwnerDirection($id);
        if (!$direction) {
            throw new \InvalidArgumentException("Owner Direction ID {$id} not found");
        }

        $now = date('Y-m-d H:i:s');
        $update = [
            'status'          => 'acknowledged',
            'acknowledged_at' => $now,
            'acknowledged_by' => $acknowledgedBy,
            'updated_at'      => $now,
        ];

        $this->db->table('owner_directions')->where('id', $id)->update($update);

        $this->logAudit(
            'acknowledge',
            'owner_direction',
            (string)$id,
            $actorUserId,
            null,
            $direction,
            $update,
            "Direction {$direction['direction_code']} acknowledged by {$acknowledgedBy}"
        );

        return $this->getOwnerDirection($id);
    }

    // =========================================================================
    // 5. Executive Decisions
    // =========================================================================

    public function getExecutiveDecisions(?string $status = null, ?string $category = null): array
    {
        $builder = $this->db->table('executive_decisions ed')
            ->select('ed.*, gb.name as governance_body_name, od.direction_code, od.subject as direction_subject')
            ->join('governance_bodies gb', 'gb.id = ed.governance_body_id', 'left')
            ->join('owner_directions od', 'od.id = ed.direction_id', 'left');

        if ($status) {
            $builder->where('ed.status', $status);
        }
        if ($category) {
            $builder->where('ed.decision_category', $category);
        }

        $builder->orderBy('ed.id', 'DESC');
        return $builder->get()->getResultArray();
    }

    public function getExecutiveDecision(int $id): ?array
    {
        $d = $this->db->table('executive_decisions ed')
            ->select('ed.*, gb.name as governance_body_name, od.direction_code, od.subject as direction_subject')
            ->join('governance_bodies gb', 'gb.id = ed.governance_body_id', 'left')
            ->join('owner_directions od', 'od.id = ed.direction_id', 'left')
            ->where('ed.id', $id)
            ->get()
            ->getRowArray();

        return $d ?: null;
    }

    public function createExecutiveDecision(array $data, ?int $actorUserId = null): array
    {
        $now = date('Y-m-d H:i:s');
        $code = $data['decision_code'] ?? ('DEC-BOARD-' . date('Ymd-His'));

        $insert = [
            'decision_code'      => $code,
            'governance_body_id' => (int)$data['governance_body_id'],
            'direction_id'       => !empty($data['direction_id']) ? (int)$data['direction_id'] : null,
            'title'              => $data['title'],
            'summary'            => $data['summary'],
            'decision_category'  => $data['decision_category'] ?? 'operational',
            'decision_level'     => $data['decision_level'] ?? 'B',
            'decided_by'         => $data['decided_by'] ?? 'Continuous Executive Board',
            'effective_date'     => $data['effective_date'] ?? date('Y-m-d'),
            'status'             => $data['status'] ?? 'ratified',
            'notes'              => $data['notes'] ?? null,
            'created_at'         => $now,
            'updated_at'         => $now,
        ];

        $this->db->table('executive_decisions')->insert($insert);
        $insert['id'] = $this->db->insertID();

        $this->logAudit(
            'create',
            'executive_decision',
            (string)$insert['id'],
            $actorUserId,
            null,
            null,
            $insert,
            "Recorded Executive Decision {$insert['decision_code']}: {$insert['title']}"
        );

        return $insert;
    }

    public function ratifyExecutiveDecision(int $id, string $ratifiedBy, ?string $notes = null, ?int $actorUserId = null): array
    {
        $dec = $this->getExecutiveDecision($id);
        if (!$dec) {
            throw new \InvalidArgumentException("Executive Decision ID {$id} not found");
        }

        $now = date('Y-m-d H:i:s');
        $update = [
            'status'     => 'ratified',
            'notes'      => $notes ? ($dec['notes'] . "\nRatified by {$ratifiedBy}: {$notes}") : $dec['notes'],
            'updated_at' => $now,
        ];

        $this->db->table('executive_decisions')->where('id', $id)->update($update);

        $this->logAudit(
            'ratify',
            'executive_decision',
            (string)$id,
            $actorUserId,
            null,
            $dec,
            $update,
            "Executive Decision {$dec['decision_code']} ratified by {$ratifiedBy}"
        );

        return $this->getExecutiveDecision($id);
    }

    // =========================================================================
    // 6. Governance Audit Logs
    // =========================================================================

    public function getAuditLogs(int $limit = 50, int $offset = 0): array
    {
        return $this->db->table('governance_audit_logs')
            ->orderBy('id', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    public function logAudit(
        string $action,
        string $entityType,
        string $entityId,
        ?int $actorUserId = null,
        ?string $actorAgentId = null,
        ?array $oldState = null,
        ?array $newState = null,
        ?string $notes = null
    ): void {
        $actorType = 'system';
        $actorId = 'system';
        $actorName = 'MCE System';

        if ($actorUserId) {
            $user = $this->db->table('users')->where('id', $actorUserId)->get()->getRowArray();
            $actorType = 'user';
            $actorId = (string)$actorUserId;
            $actorName = $user['full_name'] ?? ($user['username'] ?? "User #{$actorUserId}");
        } elseif ($actorAgentId) {
            $actorType = 'agent';
            $actorId = $actorAgentId;
            $actorName = "Agent {$actorAgentId}";
        }

        $this->db->table('governance_audit_logs')->insert([
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'action'      => $action,
            'actor_type'  => $actorType,
            'actor_id'    => $actorId,
            'actor_name'  => $actorName,
            'old_state'   => $oldState ? json_encode($oldState) : null,
            'new_state'   => $newState ? json_encode($newState) : null,
            'notes'       => $notes,
            'ip_address'  => service('request')->getIPAddress() ?: '127.0.0.1',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }
}
