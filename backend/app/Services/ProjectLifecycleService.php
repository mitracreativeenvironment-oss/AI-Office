<?php

namespace App\Services;

use Config\Database;
use InvalidArgumentException;
use RuntimeException;

class ProjectLifecycleService
{
    protected $db;
    protected ProjectService $projectService;

    // Customer Lifecycle Stages
    public const CUST_STAGES = [
        'lead',
        'qualification',
        'proposal',
        'contracted',
        'planning',
        'execution',
        'review_acceptance',
        'delivery_closure',
        'warranty_support',
    ];

    // Internal Lifecycle Stages
    public const INT_STAGES = [
        'idea_proposal',
        'evaluation',
        'authorized',
        'planning',
        'execution',
        'review',
        'completed_archived',
    ];

    public function __construct(?ProjectService $projectService = null)
    {
        $this->db = Database::connect();
        $this->projectService = $projectService ?? new ProjectService();
    }

    /**
     * Get valid stages for a given project type.
     */
    public function getValidStages(string $projectType): array
    {
        return ($projectType === 'customer_project') ? self::CUST_STAGES : self::INT_STAGES;
    }

    /**
     * Transition project lifecycle stage.
     */
    public function transitionStage(int $projectId, string $targetStage, string $reason, array $actor, array $options = []): array
    {
        $project = $this->db->table('projects')->where('id', $projectId)->get()->getRowArray();
        if (!$project) {
            throw new RuntimeException("Project #{$projectId} not found.");
        }

        $type = $project['project_type'];
        $currentStage = $project['lifecycle_stage'];
        $currentStatus = $project['status'];
        $validStages = $this->getValidStages($type);

        if (!in_array($targetStage, $validStages)) {
            throw new InvalidArgumentException("Stage '{$targetStage}' is invalid for {$type}. Valid stages: " . implode(', ', $validStages));
        }

        if ($currentStage === $targetStage) {
            return $project;
        }

        // Validate state transitions
        $this->validateStageTransition($project, $currentStage, $targetStage, $actor, $options);

        $now = date('Y-m-d H:i:s');
        $newStatus = $currentStatus;

        // Auto-update status flag based on stage
        if (in_array($targetStage, ['delivery_closure', 'warranty_support', 'completed_archived'])) {
            $newStatus = 'completed';
        } elseif (in_array($targetStage, ['execution', 'planning', 'review_acceptance', 'review'])) {
            $newStatus = 'active';
        }

        $updateData = [
            'lifecycle_stage' => $targetStage,
            'status'          => $newStatus,
            'updated_at'      => $now,
        ];

        // If transitioning to execution and start_date is not set, set start_date
        if ($targetStage === 'execution' && empty($project['start_date'])) {
            $updateData['start_date'] = date('Y-m-d');
        }

        // If completed, set actual_completion_date
        if (in_array($targetStage, ['delivery_closure', 'completed_archived']) && empty($project['actual_completion_date'])) {
            $updateData['actual_completion_date'] = date('Y-m-d');
            $updateData['progress_percent'] = 100;
        }

        $this->db->table('projects')->where('id', $projectId)->update($updateData);

        // Record Status History
        $this->db->table('project_status_history')->insert([
            'project_id'          => $projectId,
            'from_stage'          => $currentStage,
            'to_stage'            => $targetStage,
            'from_status'         => $currentStatus,
            'to_status'           => $newStatus,
            'changed_by_user_id'  => $actor['id'] ?? null,
            'changed_by_agent_id' => $actor['agent_id'] ?? null,
            'reason'              => $reason,
            'notes'               => $options['notes'] ?? null,
            'created_at'          => $now,
        ]);

        // Audit Event
        $this->projectService->logAuditEvent($projectId, 'project.lifecycle_transition', $actor, [
            'from_stage' => $currentStage,
            'to_stage'   => $targetStage,
            'reason'     => $reason,
        ]);

        return $this->projectService->getProjectById($projectId, $actor);
    }

    /**
     * Validate business rules for stage transitions.
     */
    protected function validateStageTransition(array $project, string $fromStage, string $toStage, array $actor, array $options): void
    {
        $type = $project['project_type'];
        $stages = $this->getValidStages($type);

        $fromIdx = array_search($fromStage, $stages);
        $toIdx = array_search($toStage, $stages);

        if ($fromIdx === false || $toIdx === false) {
            throw new InvalidArgumentException("Invalid lifecycle stage.");
        }

        // Don't allow skipping forward more than 2 steps unless forced by admin/owner
        $roles = $actor['roles'] ?? [];
        $isPrivileged = in_array('superadmin', $roles) || in_array('owner', $roles) || in_array('admin', $roles);

        if (!$isPrivileged && ($toIdx - $fromIdx > 2)) {
            throw new RuntimeException("Cannot skip stages forward from '{$fromStage}' to '{$toStage}' without authorized governance override.");
        }

        // Customer Project Specific Rules
        if ($type === 'customer_project') {
            if ($toStage === 'contracted' || $toStage === 'planning' || $toStage === 'execution') {
                if ((float)$project['approved_budget_limit'] <= 0 && empty($options['bypass_budget_check'])) {
                    // Requires approved budget limit
                    throw new RuntimeException("Cannot advance customer project to '{$toStage}' without approved budget limit.");
                }
            }
        }

        // Internal Initiative Specific Rules (DoA Level B: <= 5,000,000 IDR)
        if ($type === 'internal_initiative') {
            if ($toStage === 'planning' || $toStage === 'execution') {
                $budget = (float)$project['proposed_budget'];
                if ($budget > 5000000.00 && !$isPrivileged) {
                    throw new RuntimeException("Internal initiatives with proposed budget > IDR 5,000,000 require Human Owner DoA Level A approval.");
                }
            }
        }

        // Deliverables check for closure
        if (in_array($toStage, ['delivery_closure', 'completed_archived'])) {
            $blockerIssues = $this->db->table('project_issues')
                ->where('project_id', $project['id'])
                ->where('severity', 'blocker')
                ->where('status !=', 'closed')
                ->countAllResults();

            if ($blockerIssues > 0 && empty($options['force_close'])) {
                throw new RuntimeException("Cannot close project with {$blockerIssues} open blocker issue(s). Resolve all blockers first.");
            }
        }
    }

    /**
     * Retrieve project status transition history.
     */
    public function getStatusHistory(int $projectId): array
    {
        return $this->db->table('project_status_history psh')
            ->select('psh.*, u.username, u.full_name, a.name as agent_name, a.agent_code')
            ->join('users u', 'u.id = psh.changed_by_user_id', 'left')
            ->join('ai_agent_registry a', 'a.id = psh.changed_by_agent_id', 'left')
            ->where('psh.project_id', $projectId)
            ->orderBy('psh.id', 'DESC')
            ->get()->getResultArray();
    }
}
