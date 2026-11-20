<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use App\Services\CollaborationAccessService;
use App\Services\MessagingService;
use App\Services\AnnouncementService;
use App\Services\ExecutiveCoordinationService;
use App\Services\MeetingManagementService;
use App\Services\CollaborationNotificationService;
use App\Services\CollaborationSearchService;

class CollaborationController extends BaseApiController
{
    protected CollaborationAccessService $accessService;
    protected MessagingService $messagingService;
    protected AnnouncementService $announcementService;
    protected ExecutiveCoordinationService $executiveService;
    protected MeetingManagementService $meetingService;
    protected CollaborationNotificationService $notificationService;
    protected CollaborationSearchService $searchService;

    public function __construct()
    {
        $this->accessService = new CollaborationAccessService();
        $this->messagingService = new MessagingService();
        $this->announcementService = new AnnouncementService();
        $this->executiveService = new ExecutiveCoordinationService();
        $this->meetingService = new MeetingManagementService();
        $this->notificationService = new CollaborationNotificationService();
        $this->searchService = new CollaborationSearchService();
    }

    protected function getUserContext(): array
    {
        $u = $this->request->authenticatedUser ?? null;
        if (!$u) {
            return [
                'id'       => null,
                'username' => 'guest',
                'roles'    => [],
                'unit_id'  => null,
                'agent_id' => null,
                'type'     => 'human',
            ];
        }

        return [
            'id'       => $u->id ?? null,
            'username' => $u->username ?? null,
            'name'     => $u->full_name ?? ($u->username ?? 'User'),
            'roles'    => $u->roles ?? [],
            'unit_id'  => $u->unit_id ?? null,
            'agent_id' => $u->agent_id ?? null,
            'type'     => !empty($u->agent_id) ? 'agent' : 'human',
        ];
    }

    // ==========================================
    // 1. EXECUTIVE COORDINATION
    // ==========================================

