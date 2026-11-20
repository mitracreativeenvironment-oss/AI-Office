<?php

namespace App\Services;

use Config\Database;
use InvalidArgumentException;
use RuntimeException;

class ProjectService
{
    protected $db;
    protected ProjectAccessService $accessService;

    public function __construct(?ProjectAccessService $accessService = null)
    {
        $this->db = Database::connect();
        $this->accessService = $accessService ?? new ProjectAccessService();
    }

    /**
     * Retrieve projects list with filtering and security classification.
     */
    public function getProjects(array $filters = [], ?array $userContext = null): array
    {
        $builder = $this->db->table('projects p')
            ->select('p.*, 
                      c.company_name as client_name, c.client_code,
                      u.username as owner_username, u.full_name as owner_full_name,
                      pm_a.name as pm_agent_name, pm_a.agent_code as pm_agent_code,
                      dl_a.name as dl_agent_name, dl_a.agent_code as dl_agent_code,
                      ou.name as unit_name, ou.code as unit_code')
            ->join('project_clients c', 'c.id = p.client_id', 'left')
            ->join('users u', 'u.id = p.project_owner_user_id', 'left')
            ->join('ai_agent_registry pm_a', 'pm_a.id = p.project_manager_agent_id', 'left')
            ->join('ai_agent_registry dl_a', 'dl_a.id = p.delivery_lead_agent_id', 'left')
            ->join('organization_units ou', 'ou.id = p.unit_id', 'left');

        if (!empty($filters['project_type'])) {
            $builder->where('p.project_type', $filters['project_type']);
        }
        if (!empty($filters['category'])) {
            $builder->where('p.category', $filters['category']);
        }
        if (!empty($filters['status'])) {
            $builder->where('p.status', $filters['status']);
        }
        if (!empty($filters['lifecycle_stage'])) {
            $builder->where('p.lifecycle_stage', $filters['lifecycle_stage']);
        }
        if (!empty($filters['health_status'])) {
            $builder->where('p.health_status', $filters['health_status']);
        }
        if (!empty($filters['client_id'])) {
            $builder->where('p.client_id', (int)$filters['client_id']);
        }
        if (!empty($filters['unit_id'])) {
            $builder->where('p.unit_id', (int)$filters['unit_id']);
        }
        if (!empty($filters['search'])) {
            $kw = $filters['search'];
            $builder->groupStart()
                ->like('p.name', $kw)
                ->orLike('p.project_code', $kw)
                ->orLike('p.description', $kw)
                ->groupEnd();
        }

        $builder->orderBy('p.id', 'DESC');
        $rows = $builder->get()->getResultArray();

        // Apply access control filter
        $result = [];
        foreach ($rows as $row) {
            $classification = $row['confidentiality_level'] ?? ProjectAccessService::LEVEL_INTERNAL;
            if ($this->accessService->canAccessClassification($classification, $userContext)) {
                $result[] = $this->formatProjectSummary($row);
            }
        }

        return $result;
    }

    /**
     * Retrieve single project by ID with full details.
     */
    public function getProjectById(int $id, ?array $userContext = null): ?array
    {
        $project = $this->db->table('projects p')
            ->select('p.*, 
                      c.company_name as client_name, c.client_code, c.industry as client_industry,
                      u.username as owner_username, u.full_name as owner_full_name,
                      pm_a.name as pm_agent_name, pm_a.agent_code as pm_agent_code,
                      dl_a.name as dl_agent_name, dl_a.agent_code as dl_agent_code,
                      ou.name as unit_name, ou.code as unit_code')
            ->join('project_clients c', 'c.id = p.client_id', 'left')
            ->join('users u', 'u.id = p.project_owner_user_id', 'left')
            ->join('ai_agent_registry pm_a', 'pm_a.id = p.project_manager_agent_id', 'left')
            ->join('ai_agent_registry dl_a', 'dl_a.id = p.delivery_lead_agent_id', 'left')
            ->join('organization_units ou', 'ou.id = p.unit_id', 'left')
            ->where('p.id', $id)
            ->get()->getRowArray();

        if (!$project) {
            return null;
        }

        $classification = $project['confidentiality_level'] ?? ProjectAccessService::LEVEL_INTERNAL;
        if (!$this->accessService->canAccessClassification($classification, $userContext)) {
            return null;
        }

        // Project Brief
        $brief = $this->db->table('project_briefs')->where('project_id', $id)->get()->getRowArray();

        // Active Scope Baseline
        $scope = $this->db->table('project_scopes')
            ->where('project_id', $id)
            ->where('is_baseline', 1)
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();

        // Milestones
        $milestones = $this->db->table('project_milestones')
            ->where('project_id', $id)
            ->orderBy('order_index', 'ASC')
            ->orderBy('target_date', 'ASC')
            ->get()->getResultArray();

        // Deliverables
        $deliverables = $this->db->table('project_deliverables d')
            ->select('d.*, a.name as responsible_agent_name, u.full_name as responsible_user_name')
            ->join('ai_agent_registry a', 'a.id = d.responsible_agent_id', 'left')
            ->join('users u', 'u.id = d.responsible_user_id', 'left')
            ->where('d.project_id', $id)
            ->orderBy('d.due_date', 'ASC')
            ->get()->getResultArray();

        // Team Members
        $team = $this->db->table('project_team_members tm')
            ->select('tm.*, a.name as agent_name, a.agent_code, u.full_name as user_full_name, u.username')
            ->join('ai_agent_registry a', 'a.id = tm.agent_id', 'left')
            ->join('users u', 'u.id = tm.user_id', 'left')
            ->where('tm.project_id', $id)
            ->where('tm.status', 'active')
            ->orderBy('tm.id', 'ASC')
            ->get()->getResultArray();

        // Active Risks & Issues counts
        $risksCount = $this->db->table('project_risks')->where('project_id', $id)->countAllResults();
        $issuesCount = $this->db->table('project_issues')->where('project_id', $id)->where('status !=', 'closed')->countAllResults();
        $crCount = $this->db->table('project_change_requests')->where('project_id', $id)->countAllResults();

        // Linked Workforce Tasks count
        $tasksCount = $this->db->table('workforce_tasks')->where('project_id', $id)->countAllResults();

        $formatted = $this->formatProjectSummary($project);
        $formatted['brief'] = $brief;
        $formatted['active_scope'] = $scope;
        $formatted['milestones'] = $milestones;
        $formatted['deliverables'] = $deliverables;
        $formatted['team_members'] = $team;
        $formatted['counts'] = [
            'milestones'   => count($milestones),
            'deliverables' => count($deliverables),
            'team_members' => count($team),
            'risks'        => $risksCount,
            'open_issues'  => $issuesCount,
            'change_reqs'  => $crCount,
            'linked_tasks' => $tasksCount,
        ];

        return $formatted;
    }

    /**
     * Create a new project.
     */
    public function createProject(array $data, array $actor): array
    {
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            throw new InvalidArgumentException('Project name is required.');
        }

        $type = $data['project_type'] ?? 'customer_project';
        if (!in_array($type, ['customer_project', 'internal_initiative'])) {
            throw new InvalidArgumentException('Invalid project type.');
        }

        // Customer project requires client_id
        $clientId = !empty($data['client_id']) ? (int)$data['client_id'] : null;
        if ($type === 'customer_project' && empty($clientId)) {
            throw new InvalidArgumentException('Customer projects require a valid client_id.');
        }

        // Auto-generate project code if not provided
        $code = trim($data['project_code'] ?? '');
        if (empty($code)) {
            $prefix = ($type === 'customer_project') ? 'PRJ-CUST-' : 'PRJ-INT-';
            $year = date('Y');
            $count = $this->db->table('projects')->like('project_code', "{$prefix}{$year}-", 'after')->countAllResults();
            $code = sprintf("%s%s-%03d", $prefix, $year, $count + 1);
        }

        // Auto-generate UUID
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $now = date('Y-m-d H:i:s');
        $sponsoringAuthority = $data['sponsoring_authority'] ?? ($type === 'customer_project' ? 'human_owner' : 'continuous_executive_board');
        $initialStage = ($type === 'customer_project') ? 'lead' : 'idea_proposal';
        if (!empty($data['lifecycle_stage'])) {
            $initialStage = $data['lifecycle_stage'];
        }

        $insertData = [
            'project_uuid'            => $uuid,
            'project_code'            => $code,
            'name'                    => $name,
            'project_type'            => $type,
            'category'                => $data['category'] ?? 'web_app',
            'description'             => $data['description'] ?? null,
            'objectives'              => $data['objectives'] ?? null,
            'client_id'               => $clientId,
            'unit_id'                 => !empty($data['unit_id']) ? (int)$data['unit_id'] : null,
            'project_owner_user_id'   => !empty($data['project_owner_user_id']) ? (int)$data['project_owner_user_id'] : ($actor['id'] ?? null),
            'project_manager_user_id' => !empty($data['project_manager_user_id']) ? (int)$data['project_manager_user_id'] : null,
            'project_manager_agent_id'=> !empty($data['project_manager_agent_id']) ? (int)$data['project_manager_agent_id'] : null,
            'delivery_lead_agent_id'  => !empty($data['delivery_lead_agent_id']) ? (int)$data['delivery_lead_agent_id'] : null,
            'sponsoring_authority'    => $sponsoringAuthority,
            'sponsoring_direction_id' => !empty($data['sponsoring_direction_id']) ? (int)$data['sponsoring_direction_id'] : null,
            'sponsoring_decision_id'  => !empty($data['sponsoring_decision_id']) ? (int)$data['sponsoring_decision_id'] : null,
            'channel_id'              => !empty($data['channel_id']) ? (int)$data['channel_id'] : null,
            'lifecycle_stage'         => $initialStage,
            'status'                  => $data['status'] ?? 'draft',
            'priority'                => $data['priority'] ?? 'medium',
            'confidentiality_level'   => $data['confidentiality_level'] ?? 'internal',
            'start_date'              => $data['start_date'] ?? null,
            'target_completion_date'  => $data['target_completion_date'] ?? null,
            'actual_completion_date'  => null,
            'budget_currency'         => $data['budget_currency'] ?? 'IDR',
            'proposed_budget'         => (float)($data['proposed_budget'] ?? 0.00),
            'approved_budget_limit'   => (float)($data['approved_budget_limit'] ?? 0.00),
            'actual_cost_incurred'    => 0.00,
            'progress_percent'        => 0,
            'health_status'           => 'healthy',
            'created_at'              => $now,
            'updated_at'              => $now,
        ];

        $this->db->table('projects')->insert($insertData);
        $projectId = (int)$this->db->insertID();

        // Log Status History
        $this->db->table('project_status_history')->insert([
            'project_id'          => $projectId,
            'from_stage'          => null,
            'to_stage'            => $initialStage,
            'from_status'         => null,
            'to_status'           => $insertData['status'],
            'changed_by_user_id'  => $actor['id'] ?? null,
            'changed_by_agent_id' => $actor['agent_id'] ?? null,
            'reason'              => 'Project creation',
            'notes'               => 'Initial creation by ' . ($actor['username'] ?? 'system'),
            'created_at'          => $now,
        ]);

        // Audit Event
        $this->logAuditEvent($projectId, 'project.created', $actor, [
            'project_code' => $code,
            'name'         => $name,
            'type'         => $type,
        ]);

        return $this->getProjectById($projectId, $actor);
    }

    /**
     * Update project fields.
     */
    public function updateProject(int $id, array $data, array $actor): ?array
    {
        $existing = $this->db->table('projects')->where('id', $id)->get()->getRowArray();
        if (!$existing) {
            throw new RuntimeException("Project #{$id} not found.");
        }

        $fields = [
            'name', 'category', 'description', 'objectives', 'unit_id',
            'project_owner_user_id', 'project_manager_user_id', 'project_manager_agent_id', 'delivery_lead_agent_id',
            'priority', 'confidentiality_level', 'start_date', 'target_completion_date', 'health_status'
        ];

        $updateData = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $updateData[$f] = $data[$f];
            }
        }
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        $this->db->table('projects')->where('id', $id)->update($updateData);

