<?php

namespace App\Services;

use Config\Database;

class AgentRegistryService
{
    protected $db;

    public function __construct($db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Generate an RFC 4122 v4 UUID.
     */
    public static function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // version 4
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variant RFC 4122
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * List agents with filtering, search, and pagination.
     */
    public function listAgents(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $builder = $this->db->table('ai_agent_registry r')
            ->select('r.*, u.name as unit_name, u.code as unit_code, p.title as position_title, p.code as position_code')
            ->join('organization_units u', 'u.id = r.primary_unit_id', 'left')
            ->join('organization_positions p', 'p.id = r.primary_position_id', 'left')
            ->where('r.deleted_at IS NULL');

        if (!empty($filters['lifecycle_state'])) {
            $builder->where('r.lifecycle_state', $filters['lifecycle_state']);
        }
        if (!empty($filters['operational_status'])) {
            $builder->where('r.operational_status', $filters['operational_status']);
        }
        if (!empty($filters['classification'])) {
            $builder->where('r.classification', $filters['classification']);
        }
        if (!empty($filters['unit_id'])) {
            $builder->where('r.primary_unit_id', (int)$filters['unit_id']);
        }
        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $builder->groupStart()
                ->like('r.name', $term)
                ->orLike('r.display_name', $term)
                ->orLike('r.agent_code', $term)
                ->groupEnd();
        }

        // Total count clone
        $total = $builder->countAllResults(false);

        $builder->orderBy('r.id', 'ASC');
        $builder->limit($limit, $offset);
        $items = $builder->get()->getResultArray();

        foreach ($items as &$item) {
            $item['id'] = (int)$item['id'];
            $item['primary_unit_id'] = $item['primary_unit_id'] ? (int)$item['primary_unit_id'] : null;
            $item['primary_position_id'] = $item['primary_position_id'] ? (int)$item['primary_position_id'] : null;

            // Counts of active skills and responsibilities
            $item['skills_count'] = $this->db->table('ai_agent_skills')
                ->where('agent_id', $item['id'])
                ->where('is_active', 1)
                ->countAllResults();

            $item['responsibilities_count'] = $this->db->table('ai_agent_responsibilities')
                ->where('agent_id', $item['id'])
                ->where('status', 'active')
                ->countAllResults();

            // Runtime mapping preview
            $runtime = $this->db->table('ai_agent_runtime_mappings')
                ->where('agent_id', $item['id'])
                ->get()
                ->getRowArray();
            $item['runtime_status'] = $runtime['integration_status'] ?? 'unconfigured';
            $item['runtime_provider'] = $runtime['provider'] ?? null;
        }

        return [
            'items'  => $items,
            'total'  => $total,
            'limit'  => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Get single agent by ID with full profile, placement, skills, and runtime mappings.
     */
    public function getAgent(int $id): ?array
    {
        $agent = $this->db->table('ai_agent_registry r')
            ->select('r.*, u.name as unit_name, u.code as unit_code, p.title as position_title, p.code as position_code')
            ->join('organization_units u', 'u.id = r.primary_unit_id', 'left')
            ->join('organization_positions p', 'p.id = r.primary_position_id', 'left')
            ->where('r.id', $id)
            ->where('r.deleted_at IS NULL')
            ->get()
            ->getRowArray();

        if (!$agent) {
            return null;
        }

        $agent['id'] = (int)$agent['id'];
        $agent['primary_unit_id'] = $agent['primary_unit_id'] ? (int)$agent['primary_unit_id'] : null;
        $agent['primary_position_id'] = $agent['primary_position_id'] ? (int)$agent['primary_position_id'] : null;

        // Profile
        $agent['profile'] = $this->db->table('ai_agent_profiles')->where('agent_id', $id)->get()->getRowArray() ?: null;
        if ($agent['profile'] && !empty($agent['profile']['communication_preferences'])) {
            $agent['profile']['communication_preferences'] = json_decode($agent['profile']['communication_preferences'], true);
        }

        // Active Skills
        $agent['skills'] = $this->db->table('ai_agent_skills s')
            ->select('s.*, sc.code as skill_code, sc.name as skill_name, sc.category as skill_category')
            ->join('skill_catalogue sc', 'sc.id = s.skill_id')
            ->where('s.agent_id', $id)
            ->where('s.is_active', 1)
            ->get()
            ->getResultArray();

        // Active Responsibilities
        $agent['responsibilities'] = $this->db->table('ai_agent_responsibilities')
            ->where('agent_id', $id)
            ->where('status', 'active')
            ->orderBy('priority', 'ASC')
            ->get()
            ->getResultArray();

        // Active Organizational Assignment
        $agent['active_assignment'] = $this->db->table('ai_agent_assignments a')
            ->select('a.*, u.name as unit_name, p.title as position_title')
            ->join('organization_units u', 'u.id = a.unit_id', 'left')
            ->join('organization_positions p', 'p.id = a.position_id', 'left')
            ->where('a.agent_id', $id)
            ->where('a.status', 'active')
            ->get()
            ->getRowArray();

        // Runtime Mapping (Technical Identity)
        $agent['runtime_mapping'] = $this->db->table('ai_agent_runtime_mappings')
            ->where('agent_id', $id)
            ->get()
            ->getRowArray();

        return $agent;
    }

    public function getAgentByUuid(string $uuid): ?array
    {
        $row = $this->db->table('ai_agent_registry')->where('agent_uuid', $uuid)->where('deleted_at IS NULL')->get()->getRowArray();
        return $row ? $this->getAgent((int)$row['id']) : null;
    }

    public function getAgentByCode(string $code): ?array
    {
        $row = $this->db->table('ai_agent_registry')->where('agent_code', $code)->where('deleted_at IS NULL')->get()->getRowArray();
        return $row ? $this->getAgent((int)$row['id']) : null;
    }

    /**
     * Create a new Agent Identity in Draft state.
     */
    public function createAgent(array $data, ?int $actorUserId = null): array
    {
        $name = trim($data['name'] ?? '');
        $displayName = trim($data['display_name'] ?? $name);

        if (empty($name)) {
            throw new \InvalidArgumentException('Agent name is required.', 422);
        }

        $code = trim($data['agent_code'] ?? '');
        if (empty($code)) {
            $code = 'AGT-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 4)) . '-' . rand(100, 999);
        }

        // Check uniqueness of agent_code
        $exists = $this->db->table('ai_agent_registry')->where('agent_code', $code)->countAllResults();
        if ($exists > 0) {
            throw new \RuntimeException("Agent code '{$code}' is already registered.", 409);
        }

        $now = date('Y-m-d H:i:s');
        $uuid = self::generateUuid();

        $unitId = !empty($data['primary_unit_id']) ? (int)$data['primary_unit_id'] : null;
        if ($unitId) {
            $unitExists = $this->db->table('organization_units')->where('id', $unitId)->countAllResults();
            if ($unitExists === 0) {
                throw new \InvalidArgumentException("Unit ID #{$unitId} not found.", 404);
            }
        }

        $positionId = !empty($data['primary_position_id']) ? (int)$data['primary_position_id'] : null;
        if ($positionId) {
            $posExists = $this->db->table('organization_positions')->where('id', $positionId)->countAllResults();
            if ($posExists === 0) {
                throw new \InvalidArgumentException("Position ID #{$positionId} not found.", 404);
            }
        }

        $insert = [
            'agent_uuid'                => $uuid,
            'agent_code'                => $code,
            'name'                      => $name,
            'display_name'              => $displayName,
            'classification'            => $data['classification'] ?? 'operational',
            'lifecycle_state'           => 'draft',
            'operational_status'        => 'idle',
            'primary_unit_id'           => $unitId,
            'primary_position_id'       => $positionId,
            'roster_schedule_type'      => $data['roster_schedule_type'] ?? '5_2_staggered',
            'welfare_tier'              => $data['welfare_tier'] ?? 'tier_standard',
            'designated_replacement_id' => !empty($data['designated_replacement_id']) ? (int)$data['designated_replacement_id'] : null,
            'created_by_user_id'        => $actorUserId,
            'created_at'                => $now,
            'updated_at'                => $now,
        ];

        $this->db->table('ai_agent_registry')->insert($insert);
        $agentId = $this->db->insertID();

        // Initial Profile
        $this->db->table('ai_agent_profiles')->insert([
            'agent_id'                          => $agentId,
            'description'                       => $data['description'] ?? null,
            'organizational_purpose'            => $data['organizational_purpose'] ?? null,
            'primary_responsibilities_summary'  => $data['primary_responsibilities_summary'] ?? null,
            'secondary_responsibilities_summary'=> $data['secondary_responsibilities_summary'] ?? null,
            'core_capabilities_summary'         => $data['core_capabilities_summary'] ?? null,
            'communication_preferences'         => !empty($data['communication_preferences']) ? (is_array($data['communication_preferences']) ? json_encode($data['communication_preferences']) : $data['communication_preferences']) : null,
            'profile_version'                   => 1,
            'created_at'                        => $now,
            'updated_at'                        => $now,
        ]);

        // Lifecycle event: creation in draft
        $this->db->table('ai_agent_lifecycle_events')->insert([
            'agent_id'             => $agentId,
            'from_state'           => 'none',
            'to_state'             => 'draft',
            'initiated_by_user_id' => $actorUserId,
            'initiated_by_name'    => $actorUserId ? "User #{$actorUserId}" : 'System Onboarding',
            'reason'               => 'Agent identity registered in MCE AI Workforce Registry.',
            'created_at'           => $now,
        ]);

        // Initial Inaugural Performance History Record
        $this->db->table('ai_agent_performance_history')->insert([
            'agent_id'            => $agentId,
            'agent_uuid'          => $uuid,
            'category'            => 'lifecycle_transition',
            'title'               => 'Registrasi Identitas Workforce',
            'description'         => "Identitas permanen agen {$displayName} ({$code}) berhasil diregistrasi di database MCE.",
            'task_ref'            => 'REGISTRATION',
            'output_ref'          => "UUID: {$uuid}",
            'evidence_status'     => 'verified',
            'evaluator_identity'  => $actorUserId ? "User #{$actorUserId}" : 'System',
            'unit_id_at_time'     => $unitId,
            'position_id_at_time' => $positionId,
            'created_at'          => $now,
        ]);

        // Log Governance Audit
        $this->logAudit('create', 'ai_agent_registry', (string)$agentId, $actorUserId, null, $insert, "Created AI Agent {$code} ({$name})");

        return $this->getAgent($agentId);
    }

    /**
     * Update Agent Identity properties (display name, classification, policy fields).
     * Prevents modifying immutable fields (UUID, agent_code).
     */
    public function updateAgent(int $id, array $data, ?int $actorUserId = null): array
    {
        $agent = $this->db->table('ai_agent_registry')->where('id', $id)->where('deleted_at IS NULL')->get()->getRowArray();
        if (!$agent) {
            throw new \RuntimeException("Agent #{$id} not found.", 404);
        }

        $now = date('Y-m-d H:i:s');
        $update = [];

        if (isset($data['display_name'])) $update['display_name'] = trim($data['display_name']);
        if (isset($data['classification'])) $update['classification'] = trim($data['classification']);
        if (isset($data['operational_status'])) $update['operational_status'] = trim($data['operational_status']);
        if (isset($data['roster_schedule_type'])) $update['roster_schedule_type'] = trim($data['roster_schedule_type']);
        if (isset($data['welfare_tier'])) $update['welfare_tier'] = trim($data['welfare_tier']);
        if (array_key_exists('designated_replacement_id', $data)) {
            $update['designated_replacement_id'] = !empty($data['designated_replacement_id']) ? (int)$data['designated_replacement_id'] : null;
        }

        if (empty($update)) {
            return $this->getAgent($id);
        }

        $update['updated_at'] = $now;
        $this->db->table('ai_agent_registry')->where('id', $id)->update($update);

        $this->logAudit('update', 'ai_agent_registry', (string)$id, $actorUserId, $agent, $update, "Updated AI Agent properties for {$agent['agent_code']}");

        return $this->getAgent($id);
    }

    /**
     * Update Agent Profile (increments profile_version).
     */
    public function updateProfile(int $agentId, array $data, ?int $actorUserId = null): array
    {
        $profile = $this->db->table('ai_agent_profiles')->where('agent_id', $agentId)->get()->getRowArray();
        $now = date('Y-m-d H:i:s');

        $update = [
            'updated_at' => $now,
        ];

        if (isset($data['description'])) $update['description'] = $data['description'];
        if (isset($data['organizational_purpose'])) $update['organizational_purpose'] = $data['organizational_purpose'];
        if (isset($data['primary_responsibilities_summary'])) $update['primary_responsibilities_summary'] = $data['primary_responsibilities_summary'];
        if (isset($data['secondary_responsibilities_summary'])) $update['secondary_responsibilities_summary'] = $data['secondary_responsibilities_summary'];
        if (isset($data['core_capabilities_summary'])) $update['core_capabilities_summary'] = $data['core_capabilities_summary'];
        if (isset($data['communication_preferences'])) {
            $update['communication_preferences'] = is_array($data['communication_preferences']) ? json_encode($data['communication_preferences']) : $data['communication_preferences'];
        }

        if ($profile) {
            $update['profile_version'] = (int)$profile['profile_version'] + 1;
            $this->db->table('ai_agent_profiles')->where('agent_id', $agentId)->update($update);
        } else {
            $update['agent_id'] = $agentId;
            $update['profile_version'] = 1;
            $update['created_at'] = $now;
            $this->db->table('ai_agent_profiles')->insert($update);
        }

        $this->logAudit('update', 'ai_agent_profile', (string)$agentId, $actorUserId, $profile, $update, "Updated profile for agent #{$agentId}");

        return $this->getAgent($agentId);
    }

    /**
     * Get Workforce Metrics (real DB data, zero fake numbers).
     */
    public function getMetrics(): array
    {
        $total = $this->db->table('ai_agent_registry')->where('deleted_at IS NULL')->countAllResults();

        // By Lifecycle State
        $states = ['draft', 'proposed', 'under_review', 'approved', 'provisioning_pending', 'active', 'temporarily_suspended', 'under_reconfiguration', 'retired', 'archived'];
        $byState = [];
        foreach ($states as $st) {
            $byState[$st] = $this->db->table('ai_agent_registry')
                ->where('lifecycle_state', $st)
                ->where('deleted_at IS NULL')
                ->countAllResults();
        }

        // By Operational Status
        $statuses = ['idle', 'working', 'on_leave', 'handover_in_progress', 'offline'];
        $byStatus = [];
        foreach ($statuses as $stat) {
            $byStatus[$stat] = $this->db->table('ai_agent_registry')
                ->where('operational_status', $stat)
                ->where('deleted_at IS NULL')
                ->countAllResults();
        }

        // By Classification
        $classQuery = $this->db->table('ai_agent_registry')
            ->select('classification, COUNT(*) as count')
            ->where('deleted_at IS NULL')
            ->groupBy('classification')
            ->get()
            ->getResultArray();

        $byClassification = [];
        foreach ($classQuery as $row) {
            $byClassification[$row['classification']] = (int)$row['count'];
        }

        return [
            'total_registered_agents' => $total,
            'active_agents'           => $byState['active'] ?? 0,
            'suspended_agents'        => $byState['temporarily_suspended'] ?? 0,
            'under_review_agents'     => ($byState['proposed'] ?? 0) + ($byState['under_review'] ?? 0),
            'retired_agents'          => $byState['retired'] ?? 0,
            'by_lifecycle_state'      => $byState,
            'by_operational_status'   => $byStatus,
            'by_classification'       => $byClassification,
        ];
    }

    /**
     * Runtime Mapping Management (Hermes / 9Router boundary without storing raw API secrets).
     */
    public function getRuntimeMappings(int $agentId): array
    {
        return $this->db->table('ai_agent_runtime_mappings')
            ->where('agent_id', $agentId)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function setRuntimeMapping(int $agentId, array $data, ?int $actorUserId = null): array
    {
        $agent = $this->db->table('ai_agent_registry')->where('id', $agentId)->where('deleted_at IS NULL')->get()->getRowArray();
        if (!$agent) {
            throw new \RuntimeException("Agent #{$agentId} not found.", 404);
        }

        $now = date('Y-m-d H:i:s');
        $existing = $this->db->table('ai_agent_runtime_mappings')->where('agent_id', $agentId)->get()->getRowArray();

        $payload = [
            'provider'                  => $data['provider'] ?? ($existing['provider'] ?? 'hermes_docker'),
            'runtime_profile_reference' => $data['runtime_profile_reference'] ?? ($existing['runtime_profile_reference'] ?? ('hermes-profile-' . strtolower(str_replace('AGT-', '', $agent['agent_code'])))),
            'runtime_instance_id'       => $data['runtime_instance_id'] ?? ($existing['runtime_instance_id'] ?? null),
            'model_gateway_preference'  => $data['model_gateway_preference'] ?? ($existing['model_gateway_preference'] ?? '9router'),
            'configuration_version'     => $data['configuration_version'] ?? ($existing['configuration_version'] ?? 'v1.0.0'),
            'integration_status'        => $data['integration_status'] ?? ($existing['integration_status'] ?? 'mapped'),
            'effective_start_date'      => $data['effective_start_date'] ?? ($existing['effective_start_date'] ?? $now),
            'effective_end_date'        => $data['effective_end_date'] ?? ($existing['effective_end_date'] ?? null),
            'notes'                     => $data['notes'] ?? ($existing['notes'] ?? null),
            'updated_at'                => $now,
        ];

        if ($existing) {
            $this->db->table('ai_agent_runtime_mappings')->where('id', $existing['id'])->update($payload);
            $mappingId = $existing['id'];
        } else {
            $payload['agent_id'] = $agentId;
            $payload['created_at'] = $now;
            $this->db->table('ai_agent_runtime_mappings')->insert($payload);
            $mappingId = $this->db->insertID();
        }

        $this->logAudit('update', 'ai_agent_runtime_mapping', (string)$mappingId, $actorUserId, $existing, $payload, "Configured runtime mapping for agent #{$agentId}");

        return $this->db->table('ai_agent_runtime_mappings')->where('id', $mappingId)->get()->getRowArray();
    }

    protected function logAudit(string $action, string $entityType, string $entityId, ?int $actorUserId, ?array $old, ?array $new, ?string $notes = null): void
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
