<?php

namespace App\Services;

class AnnouncementService
{
    protected $db;
    protected $accessService;
    protected $notificationService;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->accessService = new CollaborationAccessService();
        $this->notificationService = new CollaborationNotificationService();
    }

    /**
     * List announcements with filtering and access controls.
     */
    public function listAnnouncements(array $filters = [], ?array $userContext = null): array
    {
        $builder = $this->db->table('collaboration_announcements');

        $allowedClassifications = $this->accessService->getAllowedClassifications($userContext);
        $builder->whereIn('classification', $allowedClassifications);

        if (isset($filters['target_scope']) && $filters['target_scope'] !== '') {
            $builder->where('target_scope', $filters['target_scope']);
        }

        if (isset($filters['priority']) && $filters['priority'] !== '') {
            $builder->where('priority', $filters['priority']);
        }

        if (isset($filters['is_published'])) {
            $builder->where('is_published', (int)$filters['is_published']);
        } else {
            $builder->where('is_published', 1);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('title', $search)
                ->orLike('content', $search)
                ->orLike('announcement_code', $search)
                ->groupEnd();
        }

        // Filter out expired unless explicitly requested
        if (empty($filters['include_expired'])) {
            $now = date('Y-m-d H:i:s');
            $builder->groupStart()
                ->where('expires_at IS NULL')
                ->orWhere('expires_at >=', $now)
                ->groupEnd();
        }

        $limit = isset($filters['limit']) ? min((int)$filters['limit'], 100) : 50;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;

        return $builder->orderBy('published_at', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    /**
     * Get single announcement.
     */
    public function getAnnouncement(int $id, ?array $userContext = null): ?array
    {
        $announcement = $this->db->table('collaboration_announcements')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (!$announcement) {
            return null;
        }

        if (!$this->accessService->canAccessClassification($announcement['classification'], $userContext, $announcement['target_unit_id'] ? (int)$announcement['target_unit_id'] : null)) {
            return null;
        }

        return $announcement;
    }

    /**
     * Create a new official announcement.
     */
    public function createAnnouncement(array $data, int $userId, string $userName): array
    {
        $now = date('Y-m-d H:i:s');
        $code = 'ANN-' . date('Y') . '-' . str_pad((string)mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

        $record = [
            'announcement_code'    => $data['announcement_code'] ?? $code,
            'title'                => trim($data['title']),
            'content'              => trim($data['content']),
            'target_scope'         => $data['target_scope'] ?? 'all_organization',
            'target_unit_id'       => !empty($data['target_unit_id']) ? (int)$data['target_unit_id'] : null,
            'priority'             => $data['priority'] ?? 'routine',
            'classification'       => $data['classification'] ?? 'internal',
            'published_by_user_id' => $userId,
            'published_by_name'    => $userName,
            'is_published'         => isset($data['is_published']) ? (int)$data['is_published'] : 1,
            'published_at'         => (isset($data['is_published']) && (int)$data['is_published'] === 0) ? null : ($data['published_at'] ?? $now),
            'expires_at'           => !empty($data['expires_at']) ? $data['expires_at'] : null,
            'created_at'           => $now,
            'updated_at'           => $now,
        ];

        $this->db->table('collaboration_announcements')->insert($record);
        $insertId = (int)$this->db->insertID();
        $record['id'] = $insertId;

        // Log audit event
        $this->db->table('collaboration_audit_events')->insert([
            'event_type'    => 'announcement_created',
            'actor_type'    => 'user',
            'actor_id'      => $userId,
            'actor_name'    => $userName,
            'entity_type'   => 'announcement',
            'entity_id'     => (string)$insertId,
            'metadata_json' => json_encode(['code' => $record['announcement_code'], 'title' => $record['title'], 'priority' => $record['priority']]),
            'ip_address'    => service('request')->getIPAddress(),
            'created_at'    => $now,
        ]);

        return $record;
    }

    /**
     * Update existing announcement.
     */
    public function updateAnnouncement(int $id, array $data, int $userId, string $userName): ?array
    {
        $existing = $this->db->table('collaboration_announcements')->where('id', $id)->get()->getRowArray();
        if (!$existing) {
            return null;
        }

        $now = date('Y-m-d H:i:s');
        $updates = ['updated_at' => $now];

        $allowedFields = ['title', 'content', 'target_scope', 'target_unit_id', 'priority', 'classification', 'is_published', 'expires_at'];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = $data[$field];
            }
        }

        $this->db->table('collaboration_announcements')->where('id', $id)->update($updates);

        // Audit log
        $this->db->table('collaboration_audit_events')->insert([
            'event_type'    => 'announcement_updated',
            'actor_type'    => 'user',
            'actor_id'      => $userId,
            'actor_name'    => $userName,
            'entity_type'   => 'announcement',
            'entity_id'     => (string)$id,
            'metadata_json' => json_encode($updates),
            'ip_address'    => service('request')->getIPAddress(),
            'created_at'    => $now,
        ]);

        return array_merge($existing, $updates);
    }

    /**
     * Delete announcement.
     */
    public function deleteAnnouncement(int $id, int $userId, string $userName): bool
    {
        $existing = $this->db->table('collaboration_announcements')->where('id', $id)->get()->getRowArray();
        if (!$existing) {
            return false;
        }

        $deleted = $this->db->table('collaboration_announcements')->where('id', $id)->delete();

        if ($deleted) {
            $this->db->table('collaboration_audit_events')->insert([
                'event_type'    => 'announcement_deleted',
                'actor_type'    => 'user',
                'actor_id'      => $userId,
                'actor_name'    => $userName,
                'entity_type'   => 'announcement',
                'entity_id'     => (string)$id,
                'metadata_json' => json_encode(['deleted_code' => $existing['announcement_code']]),
                'ip_address'    => service('request')->getIPAddress(),
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        }

        return (bool)$deleted;
    }
}
