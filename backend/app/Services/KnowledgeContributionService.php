<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class KnowledgeContributionService
{
    protected BaseConnection $db;
    protected KnowledgeBaseService $kbService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->kbService = new KnowledgeBaseService();
    }

    public function listContributions(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $builder = $this->db->table('knowledge_contributions kc')
            ->select('kc.*, 
                      c.name as proposed_category_name, c.domain as proposed_domain,
                      ag.name as contributor_agent_name, ag.agent_code as contributor_agent_code,
                      u.full_name as contributor_user_name,
                      rev_u.full_name as reviewer_user_name,
                      rev_ag.name as reviewer_agent_name, rev_ag.agent_code as reviewer_agent_code')
            ->join('knowledge_categories c', 'c.id = kc.proposed_category_id')
            ->join('ai_agent_registry ag', 'ag.id = kc.contributor_agent_id', 'left')
            ->join('users u', 'u.id = kc.contributor_user_id', 'left')
            ->join('users rev_u', 'rev_u.id = kc.reviewer_user_id', 'left')
            ->join('ai_agent_registry rev_ag', 'rev_ag.id = kc.reviewer_agent_id', 'left');

        if (!empty($filters['status'])) {
            $builder->where('kc.status', $filters['status']);
        }
        if (!empty($filters['contributor_agent_id'])) {
            $builder->where('kc.contributor_agent_id', (int)$filters['contributor_agent_id']);
        }
        if (!empty($filters['proposed_category_id'])) {
            $builder->where('kc.proposed_category_id', (int)$filters['proposed_category_id']);
        }

        $total = $builder->countAllResults(false);
        $builder->orderBy('kc.id', 'DESC');
        $builder->limit($limit, $offset);
        $items = $builder->get()->getResultArray();

        foreach ($items as &$item) {
            $item['id'] = (int)$item['id'];
            $item['proposed_category_id'] = (int)$item['proposed_category_id'];
        }

        return ['items' => $items, 'total' => $total];
    }

    public function getContribution(int $id): ?array
    {
        $item = $this->db->table('knowledge_contributions kc')
            ->select('kc.*, 
                      c.name as proposed_category_name, c.domain as proposed_domain,
                      ag.name as contributor_agent_name, ag.agent_code as contributor_agent_code,
                      u.full_name as contributor_user_name,
                      rev_u.full_name as reviewer_user_name,
                      rev_ag.name as reviewer_agent_name, rev_ag.agent_code as reviewer_agent_code')
            ->join('knowledge_categories c', 'c.id = kc.proposed_category_id')
            ->join('ai_agent_registry ag', 'ag.id = kc.contributor_agent_id', 'left')
            ->join('users u', 'u.id = kc.contributor_user_id', 'left')
            ->join('users rev_u', 'rev_u.id = kc.reviewer_user_id', 'left')
            ->join('ai_agent_registry rev_ag', 'rev_ag.id = kc.reviewer_agent_id', 'left')
            ->where('kc.id', $id)
            ->get()
            ->getRowArray();

        if ($item) {
            $item['id'] = (int)$item['id'];
            $item['proposed_category_id'] = (int)$item['proposed_category_id'];
        }
        return $item;
    }

    /**
     * Submit a Knowledge Contribution Candidate
     */
    public function submitContribution(array $data, ?int $actorAgentId = null, ?int $actorUserId = null): array
    {
        $code = trim($data['contribution_code'] ?? 'KBC-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6)));
        $now = date('Y-m-d H:i:s');

        $contributorType = $data['contributor_type'] ?? ($actorAgentId ? 'agent' : 'human');
        $agentId = !empty($data['contributor_agent_id']) ? (int)$data['contributor_agent_id'] : $actorAgentId;
        $userId = ($contributorType === 'agent') ? null : ($actorUserId ?? (!empty($data['contributor_user_id']) ? (int)$data['contributor_user_id'] : null));

        $insert = [
            'contribution_code'       => $code,
            'contributor_type'        => $contributorType,
            'contributor_user_id'     => $userId,
            'contributor_agent_id'    => $agentId,
            'target_record_id'        => !empty($data['target_record_id']) ? (int)$data['target_record_id'] : null,
            'proposed_category_id'    => (int)($data['proposed_category_id'] ?? 3),
            'proposed_classification' => $data['proposed_classification'] ?? 'internal',
            'title'                   => trim($data['title'] ?? 'Proposed Knowledge Contribution'),
            'summary'                 => $data['summary'] ?? null,
            'content'                 => $data['content'] ?? '',
            'evidence_references'     => $data['evidence_references'] ?? null,
            'source_context'          => $data['source_context'] ?? null,
            'status'                  => 'submitted',
            'created_at'              => $now,
            'updated_at'              => $now,
        ];

        $this->db->table('knowledge_contributions')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();

        return $this->getContribution($insert['id']);
    }

    /**
     * Review contribution with Zero-Self-Review Rule enforcement
     */
    public function reviewContribution(int $id, string $decision, ?string $feedback = null, ?int $reviewerUserId = null, ?int $reviewerAgentId = null): array
    {
        $contribution = $this->getContribution($id);
        if (!$contribution) {
            throw new \InvalidArgumentException("Contribution #{$id} not found.", 404);
        }

        // ZERO-SELF-REVIEW RULE:
        // Contributor cannot review or approve their own contribution!
        if ($reviewerAgentId && $contribution['contributor_agent_id'] && (int)$contribution['contributor_agent_id'] === (int)$reviewerAgentId) {
            throw new \RuntimeException("Zero-Self-Review Violation: Agent #{$reviewerAgentId} cannot review its own knowledge contribution.", 403);
        }
        if ($reviewerUserId && $contribution['contributor_user_id'] && (int)$contribution['contributor_user_id'] === (int)$reviewerUserId) {
            throw new \RuntimeException("Zero-Self-Review Violation: User #{$reviewerUserId} cannot review their own knowledge contribution.", 403);
        }

        $validDecisions = ['approved', 'rejected', 'revision_requested'];
        if (!in_array($decision, $validDecisions)) {
            throw new \InvalidArgumentException("Invalid decision '{$decision}'. Valid options: approved, rejected, revision_requested.", 422);
        }

        $now = date('Y-m-d H:i:s');
        $publishedRecordId = null;
        $perfHistoryId = null;

        if ($decision === 'approved') {
            // Publish as formal knowledge record or update target
            if (!empty($contribution['target_record_id'])) {
                // Update target record
                $published = $this->kbService->updateRecord(
                    (int)$contribution['target_record_id'],
                    [
                        'content'        => $contribution['content'],
                        'summary'        => $contribution['summary'],
                        'change_summary' => "Approved contribution {$contribution['contribution_code']}: {$feedback}",
                        'bump_version'   => true,
                    ],
                    $reviewerUserId,
                    $reviewerAgentId
                );
                $publishedRecordId = $published['id'];
            } else {
                // Create new published record
                $created = $this->kbService->createRecord(
                    [
                        'category_id'           => $contribution['proposed_category_id'],
                        'title'                 => $contribution['title'],
                        'summary'               => $contribution['summary'],
                        'content'               => $contribution['content'],
                        'classification'        => $contribution['proposed_classification'],
                        'status'                => 'published',
                        'author_type'           => $contribution['contributor_type'],
                        'author_user_id'        => $contribution['contributor_user_id'],
                        'author_agent_id'       => $contribution['contributor_agent_id'],
                        'provenance_type'       => $contribution['contributor_agent_id'] ? 'agent_generated' : 'human_authored',
                        'provenance_source_ref' => $contribution['contribution_code'],
                    ],
                    $reviewerUserId,
                    $reviewerAgentId
                );
                $publishedRecordId = $created['id'];
            }

            // Link to Individual Agent Performance History if contributed by an AI Agent!
            if (!empty($contribution['contributor_agent_id'])) {
                $agent = $this->db->table('ai_agent_registry')->where('id', (int)$contribution['contributor_agent_id'])->get()->getRowArray();
                if ($agent) {
                    $perfInsert = [
                        'agent_id'           => (int)$agent['id'],
                        'agent_uuid'         => $agent['agent_uuid'],
                        'category'           => 'recognition',
                        'title'              => "Knowledge Contribution Published: {$contribution['title']}",
                        'description'        => "Agent authored and published organizational knowledge article {$contribution['contribution_code']} with peer review acceptance.",
                        'evidence_ref'       => $contribution['contribution_code'],
                        'evidence_status'    => 'verified',
                        'evaluator_identity' => $reviewerUserId ? "User #{$reviewerUserId}" : ($reviewerAgentId ? "Agent #{$reviewerAgentId}" : 'Executive Reviewer'),
                        'created_at'         => $now,
                    ];
                    $this->db->table('ai_agent_performance_history')->insert($perfInsert);
                    $perfHistoryId = (int)$this->db->insertID();
                }
            }
        }

        $this->db->table('knowledge_contributions')->where('id', $id)->update([
            'status'                 => $decision,
            'reviewer_user_id'       => $reviewerUserId,
            'reviewer_agent_id'      => $reviewerAgentId,
            'review_feedback'        => $feedback,
            'reviewed_at'            => $now,
            'published_record_id'    => $publishedRecordId,
            'performance_history_id' => $perfHistoryId,
            'updated_at'             => $now,
        ]);

        return $this->getContribution($id);
    }
}
