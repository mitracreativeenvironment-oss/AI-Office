<?php

namespace App\Services;

class KnowledgeAccessService
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
        // Public content is always accessible
        if ($classification === self::LEVEL_PUBLIC) {
            return true;
        }

        // Without authentication, only public is accessible
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
                // Any authenticated user/agent can access internal
                return true;

            case self::LEVEL_CONFIDENTIAL:
                // Confidential requires admin, executive, or membership in the specific target unit
                if ($isAdmin || $isExecutive || $isDirector) {
                    return true;
                }
                $userUnitId = (int)($userContext['unit_id'] ?? 0);
                if ($targetUnitId && $userUnitId === $targetUnitId) {
                    return true;
                }
                return false;

            case self::LEVEL_RESTRICTED:
                // Restricted requires admin, executive board, or director AI
                return $isAdmin || $isExecutive || $isDirector;

            case self::LEVEL_HIGHLY_RESTRICTED:
                // Highly restricted strictly requires Owner or Superadmin
                return $isSuperAdmin;

            default:
                return false;
        }
    }

    /**
     * Get maximum classification level visible for a user context.
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

        // Standard workforce / agent
        return [
            self::LEVEL_PUBLIC,
            self::LEVEL_INTERNAL,
            self::LEVEL_CONFIDENTIAL,
        ];
    }
}
