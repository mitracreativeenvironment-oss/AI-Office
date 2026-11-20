<?php

namespace App\Services;

use Config\Database;
use InvalidArgumentException;
use RuntimeException;

class ScopeDeliverableService
{
    protected $db;
    protected ProjectService $projectService;

    public function __construct(?ProjectService $projectService = null)
    {
        $this->db = Database::connect();
        $this->projectService = $projectService ?? new ProjectService();
    }

    /**
     * Project Brief.
     */
    public function getProjectBrief(int $projectId): ?array
    {
        return $this->db->table('project_briefs')->where('project_id', $projectId)->get()->getRowArray();
    }

    public function upsertProjectBrief(int $projectId, array $data, array $actor): array
    {
        $existing = $this->getProjectBrief($projectId);
        $now = date('Y-m-d H:i:s');

        $fields = [
            'background', 'business_objectives', 'expected_outcomes',
            'constraints', 'assumptions', 'dependencies', 'success_criteria', 'scope_boundaries'
        ];
        $payload = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $payload[$f] = $data[$f];
            }
        }
        $payload['updated_at'] = $now;

        if ($existing) {
            $this->db->table('project_briefs')->where('project_id', $projectId)->update($payload);
        } else {
            $payload['project_id'] = $projectId;
            $payload['created_at'] = $now;
            $this->db->table('project_briefs')->insert($payload);
        }

        $this->projectService->logAuditEvent($projectId, 'project.brief_upserted', $actor);

        return $this->getProjectBrief($projectId);
    }

    /**
     * Project Scopes & Baselines.
     */
    public function getProjectScopes(int $projectId): array
    {
        return $this->db->table('project_scopes s')
            ->select('s.*, u.username as approver_username, u.full_name as approver_name')
            ->join('users u', 'u.id = s.approved_by_user_id', 'left')
            ->where('s.project_id', $projectId)
            ->orderBy('s.id', 'DESC')
            ->get()->getResultArray();
    }

    public function createScopeBaseline(int $projectId, array $data, array $actor): array
    {
        $summary = trim($data['scope_summary'] ?? '');
        if (empty($summary)) {
            throw new InvalidArgumentException('Scope summary is required.');
        }

        $now = date('Y-m-d H:i:s');
        $isBaseline = !empty($data['is_baseline']) ? 1 : 0;

        // If setting this as baseline, unset any existing baselines
        if ($isBaseline) {
            $this->db->table('project_scopes')->where('project_id', $projectId)->update(['is_baseline' => 0]);
        }

        $inScope = is_array($data['in_scope_items'] ?? null) ? json_encode($data['in_scope_items']) : ($data['in_scope_items_json'] ?? null);
        $outScope = is_array($data['out_of_scope_items'] ?? null) ? json_encode($data['out_of_scope_items']) : ($data['out_of_scope_json'] ?? null);

        $insertData = [
            'project_id'          => $projectId,
            'version_number'      => $data['version_number'] ?? '1.0',
            'scope_summary'       => $summary,
            'in_scope_items_json' => $inScope,
            'out_of_scope_json'   => $outScope,
            'is_baseline'         => $isBaseline,
            'approved_by_user_id' => $actor['id'] ?? null,
            'approved_at'         => $isBaseline ? $now : null,
            'created_at'          => $now,
        ];

        $this->db->table('project_scopes')->insert($insertData);
        $id = (int)$this->db->insertID();

        $this->projectService->logAuditEvent($projectId, 'project.scope_baseline_created', $actor, [
            'scope_id'       => $id,
            'version_number' => $insertData['version_number'],
            'is_baseline'    => $isBaseline,
        ]);

        return $this->db->table('project_scopes')->where('id', $id)->get()->getRowArray();
    }

    /**
     * Milestones CRUD.
     */
    public function getMilestones(int $projectId): array
    {
        return $this->db->table('project_milestones')
            ->where('project_id', $projectId)
            ->orderBy('order_index', 'ASC')
            ->orderBy('target_date', 'ASC')
            ->get()->getResultArray();
    }

    public function createMilestone(int $projectId, array $data, array $actor): array
    {
        $title = trim($data['title'] ?? '');
        if (empty($title)) {
            throw new InvalidArgumentException('Milestone title is required.');
        }

        $code = trim($data['milestone_code'] ?? '');
        if (empty($code)) {
            $count = $this->db->table('project_milestones')->where('project_id', $projectId)->countAllResults();
            $code = sprintf("MS-%03d", $count + 1);
        }

        $now = date('Y-m-d H:i:s');
        $insertData = [
            'project_id'             => $projectId,
            'milestone_code'         => $code,
            'title'                  => $title,
            'description'            => $data['description'] ?? null,
            'target_date'            => $data['target_date'] ?? date('Y-m-d', strtotime('+14 days')),
            'actual_completion_date' => null,
            'is_critical_path'       => !empty($data['is_critical_path']) ? 1 : 0,
            'status'                 => $data['status'] ?? 'pending',
            'order_index'            => (int)($data['order_index'] ?? 1),
            'created_at'             => $now,
            'updated_at'             => $now,
        ];

        $this->db->table('project_milestones')->insert($insertData);
        $id = (int)$this->db->insertID();

        $this->projectService->recalculateProjectProgress($projectId);
        $this->projectService->logAuditEvent($projectId, 'project.milestone_created', $actor, ['milestone_id' => $id]);

        return $this->db->table('project_milestones')->where('id', $id)->get()->getRowArray();
    }

    public function updateMilestone(int $milestoneId, array $data, array $actor): array
    {
        $existing = $this->db->table('project_milestones')->where('id', $milestoneId)->get()->getRowArray();
        if (!$existing) {
            throw new RuntimeException("Milestone #{$milestoneId} not found.");
        }

        $fields = ['title', 'description', 'target_date', 'actual_completion_date', 'is_critical_path', 'status', 'order_index'];
        $updateData = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $updateData[$f] = $data[$f];
            }
        }

        if (isset($data['status']) && $data['status'] === 'achieved' && empty($existing['actual_completion_date'])) {
            $updateData['actual_completion_date'] = date('Y-m-d');
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $this->db->table('project_milestones')->where('id', $milestoneId)->update($updateData);

        $projectId = (int)$existing['project_id'];
        $this->projectService->recalculateProjectProgress($projectId);

        return $this->db->table('project_milestones')->where('id', $milestoneId)->get()->getRowArray();
    }

    public function deleteMilestone(int $milestoneId, array $actor): bool
    {
        $existing = $this->db->table('project_milestones')->where('id', $milestoneId)->get()->getRowArray();
        if (!$existing) {
            return false;
        }

        $projectId = (int)$existing['project_id'];
        $this->db->table('project_milestones')->where('id', $milestoneId)->delete();
        $this->projectService->recalculateProjectProgress($projectId);

        return true;
    }

    /**
     * Deliverables CRUD.
     */
    public function getDeliverables(int $projectId, ?int $milestoneId = null): array
    {
        $builder = $this->db->table('project_deliverables d')
            ->select('d.*, 
                      m.title as milestone_title, m.milestone_code,
                      a.name as responsible_agent_name, a.agent_code as responsible_agent_code,
                      u.full_name as responsible_user_name,
                      rev_u.full_name as reviewer_user_name')
            ->join('project_milestones m', 'm.id = d.milestone_id', 'left')
            ->join('ai_agent_registry a', 'a.id = d.responsible_agent_id', 'left')
            ->join('users u', 'u.id = d.responsible_user_id', 'left')
            ->join('users rev_u', 'rev_u.id = d.reviewed_by_user_id', 'left')
            ->where('d.project_id', $projectId);

        if ($milestoneId) {
            $builder->where('d.milestone_id', $milestoneId);
        }

        return $builder->orderBy('d.due_date', 'ASC')->get()->getResultArray();
    }

    public function createDeliverable(int $projectId, array $data, array $actor): array
    {
        $title = trim($data['title'] ?? '');
        if (empty($title)) {
            throw new InvalidArgumentException('Deliverable title is required.');
        }

        $code = trim($data['deliverable_code'] ?? '');
        if (empty($code)) {
            $count = $this->db->table('project_deliverables')->where('project_id', $projectId)->countAllResults();
            $code = sprintf("DEL-%03d", $count + 1);
        }

        $now = date('Y-m-d H:i:s');
        $insertData = [
            'project_id'              => $projectId,
            'milestone_id'            => !empty($data['milestone_id']) ? (int)$data['milestone_id'] : null,
            'deliverable_code'        => $code,
            'title'                   => $title,
            'description'             => $data['description'] ?? null,
            'responsible_agent_id'    => !empty($data['responsible_agent_id']) ? (int)$data['responsible_agent_id'] : null,
            'responsible_user_id'     => !empty($data['responsible_user_id']) ? (int)$data['responsible_user_id'] : null,
            'due_date'                => $data['due_date'] ?? null,
            'acceptance_criteria'     => $data['acceptance_criteria'] ?? null,
            'supporting_evidence_url' => $data['supporting_evidence_url'] ?? null,
            'review_status'           => $data['review_status'] ?? 'draft',
            'client_accepted'         => 0,
            'created_at'              => $now,
            'updated_at'              => $now,
        ];

        $this->db->table('project_deliverables')->insert($insertData);
        $id = (int)$this->db->insertID();

        $this->projectService->logAuditEvent($projectId, 'project.deliverable_created', $actor, ['deliverable_id' => $id]);

        return $this->db->table('project_deliverables')->where('id', $id)->get()->getRowArray();
    }

    public function updateDeliverable(int $deliverableId, array $data, array $actor): array
    {
        $fields = [
            'title', 'description', 'milestone_id', 'responsible_agent_id', 'responsible_user_id',
            'due_date', 'acceptance_criteria', 'supporting_evidence_url', 'review_status'
        ];
        $updateData = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $updateData[$f] = $data[$f];
            }
        }
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        $this->db->table('project_deliverables')->where('id', $deliverableId)->update($updateData);

        return $this->db->table('project_deliverables')->where('id', $deliverableId)->get()->getRowArray();
    }

    public function submitDeliverableForReview(int $deliverableId, string $evidenceUrl, array $actor): array
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('project_deliverables')->where('id', $deliverableId)->update([
            'supporting_evidence_url' => $evidenceUrl,
            'review_status'           => 'submitted',
            'updated_at'              => $now,
        ]);

        $d = $this->db->table('project_deliverables')->where('id', $deliverableId)->get()->getRowArray();
        if ($d) {
            $this->projectService->logAuditEvent((int)$d['project_id'], 'project.deliverable_submitted', $actor, [
                'deliverable_id' => $deliverableId,
                'evidence_url'   => $evidenceUrl,
            ]);
        }

        return $d;
    }

    public function reviewDeliverable(int $deliverableId, string $status, ?string $feedback, array $actor): array
    {
        if (!in_array($status, ['accepted', 'revision_required', 'rejected', 'under_review'])) {
            throw new InvalidArgumentException("Invalid review status: {$status}");
        }

        $now = date('Y-m-d H:i:s');
        $updateData = [
            'review_status'       => $status,
            'reviewed_by_user_id' => $actor['id'] ?? null,
            'reviewed_at'         => $now,
            'updated_at'          => $now,
        ];

        $this->db->table('project_deliverables')->where('id', $deliverableId)->update($updateData);

        $d = $this->db->table('project_deliverables')->where('id', $deliverableId)->get()->getRowArray();
        if ($d) {
            $this->projectService->logAuditEvent((int)$d['project_id'], 'project.deliverable_reviewed', $actor, [
                'deliverable_id' => $deliverableId,
                'status'         => $status,
                'feedback'       => $feedback,
            ]);
        }

        return $d;
    }

    public function clientAcceptDeliverable(int $deliverableId, array $actor): array
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('project_deliverables')->where('id', $deliverableId)->update([
            'client_accepted'    => 1,
            'client_accepted_at' => $now,
            'updated_at'         => $now,
        ]);

        $d = $this->db->table('project_deliverables')->where('id', $deliverableId)->get()->getRowArray();
        if ($d) {
            $this->projectService->logAuditEvent((int)$d['project_id'], 'project.deliverable_client_accepted', $actor, [
                'deliverable_id' => $deliverableId,
            ]);
        }

        return $d;
    }
}