        $this->logAuditEvent($id, 'project.updated', $actor, [
            'updated_fields' => array_keys($updateData),
        ]);

        return $this->getProjectById($id, $actor);
    }

    /**
     * Soft delete / cancel project.
     */
    public function deleteProject(int $id, array $actor): bool
    {
        $existing = $this->db->table('projects')->where('id', $id)->get()->getRowArray();
        if (!$existing) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('projects')->where('id', $id)->update([
            'status'     => 'cancelled',
            'updated_at' => $now,
        ]);

        $this->logAuditEvent($id, 'project.cancelled', $actor, [
            'reason' => 'Project marked as cancelled',
        ]);

        return true;
    }

    /**
     * Portfolio Summary Metrics.
     */
    public function getPortfolioMetrics(?array $userContext = null): array
    {
        $projects = $this->getProjects([], $userContext);

        $total = count($projects);
        $customerCount = 0;
        $internalCount = 0;
        $activeCount = 0;
        $healthyCount = 0;
        $atRiskCount = 0;
        $criticalCount = 0;

        $totalProposedBudget = 0.0;
        $totalApprovedBudget = 0.0;
        $totalActualCost = 0.0;
        $totalProgressSum = 0;

        $byStage = [];

        foreach ($projects as $p) {
            if ($p['project_type'] === 'customer_project') {
                $customerCount++;
            } else {
                $internalCount++;
            }

            if ($p['status'] === 'active') {
                $activeCount++;
            }

            switch ($p['health_status']) {
                case 'healthy': $healthyCount++; break;
                case 'at_risk': $atRiskCount++; break;
                case 'critical': $criticalCount++; break;
            }

            $stage = $p['lifecycle_stage'];
            $byStage[$stage] = ($byStage[$stage] ?? 0) + 1;

            $totalProposedBudget += (float)$p['proposed_budget'];
            $totalApprovedBudget += (float)$p['approved_budget_limit'];
            $totalActualCost += (float)$p['actual_cost_incurred'];
            $totalProgressSum += (int)$p['progress_percent'];
        }

        $avgProgress = $total > 0 ? round($totalProgressSum / $total, 1) : 0;
        $budgetUtilizationRate = $totalApprovedBudget > 0 ? round(($totalActualCost / $totalApprovedBudget) * 100, 1) : 0;

        return [
            'total_projects'          => $total,
            'customer_projects'       => $customerCount,
            'internal_initiatives'    => $internalCount,
            'active_projects'         => $activeCount,
            'average_progress'        => $avgProgress,
            'health_distribution'     => [
                'healthy'  => $healthyCount,
                'at_risk'  => $atRiskCount,
                'critical' => $criticalCount,
            ],
            'financials'              => [
                'currency'               => 'IDR',
                'total_proposed_budget'  => $totalProposedBudget,
                'total_approved_budget'  => $totalApprovedBudget,
                'total_actual_cost'      => $totalActualCost,
                'budget_utilization_rate'=> $budgetUtilizationRate,
            ],
            'stage_distribution'      => $byStage,
        ];
    }

    /**
     * Recalculate project progress based on milestones, deliverables, and linked workforce tasks.
     */
    public function recalculateProjectProgress(int $projectId): int
    {
        $milestones = $this->db->table('project_milestones')->where('project_id', $projectId)->get()->getResultArray();
        $tasks = $this->db->table('workforce_tasks')->where('project_id', $projectId)->get()->getResultArray();

        $calculatedPercent = 0;

        if (!empty($milestones)) {
            $totalMilestones = count($milestones);
            $achievedMilestones = 0;
            foreach ($milestones as $m) {
                if ($m['status'] === 'achieved') {
                    $achievedMilestones += 1.0;
                } elseif ($m['status'] === 'in_progress') {
                    $achievedMilestones += 0.4;
                }
            }
            $milestoneScore = ($achievedMilestones / $totalMilestones) * 100;

            if (!empty($tasks)) {
                $totalTasks = count($tasks);
                $completedTasks = 0;
                foreach ($tasks as $t) {
                    if (in_array($t['status'], ['completed', 'accepted', 'closed'])) {
                        $completedTasks += 1.0;
                    } elseif ($t['status'] === 'in_progress') {
                        $completedTasks += 0.5;
                    }
                }
                $taskScore = ($completedTasks / $totalTasks) * 100;
                $calculatedPercent = (int)round(($milestoneScore * 0.6) + ($taskScore * 0.4));
            } else {
                $calculatedPercent = (int)round($milestoneScore);
            }
        } elseif (!empty($tasks)) {
            $totalTasks = count($tasks);
            $completedTasks = 0;
            foreach ($tasks as $t) {
                if (in_array($t['status'], ['completed', 'accepted', 'closed'])) {
                    $completedTasks++;
                }
            }
            $calculatedPercent = (int)round(($completedTasks / $totalTasks) * 100);
        }

        $calculatedPercent = min(100, max(0, $calculatedPercent));

        $this->db->table('projects')->where('id', $projectId)->update([
            'progress_percent' => $calculatedPercent,
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        return $calculatedPercent;
    }

    /**
     * Retrieve tasks from workforce_tasks linked to project.
     */
    public function getLinkedTasks(int $projectId): array
    {
        return $this->db->table('workforce_tasks t')
            ->select('t.*, a.name as assignee_agent_name, a.agent_code, m.title as milestone_title')
            ->join('ai_agent_registry a', 'a.id = t.accountable_agent_id', 'left')
            ->join('project_milestones m', 'm.id = t.milestone_id', 'left')
            ->where('t.project_id', $projectId)
            ->orderBy('t.id', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Link an existing workforce_task to this project.
     */
    public function linkTask(int $projectId, int $taskId, ?int $milestoneId = null, array $actor = []): bool
    {
        $task = $this->db->table('workforce_tasks')->where('id', $taskId)->get()->getRowArray();
        if (!$task) {
            throw new RuntimeException("Task #{$taskId} not found.");
        }

        $this->db->table('workforce_tasks')->where('id', $taskId)->update([
            'project_id'   => $projectId,
            'milestone_id' => $milestoneId,
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->recalculateProjectProgress($projectId);

        $this->logAuditEvent($projectId, 'project.task_linked', $actor, [
            'task_id'      => $taskId,
            'milestone_id' => $milestoneId,
        ]);

        return true;
    }

    /**
     * Unlink a task from project.
     */
    public function unlinkTask(int $projectId, int $taskId, array $actor = []): bool
    {
        $this->db->table('workforce_tasks')->where('id', $taskId)->where('project_id', $projectId)->update([
            'project_id'   => null,
            'milestone_id' => null,
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->recalculateProjectProgress($projectId);

        $this->logAuditEvent($projectId, 'project.task_unlinked', $actor, [
            'task_id' => $taskId,
        ]);

        return true;
    }

    /**
     * Helper to write audit event.
     */
    public function logAuditEvent(int $projectId, string $eventType, array $actor, array $metadata = []): void
    {
        $actorType = 'user';
        $actorId = $actor['id'] ?? null;
        $actorName = $actor['username'] ?? ($actor['full_name'] ?? 'System');

        if (!empty($actor['agent_id'])) {
            $actorType = 'agent';
            $actorId = $actor['agent_id'];
            $actorName = $actor['agent_code'] ?? ($actor['name'] ?? 'AI Agent');
        } elseif (empty($actorId)) {
            $actorType = 'system';
            $actorName = 'System Engine';
        }

        $this->db->table('project_audit_events')->insert([
            'project_id'    => $projectId,
            'event_type'    => $eventType,
            'actor_type'    => $actorType,
            'actor_id'      => $actorId,
            'actor_name'    => $actorName,
            'metadata_json' => json_encode($metadata),
            'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Format row with typed conversions.
     */
    protected function formatProjectSummary(array $row): array
    {
        return [
            'id'                       => (int)$row['id'],
            'project_uuid'             => $row['project_uuid'],
            'project_code'             => $row['project_code'],
            'name'                     => $row['name'],
            'project_type'             => $row['project_type'],
            'category'                 => $row['category'],
            'description'              => $row['description'],
            'objectives'               => $row['objectives'],
            'client_id'                => $row['client_id'] ? (int)$row['client_id'] : null,
            'client_name'              => $row['client_name'] ?? null,
            'client_code'              => $row['client_code'] ?? null,
            'unit_id'                  => $row['unit_id'] ? (int)$row['unit_id'] : null,
            'unit_name'                => $row['unit_name'] ?? null,
            'project_owner_user_id'    => $row['project_owner_user_id'] ? (int)$row['project_owner_user_id'] : null,
            'owner_full_name'          => $row['owner_full_name'] ?? ($row['owner_username'] ?? null),
            'project_manager_user_id'  => $row['project_manager_user_id'] ? (int)$row['project_manager_user_id'] : null,
            'project_manager_agent_id' => $row['project_manager_agent_id'] ? (int)$row['project_manager_agent_id'] : null,
            'pm_agent_name'            => $row['pm_agent_name'] ?? null,
            'delivery_lead_agent_id'   => $row['delivery_lead_agent_id'] ? (int)$row['delivery_lead_agent_id'] : null,
            'dl_agent_name'            => $row['dl_agent_name'] ?? null,
            'sponsoring_authority'     => $row['sponsoring_authority'],
            'channel_id'               => $row['channel_id'] ? (int)$row['channel_id'] : null,
            'lifecycle_stage'          => $row['lifecycle_stage'],
            'status'                   => $row['status'],
            'priority'                 => $row['priority'],
            'confidentiality_level'    => $row['confidentiality_level'],
            'start_date'               => $row['start_date'],
            'target_completion_date'   => $row['target_completion_date'],
            'actual_completion_date'   => $row['actual_completion_date'],
            'budget_currency'          => $row['budget_currency'] ?? 'IDR',
            'proposed_budget'          => (float)$row['proposed_budget'],
            'approved_budget_limit'    => (float)$row['approved_budget_limit'],
            'actual_cost_incurred'     => (float)$row['actual_cost_incurred'],
            'progress_percent'         => (int)$row['progress_percent'],
            'health_status'            => $row['health_status'],
            'created_at'               => $row['created_at'],
            'updated_at'               => $row['updated_at'],
        ];
    }
}
