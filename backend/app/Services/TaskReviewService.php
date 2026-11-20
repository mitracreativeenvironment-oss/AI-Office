<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class TaskReviewService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ------------------------------------------------------------------------
    // DELIVERABLES
    // ------------------------------------------------------------------------

    public function listDeliverables(int $taskId): array
    {
        return $this->db->table('task_deliverables d')
            ->select('d.*, ag.name as submitting_agent_name, ag.agent_code as submitting_agent_code')
            ->join('ai_agent_registry ag', 'ag.id = d.submitting_agent_id')
            ->where('d.task_id', $taskId)
            ->orderBy('d.version', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function submitDeliverable(int $taskId, array $data, int $submittingAgentId): array
    {
        $title = trim($data['title'] ?? '');
        if (empty($title)) {
            throw new \InvalidArgumentException('Deliverable title is required.', 422);
        }

        // Verify task exists
        $task = $this->db->table('workforce_tasks')->where('id', $taskId)->get()->getRowArray();
        if (!$task) {
            throw new \InvalidArgumentException("Task #{$taskId} not found.", 404);
        }

        // Compute version: max existing version + 1
        $maxVersion = $this->db->table('task_deliverables')
            ->where('task_id', $taskId)
            ->selectMax('version')
            ->get()
            ->getRowArray()['version'] ?? 0;
        $version = (int)$maxVersion + 1;

        $now = date('Y-m-d H:i:s');
        $insert = [
            'task_id'             => $taskId,
            'submitting_agent_id' => $submittingAgentId,
            'deliverable_type'    => $data['deliverable_type'] ?? 'report',
            'title'               => $title,
            'description'         => $data['description'] ?? null,
            'artifact_uri'        => $data['artifact_uri'] ?? null,
            'version'             => $version,
            'review_status'       => 'pending_review',
            'is_current_accepted' => 0,
            'created_at'          => $now,
            'updated_at'          => $now,
        ];

        $this->db->table('task_deliverables')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();

        // If task is in_progress, move to under_review
        if ($task['status'] === 'in_progress') {
            $this->db->table('workforce_tasks')->where('id', $taskId)->update([
                'status'     => 'under_review',
                'updated_at' => $now,
            ]);
        }

        // Audit
        $this->db->table('task_events_audit')->insert([
            'task_id'     => $taskId,
            'event_type'  => 'deliverable_submitted',
            'from_status' => $task['status'],
            'to_status'   => 'under_review',
            'actor_type'  => 'agent',
            'actor_id'    => $submittingAgentId,
            'actor_name'  => "Agent #{$submittingAgentId}",
            'details'     => "Deliverable '{$title}' (v{$version}) submitted for formal review.",
            'created_at'  => $now,
        ]);

        return $insert;
    }

    // ------------------------------------------------------------------------
    // REVIEWS & QUALITY VERIFICATION
    // ------------------------------------------------------------------------

    public function submitReview(int $taskId, int $deliverableId, array $data, ?int $reviewerUserId = null, ?int $reviewerAgentId = null): array
    {
        $deliverable = $this->db->table('task_deliverables')->where('id', $deliverableId)->get()->getRowArray();
        if (!$deliverable || $deliverable['task_id'] != $taskId) {
            throw new \InvalidArgumentException("Deliverable #{$deliverableId} not found on Task #{$taskId}.", 404);
        }

        // Independent review rule: agent cannot review its own deliverable
        if ($reviewerAgentId && $reviewerAgentId === (int)$deliverable['submitting_agent_id']) {
            throw new \RuntimeException("Independent review rule violation: An Agent cannot formally review or verify its own deliverable.", 403);
        }

        $outcome = $data['review_outcome'] ?? 'accepted';
        $comments = trim($data['evaluation_comments'] ?? 'Evaluated according to quality standards.');
        $isVerifiedContribution = !empty($data['is_verified_contribution']) ? 1 : 0;
        $now = date('Y-m-d H:i:s');

        $reviewInsert = [
            'task_id'                  => $taskId,
            'deliverable_id'           => $deliverableId,
            'reviewer_user_id'         => $reviewerUserId,
            'reviewer_agent_id'        => $reviewerAgentId,
            'review_outcome'           => $outcome,
            'evaluation_comments'      => $comments,
            'criteria_checklist'       => !empty($data['criteria_checklist']) ? json_encode($data['criteria_checklist']) : null,
            'is_verified_contribution' => $isVerifiedContribution,
            'created_at'               => $now,
        ];

        $this->db->table('task_reviews')->insert($reviewInsert);
        $reviewInsert['id'] = (int)$this->db->insertID();

        // Update deliverable
        if ($outcome === 'accepted') {
            // Demote older accepted deliverables
            $this->db->table('task_deliverables')->where('task_id', $taskId)->update(['is_current_accepted' => 0]);

            $this->db->table('task_deliverables')->where('id', $deliverableId)->update([
                'review_status'       => 'accepted',
                'is_current_accepted' => 1,
                'updated_at'          => $now,
            ]);

            // Link to Individual Agent Performance History if verified!
            if ($isVerifiedContribution) {
                $submittingAgent = $this->db->table('ai_agent_registry')->where('id', $deliverable['submitting_agent_id'])->get()->getRowArray();
                $task = $this->db->table('workforce_tasks')->where('id', $taskId)->get()->getRowArray();

                $this->db->table('ai_agent_performance_history')->insert([
                    'agent_id'           => (int)$deliverable['submitting_agent_id'],
                    'agent_uuid'         => $submittingAgent['agent_uuid'] ?? '',
                    'category'           => 'task_completion',
                    'title'              => "Verified Deliverable: {$deliverable['title']} (v{$deliverable['version']})",
                    'description'        => "Output verified by review #{$reviewInsert['id']}: {$comments}",
                    'task_ref'           => $task['task_code'] ?? "TSK-#{$taskId}",
                    'output_ref'         => $deliverable['artifact_uri'] ?? $deliverable['title'],
                    'evidence_ref'       => "REVIEW-REF-{$reviewInsert['id']}",
                    'evidence_status'    => 'verified',
                    'evaluator_identity' => $reviewerUserId ? "User #{$reviewerUserId}" : "Agent #{$reviewerAgentId}",
                    'evaluation_notes'   => $comments,
                    'superseded_by_id'   => null,
                    'created_at'         => $now,
                ]);
            }
        } elseif ($outcome === 'revision_required') {
            $this->db->table('task_deliverables')->where('id', $deliverableId)->update([
                'review_status' => 'revision_required',
                'updated_at'    => $now,
            ]);

            $this->db->table('workforce_tasks')->where('id', $taskId)->update([
                'status'     => 'revision_required',
                'updated_at' => $now,
            ]);
        }

        // Audit
        $this->db->table('task_events_audit')->insert([
            'task_id'     => $taskId,
            'event_type'  => 'review_completed',
            'from_status' => 'under_review',
            'to_status'   => $outcome === 'accepted' ? 'verified' : ($outcome === 'revision_required' ? 'revision_required' : 'under_review'),
            'actor_type'  => $reviewerUserId ? 'human' : 'agent',
            'actor_id'    => $reviewerUserId ?? $reviewerAgentId,
            'actor_name'  => $reviewerUserId ? "User #{$reviewerUserId}" : "Agent #{$reviewerAgentId}",
            'details'     => "Review outcome: {$outcome}. Verified contribution: " . ($isVerifiedContribution ? 'YES' : 'NO'),
            'created_at'  => $now,
        ]);

        return $reviewInsert;
    }

    // ------------------------------------------------------------------------
    // BLOCKERS & EXCEPTIONS
    // ------------------------------------------------------------------------

    public function reportBlocker(int $taskId, array $data, ?int $reporterAgentId = null, ?int $reporterUserId = null): array
    {
        $desc = trim($data['description'] ?? '');
        if (empty($desc)) {
            throw new \InvalidArgumentException('Blocker description is required.', 422);
        }

        $now = date('Y-m-d H:i:s');
        $insert = [
            'task_id'              => $taskId,
            'category'             => $data['category'] ?? 'missing_information',
            'description'          => $desc,
            'severity'             => $data['severity'] ?? 'medium',
            'reported_by_agent_id' => $reporterAgentId,
            'reported_by_user_id'  => $reporterUserId,
            'status'               => 'active',
            'resolution_notes'     => null,
            'resolved_at'          => null,
            'created_at'           => $now,
            'updated_at'           => $now,
        ];

        $this->db->table('task_blockers')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();

        // Move task to blocked state
        $this->db->table('workforce_tasks')->where('id', $taskId)->update([
            'status'     => 'blocked',
            'updated_at' => $now,
        ]);

        // Audit
        $this->db->table('task_events_audit')->insert([
            'task_id'     => $taskId,
            'event_type'  => 'task_blocked',
            'from_status' => null,
            'to_status'   => 'blocked',
            'actor_type'  => $reporterUserId ? 'human' : 'agent',
            'actor_id'    => $reporterUserId ?? $reporterAgentId,
            'actor_name'  => $reporterUserId ? "User #{$reporterUserId}" : "Agent #{$reporterAgentId}",
            'details'     => "Task blocked: [{$insert['category']}] {$desc}",
            'created_at'  => $now,
        ]);

        return $insert;
    }

    public function resolveBlocker(int $blockerId, string $resolutionNotes): array
    {
        $blocker = $this->db->table('task_blockers')->where('id', $blockerId)->get()->getRowArray();
        if (!$blocker) {
            throw new \InvalidArgumentException("Blocker #{$blockerId} not found.", 404);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('task_blockers')->where('id', $blockerId)->update([
            'status'           => 'resolved',
            'resolution_notes' => $resolutionNotes,
            'resolved_at'      => $now,
            'updated_at'       => $now,
        ]);

        // Check if any other active blockers exist on this task
        $activeCount = $this->db->table('task_blockers')
            ->where('task_id', $blocker['task_id'])
            ->where('status', 'active')
            ->countAllResults();

        if ($activeCount === 0) {
            // Unblock task: move back to in_progress
            $this->db->table('workforce_tasks')->where('id', $blocker['task_id'])->update([
                'status'     => 'in_progress',
                'updated_at' => $now,
            ]);
        }

        return $this->db->table('task_blockers')->where('id', $blockerId)->get()->getRowArray();
    }
}
