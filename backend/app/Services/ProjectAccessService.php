<?php

namespace App\Services;

use Config\Database;

class ProjectAccessService
{
    public const LEVEL_PUBLIC = 'public';
    public const LEVEL_INTERNAL = 'internal';
    public const LEVEL_CONFIDENTIAL = 'confidential';
    public const LEVEL_RESTRICTED = 'restricted';

    /**
     * Determine if actor can access a specific classification level.
     */
    public function canAccessClassification(string $classification, ?array $userContext = null): bool
    {
        if ($classification === self::LEVEL_PUBLIC) {
            return true;
        }

        if (empty($userContext)) {
            return false;
        }

        $roles = $userContext['roles'] ?? [];
        if (in_array('superadmin', $roles) || in_array('owner', $roles)) {
            return true;
        }

        $isAdmin = in_array('admin', $roles);
        $isExecutive = in_array('executive', $roles) || in_array('board', $roles);
        $isDirector = in_array('director', $roles) || in_array('director_ai', $roles);

        switch ($classification) {
            case self::LEVEL_INTERNAL:
                return true;

            case self::LEVEL_CONFIDENTIAL:
                return $isAdmin || $isExecutive || $isDirector || in_array('project_manager', $roles);

            case self::LEVEL_RESTRICTED:
                return $isAdmin || $isExecutive || $isDirector;

            default:
                return false;
        }
    }

    /**
     * Check if actor has permission to perform action on a specific project.
     */
    public function canPerformProjectAction(int $projectId, string $action, ?array $userContext = null): bool
    {
        if (empty($userContext)) {
            return false;
        }

        $roles = $userContext['roles'] ?? [];
        if (in_array('superadmin', $roles) || in_array('owner', $roles)) {
            return true;
        }

        $db = Database::connect();
        $project = $db->table('projects')->where('id', $projectId)->get()->getRowArray();
        if (!$project) {
            return false;
        }

        // Classification check
        if (!$this->canAccessClassification($project['confidentiality_level'] ?? self::LEVEL_INTERNAL, $userContext)) {
            return false;
        }

        // View action is allowed if classification permits
        if ($action === 'view') {
            return true;
        }

        $userId = isset($userContext['id']) ? (int)$userContext['id'] : 0;
        $agentId = isset($userContext['agent_id']) ? (int)$userContext['agent_id'] : 0;

        // Check if actor is project owner or project manager
        if ($userId && (int)($project['project_owner_user_id'] ?? 0) === $userId) {
            return true;
        }
        if ($userId && (int)($project['project_manager_user_id'] ?? 0) === $userId) {
            return true;
        }
        if ($agentId && (int)($project['project_manager_agent_id'] ?? 0) === $agentId) {
            return true;
        }
        if ($agentId && (int)($project['delivery_lead_agent_id'] ?? 0) === $agentId) {
            return in_array($action, ['edit', 'submit_deliverable', 'create_task', 'update_task']);
        }

        // Check team member role
        $builder = $db->table('project_team_members')->where('project_id', $projectId)->where('status', 'active');
        if ($userId) {
            $builder->where('user_id', $userId);
        } elseif ($agentId) {
            $builder->where('agent_id', $agentId);
        } else {
            return false;
        }
        $member = $builder->get()->getRowArray();

        if (!$member) {
            // General admin or board can edit
            return in_array('admin', $roles) || in_array('executive', $roles);
        }

        $projectRole = $member['project_role'];
        switch ($action) {
            case 'edit':
                return in_array($projectRole, ['project_sponsor', 'project_owner', 'project_manager', 'delivery_lead']);

            case 'approve_stage':
            case 'manage_budget':
                return in_array($projectRole, ['project_sponsor', 'project_owner']) || in_array('executive', $roles);

            case 'manage_team':
                return in_array($projectRole, ['project_sponsor', 'project_owner', 'project_manager']);

            case 'approve_deliverable':
                return in_array($projectRole, ['project_sponsor', 'project_owner', 'project_manager', 'reviewer']);

            case 'submit_deliverable':
            case 'create_risk':
            case 'create_issue':
            case 'request_change':
                return true; // any active team member can report or submit

            default:
                return false;
        }
    }
}
