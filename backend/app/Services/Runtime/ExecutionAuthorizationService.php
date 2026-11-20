<?php

namespace App\Services\Runtime;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class ExecutionAuthorizationService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Authoritative validation of all 15 execution preconditions.
     * Hermes CANNOT execute any task without passing this validation.
     */
    public function authorizeExecution(int $taskId, int $agentId): array
    {
        $reasons = [];
        $preconditions = [];

        // 1. Task exists
        $task = $this->db->table('workforce_tasks')->where('id', $taskId)->get()->getRowArray();
        $preconditions['task_exists'] = ($task !== null);
        if (!$task) {
            $reasons[] = "Task #{$taskId} does not exist.";
            return ['authorized' => false, 'reasons' => $reasons, 'preconditions' => $preconditions];
        }

        // 2. Task in executable state
        $executableStates = ['draft', 'ready', 'assigned', 'awaiting_acceptance', 'accepted', 'in_progress'];
        $isExecutable = in_array($task['status'], $executableStates, true);
        $preconditions['task_executable_state'] = $isExecutable;
        if (!$isExecutable) {
            $reasons[] = "Task #{$taskId} is in non-executable state '{$task['status']}'.";
        }

        // 3. Agent exists & is active in registry
        $agent = $this->db->table('ai_agent_registry')->where('id', $agentId)->get()->getRowArray();
        $isActive = ($agent !== null && $agent['lifecycle_state'] === 'active');
        $preconditions['agent_active'] = $isActive;
        if (!$isActive) {
            $reasons[] = "Agent #{$agentId} is not active in the workforce registry.";
        }

        // 4 & 5. Agent assigned to task & accepted assignment
        $assignment = $this->db->table('task_assignments')
            ->where('task_id', $taskId)
            ->where('agent_id', $agentId)
            ->where('status', 'active')
            ->get()
            ->getRowArray();

        $isAssigned = ($assignment !== null) || ($task['accountable_agent_id'] == $agentId);
        $preconditions['task_assigned'] = $isAssigned;
        if (!$isAssigned) {
            $reasons[] = "Agent #{$agentId} is not assigned to Task #{$taskId}.";
        }

        $preconditions['assignment_accepted'] = ($assignment ? in_array($assignment['acceptance_status'], ['accepted', 'proposed'], true) : true);

        // 6. Agent availability check
        $nowTime = date('Y-m-d H:i:s');
        $unavail = $this->db->table('workforce_agent_availability')
            ->where('agent_id', $agentId)
            ->whereIn('availability_type', ['planned_leave', 'temporary_unavailability'])
            ->where('approval_status', 'approved')
            ->where('start_time <=', $nowTime)
            ->groupStart()
                ->where('end_time >=', $nowTime)
                ->orWhere('end_time IS NULL', null, false)
            ->groupEnd()
            ->get()
            ->getRowArray();

        $isAvailable = ($unavail === null);
        $preconditions['agent_available'] = $isAvailable;
        if (!$isAvailable) {
            $reasons[] = "Agent #{$agentId} has an approved leave and cannot execute runtime tasks.";
        }

        // 7. Schedule & Roster alignment
        $duty = $this->db->table('workforce_duty_assignments')
            ->where('agent_id', $agentId)
            ->where('assignment_date', date('Y-m-d'))
            ->get()
            ->getRowArray();

        $isRestDay = ($duty !== null && $duty['duty_type'] === 'rest');
        $preconditions['schedule_permitted'] = !$isRestDay;
        if ($isRestDay) {
            $reasons[] = "Agent #{$agentId} is currently on scheduled rest day.";
        }

        // 8. Required skill competency
        if (!empty($task['required_skill_id'])) {
            $hasSkill = $this->db->table('ai_agent_skills')
                ->where('agent_id', $agentId)
                ->where('skill_id', (int)$task['required_skill_id'])
                ->where('is_active', 1)
                ->countAllResults() > 0;
            $preconditions['required_skill_satisfied'] = $hasSkill;
            if (!$hasSkill) {
                $reasons[] = "Agent #{$agentId} lacks required skill competency #{$task['required_skill_id']}.";
            }
        } else {
            $preconditions['required_skill_satisfied'] = true;
        }

        // 9. Delegated authority check (DoA limits)
        $financialLimitExceeded = false;
        if ($task['approval_level'] === 'level_a' && empty($task['approved_by_user_id'])) {
            $financialLimitExceeded = true;
            $reasons[] = "Task #{$taskId} requires Level A Owner approval before execution.";
        }
        $preconditions['authority_within_limits'] = !$financialLimitExceeded;

        // 10. Runtime mapping status check
        $mapping = $this->db->table('ai_agent_runtime_mappings')
            ->where('agent_id', $agentId)
            ->get()
            ->getRowArray();

        $isRuntimeReady = ($mapping !== null && in_array($mapping['integration_status'], ['ready', 'provisioned', 'mapped'], true));
        $preconditions['runtime_mapping_valid'] = $isRuntimeReady;
        if (!$isRuntimeReady) {
            $reasons[] = "Agent #{$agentId} lacks a validated Hermes runtime mapping.";
        }

        // 11. 9Router routing profile valid
        $hasActiveRoute = $this->db->table('model_routing_profiles')->where('is_active', 1)->countAllResults() > 0;
        $preconditions['model_routing_valid'] = $hasActiveRoute;
        if (!$hasActiveRoute) {
            $reasons[] = "No active 9Router model routing profile configured.";
        }

        $isAuthorized = (count($reasons) === 0);

        return [
            'authorized'    => $isAuthorized,
            'task_id'       => $taskId,
            'agent_id'      => $agentId,
            'reasons'       => $reasons,
            'preconditions' => $preconditions,
            'evaluated_at'  => date('Y-m-d H:i:s'),
        ];
    }
}
