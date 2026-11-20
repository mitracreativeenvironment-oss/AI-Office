<?php

namespace App\Services;

class CollaborationAccessService
{
    public const LEVEL_PUBLIC = 'public';
    public const LEVEL_INTERNAL = 'internal';
    public const LEVEL_CONFIDENTIAL = 'confidential';
    public const LEVEL_RESTRICTED = 'restricted';
    public const LEVEL_HIGHLY_RESTRICTED = 'highly_restricted';

    /**
     * Determine if user context can view a specific classification.
     */
    public function canAccessClassification(string $classification, ?array $userContext = null, ?int $targetUnitId = null): bool
    {
        if ($classification === self::LEVEL_PUBLIC) {
            return true;
        }

        if (empty($userContext)) {
            return false;
        }

        $roles = $userContext['roles'] ?? [];
        $isSuperAdmin = in_array('superadmin', $roles) || in_array('owner', $roles);
        if ($isSuperAdmin) {
            return true;
        }

        $isAdmin = in_array('admin', $roles);
        $isExecutive = in_array('executive', $roles) || in_array('board', $roles);
        $isDirector = in_array('director', $roles) || in_array('director_ai', $roles);

        switch ($classification) {
            case self::LEVEL_INTERNAL:
                return true;

            case self::LEVEL_CONFIDENTIAL:
                if ($isAdmin || $isExecutive || $isDirector) {
                    return true;
                }
                $userUnitId = (int)($userContext['unit_id'] ?? 0);
                if ($targetUnitId && $userUnitId === $targetUnitId) {
                    return true;
                }
                return false;

            case self::LEVEL_RESTRICTED:
                return $isAdmin || $isExecutive || $isDirector;

            case self::LEVEL_HIGHLY_RESTRICTED:
                return $isSuperAdmin;

            default:
                return false;
        }
    }

    /**
     * Get allowed classifications array for SQL IN clauses.
     */
    public function getAllowedClassifications(?array $userContext = null): array
    {
        if (empty($userContext)) {
            return [self::LEVEL_PUBLIC];
        }

        $roles = $userContext['roles'] ?? [];
        if (in_array('superadmin', $roles) || in_array('owner', $roles)) {
            return [
                self::LEVEL_PUBLIC,
                self::LEVEL_INTERNAL,
                self::LEVEL_CONFIDENTIAL,
                self::LEVEL_RESTRICTED,
                self::LEVEL_HIGHLY_RESTRICTED,
            ];
        }

        if (in_array('admin', $roles) || in_array('executive', $roles) || in_array('director', $roles)) {
            return [
                self::LEVEL_PUBLIC,
                self::LEVEL_INTERNAL,
                self::LEVEL_CONFIDENTIAL,
                self::LEVEL_RESTRICTED,
            ];
        }

        return [
            self::LEVEL_PUBLIC,
            self::LEVEL_INTERNAL,
            self::LEVEL_CONFIDENTIAL,
        ];
    }

    /**
     * Verify sender identity and prevent identity spoofing or human impersonation.
     * Enforces that human users post as human, and AI agents post as agent with matching identity.
     */
    public function verifySenderIdentity(string $declaredSenderType, ?int $declaredSenderId, ?array $authContext = null): array
    {
        if (empty($authContext)) {
            return ['valid' => false, 'error' => 'Authentication required to transmit messages'];
        }

        $authType = $authContext['type'] ?? 'human'; // 'human' or 'agent'
        $authUserId = isset($authContext['id']) ? (int)$authContext['id'] : null;
        $authAgentId = isset($authContext['agent_id']) ? (int)$authContext['agent_id'] : null;

        // An AI Agent must NEVER claim to be human (strictly banned)
        if ($authType === 'agent' && $declaredSenderType === 'human') {
            return [
                'valid' => false,
                'error' => 'Security Violation: AI Agents are strictly prohibited from impersonating human personnel'
            ];
        }

        // A Human user cannot pretend to be an agent unless acting as superadmin/system
        $roles = $authContext['roles'] ?? [];
        $isPrivileged = in_array('superadmin', $roles) || in_array('owner', $roles);

        if ($authType === 'human') {
            if ($declaredSenderType === 'human') {
                if ($declaredSenderId && $declaredSenderId !== $authUserId && !$isPrivileged) {
                    return [
                        'valid' => false,
                        'error' => 'Security Violation: Cannot send message on behalf of another human user'
                    ];
                }
            } elseif ($declaredSenderType === 'agent' && !$isPrivileged) {
                return [
                    'valid' => false,
                    'error' => 'Human users may only dispatch agent messages via privileged orchestration'
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Verify if user/agent has access to a specific channel.
     */
    public function canAccessChannel(int $channelId, ?int $userId = null, ?int $agentId = null, ?array $userContext = null): bool
    {
        $db = \Config\Database::connect();
        $channel = $db->table('collaboration_channels')->where('id', $channelId)->get()->getRowArray();
        if (!$channel) {
            return false;
        }

        // Check classification clearance first
        if (!$this->canAccessClassification($channel['classification'], $userContext, $channel['scope_unit_id'] ? (int)$channel['scope_unit_id'] : null)) {
            return false;
        }

        // If channel is not private, anyone with clearance has access
        if ((int)$channel['is_private'] === 0) {
            return true;
        }

        // If private, must be superadmin or explicit member
        $roles = $userContext['roles'] ?? [];
        if (in_array('superadmin', $roles) || in_array('owner', $roles)) {
            return true;
        }

        $builder = $db->table('collaboration_channel_members')->where('channel_id', $channelId);
        if ($userId) {
            $builder->where('member_type', 'human')->where('user_id', $userId);
            if ($builder->countAllResults() > 0) {
                return true;
            }
        }

        if ($agentId) {
            $builder = $db->table('collaboration_channel_members')->where('channel_id', $channelId);
            $builder->where('member_type', 'agent')->where('agent_id', $agentId);
            if ($builder->countAllResults() > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify if user/agent has access to a specific conversation.
     */
    public function canAccessConversation(int $conversationId, ?int $userId = null, ?int $agentId = null, ?array $userContext = null): bool
    {
        $db = \Config\Database::connect();
        $conv = $db->table('collaboration_conversations')->where('id', $conversationId)->get()->getRowArray();
        if (!$conv) {
            return false;
        }

        $roles = $userContext['roles'] ?? [];
        if (in_array('superadmin', $roles) || in_array('owner', $roles)) {
            return true;
        }

        $builder = $db->table('collaboration_conversation_participants')->where('conversation_id', $conversationId);
        if ($userId) {
            $builder->where('participant_type', 'human')->where('user_id', $userId);
            if ($builder->countAllResults() > 0) {
                return true;
            }
        }

        if ($agentId) {
            $builder = $db->table('collaboration_conversation_participants')->where('conversation_id', $conversationId);
            $builder->where('participant_type', 'agent')->where('agent_id', $agentId);
            if ($builder->countAllResults() > 0) {
                return true;
            }
        }

        return false;
    }
}
