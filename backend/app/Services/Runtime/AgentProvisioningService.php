<?php

namespace App\Services\Runtime;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class AgentProvisioningService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Controlled 12-step Agent runtime provisioning workflow.
     */
    public function provisionAgent(int $agentId, array $options = []): array
    {
        // 1. Agent exists in registry
        $agent = $this->db->table('ai_agent_registry')->where('id', $agentId)->get()->getRowArray();
        if (!$agent) {
            throw new \InvalidArgumentException("Agent #{$agentId} not found in workforce registry.", 404);
        }

        // 2. Lifecycle status check
        if (!in_array($agent['lifecycle_state'], ['active', 'provisioning_pending'], true)) {
            throw new \RuntimeException("Agent #{$agentId} is in state '{$agent['lifecycle_state']}' and cannot be provisioned.", 400);
        }

        $now = date('Y-m-d H:i:s');
        $profileRef = $options['runtime_profile_reference'] ?? 'PROF-DEV-CODER';
        $instanceId = $options['runtime_instance_id'] ?? ('hermes-inst-' . strtolower(str_replace(['AGT-', '-'], ['', ''], $agent['agent_code'])));

        // 3. Upsert runtime mapping
        $mapping = $this->db->table('ai_agent_runtime_mappings')->where('agent_id', $agentId)->get()->getRowArray();

        if ($mapping) {
            $this->db->table('ai_agent_runtime_mappings')->where('id', $mapping['id'])->update([
                'provider'                  => $options['provider'] ?? 'hermes_simulation',
                'runtime_profile_reference' => $profileRef,
                'runtime_instance_id'       => $instanceId,
                'model_gateway_preference'  => '9router',
                'integration_status'        => 'ready',
                'notes'                     => 'Runtime mapping provisioned and validated for Hermes execution.',
                'updated_at'                => $now,
            ]);
            $mappingId = $mapping['id'];
        } else {
            $this->db->table('ai_agent_runtime_mappings')->insert([
                'agent_id'                  => $agentId,
                'provider'                  => $options['provider'] ?? 'hermes_simulation',
                'runtime_profile_reference' => $profileRef,
                'runtime_instance_id'       => $instanceId,
                'model_gateway_preference'  => '9router',
                'configuration_version'     => 'v1.0.0',
                'integration_status'        => 'ready',
                'effective_start_date'      => $now,
                'notes'                     => 'Initial runtime provisioning.',
                'created_at'                => $now,
                'updated_at'                => $now,
            ]);
            $mappingId = (int)$this->db->insertID();
        }

        // 4. Audit event
        $this->db->table('ai_agent_lifecycle_events')->insert([
            'agent_id'             => $agentId,
            'from_state'           => $agent['lifecycle_state'],
            'to_state'             => $agent['lifecycle_state'],
            'initiated_by_user_id' => $options['actor_user_id'] ?? null,
            'initiated_by_name'    => 'Runtime Provisioning Engine',
            'reason'               => "Hermes runtime instance {$instanceId} provisioned with profile {$profileRef}.",
            'created_at'           => $now,
        ]);

        return [
            'agent_id'             => $agentId,
            'agent_code'           => $agent['agent_code'],
            'runtime_instance_id'  => $instanceId,
            'runtime_profile'      => $profileRef,
            'model_gateway'        => '9router',
            'integration_status'   => 'ready',
            'provisioned_at'       => $now,
        ];
    }

    public function suspendRuntime(int $agentId, string $reason = 'Suspended by Operator'): bool
    {
        $mapping = $this->db->table('ai_agent_runtime_mappings')->where('agent_id', $agentId)->get()->getRowArray();
        if (!$mapping) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('ai_agent_runtime_mappings')->where('id', $mapping['id'])->update([
            'integration_status' => 'disabled',
            'notes'              => "Runtime suspended: {$reason}",
            'updated_at'         => $now,
        ]);

        return true;
    }

    public function validateReadiness(int $agentId): array
    {
        $agent = $this->db->table('ai_agent_registry')->where('id', $agentId)->get()->getRowArray();
        if (!$agent) {
            return ['ready' => false, 'reason' => 'Agent not found.'];
        }

        $mapping = $this->db->table('ai_agent_runtime_mappings')->where('agent_id', $agentId)->get()->getRowArray();
        if (!$mapping || $mapping['integration_status'] !== 'ready') {
            return ['ready' => false, 'reason' => 'Runtime mapping not ready.'];
        }

        return [
            'ready'               => true,
            'agent_id'            => $agentId,
            'agent_code'          => $agent['agent_code'],
            'runtime_instance_id' => $mapping['runtime_instance_id'],
            'profile_reference'   => $mapping['runtime_profile_reference'],
            'gateway'             => $mapping['model_gateway_preference'],
            'status'              => 'ready',
        ];
    }
}
