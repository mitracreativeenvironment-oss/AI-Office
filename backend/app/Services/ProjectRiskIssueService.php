<?php

namespace App\Services;

use Config\Database;
use InvalidArgumentException;
use RuntimeException;

class ProjectRiskIssueService
{
    protected $db;
    protected ProjectService $projectService;

    public function __construct(?ProjectService $projectService = null)
    {
        $this->db = Database::connect();
        $this->projectService = $projectService ?? new ProjectService();
    }

    /**
     * Risks CRUD.
     */
    public function getRisks(int $projectId): array
    {
        return $this->db->table('project_risks r')
            ->select('r.*, 
                      u.full_name as owner_user_name,
                      a.name as owner_agent_name, a.agent_code as owner_agent_code')
            ->join('users u', 'u.id = r.owner_user_id', 'left')
            ->join('ai_agent_registry a', 'a.id = r.owner_agent_id', 'left')
            ->where('r.project_id', $projectId)
            ->orderBy('r.id', 'DESC')
            ->get()->getResultArray();
    }

    public function createRisk(int $projectId, array $data, array $actor): array
    {
        $title = trim($data['title'] ?? '');
        if (empty($title)) {
            throw new InvalidArgumentException('Risk title is required.');
        }

        $code = trim($data['risk_code'] ?? '');
        if (empty($code)) {
            $count = $this->db->table('project_risks')->where('project_id', $projectId)->countAllResults();
            $code = sprintf("RSK-%03d", $count + 1);
        }

        $probability = $data['probability'] ?? 'medium';
        $impact = $data['impact'] ?? 'medium';
        $riskLevel = $data['risk_level'] ?? $impact;

        $now = date('Y-m-d H:i:s');
        $insertData = [
            'project_id'       => $projectId,
            'risk_code'        => $code,
            'title'            => $title,
            'description'      => $data['description'] ?? $title,
            'probability'      => $probability,
            'impact'           => $impact,
            'risk_level'       => $riskLevel,
            'mitigation_plan'  => $data['mitigation_plan'] ?? null,
            'contingency_plan' => $data['contingency_plan'] ?? null,
            'owner_user_id'    => !empty($data['owner_user_id']) ? (int)$data['owner_user_id'] : null,
            'owner_agent_id'   => !empty($data['owner_agent_id']) ? (int)$data['owner_agent_id'] : null,
            'status'           => $data['status'] ?? 'identified',
            'created_at'       => $now,
            'updated_at'       => $now,
        ];

        $this->db->table('project_risks')->insert($insertData);
        $id = (int)$this->db->insertID();

        $this->projectService->logAuditEvent($projectId, 'project.risk_created', $actor, ['risk_id' => $id, 'risk_code' => $code]);

        return $this->db->table('project_risks')->where('id', $id)->get()->getRowArray();
    }

    public function updateRisk(int $riskId, array $data, array $actor): array
    {
        $fields = [
            'title', 'description', 'probability', 'impact', 'risk_level',
            'mitigation_plan', 'contingency_plan', 'owner_user_id', 'owner_agent_id', 'status'
        ];
        $updateData = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $updateData[$f] = $data[$f];
            }
        }
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        $this->db->table('project_risks')->where('id', $riskId)->update($updateData);

