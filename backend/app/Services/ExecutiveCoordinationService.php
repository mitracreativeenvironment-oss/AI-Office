<?php

namespace App\Services;

class ExecutiveCoordinationService
{
    protected $db;
    protected $accessService;
    protected $notificationService;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->accessService = new CollaborationAccessService();
        $this->notificationService = new CollaborationNotificationService();
    }

    /**
     * Get Executive Overview dashboard statistics.
     */
    public function getExecutiveOverview(): array
    {
        $totalDirectives = $this->db->table('owner_directions')->countAllResults();
        $activeDirectives = $this->db->table('owner_directions')->whereIn('status', ['issued', 'acknowledged', 'in_progress'])->countAllResults();
        
        $totalDecisions = $this->db->table('executive_decisions')->countAllResults();
        $ratifiedDecisions = $this->db->table('executive_decisions')->where('status', 'ratified')->countAllResults();

        $pendingAcks = $this->db->table('owner_directions')
            ->where('requires_acknowledgment', 1)
            ->where('acknowledged_at IS NULL')
            ->countAllResults();

        $activeMeetings = $this->db->table('collaboration_meetings')
            ->whereIn('status', ['scheduled', 'confirmed', 'in_progress'])
            ->where('category', 'executive_coordination')
            ->countAllResults();

        // Recent 5 directives
        $recentDirectives = $this->db->table('owner_directions')
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        // Recent 5 executive decisions
        $recentDecisions = $this->db->table('executive_decisions')
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();

        // DoA summary profiles
        $doaProfiles = $this->db->table('authority_profiles')
            ->where('is_active', 1)
            ->get()
            ->getResultArray();

        return [
            'metrics' => [
                'total_directives'       => $totalDirectives,
                'active_directives'      => $activeDirectives,
                'total_decisions'        => $totalDecisions,
                'ratified_decisions'     => $ratifiedDecisions,
                'pending_acknowledgments'=> $pendingAcks,
                'active_board_meetings'  => $activeMeetings,
            ],
            'recent_directives' => $recentDirectives,
            'recent_decisions'  => $recentDecisions,
            'doa_profiles'      => $doaProfiles,
        ];
    }

    /**
     * List owner directives with acknowledgement join.
     */
    public function listDirectives(array $filters = []): array
    {
        $builder = $this->db->table('owner_directions od')
            ->select('od.*, cda.id AS ack_id, cda.status AS ack_status, cda.notes AS ack_notes, cda.acknowledged_at AS ack_time, cda.acknowledging_agent_id, cda.acknowledging_user_id')
            ->join('collaboration_directive_acknowledgements cda', 'cda.direction_id = od.id', 'left');

        if (!empty($filters['direction_type'])) {
            $builder->where('od.direction_type', $filters['direction_type']);
        }

        if (!empty($filters['status'])) {
            $builder->where('od.status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('od.direction_code', $search)
                ->orLike('od.subject', $search)
                ->orLike('od.description', $search)
                ->groupEnd();
        }

        $limit = isset($filters['limit']) ? min((int)$filters['limit'], 100) : 50;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;

        return $builder->orderBy('od.created_at', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    /**
     * Get single directive.
     */
    public function getDirective(int $id): ?array
    {
        $directive = $this->db->table('owner_directions od')
            ->select('od.*, cda.id AS ack_id, cda.status AS ack_status, cda.notes AS ack_notes, cda.acknowledged_at AS ack_time, cda.acknowledging_agent_id, cda.acknowledging_user_id')
            ->join('collaboration_directive_acknowledgements cda', 'cda.direction_id = od.id', 'left')
            ->where('od.id', $id)
            ->get()
            ->getRowArray();

        if (!$directive) {
            return null;
        }

        // Fetch acknowledging agent / user details
        if (!empty($directive['acknowledging_agent_id'])) {
            $agent = $this->db->table('ai_agent_registry')->where('id', $directive['acknowledging_agent_id'])->get()->getRowArray();
            $directive['acknowledging_agent'] = $agent ? ['id' => $agent['id'], 'name' => $agent['name'], 'agent_code' => $agent['agent_code']] : null;
        }

        return $directive;
    }

    /**
     * Create a new strategic direction / instruction.
     */
    public function createDirective(array $data, int $userId, string $userName): array
    {
        $now = date('Y-m-d H:i:s');
        $code = 'DIR-OWNER-' . date('Y') . '-' . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $record = [
            'direction_code'          => $data['direction_code'] ?? $code,
            'direction_type'          => $data['direction_type'] ?? 'strategic_instruction',
            'subject'                 => trim($data['subject']),
            'description'             => trim($data['description']),
            'target_body_id'          => !empty($data['target_body_id']) ? (int)$data['target_body_id'] : null,
            'target_unit_id'          => !empty($data['target_unit_id']) ? (int)$data['target_unit_id'] : null,
            'issued_by_id'            => $userId,
            'issued_by_name'          => $userName,
            'requires_acknowledgment' => isset($data['requires_acknowledgment']) ? (int)$data['requires_acknowledgment'] : 1,
            'acknowledged_at'         => null,
            'acknowledged_by'         => null,
            'due_date'                => !empty($data['due_date']) ? $data['due_date'] : null,
            'status'                  => 'issued',
            'created_at'              => $now,
            'updated_at'              => $now,
        ];

        $this->db->table('owner_directions')->insert($record);
        $record['id'] = (int)$this->db->insertID();

        // Log audit event
        $this->db->table('collaboration_audit_events')->insert([
            'event_type'    => 'directive_issued',
            'actor_type'    => 'user',
            'actor_id'      => $userId,
            'actor_name'    => $userName,
            'entity_type'   => 'owner_direction',
            'entity_id'     => (string)$record['id'],
            'metadata_json' => json_encode(['direction_code' => $record['direction_code'], 'type' => $record['direction_type']]),
            'ip_address'    => service('request')->getIPAddress(),
            'created_at'    => $now,
        ]);

        return $record;
    }

    /**
     * Acknowledge or report execution on an owner directive.
     */
    public function acknowledgeDirective(int $directionId, string $actorType, int $actorId, string $status, ?string $notes = null): array
    {
        $direction = $this->db->table('owner_directions')->where('id', $directionId)->get()->getRowArray();
        if (!$direction) {
            throw new \RuntimeException("Direction not found with ID: {$directionId}");
        }

        $now = date('Y-m-d H:i:s');
        $ack = $this->db->table('collaboration_directive_acknowledgements')->where('direction_id', $directionId)->get()->getRowArray();

        $data = [
            'direction_id'           => $directionId,
            'acknowledging_agent_id' => $actorType === 'agent' ? $actorId : null,
            'acknowledging_user_id'  => $actorType === 'human' ? $actorId : null,
            'status'                 => $status,
            'notes'                  => $notes,
            'acknowledged_at'        => $now,
        ];

        if ($ack) {
            $this->db->table('collaboration_directive_acknowledgements')->where('id', $ack['id'])->update($data);
            $ackId = (int)$ack['id'];
        } else {
            $this->db->table('collaboration_directive_acknowledgements')->insert($data);
            $ackId = (int)$this->db->insertID();
        }

        // Update direction status
        $directionStatus = ($status === 'executed') ? 'completed' : (($status === 'in_execution') ? 'in_progress' : 'acknowledged');
        $this->db->table('owner_directions')->where('id', $directionId)->update([
            'status'          => $directionStatus,
            'acknowledged_at' => $now,
            'acknowledged_by' => $actorType === 'agent' ? "Agent #{$actorId}" : "User #{$actorId}",
            'updated_at'      => $now,
        ]);

        // Audit log
        $this->db->table('collaboration_audit_events')->insert([
            'event_type'    => 'directive_acknowledged',
            'actor_type'    => ($actorType === 'human' || $actorType === 'user') ? 'user' : 'agent',
            'actor_id'      => $actorId,
            'actor_name'    => $actorType === 'agent' ? "Agent #{$actorId}" : "User #{$actorId}",
            'entity_type'   => 'owner_direction',
            'entity_id'     => (string)$directionId,
            'metadata_json' => json_encode(['ack_status' => $status, 'notes' => $notes]),
            'ip_address'    => service('request')->getIPAddress(),
            'created_at'    => $now,
        ]);

        return array_merge($data, ['id' => $ackId]);
    }

    /**
     * List executive decisions.
     */
    public function listDecisions(array $filters = []): array
    {
        $builder = $this->db->table('executive_decisions ed')
            ->select('ed.*, gb.name AS governance_body_name, od.direction_code, od.subject AS direction_subject')
            ->join('governance_bodies gb', 'gb.id = ed.governance_body_id', 'left')
            ->join('owner_directions od', 'od.id = ed.direction_id', 'left');

        if (!empty($filters['status'])) {
            $builder->where('ed.status', $filters['status']);
        }

        if (!empty($filters['decision_level'])) {
            $builder->where('ed.decision_level', $filters['decision_level']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('ed.decision_code', $search)
                ->orLike('ed.title', $search)
                ->orLike('ed.summary', $search)
                ->groupEnd();
        }

        $limit = isset($filters['limit']) ? min((int)$filters['limit'], 100) : 50;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;

        return $builder->orderBy('ed.created_at', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    /**
     * Propose an Executive Decision.
     */
    public function createDecisionProposal(array $data, string $actorType, int $actorId, string $actorName): array
    {
        $now = date('Y-m-d H:i:s');
        $code = 'DEC-BOARD-' . date('Y') . '-' . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Fetch board governance body
        $govBody = $this->db->table('governance_bodies')->where('code', 'GOV-BOARD')->get()->getRowArray();
        $govBodyId = $govBody ? (int)$govBody['id'] : (!empty($data['governance_body_id']) ? (int)$data['governance_body_id'] : 1);

        $record = [
            'decision_code'      => $data['decision_code'] ?? $code,
            'governance_body_id' => $govBodyId,
            'direction_id'       => !empty($data['direction_id']) ? (int)$data['direction_id'] : null,
            'title'              => trim($data['title']),
            'summary'            => trim($data['summary'] ?? $data['title']),
            'decision_category'  => $data['decision_category'] ?? 'operational',
            'decision_level'     => $data['decision_level'] ?? 'B',
            'decided_by'         => $actorName,
            'effective_date'     => $data['effective_date'] ?? date('Y-m-d'),
            'status'             => $data['status'] ?? 'ratified',
            'notes'              => $data['notes'] ?? null,
            'created_at'         => $now,
            'updated_at'         => $now,
        ];

        $this->db->table('executive_decisions')->insert($record);
        $record['id'] = (int)$this->db->insertID();

        // Audit log
        $this->db->table('collaboration_audit_events')->insert([
            'event_type'    => 'executive_decision_recorded',
            'actor_type'    => ($actorType === 'human' || $actorType === 'user') ? 'user' : 'agent',
            'actor_id'      => $actorId,
            'actor_name'    => $actorName,
            'entity_type'   => 'executive_decision',
            'entity_id'     => (string)$record['id'],
            'metadata_json' => json_encode(['decision_code' => $record['decision_code'], 'level' => $record['decision_level']]),
            'ip_address'    => service('request')->getIPAddress(),
            'created_at'    => $now,
        ]);

        return $record;
    }
}
