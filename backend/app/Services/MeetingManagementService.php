<?php

namespace App\Services;

class MeetingManagementService
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
     * List meetings with filtering and room joins.
     */
    public function listMeetings(array $filters = [], ?int $userId = null, ?int $agentId = null, ?array $userContext = null): array
    {
        $builder = $this->db->table('collaboration_meetings m')
            ->select('m.*, r.room_code, r.name AS room_name, r.category AS room_category, COUNT(DISTINCT p.id) AS participant_count, COUNT(DISTINCT a.id) AS agenda_count')
            ->join('hq_rooms r', 'r.id = m.room_id', 'left')
            ->join('collaboration_meeting_participants p', 'p.meeting_id = m.id', 'left')
            ->join('collaboration_meeting_agendas a', 'a.meeting_id = m.id', 'left')
            ->groupBy('m.id');

        $allowedClassifications = $this->accessService->getAllowedClassifications($userContext);
        $builder->whereIn('m.classification', $allowedClassifications);

        if (!empty($filters['status'])) {
            $builder->where('m.status', $filters['status']);
        }

        if (!empty($filters['category'])) {
            $builder->where('m.category', $filters['category']);
        }

        if (!empty($filters['room_id'])) {
            $builder->where('m.room_id', (int)$filters['room_id']);
        }

        if (!empty($filters['date'])) {
            $builder->like('m.scheduled_start_time', $filters['date'], 'after');
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('m.meeting_code', $search)
                ->orLike('m.title', $search)
                ->orLike('m.description', $search)
                ->groupEnd();
        }

        $limit = isset($filters['limit']) ? min((int)$filters['limit'], 100) : 50;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;

        return $builder->orderBy('m.scheduled_start_time', 'ASC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    /**
     * Get meeting with participants, agendas, minutes, and action items.
     */
    public function getMeeting(int $id, ?array $userContext = null): ?array
    {
        $meeting = $this->db->table('collaboration_meetings m')
            ->select('m.*, r.room_code, r.name AS room_name, r.category AS room_category')
            ->join('hq_rooms r', 'r.id = m.room_id', 'left')
            ->where('m.id', $id)
            ->get()
            ->getRowArray();

        if (!$meeting) {
            return null;
        }

        if (!$this->accessService->canAccessClassification($meeting['classification'], $userContext)) {
            return null;
        }

        // Participants
        $participants = $this->db->table('collaboration_meeting_participants p')
            ->select('p.*, u.full_name AS user_name, u.email AS user_email, a.name AS agent_name, a.agent_code')
            ->join('users u', 'u.id = p.user_id', 'left')
            ->join('ai_agent_registry a', 'a.id = p.agent_id', 'left')
            ->where('p.meeting_id', $id)
            ->orderBy('p.role', 'ASC')
            ->orderBy('p.id', 'ASC')
            ->get()
            ->getResultArray();

        // Agendas
        $agendas = $this->db->table('collaboration_meeting_agendas')
            ->where('meeting_id', $id)
            ->orderBy('item_order', 'ASC')
            ->get()
            ->getResultArray();

        // Minutes of Meeting (MoM)
        $minutes = $this->db->table('collaboration_meeting_minutes')
            ->where('meeting_id', $id)
            ->get()
            ->getRowArray();

        // Action Items
        $actionItems = $this->db->table('collaboration_meeting_action_items ai')
            ->select('ai.*, u.full_name AS assignee_user_name, a.name AS assignee_agent_name, a.agent_code AS assignee_agent_code, t.title AS linked_task_title, t.status AS linked_task_status')
            ->join('users u', 'u.id = ai.assignee_user_id', 'left')
            ->join('ai_agent_registry a', 'a.id = ai.assignee_agent_id', 'left')
            ->join('workforce_tasks t', 't.id = ai.task_id', 'left')
            ->where('ai.meeting_id', $id)
            ->orderBy('ai.id', 'ASC')
            ->get()
            ->getResultArray();

        $meeting['participants'] = $participants;
        $meeting['agendas'] = $agendas;
        $meeting['minutes'] = $minutes;
        $meeting['action_items'] = $actionItems;

        return $meeting;
    }

    /**
     * Create a new meeting.
     */
    public function createMeeting(array $data, string $organizerType, int $organizerId, string $organizerName): array
    {
        $now = date('Y-m-d H:i:s');
        $code = 'MTG-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        $record = [
            'meeting_code'         => $data['meeting_code'] ?? $code,
            'title'                => trim($data['title']),
            'category'             => $data['category'] ?? 'executive_coordination',
            'status'               => $data['status'] ?? 'scheduled',
            'classification'       => $data['classification'] ?? 'internal',
            'room_id'              => !empty($data['room_id']) ? (int)$data['room_id'] : null,
            'organizer_user_id'    => $organizerType === 'human' ? $organizerId : null,
            'organizer_agent_id'   => $organizerType === 'agent' ? $organizerId : null,
            'scheduled_start_time' => $data['scheduled_start_time'],
            'scheduled_end_time'   => $data['scheduled_end_time'],
            'actual_start_time'    => $data['actual_start_time'] ?? null,
            'actual_end_time'      => $data['actual_end_time'] ?? null,
            'description'          => $data['description'] ?? null,
            'created_at'           => $now,
            'updated_at'           => $now,
        ];

        $this->db->table('collaboration_meetings')->insert($record);
        $meetingId = (int)$this->db->insertID();
        $record['id'] = $meetingId;

        // Add organizer as participant (chair or participant)
        $this->db->table('collaboration_meeting_participants')->insert([
            'meeting_id'        => $meetingId,
            'participant_type'  => $organizerType,
            'user_id'           => $organizerType === 'human' ? $organizerId : null,
            'agent_id'          => $organizerType === 'agent' ? $organizerId : null,
            'role'              => 'chair',
            'invitation_status' => 'confirmed',
            'attendance_status' => 'unrecorded',
            'notes'             => 'Meeting Organizer',
        ]);

        // Add optional participants list
        if (!empty($data['participants']) && is_array($data['participants'])) {
            foreach ($data['participants'] as $p) {
                $pType = $p['participant_type'] ?? 'human';
                $pUserId = $pType === 'human' ? (int)($p['user_id'] ?? $p['id'] ?? 0) : null;
                $pAgentId = $pType === 'agent' ? (int)($p['agent_id'] ?? $p['id'] ?? 0) : null;

                if ($pUserId === $record['organizer_user_id'] && $pType === 'human') {
                    continue; // Skip organizer already added
                }
                if ($pAgentId === $record['organizer_agent_id'] && $pType === 'agent') {
                    continue; // Skip organizer already added
                }

                $this->db->table('collaboration_meeting_participants')->insert([
                    'meeting_id'        => $meetingId,
                    'participant_type'  => $pType,
                    'user_id'           => $pUserId,
                    'agent_id'          => $pAgentId,
                    'role'              => $p['role'] ?? 'participant',
                    'invitation_status' => 'invited',
                    'attendance_status' => 'unrecorded',
                    'notes'             => $p['notes'] ?? null,
                ]);

                // Send notification
                $this->notificationService->sendNotification(
                    $pType,
                    $pType === 'human' ? $pUserId : $pAgentId,
                    'Undangan Rapat: ' . $record['title'],
                    "Anda diundang ke rapat '{$record['title']}' dijadwalkan pada {$record['scheduled_start_time']}.",
                    'meeting_invite',
                    'high',
                    "/collaboration/meetings/{$meetingId}"
                );
            }
        }

        // Add optional agendas list
        if (!empty($data['agendas']) && is_array($data['agendas'])) {
            $order = 1;
            foreach ($data['agendas'] as $ag) {
                $this->db->table('collaboration_meeting_agendas')->insert([
                    'meeting_id'        => $meetingId,
                    'item_order'        => $ag['item_order'] ?? $order++,
                    'title'             => trim($ag['title']),
                    'description'       => $ag['description'] ?? null,
                    'allocated_minutes' => (int)($ag['allocated_minutes'] ?? 15),
                    'presenter_name'    => $ag['presenter_name'] ?? null,
                    'status'            => 'pending',
                ]);
            }
        }

        // Audit log
        $this->db->table('collaboration_audit_events')->insert([
            'event_type'    => 'meeting_scheduled',
            'actor_type'    => ($organizerType === 'human' || $organizerType === 'user') ? 'user' : 'agent',
            'actor_id'      => $organizerId,
            'actor_name'    => $organizerName,
            'entity_type'   => 'meeting',
            'entity_id'     => (string)$meetingId,
            'metadata_json' => json_encode(['meeting_code' => $record['meeting_code'], 'room_id' => $record['room_id']]),
            'ip_address'    => service('request')->getIPAddress(),
            'created_at'    => $now,
        ]);

        return $record;
    }

    /**
     * Update meeting status.
     */
    public function updateMeetingStatus(int $id, string $newStatus): array
    {
        $existing = $this->db->table('collaboration_meetings')->where('id', $id)->get()->getRowArray();
        if (!$existing) {
            throw new \RuntimeException("Meeting not found with ID {$id}");
        }

        $now = date('Y-m-d H:i:s');
        $updates = [
            'status'     => $newStatus,
            'updated_at' => $now,
        ];

        if ($newStatus === 'in_progress' && empty($existing['actual_start_time'])) {
            $updates['actual_start_time'] = $now;
        } elseif ($newStatus === 'completed' && empty($existing['actual_end_time'])) {
            $updates['actual_end_time'] = $now;
        }

        $this->db->table('collaboration_meetings')->where('id', $id)->update($updates);

        return array_merge($existing, $updates);
    }

    /**
     * Add a participant to meeting.
     */
    public function addParticipant(int $meetingId, array $data): array
    {
        $pType = $data['participant_type'] ?? 'human';
        $userId = $pType === 'human' ? (int)($data['user_id'] ?? 0) : null;
        $agentId = $pType === 'agent' ? (int)($data['agent_id'] ?? 0) : null;

        $record = [
            'meeting_id'        => $meetingId,
            'participant_type'  => $pType,
            'user_id'           => $userId,
            'agent_id'          => $agentId,
            'role'              => $data['role'] ?? 'participant',
            'invitation_status' => $data['invitation_status'] ?? 'invited',
            'attendance_status' => $data['attendance_status'] ?? 'unrecorded',
            'notes'             => $data['notes'] ?? null,
        ];

        $this->db->table('collaboration_meeting_participants')->insert($record);
        $record['id'] = (int)$this->db->insertID();

        return $record;
    }

    /**
     * Update participant attendance.
     */
    public function updateParticipantAttendance(int $meetingId, int $participantId, string $status, ?string $notes = null): bool
    {
        $updates = ['attendance_status' => $status];
        if ($notes !== null) {
            $updates['notes'] = $notes;
        }

        return (bool)$this->db->table('collaboration_meeting_participants')
            ->where('meeting_id', $meetingId)
            ->where('id', $participantId)
            ->update($updates);
    }

    /**
     * Add an agenda item.
     */
    public function addAgendaItem(int $meetingId, array $data): array
    {
        $maxOrder = $this->db->table('collaboration_meeting_agendas')
            ->where('meeting_id', $meetingId)
            ->selectMax('item_order')
            ->get()
            ->getRow();
        $nextOrder = ($maxOrder && $maxOrder->item_order) ? ((int)$maxOrder->item_order + 1) : 1;

        $record = [
            'meeting_id'        => $meetingId,
            'item_order'        => $data['item_order'] ?? $nextOrder,
            'title'             => trim($data['title']),
            'description'       => $data['description'] ?? null,
            'allocated_minutes' => (int)($data['allocated_minutes'] ?? 15),
            'presenter_name'    => $data['presenter_name'] ?? null,
            'status'            => $data['status'] ?? 'pending',
        ];

        $this->db->table('collaboration_meeting_agendas')->insert($record);
        $record['id'] = (int)$this->db->insertID();

        return $record;
    }

    /**
     * Save / update Minutes of Meeting (MoM).
     */
    public function saveMeetingMinutes(int $meetingId, array $data, string $preparerType, int $preparerId): array
    {
        $now = date('Y-m-d H:i:s');
        $existing = $this->db->table('collaboration_meeting_minutes')->where('meeting_id', $meetingId)->get()->getRowArray();

        $record = [
            'meeting_id'           => $meetingId,
            'prepared_by_user_id'  => $preparerType === 'human' ? $preparerId : null,
            'prepared_by_agent_id' => $preparerType === 'agent' ? $preparerId : null,
            'attendance_summary'   => $data['attendance_summary'] ?? null,
            'discussion_notes'     => trim($data['discussion_notes'] ?? ''),
            'formal_decisions'     => $data['formal_decisions'] ?? null,
            'updated_at'           => $now,
        ];

        if ($existing) {
            $this->db->table('collaboration_meeting_minutes')->where('id', $existing['id'])->update($record);
            $record['id'] = (int)$existing['id'];
            $record['is_approved'] = $existing['is_approved'];
            $record['approved_by_user_id'] = $existing['approved_by_user_id'];
        } else {
            $record['is_approved'] = 0;
            $record['approved_by_user_id'] = null;
            $record['approved_at'] = null;
            $record['created_at'] = $now;
            $this->db->table('collaboration_meeting_minutes')->insert($record);
            $record['id'] = (int)$this->db->insertID();
        }

        return $record;
    }

    /**
     * Formally approve Minutes of Meeting (MoM) by Human Owner/Admin.
     */
    public function approveMeetingMinutes(int $meetingId, int $userId): array
    {
        $existing = $this->db->table('collaboration_meeting_minutes')->where('meeting_id', $meetingId)->get()->getRowArray();
        if (!$existing) {
            throw new \RuntimeException("No minutes found to approve for meeting ID {$meetingId}");
        }

        $now = date('Y-m-d H:i:s');
        $updates = [
            'is_approved'         => 1,
            'approved_by_user_id' => $userId,
            'approved_at'         => $now,
            'updated_at'          => $now,
        ];

        $this->db->table('collaboration_meeting_minutes')->where('id', $existing['id'])->update($updates);

        // Update meeting status to minutes_finalized
        $this->db->table('collaboration_meetings')->where('id', $meetingId)->update([
            'status'     => 'minutes_finalized',
            'updated_at' => $now,
        ]);

        return array_merge($existing, $updates);
    }

    /**
     * List action items.
     */
    public function listActionItems(?int $meetingId = null, array $filters = []): array
    {
        $builder = $this->db->table('collaboration_meeting_action_items ai')
            ->select('ai.*, m.meeting_code, m.title AS meeting_title, u.full_name AS assignee_user_name, a.name AS assignee_agent_name, a.agent_code AS assignee_agent_code, t.title AS linked_task_title, t.task_code AS linked_task_code, t.status AS linked_task_status')
            ->join('collaboration_meetings m', 'm.id = ai.meeting_id', 'left')
            ->join('users u', 'u.id = ai.assignee_user_id', 'left')
            ->join('ai_agent_registry a', 'a.id = ai.assignee_agent_id', 'left')
            ->join('workforce_tasks t', 't.id = ai.task_id', 'left');

        if ($meetingId) {
            $builder->where('ai.meeting_id', $meetingId);
        }

        if (!empty($filters['status'])) {
            $builder->where('ai.status', $filters['status']);
        }

        if (!empty($filters['assignee_type'])) {
            $builder->where('ai.assignee_type', $filters['assignee_type']);
        }

        if (!empty($filters['assignee_id'])) {
            if ($filters['assignee_type'] === 'agent') {
                $builder->where('ai.assignee_agent_id', (int)$filters['assignee_id']);
            } else {
                $builder->where('ai.assignee_user_id', (int)$filters['assignee_id']);
            }
        }

        return $builder->orderBy('ai.due_date', 'ASC')->orderBy('ai.id', 'ASC')->get()->getResultArray();
    }

    /**
     * Create meeting action item and optionally bind to workforce_tasks.
     */
    public function createActionItem(int $meetingId, array $data, bool $syncToTask = false): array
    {
        $now = date('Y-m-d H:i:s');
        $assigneeType = $data['assignee_type'] ?? 'agent';
        $assigneeUserId = $assigneeType === 'human' ? (int)($data['assignee_user_id'] ?? $data['assignee_id'] ?? 0) : null;
        $assigneeAgentId = $assigneeType === 'agent' ? (int)($data['assignee_agent_id'] ?? $data['assignee_id'] ?? 0) : null;

        $taskId = !empty($data['task_id']) ? (int)$data['task_id'] : null;

        // If syncToTask requested and no task_id provided, create task in workforce_tasks
        if ($syncToTask && !$taskId) {
            $meeting = $this->db->table('collaboration_meetings')->where('id', $meetingId)->get()->getRowArray();
            $rootUnit = $this->db->table('organization_units')->where('code', 'ORG-MCE')->get()->getRowArray();
            $unitId = $rootUnit ? (int)$rootUnit['id'] : 1;

            $taskCode = 'TSK-MTG-' . date('Y') . '-' . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $taskUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
            $taskRecord = [
                'task_uuid'              => $taskUuid,
                'task_code'              => $taskCode,
                'title'                  => 'Action Item: ' . substr($data['description'], 0, 100),
                'description'            => "Generated from Meeting [{$meeting['meeting_code']} - {$meeting['title']}]:\n\n" . $data['description'],
                'unit_id'                => $unitId,
                'category'               => 'operational',
                'priority'               => 'medium',
                'status'                 => 'assigned',
                'origin_type'            => 'board_decision',
                'accountable_agent_id'   => $assigneeAgentId ?: 1,
                'confidentiality_level'  => 'internal',
                'created_at'             => $now,
                'updated_at'             => $now,
            ];
            $this->db->table('workforce_tasks')->insert($taskRecord);
            $taskId = (int)$this->db->insertID();

            if ($assigneeAgentId) {
                $this->db->table('task_assignments')->insert([
                    'task_id'         => $taskId,
                    'agent_id'        => $assigneeAgentId,
                    'assignment_role' => 'lead_agent',
                    'status'          => 'active',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }
        }

        $record = [
            'meeting_id'        => $meetingId,
            'agenda_item_id'    => !empty($data['agenda_item_id']) ? (int)$data['agenda_item_id'] : null,
            'task_id'           => $taskId,
            'description'       => trim($data['description']),
            'assignee_type'     => $assigneeType,
            'assignee_user_id'  => $assigneeUserId,
            'assignee_agent_id' => $assigneeAgentId,
            'due_date'          => $data['due_date'] ?? null,
            'status'            => $taskId ? 'assigned_to_task' : ($data['status'] ?? 'pending'),
            'created_at'        => $now,
            'updated_at'        => $now,
        ];

        $this->db->table('collaboration_meeting_action_items')->insert($record);
        $record['id'] = (int)$this->db->insertID();

        // Notify assignee
        $recipientId = $assigneeType === 'human' ? $assigneeUserId : $assigneeAgentId;
        if ($recipientId) {
            $this->notificationService->sendNotification(
                $assigneeType,
                $recipientId,
                'Action Item Ditugaskan',
                "Anda ditugaskan pada action item dari rapat ID #{$meetingId}: {$record['description']}",
                'action_item_assigned',
                'high',
                "/collaboration/meetings/{$meetingId}/action-items"
            );
        }

        return $record;
    }

    /**
     * Update action item status.
     */
    public function updateActionItemStatus(int $actionItemId, string $status, ?int $taskId = null): array
    {
        $existing = $this->db->table('collaboration_meeting_action_items')->where('id', $actionItemId)->get()->getRowArray();
        if (!$existing) {
            throw new \RuntimeException("Action item not found with ID {$actionItemId}");
        }

        $now = date('Y-m-d H:i:s');
        $updates = [
            'status'     => $status,
            'updated_at' => $now,
        ];
        if ($taskId !== null) {
            $updates['task_id'] = $taskId;
        }

        $this->db->table('collaboration_meeting_action_items')->where('id', $actionItemId)->update($updates);

        return array_merge($existing, $updates);
    }
}