        return $this->db->table('project_risks')->where('id', $riskId)->get()->getRowArray();
    }

    public function deleteRisk(int $riskId, array $actor): bool
    {
        return $this->db->table('project_risks')->where('id', $riskId)->delete();
    }

    /**
     * Issues CRUD.
     */
    public function getIssues(int $projectId): array
    {
        return $this->db->table('project_issues i')
            ->select('i.*, 
                      u.full_name as responsible_user_name,
                      a.name as responsible_agent_name, a.agent_code as responsible_agent_code')
            ->join('users u', 'u.id = i.responsible_user_id', 'left')
            ->join('ai_agent_registry a', 'a.id = i.responsible_agent_id', 'left')
            ->where('i.project_id', $projectId)
            ->orderBy('i.id', 'DESC')
            ->get()->getResultArray();
    }

    public function createIssue(int $projectId, array $data, array $actor): array
    {
        $title = trim($data['title'] ?? '');
        if (empty($title)) {
            throw new InvalidArgumentException('Issue title is required.');
        }

        $code = trim($data['issue_code'] ?? '');
        if (empty($code)) {
            $count = $this->db->table('project_issues')->where('project_id', $projectId)->countAllResults();
            $code = sprintf("ISS-%03d", $count + 1);
        }

        $now = date('Y-m-d H:i:s');
        $insertData = [
            'project_id'           => $projectId,
            'issue_code'           => $code,
            'title'                => $title,
            'description'          => $data['description'] ?? $title,
            'severity'             => $data['severity'] ?? 'moderate',
            'priority'             => $data['priority'] ?? 'medium',
            'resolution_plan'      => $data['resolution_plan'] ?? null,
            'responsible_user_id'  => !empty($data['responsible_user_id']) ? (int)$data['responsible_user_id'] : null,
            'responsible_agent_id' => !empty($data['responsible_agent_id']) ? (int)$data['responsible_agent_id'] : null,
            'status'               => $data['status'] ?? 'open',
            'resolved_at'          => null,
            'created_at'           => $now,
            'updated_at'           => $now,
        ];

        $this->db->table('project_issues')->insert($insertData);
        $id = (int)$this->db->insertID();

        $this->projectService->logAuditEvent($projectId, 'project.issue_created', $actor, ['issue_id' => $id, 'issue_code' => $code]);

        return $this->db->table('project_issues')->where('id', $id)->get()->getRowArray();
    }

    public function updateIssue(int $issueId, array $data, array $actor): array
    {
        $fields = [
            'title', 'description', 'severity', 'priority', 'resolution_plan',
            'responsible_user_id', 'responsible_agent_id', 'status'
        ];
        $updateData = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $updateData[$f] = $data[$f];
            }
        }
        if (isset($data['status']) && $data['status'] === 'resolved') {
            $updateData['resolved_at'] = date('Y-m-d H:i:s');
        }
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        $this->db->table('project_issues')->where('id', $issueId)->update($updateData);

        return $this->db->table('project_issues')->where('id', $issueId)->get()->getRowArray();
    }

    public function resolveIssue(int $issueId, string $resolutionPlan, array $actor): array
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('project_issues')->where('id', $issueId)->update([
            'resolution_plan' => $resolutionPlan,
            'status'          => 'resolved',
            'resolved_at'     => $now,
            'updated_at'      => $now,
        ]);

        return $this->db->table('project_issues')->where('id', $issueId)->get()->getRowArray();
    }

    /**
     * Change Requests CRUD.
     */
    public function getChangeRequests(int $projectId): array
    {
        return $this->db->table('project_change_requests cr')
            ->select('cr.*, 
                      req_u.full_name as requester_user_name,
                      app_u.full_name as approver_user_name')
            ->join('users req_u', 'req_u.id = cr.requester_user_id', 'left')
            ->join('users app_u', 'app_u.id = cr.approved_by_user_id', 'left')
            ->where('cr.project_id', $projectId)
            ->orderBy('cr.id', 'DESC')
            ->get()->getResultArray();
    }

    public function createChangeRequest(int $projectId, array $data, array $actor): array
    {
        $title = trim($data['title'] ?? '');
        if (empty($title)) {
            throw new InvalidArgumentException('Change request title is required.');
        }

        $code = trim($data['change_code'] ?? '');
        if (empty($code)) {
            $totalCount = $this->db->table('project_change_requests')->countAllResults();
            $code = sprintf("CR-%04d-%04x", $totalCount + 1, mt_rand(0, 0xffff));
        }

        $budgetImpact = (float)($data['budget_impact_amount'] ?? 0.00);
        // Determine approval level based on budget impact: > 5M requires A (Owner), <= 5M requires B (Board)
        $approvalLevel = ($budgetImpact > 5000000.00) ? 'A' : ($data['approval_level'] ?? 'B');

        $now = date('Y-m-d H:i:s');
        $insertData = [
            'project_id'             => $projectId,
            'change_code'            => $code,
            'requester_type'         => $data['requester_type'] ?? 'human',
            'requester_user_id'      => $actor['id'] ?? null,
            'requester_agent_id'     => $actor['agent_id'] ?? null,
            'title'                  => $title,
            'description'            => $data['description'] ?? $title,
            'business_justification' => $data['business_justification'] ?? null,
            'scope_impact'           => $data['scope_impact'] ?? null,
            'schedule_impact_days'   => (int)($data['schedule_impact_days'] ?? 0),
            'budget_impact_amount'   => $budgetImpact,
            'risk_assessment'        => $data['risk_assessment'] ?? null,
            'approval_level'         => $approvalLevel,
            'status'                 => 'requested',
            'created_at'             => $now,
            'updated_at'             => $now,
        ];

        $this->db->table('project_change_requests')->insert($insertData);
        $id = (int)$this->db->insertID();

        $this->projectService->logAuditEvent($projectId, 'project.change_requested', $actor, [
            'change_id'   => $id,
            'change_code' => $code,
            'budget'      => $budgetImpact,
        ]);

        return $this->db->table('project_change_requests')->where('id', $id)->get()->getRowArray();
    }

    public function evaluateChangeRequest(int $crId, string $decision, ?string $rationale, array $actor): array
    {
        if (!in_array($decision, ['approved', 'rejected', 'under_review'])) {
            throw new InvalidArgumentException("Invalid decision: {$decision}");
        }

        $cr = $this->db->table('project_change_requests')->where('id', $crId)->get()->getRowArray();
        if (!$cr) {
            throw new RuntimeException("Change request #{$crId} not found.");
        }

        // Delegated authority check
        $roles = $actor['roles'] ?? [];
        $isOwner = in_array('owner', $roles) || in_array('superadmin', $roles);
        $isBoard = in_array('board', $roles) || in_array('executive', $roles);

        if ($cr['approval_level'] === 'A' && $decision === 'approved' && !$isOwner) {
            throw new RuntimeException("Change request has Approval Level A (Owner sign-off required). Only Human Owner can approve.");
        }

        if ($cr['approval_level'] === 'B' && $decision === 'approved' && !($isOwner || $isBoard)) {
            throw new RuntimeException("Change request has Approval Level B. Continuous Executive Board or Human Owner approval required.");
        }

        $now = date('Y-m-d H:i:s');
        $updateData = [
            'status'              => $decision,
            'approved_by_user_id' => ($decision === 'approved') ? ($actor['id'] ?? null) : null,
            'approved_at'         => ($decision === 'approved') ? $now : null,
            'updated_at'          => $now,
        ];

        $this->db->table('project_change_requests')->where('id', $crId)->update($updateData);

        // If approved and has budget impact, optionally adjust project budget
        if ($decision === 'approved' && (float)$cr['budget_impact_amount'] > 0) {
            $projectId = (int)$cr['project_id'];
            $prj = $this->db->table('projects')->where('id', $projectId)->get()->getRowArray();
            if ($prj) {
                $newApproved = (float)$prj['approved_budget_limit'] + (float)$cr['budget_impact_amount'];
                $this->db->table('projects')->where('id', $projectId)->update([
                    'approved_budget_limit' => $newApproved,
                    'updated_at'            => $now,
                ]);
            }
        }

        $this->projectService->logAuditEvent((int)$cr['project_id'], 'project.change_evaluated', $actor, [
            'change_id' => $crId,
            'decision'  => $decision,
            'rationale' => $rationale,
        ]);

        return $this->db->table('project_change_requests')->where('id', $crId)->get()->getRowArray();
    }
}
