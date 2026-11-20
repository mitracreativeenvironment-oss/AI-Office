<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class WallbookHandoverService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function listHandovers(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $builder = $this->db->table('wallbook_handovers h')
            ->select('h.*, 
                      out_ag.name as outgoing_agent_name, out_ag.agent_code as outgoing_agent_code,
                      in_ag.name as incoming_agent_name, in_ag.agent_code as incoming_agent_code,
                      u.name as unit_name, s.name as shift_name, s.shift_code')
            ->join('ai_agent_registry out_ag', 'out_ag.id = h.outgoing_agent_id')
            ->join('ai_agent_registry in_ag', 'in_ag.id = h.incoming_agent_id')
            ->join('organization_units u', 'u.id = h.unit_id')
            ->join('workforce_shifts s', 's.id = h.shift_id', 'left');

        if (!empty($filters['status'])) {
            $builder->where('h.status', $filters['status']);
        }
        if (!empty($filters['unit_id'])) {
            $builder->where('h.unit_id', (int)$filters['unit_id']);
        }
        if (!empty($filters['agent_id'])) {
            $builder->groupStart()
                ->where('h.outgoing_agent_id', (int)$filters['agent_id'])
                ->orWhere('h.incoming_agent_id', (int)$filters['agent_id'])
                ->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $builder->orderBy('h.id', 'DESC');
        $builder->limit($limit, $offset);
        $items = $builder->get()->getResultArray();

        foreach ($items as &$item) {
            $item['id'] = (int)$item['id'];
            $item['items_count'] = $this->db->table('wallbook_handover_items')
                ->where('handover_id', $item['id'])
                ->countAllResults();
        }

        return ['items' => $items, 'total' => $total];
    }

    public function getHandover(int $id): ?array
    {
        $handover = $this->db->table('wallbook_handovers h')
            ->select('h.*, 
                      out_ag.name as outgoing_agent_name, out_ag.agent_code as outgoing_agent_code,
                      in_ag.name as incoming_agent_name, in_ag.agent_code as incoming_agent_code,
                      u.name as unit_name, s.name as shift_name, s.shift_code,
                      p.proposal_code')
            ->join('ai_agent_registry out_ag', 'out_ag.id = h.outgoing_agent_id')
            ->join('ai_agent_registry in_ag', 'in_ag.id = h.incoming_agent_id')
            ->join('organization_units u', 'u.id = h.unit_id')
            ->join('workforce_shifts s', 's.id = h.shift_id', 'left')
            ->join('workforce_replacement_proposals p', 'p.id = h.replacement_proposal_id', 'left')
            ->where('h.id', $id)
            ->get()
            ->getRowArray();

        if (!$handover) return null;

        $handover['id'] = (int)$handover['id'];

        // Fetch structured items
        $handover['items'] = $this->db->table('wallbook_handover_items')
            ->where('handover_id', $id)
            ->orderBy('section_category', 'ASC')
            ->orderBy('item_order', 'ASC')
            ->get()
            ->getResultArray();

        // Fetch acknowledgements & clarifications
        $handover['acknowledgements'] = $this->db->table('wallbook_acknowledgements a')
            ->select('a.*, ag.name as agent_name, ag.agent_code')
            ->join('ai_agent_registry ag', 'ag.id = a.agent_id')
            ->where('a.handover_id', $id)
            ->orderBy('a.id', 'ASC')
            ->get()
            ->getResultArray();

        return $handover;
    }

    public function createHandover(array $data, ?int $actorUserId = null): array
    {
        $outgoingId = (int)($data['outgoing_agent_id'] ?? 0);
        $incomingId = (int)($data['incoming_agent_id'] ?? 0);
        $unitId = (int)($data['unit_id'] ?? 1);

        if (!$outgoingId || !$incomingId) {
            throw new \InvalidArgumentException('Outgoing and incoming agent IDs are required.', 422);
        }

        $code = trim($data['handover_code'] ?? 'WB-' . date('Y') . '-' . rand(100, 999));
        $now = date('Y-m-d H:i:s');
        $deadline = $data['deadline_at'] ?? date('Y-m-d H:i:s', strtotime('+24 hours'));

        $insert = [
            'handover_code'           => $code,
            'title'                   => trim($data['title'] ?? 'Operational Duty Handover'),
            'outgoing_agent_id'       => $outgoingId,
            'incoming_agent_id'       => $incomingId,
            'unit_id'                 => $unitId,
            'duty_assignment_id'      => !empty($data['duty_assignment_id']) ? (int)$data['duty_assignment_id'] : null,
            'replacement_proposal_id' => !empty($data['replacement_proposal_id']) ? (int)$data['replacement_proposal_id'] : null,
            'shift_id'                => !empty($data['shift_id']) ? (int)$data['shift_id'] : null,
            'deadline_at'             => $deadline,
            'status'                  => 'draft',
            'submitted_at'            => null,
            'acknowledged_at'         => null,
            'accepted_at'             => null,
            'escalated_at'            => null,
            'escalation_reason'       => null,
            'created_at'              => $now,
            'updated_at'              => $now,
        ];

        $this->db->table('wallbook_handovers')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();

        // If items are provided inline, add them
        if (!empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $idx => $item) {
                $this->addHandoverItem($insert['id'], array_merge($item, ['item_order' => $idx + 1]));
            }
        }

        // Audit
        $this->db->table('workforce_scheduling_audit')->insert([
            'event_type'     => 'handover_created',
            'target_type'    => 'wallbook',
            'target_id'      => $insert['id'],
            'agent_id'       => $outgoingId,
            'actor_user_id'  => $actorUserId,
            'actor_name'     => $actorUserId ? "User #{$actorUserId}" : 'Agent Outgoing Process',
            'action_details' => "Draft Wallbook handover {$code} initiated between Outgoing Agent #{$outgoingId} and Incoming Agent #{$incomingId}.",
            'created_at'     => $now,
        ]);

        return $this->getHandover($insert['id']);
    }

    public function addHandoverItem(int $handoverId, array $data): array
    {
        $now = date('Y-m-d H:i:s');
        $insert = [
            'handover_id'      => $handoverId,
            'section_category' => $data['section_category'] ?? 'context_instructions',
            'item_order'       => (int)($data['item_order'] ?? 1),
            'title'            => trim($data['title'] ?? 'Operational Note'),
            'content'          => trim($data['content'] ?? ''),
            'item_status'      => $data['item_status'] ?? 'transferred',
            'reference_type'   => $data['reference_type'] ?? null,
            'reference_id'     => $data['reference_id'] ?? null,
            'created_at'       => $now,
            'updated_at'       => $now,
        ];

        $this->db->table('wallbook_handover_items')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();
        return $insert;
    }

    /**
     * Submit Wallbook handover:
     * Validates that required structured sections exist before allowing submission!
     */
    public function submitHandover(int $id, ?int $actorUserId = null): array
    {
        $handover = $this->getHandover($id);
        if (!$handover) {
            throw new \InvalidArgumentException("Handover #{$id} not found.", 404);
        }

        // Validate mandatory sections
        $existingSections = array_column($handover['items'], 'section_category');

        $hasResponsibilities = in_array('responsibilities', $existingSections);
        $hasTasks = in_array('active_tasks', $existingSections) || in_array('pending_work', $existingSections);
        $hasRisks = in_array('operational_risks', $existingSections) || in_array('known_issues', $existingSections);
        $hasInstructions = in_array('context_instructions', $existingSections);

        if (!$hasResponsibilities || !$hasTasks || !$hasRisks || !$hasInstructions) {
            throw new \RuntimeException(
                "Handover submission rejected: Missing mandatory sections. " .
                "Required: responsibilities (" . ($hasResponsibilities ? 'OK' : 'MISSING') . "), " .
                "tasks/pending_work (" . ($hasTasks ? 'OK' : 'MISSING') . "), " .
                "risks/issues (" . ($hasRisks ? 'OK' : 'MISSING') . "), " .
                "context_instructions (" . ($hasInstructions ? 'OK' : 'MISSING') . ").",
                422
            );
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('wallbook_handovers')->where('id', $id)->update([
            'status'       => 'awaiting_acknowledgement',
            'submitted_at' => $now,
            'updated_at'   => $now,
        ]);

        // Audit
        $this->db->table('workforce_scheduling_audit')->insert([
            'event_type'     => 'handover_submitted',
            'target_type'    => 'wallbook',
            'target_id'      => $id,
            'agent_id'       => $handover['outgoing_agent_id'],
            'actor_user_id'  => $actorUserId,
            'actor_name'     => $actorUserId ? "User #{$actorUserId}" : $handover['outgoing_agent_name'],
            'action_details' => "Wallbook handover {$handover['handover_code']} submitted for acknowledgement.",
            'created_at'     => $now,
        ]);

        return $this->getHandover($id);
    }

    public function acknowledgeHandover(int $id, int $incomingAgentId, ?string $comments = null): array
    {
        $handover = $this->getHandover($id);
        if (!$handover) {
            throw new \InvalidArgumentException("Handover #{$id} not found.", 404);
        }

        $now = date('Y-m-d H:i:s');

        // Log acknowledgement
        $this->db->table('wallbook_acknowledgements')->insert([
            'handover_id'             => $id,
            'agent_id'                => $incomingAgentId,
            'action'                  => 'acknowledge',
            'comments'                => $comments ?? 'Handover context and responsibilities formally acknowledged.',
            'clarification_questions' => null,
            'created_at'              => $now,
        ]);

        $this->db->table('wallbook_handovers')->where('id', $id)->update([
            'status'          => 'acknowledged',
            'acknowledged_at' => $now,
            'updated_at'      => $now,
        ]);

        // Audit
        $this->db->table('workforce_scheduling_audit')->insert([
            'event_type'     => 'handover_acknowledged',
            'target_type'    => 'wallbook',
            'target_id'      => $id,
            'agent_id'       => $incomingAgentId,
            'actor_name'     => $handover['incoming_agent_name'],
            'action_details' => "Handover {$handover['handover_code']} acknowledged by incoming Agent #{$incomingAgentId}.",
            'created_at'     => $now,
        ]);

        return $this->getHandover($id);
    }

    public function requestClarification(int $id, int $incomingAgentId, string $questions): array
    {
        $handover = $this->getHandover($id);
        if (!$handover) {
            throw new \InvalidArgumentException("Handover #{$id} not found.", 404);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('wallbook_acknowledgements')->insert([
            'handover_id'             => $id,
            'agent_id'                => $incomingAgentId,
            'action'                  => 'request_clarification',
            'comments'                => 'Clarification requested by incoming agent.',
            'clarification_questions' => $questions,
            'created_at'              => $now,
        ]);

        $this->db->table('wallbook_handovers')->where('id', $id)->update([
            'status'     => 'clarification_required',
            'updated_at' => $now,
        ]);

        // Audit
        $this->db->table('workforce_scheduling_audit')->insert([
            'event_type'     => 'handover_clarification_requested',
            'target_type'    => 'wallbook',
            'target_id'      => $id,
            'agent_id'       => $incomingAgentId,
            'actor_name'     => $handover['incoming_agent_name'],
            'action_details' => "Clarification requested on handover {$handover['handover_code']}: {$questions}",
            'created_at'     => $now,
        ]);

        return $this->getHandover($id);
    }

    public function acceptHandover(int $id, int $incomingAgentId, ?string $comments = null): array
    {
        $handover = $this->getHandover($id);
        if (!$handover) {
            throw new \InvalidArgumentException("Handover #{$id} not found.", 404);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('wallbook_acknowledgements')->insert([
            'handover_id'             => $id,
            'agent_id'                => $incomingAgentId,
            'action'                  => 'accept',
            'comments'                => $comments ?? 'Handover formally accepted. Incoming agent assumes operational duty.',
            'clarification_questions' => null,
            'created_at'              => $now,
        ]);

        $this->db->table('wallbook_handovers')->where('id', $id)->update([
            'status'      => 'accepted',
            'accepted_at' => $now,
            'updated_at'  => $now,
        ]);

        // Audit
        $this->db->table('workforce_scheduling_audit')->insert([
            'event_type'     => 'handover_accepted',
            'target_type'    => 'wallbook',
            'target_id'      => $id,
            'agent_id'       => $incomingAgentId,
            'actor_name'     => $handover['incoming_agent_name'],
            'action_details' => "Handover {$handover['handover_code']} accepted! Incoming Agent assumes active duty.",
            'created_at'     => $now,
        ]);

        return $this->getHandover($id);
    }

    /**
     * Scan and escalate overdue handovers (deadline exceeded without acceptance).
     */
    public function escalateOverdueHandovers(): array
    {
        $now = date('Y-m-d H:i:s');
        $overdue = $this->db->table('wallbook_handovers')
            ->where('deadline_at <', $now)
            ->whereIn('status', ['submitted', 'awaiting_acknowledgement', 'clarification_required'])
            ->get()
            ->getResultArray();

        $escalated = [];
        foreach ($overdue as $h) {
            $this->db->table('wallbook_handovers')->where('id', $h['id'])->update([
                'status'            => 'escalated',
                'escalated_at'      => $now,
                'escalation_reason' => "Automated escalation: Handover #{$h['handover_code']} exceeded deadline ({$h['deadline_at']}) without formal acknowledgement.",
                'updated_at'        => $now,
            ]);

            // Audit
            $this->db->table('workforce_scheduling_audit')->insert([
                'event_type'     => 'handover_escalated',
                'target_type'    => 'wallbook',
                'target_id'      => $h['id'],
                'agent_id'       => $h['incoming_agent_id'],
                'actor_name'     => 'Automated Operations Monitor',
                'action_details' => "Overdue handover {$h['handover_code']} escalated to supervisor.",
                'created_at'     => $now,
            ]);

            $escalated[] = $h['handover_code'];
        }

        return [
            'escalated_count' => count($escalated),
            'escalated_codes' => $escalated,
        ];
    }
}
