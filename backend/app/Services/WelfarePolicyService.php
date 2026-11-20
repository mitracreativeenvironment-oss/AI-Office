<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class WelfarePolicyService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Get currently active approved policy.
     */
    public function getActivePolicy(): ?array
    {
        $policy = $this->db->table('welfare_policies')
            ->where('is_active', 1)
            ->where('approval_status', 'approved')
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        if ($policy) {
            $policy['id'] = (int)$policy['id'];
            $policy['base_duty_tickets'] = (float)$policy['base_duty_tickets'];
            $policy['max_issuance_limit_per_batch'] = (float)$policy['max_issuance_limit_per_batch'];
            $policy['requires_human_approval'] = (int)$policy['requires_human_approval'];
            $policy['is_active'] = (int)$policy['is_active'];
        }

        return $policy;
    }

    /**
     * List all policy versions.
     */
    public function listPolicies(): array
    {
        $policies = $this->db->table('welfare_policies')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        foreach ($policies as &$p) {
            $p['id'] = (int)$p['id'];
            $p['base_duty_tickets'] = (float)$p['base_duty_tickets'];
            $p['max_issuance_limit_per_batch'] = (float)$p['max_issuance_limit_per_batch'];
            $p['requires_human_approval'] = (int)$p['requires_human_approval'];
            $p['is_active'] = (int)$p['is_active'];
        }

        return $policies;
    }

    /**
     * Create a new policy proposal (Draft or Pending Approval).
     */
    public function createPolicy(array $data, ?int $actorUserId = null): array
    {
        $code = trim($data['policy_code'] ?? 'POL-WLF-' . date('Y') . '-V' . rand(2, 99));
        $version = trim($data['version'] ?? '1.1.0');
        $now = date('Y-m-d H:i:s');

        $insert = [
            'policy_code'                  => $code,
            'version'                      => $version,
            'title'                        => trim($data['title'] ?? 'Updated Welfare Compensation Policy'),
            'description'                  => $data['description'] ?? null,
            'ticket_unit_name'             => $data['ticket_unit_name'] ?? 'Welfare Ticket',
            'ticket_unit_symbol'           => $data['ticket_unit_symbol'] ?? 'WT',
            'base_period'                  => $data['base_period'] ?? 'weekly',
            'base_duty_tickets'            => isset($data['base_duty_tickets']) ? (float)$data['base_duty_tickets'] : 10.00,
            'bonus_calculation_method'     => $data['bonus_calculation_method'] ?? 'task_category',
            'max_issuance_limit_per_batch' => isset($data['max_issuance_limit_per_batch']) ? (float)$data['max_issuance_limit_per_batch'] : 1000.00,
            'requires_human_approval'      => 1,
            'approval_status'              => 'pending_approval',
            'effective_from'               => $data['effective_from'] ?? date('Y-m-d'),
            'created_by'                   => $actorUserId,
            'change_reason'                => $data['change_reason'] ?? 'Policy amendment proposal',
            'is_active'                    => 0,
            'created_at'                   => $now,
            'updated_at'                   => $now,
        ];

        $this->db->table('welfare_policies')->insert($insert);
        $insertId = (int)$this->db->insertID();

        // Audit Trail
        $this->db->table('welfare_audit_trail')->insert([
            'event_uuid'     => \App\Services\AgentRegistryService::generateUuid(),
            'event_category' => 'policy',
            'action'         => 'policy_created',
            'target_type'    => 'welfare_policy',
            'target_id'      => $insertId,
            'actor_type'     => 'human_user',
            'actor_id'       => $actorUserId,
            'details'        => json_encode(['policy_code' => $code, 'version' => $version]),
            'created_at'     => $now,
        ]);

        return $this->db->table('welfare_policies')->where('id', $insertId)->get()->getRowArray();
    }

    /**
     * Approve and activate a policy (Supersedes older versions).
     */
    public function approvePolicy(int $policyId, int $actorUserId, ?string $notes = null): array
    {
        $policy = $this->db->table('welfare_policies')->where('id', $policyId)->get()->getRowArray();
        if (!$policy) {
            throw new \InvalidArgumentException("Policy #{$policyId} not found.", 404);
        }

        $now = date('Y-m-d H:i:s');

        // Deactivate other policies
        $this->db->table('welfare_policies')->where('id !=', $policyId)->update([
            'is_active'  => 0,
            'updated_at' => $now,
        ]);

        // Activate this policy
        $this->db->table('welfare_policies')->where('id', $policyId)->update([
            'approval_status' => 'approved',
            'approved_by'     => $actorUserId,
            'is_active'       => 1,
            'updated_at'      => $now,
        ]);

        // Audit Trail
        $this->db->table('welfare_audit_trail')->insert([
            'event_uuid'     => \App\Services\AgentRegistryService::generateUuid(),
            'event_category' => 'policy',
            'action'         => 'policy_approved',
            'target_type'    => 'welfare_policy',
            'target_id'      => $policyId,
            'actor_type'     => 'human_user',
            'actor_id'       => $actorUserId,
            'details'        => json_encode(['notes' => $notes, 'policy_code' => $policy['policy_code']]),
            'created_at'     => $now,
        ]);

        return $this->db->table('welfare_policies')->where('id', $policyId)->get()->getRowArray();
    }
}
