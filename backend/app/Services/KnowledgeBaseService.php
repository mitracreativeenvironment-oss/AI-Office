<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class KnowledgeBaseService
{
    protected BaseConnection $db;
    protected KnowledgeAccessService $accessService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->accessService = new KnowledgeAccessService();
    }

    /**
     * Campus-wide knowledge management metrics overview.
     */
    public function getOverview(): array
    {
        $totalRecords = $this->db->table('knowledge_records')->countAllResults();
        $publishedRecords = $this->db->table('knowledge_records')->where('status', 'published')->countAllResults();
        $draftRecords = $this->db->table('knowledge_records')->where('status', 'draft')->countAllResults();
        $underReviewRecords = $this->db->table('knowledge_records')->whereIn('status', ['submitted', 'under_review'])->countAllResults();
        $archivedRecords = $this->db->table('knowledge_records')->whereIn('status', ['archived', 'superseded'])->countAllResults();

        $totalDocuments = $this->db->table('documents')->countAllResults();
        $activeMemories = $this->db->table('agent_memories')->where('status', 'active')->countAllResults();
        $totalContributions = $this->db->table('knowledge_contributions')->countAllResults();
        $pendingContributions = $this->db->table('knowledge_contributions')->where('status', 'submitted')->countAllResults();

        $activeWallbooks = $this->db->table('wallbook_handovers')->whereIn('status', ['draft', 'submitted', 'awaiting_acknowledgement', 'under_review'])->countAllResults();

        // Count by domain
        $byDomain = [
            'corporate'       => $this->db->table('knowledge_records r')->join('knowledge_categories c', 'c.id = r.category_id')->where('c.domain', 'corporate')->countAllResults(),
            'operational'     => $this->db->table('knowledge_records r')->join('knowledge_categories c', 'c.id = r.category_id')->where('c.domain', 'operational')->countAllResults(),
            'project'         => $this->db->table('knowledge_records r')->join('knowledge_categories c', 'c.id = r.category_id')->where('c.domain', 'project')->countAllResults(),
            'technical'       => $this->db->table('knowledge_records r')->join('knowledge_categories c', 'c.id = r.category_id')->where('c.domain', 'technical')->countAllResults(),
            'agent_knowledge' => $this->db->table('knowledge_records r')->join('knowledge_categories c', 'c.id = r.category_id')->where('c.domain', 'agent_knowledge')->countAllResults(),
        ];

        return [
            'total_records'          => $totalRecords,
            'published_records'      => $publishedRecords,
            'draft_records'          => $draftRecords,
            'under_review_records'   => $underReviewRecords,
            'archived_records'       => $archivedRecords,
            'total_documents'        => $totalDocuments,
            'active_memories'        => $activeMemories,
            'total_contributions'    => $totalContributions,
            'pending_contributions'  => $pendingContributions,
            'active_wallbook_entries'=> $activeWallbooks,
            'by_domain'              => $byDomain,
        ];
    }

    /**
     * Categories Listing
     */
    public function listCategories(array $filters = []): array
    {
        $builder = $this->db->table('knowledge_categories');
        if (!empty($filters['domain'])) {
            $builder->where('domain', $filters['domain']);
        }
        if (isset($filters['is_active'])) {
            $builder->where('is_active', (int)$filters['is_active']);
        }
        $builder->orderBy('domain', 'ASC')->orderBy('name', 'ASC');
        $items = $builder->get()->getResultArray();

        foreach ($items as &$cat) {
            $cat['id'] = (int)$cat['id'];
            $cat['records_count'] = $this->db->table('knowledge_records')->where('category_id', $cat['id'])->countAllResults();
        }

        return $items;
    }

    public function getCategory(int $id): ?array
    {
        $cat = $this->db->table('knowledge_categories')->where('id', $id)->get()->getRowArray();
        if ($cat) {
            $cat['id'] = (int)$cat['id'];
            $cat['records_count'] = $this->db->table('knowledge_records')->where('category_id', $id)->countAllResults();
        }
        return $cat;
    }

    public function createCategory(array $data): array
    {
        $code = trim($data['category_code'] ?? 'CAT-' . strtoupper(substr($data['domain'] ?? 'GEN', 0, 4)) . '-' . rand(100, 999));
        $now = date('Y-m-d H:i:s');
        $insert = [
            'category_code'          => $code,
            'domain'                 => $data['domain'] ?? 'operational',
            'name'                   => trim($data['name'] ?? 'Untitled Category'),
            'description'            => $data['description'] ?? null,
            'parent_category_id'     => !empty($data['parent_category_id']) ? (int)$data['parent_category_id'] : null,
            'default_classification' => $data['default_classification'] ?? 'internal',
            'is_active'              => (int)($data['is_active'] ?? 1),
            'created_at'             => $now,
            'updated_at'             => $now,
        ];

        $this->db->table('knowledge_categories')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();
        return $insert;
    }

    /**
     * Knowledge Records Listing with authorization-aware filtering
     */
    public function listRecords(array $filters = [], int $limit = 20, int $offset = 0, ?array $userContext = null): array
    {
        $builder = $this->db->table('knowledge_records r')
            ->select('r.*, c.name as category_name, c.domain as category_domain, u.name as unit_name,
                      author_user.full_name as author_user_name,
                      author_agent.name as author_agent_name, author_agent.agent_code as author_agent_code')
            ->join('knowledge_categories c', 'c.id = r.category_id')
            ->join('organization_units u', 'u.id = r.owner_unit_id', 'left')
            ->join('users author_user', 'author_user.id = r.author_user_id', 'left')
            ->join('ai_agent_registry author_agent', 'author_agent.id = r.author_agent_id', 'left');

        // Access Control Filter
        $allowedClassifications = $this->accessService->getAllowedClassifications($userContext);
        $builder->whereIn('r.classification', $allowedClassifications);

        if (!empty($filters['domain'])) {
            $builder->where('c.domain', $filters['domain']);
        }
        if (!empty($filters['category_id'])) {
            $builder->where('r.category_id', (int)$filters['category_id']);
        }
        if (!empty($filters['classification'])) {
            $builder->where('r.classification', $filters['classification']);
        }
        if (!empty($filters['status'])) {
            $builder->where('r.status', $filters['status']);
        } else {
            // Default: show published records or records owned by user
            if (empty($userContext)) {
                $builder->where('r.status', 'published');
            }
        }
        if (!empty($filters['owner_unit_id'])) {
            $builder->where('r.owner_unit_id', (int)$filters['owner_unit_id']);
        }
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $builder->groupStart()
                ->like('r.title', $search)
                ->orLike('r.summary', $search)
                ->orLike('r.record_code', $search)
                ->orLike('r.tags_json', $search)
                ->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $builder->orderBy('r.is_latest', 'DESC')->orderBy('r.id', 'DESC');
        $builder->limit($limit, $offset);
        $items = $builder->get()->getResultArray();

        foreach ($items as &$item) {
            $item['id'] = (int)$item['id'];
            $item['category_id'] = (int)$item['category_id'];
            $item['tags'] = !empty($item['tags_json']) ? json_decode($item['tags_json'], true) : [];
        }

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Get single record with clearance authorization check
     */
    public function getRecord(int $id, ?array $userContext = null): ?array
    {
        $record = $this->db->table('knowledge_records r')
            ->select('r.*, c.name as category_name, c.domain as category_domain, u.name as unit_name,
                      author_user.full_name as author_user_name,
                      author_agent.name as author_agent_name, author_agent.agent_code as author_agent_code')
            ->join('knowledge_categories c', 'c.id = r.category_id')
            ->join('organization_units u', 'u.id = r.owner_unit_id', 'left')
            ->join('users author_user', 'author_user.id = r.author_user_id', 'left')
            ->join('ai_agent_registry author_agent', 'author_agent.id = r.author_agent_id', 'left')
            ->where('r.id', $id)
            ->get()
            ->getRowArray();

        if (!$record) return null;

        // Check classification clearance if user context is provided
        if ($userContext !== null && !$this->accessService->canAccessClassification($record['classification'], $userContext, (int)($record['owner_unit_id'] ?? 0))) {
            throw new \RuntimeException("Access denied: You do not hold sufficient clearance for {$record['classification']} knowledge.", 403);
        }

        $record['id'] = (int)$record['id'];
        $record['category_id'] = (int)$record['category_id'];
        $record['tags'] = !empty($record['tags_json']) ? json_decode($record['tags_json'], true) : [];

        // Attach versions history
        $record['versions'] = $this->db->table('knowledge_versions v')
            ->select('v.*, u.full_name as approved_by_name')
            ->join('users u', 'u.id = v.approved_by_user_id', 'left')
            ->where('v.record_id', $id)
            ->orderBy('v.id', 'DESC')
            ->get()
            ->getResultArray();

        return $record;
    }

    /**
     * Create Knowledge Record Draft
     */
    public function createRecord(array $data, ?int $actorUserId = null, ?int $actorAgentId = null): array
    {
        $catId = (int)($data['category_id'] ?? 1);
        $cat = $this->getCategory($catId);
        $domainPrefix = strtoupper(substr($cat['domain'] ?? 'OPS', 0, 4));

        $code = trim($data['record_code'] ?? 'KB-' . $domainPrefix . '-' . rand(100, 999));
        $now = date('Y-m-d H:i:s');

        $authorType = $actorAgentId ? 'agent' : 'human';
        $status = $data['status'] ?? 'draft';

        $insert = [
            'record_code'           => $code,
            'category_id'           => $catId,
            'title'                 => trim($data['title'] ?? 'Untitled Article'),
            'summary'               => $data['summary'] ?? null,
            'content'               => $data['content'] ?? '',
            'content_format'        => $data['content_format'] ?? 'markdown',
            'classification'        => $data['classification'] ?? ($cat['default_classification'] ?? 'internal'),
            'status'                => $status,
            'version'               => $data['version'] ?? '1.0',
            'owner_type'            => $data['owner_type'] ?? 'unit',
            'owner_unit_id'         => !empty($data['owner_unit_id']) ? (int)$data['owner_unit_id'] : 1,
            'author_type'           => $authorType,
            'author_user_id'        => $actorUserId,
            'author_agent_id'       => $actorAgentId,
            'provenance_type'       => $data['provenance_type'] ?? ($actorAgentId ? 'agent_generated' : 'human_authored'),
            'provenance_source_ref' => $data['provenance_source_ref'] ?? null,
            'related_task_id'       => !empty($data['related_task_id']) ? (int)$data['related_task_id'] : null,
            'tags_json'             => isset($data['tags']) && is_array($data['tags']) ? json_encode($data['tags']) : ($data['tags_json'] ?? null),
            'effective_from'        => $data['effective_from'] ?? date('Y-m-d'),
            'review_due_date'       => $data['review_due_date'] ?? date('Y-m-d', strtotime('+1 year')),
            'is_latest'             => 1,
            'created_at'            => $now,
            'updated_at'            => $now,
        ];

        $this->db->table('knowledge_records')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();

        // Write initial version
        $this->db->table('knowledge_versions')->insert([
            'record_id'           => $insert['id'],
            'version_number'      => $insert['version'],
            'title'               => $insert['title'],
            'summary'             => $insert['summary'],
            'content'             => $insert['content'],
            'change_summary'      => 'Initial creation draft.',
            'author_type'         => $authorType,
            'author_user_id'      => $actorUserId,
            'author_agent_id'     => $actorAgentId,
            'approved_by_user_id' => $status === 'published' ? $actorUserId : null,
            'approved_at'         => $status === 'published' ? $now : null,
            'created_at'          => $now,
        ]);

        return $this->getRecord($insert['id']);
    }

    /**
     * Update Record & create version revision if requested
     */
    public function updateRecord(int $id, array $data, ?int $actorUserId = null, ?int $actorAgentId = null): array
    {
        $existing = $this->getRecord($id);
        if (!$existing) {
            throw new \InvalidArgumentException("Knowledge record #{$id} not found.", 404);
        }

        $now = date('Y-m-d H:i:s');
        $newVersion = $data['version'] ?? $existing['version'];
        $isNewVersion = ($newVersion !== $existing['version']) || !empty($data['bump_version']);

        $update = [
            'updated_at' => $now,
        ];

        if (isset($data['title'])) $update['title'] = trim($data['title']);
        if (isset($data['summary'])) $update['summary'] = $data['summary'];
        if (isset($data['content'])) $update['content'] = $data['content'];
        if (isset($data['classification'])) $update['classification'] = $data['classification'];
        if (isset($data['category_id'])) $update['category_id'] = (int)$data['category_id'];
        if (isset($data['owner_unit_id'])) $update['owner_unit_id'] = (int)$data['owner_unit_id'];
        if (isset($data['tags'])) $update['tags_json'] = json_encode($data['tags']);
        if ($isNewVersion) $update['version'] = $newVersion;

        $this->db->table('knowledge_records')->where('id', $id)->update($update);

        if ($isNewVersion || !empty($data['change_summary'])) {
            $authorType = $actorAgentId ? 'agent' : 'human';
            $this->db->table('knowledge_versions')->insert([
                'record_id'           => $id,
                'version_number'      => $newVersion,
                'title'               => $update['title'] ?? $existing['title'],
                'summary'             => $update['summary'] ?? $existing['summary'],
                'content'             => $update['content'] ?? $existing['content'],
                'change_summary'      => $data['change_summary'] ?? 'Updated knowledge content revision.',
                'author_type'         => $authorType,
                'author_user_id'      => $actorUserId,
                'author_agent_id'     => $actorAgentId,
                'approved_by_user_id' => $actorUserId,
                'approved_at'         => $now,
                'created_at'          => $now,
            ]);
        }

        return $this->getRecord($id);
    }

    /**
     * Transition knowledge lifecycle status
     */
    public function transitionStatus(int $id, string $targetStatus, ?string $reason = null, ?int $actorUserId = null): array
    {
        $existing = $this->getRecord($id);
        if (!$existing) {
            throw new \InvalidArgumentException("Knowledge record #{$id} not found.", 404);
        }

        $allowedTransitions = [
            'draft'           => ['submitted', 'under_review', 'archived'],
            'submitted'       => ['under_review', 'draft', 'rejected'],
            'under_review'    => ['approved', 'needs_revision', 'rejected'],
            'approved'        => ['published', 'draft'],
            'published'       => ['needs_revision', 'superseded', 'expired', 'archived', 'withdrawn'],
            'needs_revision'  => ['submitted', 'draft'],
            'rejected'        => ['draft', 'archived'],
            'superseded'      => ['archived'],
            'expired'         => ['under_review', 'archived'],
            'archived'        => ['draft'], // Can be restored to draft
            'withdrawn'       => ['archived'],
        ];

        $currentStatus = $existing['status'];
        if (!isset($allowedTransitions[$currentStatus]) || !in_array($targetStatus, $allowedTransitions[$currentStatus])) {
            throw new \RuntimeException("Invalid status transition from '{$currentStatus}' to '{$targetStatus}'.", 400);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('knowledge_records')->where('id', $id)->update([
            'status'     => $targetStatus,
            'updated_at' => $now,
        ]);

        return $this->getRecord($id);
    }
}
