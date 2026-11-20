<?php

namespace App\Services;

class CollaborationNotificationService
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * List notifications for a recipient (human or agent).
     */
    public function listNotifications(string $recipientType, int $recipientId, array $filters = []): array
    {
        $builder = $this->db->table('collaboration_notifications')
            ->where('recipient_type', $recipientType);

        if ($recipientType === 'human') {
            $builder->where('user_id', $recipientId);
        } else {
            $builder->where('agent_id', $recipientId);
        }

        if (isset($filters['is_read'])) {
            $builder->where('is_read', (int)$filters['is_read']);
        }

        if (!empty($filters['type'])) {
            $builder->where('type', $filters['type']);
        }

        if (!empty($filters['priority'])) {
            $builder->where('priority', $filters['priority']);
        }

        $limit = isset($filters['limit']) ? min((int)$filters['limit'], 100) : 50;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;

        return $builder->orderBy('created_at', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    /**
     * Get unread notification count.
     */
    public function getUnreadCount(string $recipientType, int $recipientId): int
    {
        $builder = $this->db->table('collaboration_notifications')
            ->where('recipient_type', $recipientType)
            ->where('is_read', 0);

        if ($recipientType === 'human') {
            $builder->where('user_id', $recipientId);
        } else {
            $builder->where('agent_id', $recipientId);
        }

        return $builder->countAllResults();
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(int $notificationId, string $recipientType, int $recipientId): bool
    {
        $builder = $this->db->table('collaboration_notifications')
            ->where('id', $notificationId)
            ->where('recipient_type', $recipientType);

        if ($recipientType === 'human') {
            $builder->where('user_id', $recipientId);
        } else {
            $builder->where('agent_id', $recipientId);
        }

        return $builder->update([
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Mark all notifications as read for recipient.
     */
    public function markAllAsRead(string $recipientType, int $recipientId): bool
    {
        $builder = $this->db->table('collaboration_notifications')
            ->where('recipient_type', $recipientType)
            ->where('is_read', 0);

        if ($recipientType === 'human') {
            $builder->where('user_id', $recipientId);
        } else {
            $builder->where('agent_id', $recipientId);
        }

        return $builder->update([
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Dispatch an in-app notification.
     */
    public function sendNotification(
        string $recipientType,
        int $recipientId,
        string $title,
        string $message,
        string $type = 'message',
        string $priority = 'medium',
        ?string $resourceUri = null
    ): int {
        $data = [
            'recipient_type' => $recipientType,
            'user_id'        => $recipientType === 'human' ? $recipientId : null,
            'agent_id'       => $recipientType === 'agent' ? $recipientId : null,
            'title'          => $title,
            'message'        => $message,
            'type'           => $type,
            'priority'       => $priority,
            'resource_uri'   => $resourceUri,
            'is_read'        => 0,
            'read_at'        => null,
            'created_at'     => date('Y-m-d H:i:s'),
        ];

        $this->db->table('collaboration_notifications')->insert($data);
        return (int)$this->db->insertID();
    }
}
