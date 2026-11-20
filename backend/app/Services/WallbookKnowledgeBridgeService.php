<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class WallbookKnowledgeBridgeService
{
    protected BaseConnection $db;
    protected KnowledgeContributionService $contributionService;
    protected WallbookHandoverService $wallbookService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->contributionService = new KnowledgeContributionService();
        $this->wallbookService = new WallbookHandoverService();
    }

    /**
     * Get or initialize checklist items for a Wallbook handover
     */
    public function listHandoverChecklists(int $handoverId): array
    {
        $items = $this->db->table('wallbook_handover_checklists')
            ->where('handover_id', $handoverId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($items)) {
            $this->initializeDefaultChecklist($handoverId);
            $items = $this->db->table('wallbook_handover_checklists')
                ->where('handover_id', $handoverId)
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();
        }

        foreach ($items as &$item) {
            $item['id'] = (int)$item['id'];
            $item['handover_id'] = (int)$item['handover_id'];
            $item['is_mandatory'] = (bool)$item['is_mandatory'];
            $item['is_satisfied'] = (bool)$item['is_satisfied'];
        }

        return $items;
    }

    /**
     * Update checklist satisfaction state
     */
    public function updateChecklistItem(int $checklistId, bool $isSatisfied, ?string $notes = null): array
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('wallbook_handover_checklists')->where('id', $checklistId)->update([
            'is_satisfied' => $isSatisfied ? 1 : 0,
            'satisfied_at' => $isSatisfied ? $now : null,
            'notes'        => $notes,
        ]);

        return $this->db->table('wallbook_handover_checklists')->where('id', $checklistId)->get()->getRowArray();
    }

    /**
     * Bridge: Promote an operational insight or resolved blocker from Wallbook into a Knowledge Contribution
     */
    public function promoteHandoverItemToKnowledge(
        int $handoverId,
        int $handoverItemId,
        array $proposalData,
        ?int $actorUserId = null,
        ?int $actorAgentId = null
    ): array {
        $handover = $this->wallbookService->getHandover($handoverId);
        if (!$handover) {
            throw new \InvalidArgumentException("Handover #{$handoverId} not found.", 404);
        }

        $item = $this->db->table('wallbook_handover_items')
            ->where('id', $handoverItemId)
            ->where('handover_id', $handoverId)
            ->get()
            ->getRowArray();

        if (!$item) {
            throw new \InvalidArgumentException("Handover item #{$handoverItemId} not found in handover #{$handoverId}.", 404);
        }

        $contributorAgentId = $actorAgentId ?? (int)$handover['outgoing_agent_id'];
        $title = $proposalData['title'] ?? "Lesson Learned from {$handover['handover_code']}: {$item['title']}";
        $summary = $proposalData['summary'] ?? "Distilled operational lesson from Wallbook handover item {$item['section_category']}.";
        $content = $proposalData['content'] ?? "## Operational Context\nHandover: {$handover['handover_code']} ({$handover['title']})\nSection: {$item['section_category']}\n\n## Insight / Procedure\n{$item['content']}";

        $payload = [
            'contributor_agent_id'    => $contributorAgentId,
            'contributor_user_id'     => $actorUserId,
            'proposed_category_id'    => $proposalData['proposed_category_id'] ?? 4, // Operational Troubleshooting
            'proposed_classification' => $proposalData['proposed_classification'] ?? 'internal',
            'title'                   => $title,
            'summary'                 => $summary,
            'content'                 => $content,
            'evidence_references'     => "wallbook:{$handoverId}:item:{$handoverItemId}",
            'source_context'          => "wallbook_handover:{$handover['handover_code']}",
        ];

        return $this->contributionService->submitContribution($payload, $actorAgentId, $actorUserId);
    }

    protected function initializeDefaultChecklist(int $handoverId): void
    {
        $now = date('Y-m-d H:i:s');
        $defaults = [
            [
                'handover_id'        => $handoverId,
                'checklist_code'     => 'CHK-STATUS-UPDATED',
                'item_label'         => 'Current task progress & completed items formally documented in Wallbook',
                'is_mandatory'       => 1,
                'is_satisfied'       => 0,
                'notes'              => 'Requires review against active task list.',
                'created_at'         => $now,
            ],
            [
                'handover_id'        => $handoverId,
                'checklist_code'     => 'CHK-BLOCKERS-RISKS',
                'item_label'         => 'Operational risks, known impediments, and technical dependencies logged',
                'is_mandatory'       => 1,
                'is_satisfied'       => 0,
                'notes'              => 'Mandatory risks documentation.',
                'created_at'         => $now,
            ],
            [
                'handover_id'        => $handoverId,
                'checklist_code'     => 'CHK-CREDENTIALS-SAFE',
                'item_label'         => 'No raw credentials, secrets, or API keys written in plaintext handover',
                'is_mandatory'       => 1,
                'is_satisfied'       => 1,
                'notes'              => 'Compliant with secret governance.',
                'created_at'         => $now,
            ],
            [
                'handover_id'        => $handoverId,
                'checklist_code'     => 'CHK-RECIPIENT-ACK',
                'item_label'         => 'Incoming replacement agent identified and briefed on active responsibilities',
                'is_mandatory'       => 1,
                'is_satisfied'       => 0,
                'notes'              => 'Awaiting incoming agent acknowledgement.',
                'created_at'         => $now,
            ],
        ];

        foreach ($defaults as $d) {
            $this->db->table('wallbook_handover_checklists')->insert($d);
        }
    }
}