    public function executiveOverview()
    {
        try {
            $data = $this->executiveService->getExecutiveOverview();
            return $this->respondSuccess($data, 'Executive coordination overview retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function listDirectives()
    {
        try {
            $filters = [
                'direction_type' => $this->request->getGet('direction_type'),
                'status'         => $this->request->getGet('status'),
                'search'         => $this->request->getGet('search'),
                'limit'          => $this->request->getGet('limit'),
                'offset'         => $this->request->getGet('offset'),
            ];
            $data = $this->executiveService->listDirectives($filters);
            return $this->respondSuccess($data, 'Owner directives retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getDirective($id = null)
    {
        try {
            $directive = $this->executiveService->getDirective((int)$id);
            if (!$directive) {
                return $this->respondError('Directive not found.', ResponseInterface::HTTP_NOT_FOUND);
            }
            return $this->respondSuccess($directive, 'Directive details retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createDirective()
    {
        try {
            $user = $this->getUserContext();
            $roles = $user['roles'] ?? [];
            if (!in_array('owner', $roles) && !in_array('superadmin', $roles)) {
                return $this->respondError('Only the Human Owner / Executive Authority may issue directives.', ResponseInterface::HTTP_FORBIDDEN);
            }

            $raw = $this->request->getJSON(true) ?? $this->request->getPost();
            if (empty($raw['subject']) || empty($raw['description'])) {
                return $this->respondError('Subject and description are required.', ResponseInterface::HTTP_BAD_REQUEST);
            }

            $created = $this->executiveService->createDirective($raw, (int)$user['id'], $user['name']);
            return $this->respondSuccess($created, 'Directive issued successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function acknowledgeDirective($id = null)
    {
        try {
            $user = $this->getUserContext();
            $raw = $this->request->getJSON(true) ?? $this->request->getPost();

            $status = $raw['status'] ?? 'acknowledged';
            $notes = $raw['notes'] ?? null;
            $actorType = $raw['actor_type'] ?? $user['type'];
            $actorId = ($actorType === 'agent') ? (int)($raw['agent_id'] ?? $user['agent_id'] ?? 1) : (int)$user['id'];

            $result = $this->executiveService->acknowledgeDirective((int)$id, $actorType, $actorId, $status, $notes);
            return $this->respondSuccess($result, 'Directive status updated successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function listDecisions()
    {
        try {
            $filters = [
                'status'         => $this->request->getGet('status'),
                'decision_level' => $this->request->getGet('decision_level'),
                'search'         => $this->request->getGet('search'),
                'limit'          => $this->request->getGet('limit'),
                'offset'         => $this->request->getGet('offset'),
            ];
            $data = $this->executiveService->listDecisions($filters);
            return $this->respondSuccess($data, 'Executive decisions retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createDecisionProposal()
    {
        try {
            $user = $this->getUserContext();
            $raw = $this->request->getJSON(true) ?? $this->request->getPost();

            if (empty($raw['title'])) {
                return $this->respondError('Decision title is required.', ResponseInterface::HTTP_BAD_REQUEST);
            }

            $actorType = $user['type'];
            $actorId = ($actorType === 'agent') ? (int)$user['agent_id'] : (int)$user['id'];
            $actorName = $user['name'];

            $created = $this->executiveService->createDecisionProposal($raw, $actorType, $actorId, $actorName);
            return $this->respondSuccess($created, 'Executive decision proposal recorded successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ==========================================
    // 2. CHANNELS
    // ==========================================

    public function listChannels()
    {
        try {
            $user = $this->getUserContext();
            $filters = [
                'domain'     => $this->request->getGet('domain'),
                'is_private' => $this->request->getGet('is_private'),
                'search'     => $this->request->getGet('search'),
            ];
            $data = $this->messagingService->listChannels($filters, $user['id'], $user['agent_id'], $user);
            return $this->respondSuccess($data, 'Channels retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getChannel($id = null)
    {
        try {
            $user = $this->getUserContext();
            $channel = $this->messagingService->getChannel((int)$id, $user['id'], $user['agent_id'], $user);
            if (!$channel) {
                return $this->respondError('Channel not found or access denied.', ResponseInterface::HTTP_NOT_FOUND);
            }
            return $this->respondSuccess($channel, 'Channel retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createChannel()
    {
        try {
            $user = $this->getUserContext();
            $raw = $this->request->getJSON(true) ?? $this->request->getPost();

            if (empty($raw['name'])) {
                return $this->respondError('Channel name is required.', ResponseInterface::HTTP_BAD_REQUEST);
            }

            $created = $this->messagingService->createChannel($raw, (int)$user['id']);
            return $this->respondSuccess($created, 'Channel created successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function joinChannel($id = null)
    {
        try {
            $user = $this->getUserContext();
            $raw = $this->request->getJSON(true) ?? $this->request->getPost();

            $memberType = $raw['member_type'] ?? $user['type'];
            $memberId = ($memberType === 'agent') ? (int)($raw['agent_id'] ?? $user['agent_id'] ?? 1) : (int)$user['id'];
            $role = $raw['role'] ?? 'member';

            $success = $this->messagingService->joinChannel((int)$id, $memberType, $memberId, $role);
            return $this->respondSuccess(['joined' => $success], 'Joined channel successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function leaveChannel($id = null)
    {
        try {
            $user = $this->getUserContext();
            $raw = $this->request->getJSON(true) ?? $this->request->getPost();

            $memberType = $raw['member_type'] ?? $user['type'];
            $memberId = ($memberType === 'agent') ? (int)($raw['agent_id'] ?? $user['agent_id'] ?? 1) : (int)$user['id'];

            $success = $this->messagingService->leaveChannel((int)$id, $memberType, $memberId);
            return $this->respondSuccess(['left' => $success], 'Left channel successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getChannelMessages($id = null)
    {
        try {
            $user = $this->getUserContext();
            $filters = [
                'parent_message_id' => $this->request->getGet('parent_message_id'),
                'limit'             => $this->request->getGet('limit'),
                'offset'            => $this->request->getGet('offset'),
            ];

            $messages = $this->messagingService->getMessages('channel', (int)$id, $filters, $user['id'], $user['agent_id'], $user);
            return $this->respondSuccess($messages, 'Channel messages retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_FORBIDDEN);
        }
    }

    public function sendChannelMessage($id = null)
    {
        try {
            $user = $this->getUserContext();
            $raw = $this->request->getJSON(true) ?? $this->request->getPost();

            if (empty($raw['content'])) {
                return $this->respondError('Message content is required.', ResponseInterface::HTTP_BAD_REQUEST);
            }

            $senderType = $raw['sender_type'] ?? $user['type'];
            $senderId = ($senderType === 'agent') ? (int)($raw['sender_agent_id'] ?? $user['agent_id'] ?? 1) : (int)$user['id'];
            $senderDisplayName = $raw['sender_display_name'] ?? $user['name'];

            $message = $this->messagingService->sendMessage('channel', (int)$id, $raw, $senderType, $senderId, $senderDisplayName, $user);
            return $this->respondSuccess($message, 'Message transmitted successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    // ==========================================
    // 3. DIRECT & GROUP CONVERSATIONS
    // ==========================================

    public function listConversations()
    {
        try {
            $user = $this->getUserContext();
            $data = $this->messagingService->listConversations($user['id'], $user['agent_id'], $user);
            return $this->respondSuccess($data, 'Conversations retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getConversation($id = null)
    {
        try {
            $user = $this->getUserContext();
            $conv = $this->messagingService->getConversation((int)$id, $user['id'], $user['agent_id'], $user);
            if (!$conv) {
                return $this->respondError('Conversation not found or access denied.', ResponseInterface::HTTP_NOT_FOUND);
            }
            return $this->respondSuccess($conv, 'Conversation details retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createConversation()
    {
        try {
            $user = $this->getUserContext();
            $raw = $this->request->getJSON(true) ?? $this->request->getPost();

            $creatorType = $user['type'];
            $creatorId = ($creatorType === 'agent') ? (int)$user['agent_id'] : (int)$user['id'];
            $participants = $raw['participants'] ?? [];

            $created = $this->messagingService->createConversation($raw, $creatorType, $creatorId, $participants);
            return $this->respondSuccess($created, 'Conversation established successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getConversationMessages($id = null)
    {
        try {
            $user = $this->getUserContext();
            $filters = [
                'parent_message_id' => $this->request->getGet('parent_message_id'),
                'limit'             => $this->request->getGet('limit'),
                'offset'            => $this->request->getGet('offset'),
            ];

            $messages = $this->messagingService->getMessages('conversation', (int)$id, $filters, $user['id'], $user['agent_id'], $user);
            return $this->respondSuccess($messages, 'Conversation messages retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_FORBIDDEN);
        }
    }

    public function sendConversationMessage($id = null)
    {
        try {
            $user = $this->getUserContext();
            $raw = $this->request->getJSON(true) ?? $this->request->getPost();

            if (empty($raw['content'])) {
                return $this->respondError('Message content is required.', ResponseInterface::HTTP_BAD_REQUEST);
            }

            $senderType = $raw['sender_type'] ?? $user['type'];
            $senderId = ($senderType === 'agent') ? (int)($raw['sender_agent_id'] ?? $user['agent_id'] ?? 1) : (int)$user['id'];
            $senderDisplayName = $raw['sender_display_name'] ?? $user['name'];

            $message = $this->messagingService->sendMessage('conversation', (int)$id, $raw, $senderType, $senderId, $senderDisplayName, $user);
            return $this->respondSuccess($message, 'Message transmitted successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function pinMessage($id = null)
    {
        try {
            $raw = $this->request->getJSON(true) ?? $this->request->getPost();
            $isPinned = isset($raw['is_pinned']) ? (bool)$raw['is_pinned'] : true;

            $success = $this->messagingService->pinMessage((int)$id, $isPinned);
            return $this->respondSuccess(['pinned' => $success], 'Message pin status updated.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function markMessagesRead()
    {
        try {
            $user = $this->getUserContext();
            $raw = $this->request->getJSON(true) ?? $this->request->getPost();

            $targetType = $raw['target_type'] ?? 'channel';
            $targetId = (int)($raw['target_id'] ?? 0);
            $lastMessageId = (int)($raw['last_message_id'] ?? 0);

            $actorType = $user['type'];
            $actorId = ($actorType === 'agent') ? (int)$user['agent_id'] : (int)$user['id'];

            $success = $this->messagingService->markAsRead($targetType, $targetId, $lastMessageId, $actorType, $actorId);
            return $this->respondSuccess(['read' => $success], 'Messages marked as read.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ==========================================
    // 4. ANNOUNCEMENTS
    // ==========================================

    public function listAnnouncements()
    {
        try {
            $user = $this->getUserContext();
            $filters = [
                'target_scope'     => $this->request->getGet('target_scope'),
                'priority'         => $this->request->getGet('priority'),
                'is_published'     => $this->request->getGet('is_published'),
                'search'           => $this->request->getGet('search'),
                'include_expired'  => $this->request->getGet('include_expired'),
                'limit'            => $this->request->getGet('limit'),
                'offset'           => $this->request->getGet('offset'),
            ];

            $data = $this->announcementService->listAnnouncements($filters, $user);
            return $this->respondSuccess($data, 'Announcements retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getAnnouncement($id = null)
    {
        try {
            $user = $this->getUserContext();
            $ann = $this->announcementService->getAnnouncement((int)$id, $user);
            if (!$ann) {
                return $this->respondError('Announcement not found or access denied.', ResponseInterface::HTTP_NOT_FOUND);
            }
            return $this->respondSuccess($ann, 'Announcement retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createAnnouncement()
    {
        try {
            $user = $this->getUserContext();
            $raw = $this->request->getJSON(true) ?? $this->request->getPost();

            if (empty($raw['title']) || empty($raw['content'])) {
                return $this->respondError('Title and content are required.', ResponseInterface::HTTP_BAD_REQUEST);
            }

            $created = $this->announcementService->createAnnouncement($raw, (int)$user['id'], $user['name']);
            return $this->respondSuccess($created, 'Announcement created successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateAnnouncement($id = null)
    {
        try {
            $user = $this->getUserContext();
            $raw = $this->request->getJSON(true) ?? $this->request->getPost();

            $updated = $this->announcementService->updateAnnouncement((int)$id, $raw, (int)$user['id'], $user['name']);
            if (!$updated) {
                return $this->respondError('Announcement not found.', ResponseInterface::HTTP_NOT_FOUND);
            }
            return $this->respondSuccess($updated, 'Announcement updated successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteAnnouncement($id = null)
    {
        try {
            $user = $this->getUserContext();
            $deleted = $this->announcementService->deleteAnnouncement((int)$id, (int)$user['id'], $user['name']);
            if (!$deleted) {
                return $this->respondError('Announcement not found or already deleted.', ResponseInterface::HTTP_NOT_FOUND);
            }
            return $this->respondSuccess(['deleted' => true], 'Announcement removed successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ==========================================
    // 5. MEETINGS & GOVERNANCE
    // ==========================================

    public function listMeetings()
    {
        try {
            $user = $this->getUserContext();
            $filters = [
                'status'   => $this->request->getGet('status'),
                'category' => $this->request->getGet('category'),
                'room_id'  => $this->request->getGet('room_id'),
                'date'     => $this->request->getGet('date'),
                'search'   => $this->request->getGet('search'),
                'limit'    => $this->request->getGet('limit'),
                'offset'   => $this->request->getGet('offset'),
            ];

            $data = $this->meetingService->listMeetings($filters, $user['id'], $user['agent_id'], $user);
            return $this->respondSuccess($data, 'Meetings retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getMeeting($id = null)
    {
        try {
            $user = $this->getUserContext();
            $meeting = $this->meetingService->getMeeting((int)$id, $user);
            if (!$meeting) {
                return $this->respondError('Meeting not found or access denied.', ResponseInterface::HTTP_NOT_FOUND);
            }
            return $this->respondSuccess($meeting, 'Meeting details retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createMeeting()
    {
        try {
            $user = $this->getUserContext();
            $raw = $this->request->getJSON(true) ?? $this->request->getPost();

            if (empty($raw['title']) || empty($raw['scheduled_start_time']) || empty($raw['scheduled_end_time'])) {
                return $this->respondError('Title, start time, and end time are required.', ResponseInterface::HTTP_BAD_REQUEST);
            }

            $organizerType = $raw['organizer_type'] ?? $user['type'];
            $organizerId = ($organizerType === 'agent') ? (int)($raw['organizer_agent_id'] ?? $user['agent_id'] ?? 1) : (int)$user['id'];
            $organizerName = $raw['organizer_name'] ?? $user['name'];

            $created = $this->meetingService->createMeeting($raw, $organizerType, $organizerId, $organizerName);
            return $this->respondSuccess($created, 'Meeting scheduled successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateMeetingStatus($id = null)
    {
        try {
            $raw = $this->request->getJSON(true) ?? $this->request->getPost();
            if (empty($raw['status'])) {
                return $this->respondError('Status is required.', ResponseInterface::HTTP_BAD_REQUEST);
            }

            $updated = $this->meetingService->updateMeetingStatus((int)$id, $raw['status']);
            return $this->respondSuccess($updated, 'Meeting status updated successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function addParticipant($id = null)
    {
        try {
            $raw = $this->request->getJSON(true) ?? $this->request->getPost();
            $participant = $this->meetingService->addParticipant((int)$id, $raw);
            return $this->respondSuccess($participant, 'Participant added to meeting successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateParticipantAttendance($id = null, $participantId = null)
    {
        try {
            $raw = $this->request->getJSON(true) ?? $this->request->getPost();
            $status = $raw['attendance_status'] ?? 'attended';
            $notes = $raw['notes'] ?? null;

            $success = $this->meetingService->updateParticipantAttendance((int)$id, (int)$participantId, $status, $notes);
            return $this->respondSuccess(['updated' => $success], 'Participant attendance updated.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function addAgendaItem($id = null)
    {
        try {
            $raw = $this->request->getJSON(true) ?? $this->request->getPost();
            if (empty($raw['title'])) {
                return $this->respondError('Agenda title is required.', ResponseInterface::HTTP_BAD_REQUEST);
            }

            $agenda = $this->meetingService->addAgendaItem((int)$id, $raw);
            return $this->respondSuccess($agenda, 'Agenda item added successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getMeetingMinutes($id = null)
    {
        try {
            $user = $this->getUserContext();
            $meeting = $this->meetingService->getMeeting((int)$id, $user);
            if (!$meeting) {
                return $this->respondError('Meeting not found or access denied.', ResponseInterface::HTTP_NOT_FOUND);
            }
            return $this->respondSuccess($meeting['minutes'] ?? null, 'Minutes of meeting retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function saveMeetingMinutes($id = null)
    {
        try {
            $user = $this->getUserContext();
            $raw = $this->request->getJSON(true) ?? $this->request->getPost();

            $preparerType = $raw['preparer_type'] ?? $user['type'];
            $preparerId = ($preparerType === 'agent') ? (int)($raw['preparer_agent_id'] ?? $user['agent_id'] ?? 1) : (int)$user['id'];

            $minutes = $this->meetingService->saveMeetingMinutes((int)$id, $raw, $preparerType, $preparerId);
            return $this->respondSuccess($minutes, 'Minutes of meeting saved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approveMeetingMinutes($id = null)
    {
        try {
            $user = $this->getUserContext();
            $roles = $user['roles'] ?? [];
            if (!in_array('owner', $roles) && !in_array('superadmin', $roles) && !in_array('admin', $roles)) {
                return $this->respondError('Only authorized leadership can approve meeting minutes.', ResponseInterface::HTTP_FORBIDDEN);
            }

            $approved = $this->meetingService->approveMeetingMinutes((int)$id, (int)$user['id']);
            return $this->respondSuccess($approved, 'Minutes of meeting formally approved and meeting concluded.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function listActionItems()
    {
        try {
            $meetingId = $this->request->getGet('meeting_id') ? (int)$this->request->getGet('meeting_id') : null;
            $filters = [
                'status'        => $this->request->getGet('status'),
                'assignee_type' => $this->request->getGet('assignee_type'),
                'assignee_id'   => $this->request->getGet('assignee_id'),
            ];

            $data = $this->meetingService->listActionItems($meetingId, $filters);
            return $this->respondSuccess($data, 'Action items retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createActionItem($id = null)
    {
        try {
            $raw = $this->request->getJSON(true) ?? $this->request->getPost();
            if (empty($raw['description'])) {
                return $this->respondError('Action item description is required.', ResponseInterface::HTTP_BAD_REQUEST);
            }

            $syncToTask = !empty($raw['sync_to_task']);
            $actionItem = $this->meetingService->createActionItem((int)$id, $raw, $syncToTask);
            return $this->respondSuccess($actionItem, 'Action item created successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateActionItemStatus($id = null)
    {
        try {
            $raw = $this->request->getJSON(true) ?? $this->request->getPost();
            if (empty($raw['status'])) {
                return $this->respondError('Status is required.', ResponseInterface::HTTP_BAD_REQUEST);
            }

            $taskId = !empty($raw['task_id']) ? (int)$raw['task_id'] : null;
            $updated = $this->meetingService->updateActionItemStatus((int)$id, $raw['status'], $taskId);
            return $this->respondSuccess($updated, 'Action item status updated successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    // ==========================================
    // 6. NOTIFICATIONS & SEARCH
    // ==========================================

    public function listNotifications()
    {
        try {
            $user = $this->getUserContext();
            $recipientType = $user['type'];
            $recipientId = ($recipientType === 'agent') ? (int)$user['agent_id'] : (int)$user['id'];

            $filters = [
                'is_read'  => $this->request->getGet('is_read'),
                'type'     => $this->request->getGet('type'),
                'priority' => $this->request->getGet('priority'),
                'limit'    => $this->request->getGet('limit'),
                'offset'   => $this->request->getGet('offset'),
            ];

            $notifications = $this->notificationService->listNotifications($recipientType, $recipientId, $filters);
            return $this->respondSuccess($notifications, 'Notifications retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getUnreadCount()
    {
        try {
            $user = $this->getUserContext();
            $recipientType = $user['type'];
            $recipientId = ($recipientType === 'agent') ? (int)$user['agent_id'] : (int)$user['id'];

            $count = $this->notificationService->getUnreadCount($recipientType, $recipientId);
            return $this->respondSuccess(['unread_count' => $count], 'Unread notification count retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function markNotificationRead($id = null)
    {
        try {
            $user = $this->getUserContext();
            $recipientType = $user['type'];
            $recipientId = ($recipientType === 'agent') ? (int)$user['agent_id'] : (int)$user['id'];

            $success = $this->notificationService->markAsRead((int)$id, $recipientType, $recipientId);
            return $this->respondSuccess(['read' => $success], 'Notification marked as read.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function markAllNotificationsRead()
    {
        try {
            $user = $this->getUserContext();
            $recipientType = $user['type'];
            $recipientId = ($recipientType === 'agent') ? (int)$user['agent_id'] : (int)$user['id'];

            $success = $this->notificationService->markAllAsRead($recipientType, $recipientId);
            return $this->respondSuccess(['all_read' => $success], 'All notifications marked as read.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function searchCollaboration()
    {
        try {
            $user = $this->getUserContext();
            $query = $this->request->getGet('q') ?? '';
            $results = $this->searchService->search($query, $user);
            return $this->respondSuccess($results, 'Collaboration search executed successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
