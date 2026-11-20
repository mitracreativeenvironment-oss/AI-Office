<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class AgentMemoryService
{
    protected BaseConnection $db;
    protected KnowledgeContributionService $contributionService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->contributionService = new KnowledgeContributionService();
    }

    /**
     * List memories for a specific agent with privacy boundary checks
     */
    public function listMemories(int $agentId, array $filters = [], int $limit = 50, ?array $userContext = null): array
    {
        // Enforce privacy:
        // An agent can access its own memories.
        // Human admins, directors, or supervisors can audit.
        // Other agents cannot access another agent's private working/episodic memories unless shared.
        $this->validateMemoryAccess($agentId, $userContext);

        $builder = $this->db->table('agent_memories m')
            ->select('m.*, ag.name as agent_name, ag.agent_code')
            ->join('ai_agent_registry ag', 'ag.id = m.agent_id')
            ->where('m.agent_id', $agentId);

        if (!empty($filters['memory_type'])) {
            $builder->where('m.memory_type', $filters['memory_type']);
        }
        if (!empty($filters['status'])) {
            $builder->where('m.status', $filters['status']);
        } else {
            $builder->where('m.status', 'active');
        }
        if (!empty($filters['classification'])) {
            $builder->where('m.classification', $filters['classification']);
        }
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $builder->groupStart()
                ->like('m.title', $search)
                ->orLike('m.content', $search)
                ->orLike('m.context_tags_json', $search)
                ->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $builder->orderBy('m.importance_score', 'DESC')->orderBy('m.id', 'DESC');
        $builder->limit($limit);
        $items = $builder->get()->getResultArray();

        foreach ($items as &$item) {
            $item['id'] = (int)$item['id'];
            $item['agent_id'] = (int)$item['agent_id'];
            $item['importance_score'] = (float)$item['importance_score'];
            $item['access_count'] = (int)$item['access_count'];
            $item['tags'] = !empty($item['context_tags_json']) ? json_decode($item['context_tags_json'], true) : [];
        }

        return ['items' => $items, 'total' => $total];
    }

    public function getMemory(int $id, ?array $userContext = null): ?array
    {
        $memory = $this->db->table('agent_memories m')
            ->select('m.*, ag.name as agent_name, ag.agent_code')
            ->join('ai_agent_registry ag', 'ag.id = m.agent_id')
            ->where('m.id', $id)
            ->get()
            ->getRowArray();

        if (!$memory) return null;

        $this->validateMemoryAccess((int)$memory['agent_id'], $userContext);

        // Increment access count & update last accessed
        $now = date('Y-m-d H:i:s');
        $this->db->table('agent_memories')->where('id', $id)->update([
            'access_count'     => (int)$memory['access_count'] + 1,
            'last_accessed_at' => $now,
        ]);

        $memory['id'] = (int)$memory['id'];
        $memory['agent_id'] = (int)$memory['agent_id'];
        $memory['importance_score'] = (float)$memory['importance_score'];
        $memory['access_count'] = (int)$memory['access_count'] + 1;
        $memory['tags'] = !empty($memory['context_tags_json']) ? json_decode($memory['context_tags_json'], true) : [];

        // Attach versions
        $memory['versions'] = $this->db->table('agent_memory_versions')
            ->where('memory_id', $id)
            ->orderBy('version_number', 'DESC')
            ->get()
            ->getResultArray();

        return $memory;
    }

    public function createMemory(int $agentId, array $data): array
    {
        $type = $data['memory_type'] ?? 'episodic_memory';
        $prefix = strtoupper(substr($type, 0, 4));
        $code = trim($data['memory_code'] ?? 'MEM-' . $agentId . '-' . $prefix . '-' . rand(100, 999));
        $now = date('Y-m-d H:i:s');

        $retention = $data['retention_policy'] ?? 'permanent';
        $expiresAt = null;
        if ($retention === '30_days') {
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        } elseif ($retention === '90_days') {
            $expiresAt = date('Y-m-d H:i:s', strtotime('+90 days'));
        } elseif ($retention === 'session_ephemeral') {
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        }

        $insert = [
            'memory_code'       => $code,
            'agent_id'          => $agentId,
            'memory_type'       => $type,
            'title'             => trim($data['title'] ?? 'Consolidated Agent Memory'),
            'content'           => $data['content'] ?? '',
            'context_tags_json' => isset($data['tags']) && is_array($data['tags']) ? json_encode($data['tags']) : ($data['context_tags_json'] ?? null),
            'source_type'       => $data['source_type'] ?? 'task_execution',
            'source_reference'  => $data['source_reference'] ?? null,
            'classification'    => $data['classification'] ?? 'internal',
            'status'            => 'active',
            'retention_policy'  => $retention,
            'expires_at'        => $expiresAt,
            'importance_score'  => (float)($data['importance_score'] ?? 0.50),
            'access_count'      => 0,
            'last_accessed_at'  => $now,
            'created_at'        => $now,
            'updated_at'        => $now,
        ];

        $this->db->table('agent_memories')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();

        // Version 1
        $this->db->table('agent_memory_versions')->insert([
            'memory_id'      => $insert['id'],
            'version_number' => 1,
            'content'        => $insert['content'],
            'change_reason'  => 'Initial memory creation.',
            'created_at'     => $now,
        ]);

        return $this->getMemory($insert['id']);
    }

    public function updateMemory(int $id, array $data): array
    {
        $existing = $this->db->table('agent_memories')->where('id', $id)->get()->getRowArray();
        if (!$existing) {
            throw new \InvalidArgumentException("Memory #{$id} not found.", 404);
        }

        $now = date('Y-m-d H:i:s');
        $update = ['updated_at' => $now];

        if (isset($data['title'])) $update['title'] = trim($data['title']);
        if (isset($data['content'])) $update['content'] = $data['content'];
        if (isset($data['importance_score'])) $update['importance_score'] = (float)$data['importance_score'];
        if (isset($data['status'])) $update['status'] = $data['status'];
        if (isset($data['tags'])) $update['context_tags_json'] = json_encode($data['tags']);

        $this->db->table('agent_memories')->where('id', $id)->update($update);

        if (!empty($data['content']) && $data['content'] !== $existing['content']) {
            $latestVer = $this->db->table('agent_memory_versions')
                ->where('memory_id', $id)
                ->selectMax('version_number')
                ->get()
                ->getRowArray();
            $nextVer = ((int)($latestVer['version_number'] ?? 0)) + 1;

            $this->db->table('agent_memory_versions')->insert([
                'memory_id'      => $id,
                'version_number' => $nextVer,
                'content'        => $data['content'],
                'change_reason'  => $data['change_reason'] ?? 'Memory updated by agent reasoning.',
                'created_at'     => $now,
            ]);
        }

        return $this->getMemory($id);
    }

    public function archiveMemory(int $id): array
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('agent_memories')->where('id', $id)->update([
            'status'     => 'archived',
            'updated_at' => $now,
        ]);
        return $this->getMemory($id);
    }

    /**
     * Promote an agent's memory into a formal organizational knowledge contribution
     */
    public function promoteMemoryToContribution(int $memoryId, array $proposalData, ?int $actorAgentId = null): array
    {
        $memory = $this->db->table('agent_memories')->where('id', $memoryId)->get()->getRowArray();
        if (!$memory) {
            throw new \InvalidArgumentException("Memory #{$memoryId} not found.", 404);
        }

        $agentId = $actorAgentId ?? (int)$memory['agent_id'];

        $payload = [
            'contributor_agent_id'    => $agentId,
            'proposed_category_id'    => $proposalData['proposed_category_id'] ?? 7, // Agent knowledge
            'proposed_classification' => $proposalData['proposed_classification'] ?? 'internal',
            'title'                   => $proposalData['title'] ?? "Promoted Memory: {$memory['title']}",
            'summary'                 => $proposalData['summary'] ?? "Distilled from agent memory {$memory['memory_code']}.",
            'content'                 => $proposalData['content'] ?? $memory['content'],
            'evidence_references'     => $memory['source_reference'] ?? $memory['memory_code'],
            'source_context'          => "agent_memory:{$memoryId}",
        ];

        return $this->contributionService->submitContribution($payload, $agentId);
    }

    protected function validateMemoryAccess(int $targetAgentId, ?array $userContext = null): void
    {
        if (empty($userContext)) {
            return; // Allow internal system invocations
        }

        $roles = $userContext['roles'] ?? [];
        if (in_array('superadmin', $roles) || in_array('admin', $roles) || in_array('director', $roles) || in_array('executive', $roles)) {
            return;
        }

        // If caller is an agent
        $callerAgentId = (int)($userContext['agent_id'] ?? 0);
        if ($callerAgentId && $callerAgentId === $targetAgentId) {
            return;
        }

        // Otherwise forbidden
        throw new \RuntimeException("Access denied: You are not authorized to inspect Agent #{$targetAgentId}'s private memories.", 403);
    }
}
