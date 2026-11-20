<?php

namespace App\Services;

class MessagingService
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
     * List accessible channels for the user/agent.
     */
    public function listChannels(array $filters = [], ?int $userId = null, ?int $agentId = null, ?array $userContext = null): array
    {
        $builder = $this->db->table('collaboration_channels c')
            ->select('c.*, u.code AS unit_code, u.name AS unit_name, COUNT(DISTINCT m.id) AS member_count')
            ->join('organization_units u', 'u.id = c.scope_unit_id', 'left')
            ->join('collaboration_channel_members m', 'm.channel_id = c.id', 'left')
            ->groupBy('c.id');

        $allowedClassifications = $this->accessService->getAllowedClassifications($userContext);
        $builder->whereIn('c.classification', $allowedClassifications);

        if (!empty($filters['domain'])) {
            $builder->where('c.domain', $filters['domain']);
        }

        if (isset($filters['is_private'])) {
            $builder->where('c.is_private', (int)$filters['is_private']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('c.name', $search)
                ->orLike('c.channel_code', $search)
                ->orLike('c.description', $search)
                ->groupEnd();
        }

        $channels = $builder->orderBy('c.is_private', 'ASC')->orderBy('c.name', 'ASC')->get()->getResultArray();

        // Check membership for current user/agent
        foreach ($channels as &$channel) {
            $channel['is_member'] = false;
            $channel['membership_role'] = null;

            if ($userId) {
                $mem = $this->db->table('collaboration_channel_members')
                    ->where('channel_id', $channel['id'])
                    ->where('member_type', 'human')
                    ->where('user_id', $userId)
                    ->get()
                    ->getRowArray();
                if ($mem) {
                    $channel['is_member'] = true;
                    $channel['membership_role'] = $mem['role'];
                }
            } elseif ($agentId) {
                $mem = $this->db->table('collaboration_channel_members')
                    ->where('channel_id', $channel['id'])
                    ->where('member_type', 'agent')
                    ->where('agent_id', $agentId)
                    ->get()
                    ->getRowArray();
                if ($mem) {
                    $channel['is_member'] = true;
                    $channel['membership_role'] = $mem['role'];
                }
            }
        }

        return $channels;
    }

    /**
     * Get single channel details and members.
     */
    public function getChannel(int $channelId, ?int $userId = null, ?int $agentId = null, ?array $userContext = null): ?array
    {
        $channel = $this->db->table('collaboration_channels c')
            ->select('c.*, u.code AS unit_code, u.name AS unit_name')
            ->join('organization_units u', 'u.id = c.scope_unit_id', 'left')
            ->where('c.id', $channelId)
            ->get()
            ->getRowArray();

        if (!$channel) {
            return null;
        }

        if (!$this->accessService->canAccessChannel($channelId, $userId, $agentId, $userContext)) {
            return null;
        }

        // Get members
        $members = $this->db->table('collaboration_channel_members m')
            ->select('m.*, u.full_name AS user_name, u.email AS user_email, a.name AS agent_name, a.agent_code')
            ->join('users u', 'u.id = m.user_id', 'left')
            ->join('ai_agent_registry a', 'a.id = m.agent_id', 'left')
            ->where('m.channel_id', $channelId)
            ->orderBy('m.role', 'ASC')
            ->orderBy('m.id', 'ASC')
            ->get()
            ->getResultArray();

        $channel['members'] = $members;
        return $channel;
    }

    /**
     * Create a new channel.
     */
    public function createChannel(array $data, int $userId): array
    {
        $now = date('Y-m-d H:i:s');
        $code = 'CHN-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        $record = [
            'channel_code'       => $data['channel_code'] ?? $code,
            'name'               => trim($data['name']),
            'description'        => $data['description'] ?? null,
            'domain'             => $data['domain'] ?? 'corporate_ops',
            'scope_unit_id'      => !empty($data['scope_unit_id']) ? (int)$data['scope_unit_id'] : null,
            'classification'     => $data['classification'] ?? 'internal',
            'is_private'         => isset($data['is_private']) ? (int)$data['is_private'] : 0,
            'is_archived'        => 0,
            'created_by_user_id' => $userId,
            'created_at'         => $now,
            'updated_at'         => $now,
        ];

        $this->db->table('collaboration_channels')->insert($record);
        $channelId = (int)$this->db->insertID();
        $record['id'] = $channelId;

        // Add creator as channel admin
        $this->db->table('collaboration_channel_members')->insert([
            'channel_id'            => $channelId,
            'member_type'           => 'human',
            'user_id'               => $userId,
            'agent_id'              => null,
            'role'                  => 'admin',
            'notifications_enabled' => 1,
            'joined_at'             => $now,
        ]);

        return $record;
    }

    /**
     * Join channel.
     */
    public function joinChannel(int $channelId, string $memberType, int $memberId, string $role = 'member'): bool
    {
        $builder = $this->db->table('collaboration_channel_members')->where('channel_id', $channelId);
        if ($memberType === 'human') {
            $builder->where('member_type', 'human')->where('user_id', $memberId);
        } else {
            $builder->where('member_type', 'agent')->where('agent_id', $memberId);
        }

        if ($builder->countAllResults() > 0) {
            return true; // Already a member
        }

        return (bool)$this->db->table('collaboration_channel_members')->insert([
            'channel_id'            => $channelId,
            'member_type'           => $memberType,
            'user_id'               => $memberType === 'human' ? $memberId : null,
            'agent_id'              => $memberType === 'agent' ? $memberId : null,
            'role'                  => $role,
            'notifications_enabled' => 1,
            'joined_at'             => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Leave channel.
     */
    public function leaveChannel(int $channelId, string $memberType, int $memberId): bool
    {
        $builder = $this->db->table('collaboration_channel_members')->where('channel_id', $channelId);
        if ($memberType === 'human') {
            $builder->where('member_type', 'human')->where('user_id', $memberId);
        } else {
            $builder->where('member_type', 'agent')->where('agent_id', $memberId);
        }

        return (bool)$builder->delete();
    }

    /**
     * List direct and group conversations for user or agent.
     */
    public function listConversations(?int $userId = null, ?int $agentId = null, ?array $userContext = null): array
    {
        $builder = $this->db->table('collaboration_conversations c')
            ->select('c.*, COUNT(DISTINCT p.id) AS participant_count')
            ->join('collaboration_conversation_participants p', 'p.conversation_id = c.id', 'left')
            ->groupBy('c.id');

        // Check if user is superadmin/owner or regular participant
        $roles = $userContext['roles'] ?? [];
        $isSuper = in_array('superadmin', $roles) || in_array('owner', $roles);

        if (!$isSuper) {
            $builder->join('collaboration_conversation_participants my_p', 'my_p.conversation_id = c.id');
            if ($userId) {
                $builder->where('my_p.participant_type', 'human')->where('my_p.user_id', $userId);
            } elseif ($agentId) {
                $builder->where('my_p.participant_type', 'agent')->where('my_p.agent_id', $agentId);
            }
        }

        $conversations = $builder->orderBy('c.last_message_at', 'DESC')->orderBy('c.id', 'DESC')->get()->getResultArray();

        // Fetch participants for each conversation
        foreach ($conversations as &$conv) {
            $participants = $this->db->table('collaboration_conversation_participants p')
                ->select('p.*, u.full_name AS user_name, a.name AS agent_name, a.agent_code')
                ->join('users u', 'u.id = p.user_id', 'left')
                ->join('ai_agent_registry a', 'a.id = p.agent_id', 'left')
                ->where('p.conversation_id', $conv['id'])
                ->get()
                ->getResultArray();
            $conv['participants'] = $participants;
        }

        return $conversations;
    }

    /**
     * Get single conversation details.
     */
    public function getConversation(int $convId, ?int $userId = null, ?int $agentId = null, ?array $userContext = null): ?array
    {
        $conv = $this->db->table('collaboration_conversations')->where('id', $convId)->get()->getRowArray();
        if (!$conv) {
            return null;
        }

        if (!$this->accessService->canAccessConversation($convId, $userId, $agentId, $userContext)) {
            return null;
        }

        $participants = $this->db->table('collaboration_conversation_participants p')
            ->select('p.*, u.full_name AS user_name, u.email AS user_email, a.name AS agent_name, a.agent_code')
            ->join('users u', 'u.id = p.user_id', 'left')
            ->join('ai_agent_registry a', 'a.id = p.agent_id', 'left')
            ->where('p.conversation_id', $convId)
            ->get()
            ->getResultArray();

        $conv['participants'] = $participants;
        return $conv;
    }

    /**
     * Create conversation (direct, group, or executive session).
     */
    public function createConversation(array $data, string $creatorType, int $creatorId, array $participants = []): array
    {
        $now = date('Y-m-d H:i:s');
        $code = 'CONV-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        $record = [
            'conversation_code'   => $data['conversation_code'] ?? $code,
            'type'                => $data['type'] ?? 'direct',
            'title'               => $data['title'] ?? null,
            'created_by_user_id'  => $creatorType === 'human' ? $creatorId : null,
            'created_by_agent_id' => $creatorType === 'agent' ? $creatorId : null,
            'classification'      => $data['classification'] ?? 'internal',
            'is_active'           => 1,
            'last_message_at'     => $now,
            'created_at'          => $now,
            'updated_at'          => $now,
        ];

        $this->db->table('collaboration_conversations')->insert($record);
        $convId = (int)$this->db->insertID();
        $record['id'] = $convId;

        // Add creator as owner participant
        $this->db->table('collaboration_conversation_participants')->insert([
            'conversation_id'  => $convId,
            'participant_type' => $creatorType,
            'user_id'          => $creatorType === 'human' ? $creatorId : null,
            'agent_id'         => $creatorType === 'agent' ? $creatorId : null,
            'role'             => 'owner',
            'is_muted'         => 0,
            'joined_at'        => $now,
        ]);

        // Add other participants
        foreach ($participants as $p) {
            $pType = $p['participant_type'] ?? 'human';
            $pUserId = $pType === 'human' ? (int)($p['user_id'] ?? $p['id'] ?? 0) : null;
            $pAgentId = $pType === 'agent' ? (int)($p['agent_id'] ?? $p['id'] ?? 0) : null;

            if ($pType === $creatorType && ($pUserId === $creatorId || $pAgentId === $creatorId)) {
                continue; // Skip creator
            }

            $this->db->table('collaboration_conversation_participants')->insert([
                'conversation_id'  => $convId,
                'participant_type' => $pType,
                'user_id'          => $pUserId,
                'agent_id'         => $pAgentId,
                'role'             => $p['role'] ?? 'member',
                'is_muted'         => 0,
                'joined_at'        => $now,
            ]);
        }

        return $record;
    }

    /**
     * Get messages for a channel or conversation.
     */
    public function getMessages(string $targetType, int $targetId, array $filters = [], ?int $userId = null, ?int $agentId = null, ?array $userContext = null): array
    {
        // Check access
        if ($targetType === 'channel') {
            if (!$this->accessService->canAccessChannel($targetId, $userId, $agentId, $userContext)) {
                throw new \RuntimeException("Access denied to channel {$targetId}");
            }
        } else {
            if (!$this->accessService->canAccessConversation($targetId, $userId, $agentId, $userContext)) {
                throw new \RuntimeException("Access denied to conversation {$targetId}");
            }
        }

        $builder = $this->db->table('collaboration_messages m')
            ->select('m.*, u.email AS sender_user_email, a.agent_code AS sender_agent_code')
            ->join('users u', 'u.id = m.sender_user_id', 'left')
            ->join('ai_agent_registry a', 'a.id = m.sender_agent_id', 'left');

        if ($targetType === 'channel') {
            $builder->where('m.channel_id', $targetId);
        } else {
            $builder->where('m.conversation_id', $targetId);
        }

        if (isset($filters['parent_message_id'])) {
            if ($filters['parent_message_id'] === 'root') {
                $builder->where('m.parent_message_id IS NULL');
            } else {
                $builder->where('m.parent_message_id', (int)$filters['parent_message_id']);
            }
        }

        $limit = isset($filters['limit']) ? min((int)$filters['limit'], 100) : 50;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;

        $messages = $builder->orderBy('m.created_at', 'ASC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        // Fetch references for each message
        if (!empty($messages)) {
            $messageIds = array_column($messages, 'id');
            $references = $this->db->table('collaboration_message_references')
                ->whereIn('message_id', $messageIds)
                ->get()
                ->getResultArray();

            $refMap = [];
            foreach ($references as $ref) {
                $refMap[$ref['message_id']][] = $ref;
            }

            foreach ($messages as &$msg) {
                $msg['references'] = $refMap[$msg['id']] ?? [];
            }
        }

        return $messages;
    }

    /**
     * Send a message to channel or conversation.
     * Enforces strict sender validation and prevents human impersonation.
     */
    public function sendMessage(
        string $targetType,
        int $targetId,
        array $data,
        string $senderType,
        int $senderId,
        string $senderDisplayName,
        ?array $authContext = null
    ): array {
        // Strict identity validation
        $identityCheck = $this->accessService->verifySenderIdentity($senderType, $senderId, $authContext);
        if (!$identityCheck['valid']) {
            throw new \RuntimeException($identityCheck['error']);
        }

        // Access validation
        if ($targetType === 'channel') {
            $channel = $this->db->table('collaboration_channels')->where('id', $targetId)->get()->getRowArray();
            if (!$channel) {
                throw new \RuntimeException("Channel not found");
            }
            if (!$this->accessService->canAccessChannel($targetId, $senderType === 'human' ? $senderId : null, $senderType === 'agent' ? $senderId : null, $authContext)) {
                throw new \RuntimeException("Access denied to target channel");
            }
            $classification = $data['classification'] ?? $channel['classification'];
        } else {
            $conv = $this->db->table('collaboration_conversations')->where('id', $targetId)->get()->getRowArray();
            if (!$conv) {
                throw new \RuntimeException("Conversation not found");
            }
            if (!$this->accessService->canAccessConversation($targetId, $senderType === 'human' ? $senderId : null, $senderType === 'agent' ? $senderId : null, $authContext)) {
                throw new \RuntimeException("Access denied to target conversation");
            }
            $classification = $data['classification'] ?? $conv['classification'];
        }

        $now = date('Y-m-d H:i:s');
        $code = 'MSG-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        $record = [
            'message_code'        => $data['message_code'] ?? $code,
            'channel_id'          => $targetType === 'channel' ? $targetId : null,
            'conversation_id'     => $targetType === 'conversation' ? $targetId : null,
            'parent_message_id'   => !empty($data['parent_message_id']) ? (int)$data['parent_message_id'] : null,
            'sender_type'         => $senderType,
            'sender_user_id'      => $senderType === 'human' ? $senderId : null,
            'sender_agent_id'     => $senderType === 'agent' ? $senderId : null,
            'sender_display_name' => $senderDisplayName,
            'content'             => trim($data['content']),
            'content_format'      => $data['content_format'] ?? 'markdown',
            'classification'      => $classification,
            'is_pinned'           => 0,
            'is_edited'           => 0,
            'is_deleted'          => 0,
            'created_at'          => $now,
        ];

        $this->db->table('collaboration_messages')->insert($record);
        $messageId = (int)$this->db->insertID();
        $record['id'] = $messageId;

        // Update conversation last_message_at if conversation
        if ($targetType === 'conversation') {
            $this->db->table('collaboration_conversations')->where('id', $targetId)->update([
                'last_message_at' => $now,
                'updated_at'      => $now,
            ]);
        }

        // Insert polymorphic references if provided
        $insertedRefs = [];
        if (!empty($data['references']) && is_array($data['references'])) {
            foreach ($data['references'] as $ref) {
                $refRecord = [
                    'message_id'      => $messageId,
                    'reference_type'  => $ref['reference_type'],
                    'reference_id'    => (int)$ref['reference_id'],
                    'reference_code'  => $ref['reference_code'] ?? null,
                    'reference_title' => $ref['reference_title'] ?? null,
                    'created_at'      => $now,
                ];
                $this->db->table('collaboration_message_references')->insert($refRecord);
                $refRecord['id'] = (int)$this->db->insertID();
                $insertedRefs[] = $refRecord;
            }
        }
        $record['references'] = $insertedRefs;

        return $record;
    }

    /**
     * Pin or unpin a message.
     */
    public function pinMessage(int $messageId, bool $isPinned): bool
    {
        return (bool)$this->db->table('collaboration_messages')
            ->where('id', $messageId)
            ->update(['is_pinned' => $isPinned ? 1 : 0]);
    }

    /**
     * Mark messages as read.
     */
    public function markAsRead(string $targetType, int $targetId, int $lastMessageId, string $actorType, int $actorId): bool
    {
        if ($targetType === 'channel') {
            $builder = $this->db->table('collaboration_channel_members')->where('channel_id', $targetId);
            if ($actorType === 'human') {
                $builder->where('member_type', 'human')->where('user_id', $actorId);
            } else {
                $builder->where('member_type', 'agent')->where('agent_id', $actorId);
            }
            return (bool)$builder->update(['last_read_message_id' => $lastMessageId]);
        } else {
            $builder = $this->db->table('collaboration_conversation_participants')->where('conversation_id', $targetId);
            if ($actorType === 'human') {
                $builder->where('participant_type', 'human')->where('user_id', $actorId);
            } else {
                $builder->where('participant_type', 'agent')->where('agent_id', $actorId);
            }
            return (bool)$builder->update(['last_read_message_id' => $lastMessageId]);
        }
    }
}
