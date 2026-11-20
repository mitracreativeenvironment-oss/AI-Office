<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\CollaborationAccessService;
use App\Services\MessagingService;
use App\Services\AnnouncementService;
use App\Services\ExecutiveCoordinationService;
use App\Services\MeetingManagementService;
use App\Services\CollaborationNotificationService;
use App\Services\CollaborationSearchService;
use App\Controllers\Api\CollaborationController;
use Config\Database;

/**
 * @internal
 */
final class CollaborationTest extends CIUnitTestCase
{
    protected CollaborationAccessService $accessService;
    protected MessagingService $messagingService;
    protected AnnouncementService $announcementService;
    protected ExecutiveCoordinationService $executiveService;
    protected MeetingManagementService $meetingService;
    protected CollaborationNotificationService $notificationService;
    protected CollaborationSearchService $searchService;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accessService = new CollaborationAccessService();
        $this->messagingService = new MessagingService();
        $this->announcementService = new AnnouncementService();
        $this->executiveService = new ExecutiveCoordinationService();
        $this->meetingService = new MeetingManagementService();
        $this->notificationService = new CollaborationNotificationService();
        $this->searchService = new CollaborationSearchService();
        $this->db = Database::connect();
    }

    public function testServicesAndControllerInstantiate(): void
    {
        $this->assertInstanceOf(CollaborationAccessService::class, $this->accessService);
        $this->assertInstanceOf(MessagingService::class, $this->messagingService);
        $this->assertInstanceOf(AnnouncementService::class, $this->announcementService);
        $this->assertInstanceOf(ExecutiveCoordinationService::class, $this->executiveService);
        $this->assertInstanceOf(MeetingManagementService::class, $this->meetingService);
        $this->assertInstanceOf(CollaborationNotificationService::class, $this->notificationService);
        $this->assertInstanceOf(CollaborationSearchService::class, $this->searchService);

        $controller = new CollaborationController();
        $this->assertInstanceOf(CollaborationController::class, $controller);
    }

    public function testClassificationAccessRules(): void
    {
        // Public accessible by all
        $this->assertTrue($this->accessService->canAccessClassification('public', null));

        // Internal requires authentication
        $this->assertFalse($this->accessService->canAccessClassification('internal', null));
        $this->assertTrue($this->accessService->canAccessClassification('internal', ['roles' => ['staff']]));

        // Restricted requires admin / executive
        $this->assertFalse($this->accessService->canAccessClassification('restricted', ['roles' => ['staff']]));
        $this->assertTrue($this->accessService->canAccessClassification('restricted', ['roles' => ['executive']]));
        $this->assertTrue($this->accessService->canAccessClassification('restricted', ['roles' => ['owner']]));

        // Highly restricted strictly requires superadmin / owner
        $this->assertFalse($this->accessService->canAccessClassification('highly_restricted', ['roles' => ['admin']]));
        $this->assertTrue($this->accessService->canAccessClassification('highly_restricted', ['roles' => ['owner']]));
    }

    public function testAntiImpersonationSecurityGuardrail(): void
    {
        // 1. Agent attempting to impersonate a human MUST FAIL
        $agentAuth = ['type' => 'agent', 'id' => 1, 'agent_id' => 1, 'roles' => ['agent']];
        $result = $this->accessService->verifySenderIdentity('human', 1, $agentAuth);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('AI Agents are strictly prohibited from impersonating human personnel', $result['error']);

        // 2. Normal human attempting to spoof another user ID
        $humanAuth = ['type' => 'human', 'id' => 5, 'roles' => ['member']];
        $result2 = $this->accessService->verifySenderIdentity('human', 1, $humanAuth);
        $this->assertFalse($result2['valid']);
        $this->assertStringContainsString('Cannot send message on behalf of another human user', $result2['error']);

        // 3. Genuine human matching identity MUST PASS
        $result3 = $this->accessService->verifySenderIdentity('human', 5, $humanAuth);
        $this->assertTrue($result3['valid']);

        // 4. Genuine agent matching identity MUST PASS
        $result4 = $this->accessService->verifySenderIdentity('agent', 1, $agentAuth);
        $this->assertTrue($result4['valid']);
    }

    public function testExecutiveOverview(): void
    {
        $overview = $this->executiveService->getExecutiveOverview();
        $this->assertIsArray($overview);
        $this->assertArrayHasKey('metrics', $overview);
        $this->assertArrayHasKey('recent_directives', $overview);
        $this->assertArrayHasKey('recent_decisions', $overview);
        $this->assertArrayHasKey('doa_profiles', $overview);
        $this->assertGreaterThanOrEqual(1, $overview['metrics']['total_directives']);
    }

    public function testDirectiveLifecycleAndAcknowledgement(): void
    {
        $admin = $this->db->table('users')->orderBy('id', 'ASC')->get(1)->getRowArray();
        $userId = $admin ? (int)$admin['id'] : 1;

        // 1. Create strategic directive
        $directive = $this->executiveService->createDirective([
            'direction_type'          => 'strategic_instruction',
            'subject'                 => 'Test Unit Directive for Operations Sync',
            'description'             => 'Mandatory alignment on DoA Level B boundaries.',
            'requires_acknowledgment' => 1,
            'due_date'                => date('Y-m-d', strtotime('+7 days')),
        ], $userId, 'Human Owner');

        $this->assertNotEmpty($directive['id']);
        $this->assertStringStartsWith('DIR-OWNER-', $directive['direction_code']);
        $dirId = $directive['id'];

        // 2. Acknowledge directive by Director AI
        $ack = $this->executiveService->acknowledgeDirective($dirId, 'agent', 1, 'in_execution', 'Director AI actively coordinating tasks.');
        $this->assertNotEmpty($ack['id']);
        $this->assertSame('in_execution', $ack['status']);

        // 3. Get Directive details and verify join
        $fetched = $this->executiveService->getDirective($dirId);
        $this->assertNotNull($fetched);
        $this->assertSame('in_progress', $fetched['status']);
        $this->assertSame('in_execution', $fetched['ack_status']);

        // 4. Mark executed
        $ackFinal = $this->executiveService->acknowledgeDirective($dirId, 'agent', 1, 'executed', 'All operations aligned.');
        $fetchedFinal = $this->executiveService->getDirective($dirId);
        $this->assertSame('completed', $fetchedFinal['status']);
        $this->assertSame('executed', $ackFinal['status']);
    }

    public function testExecutiveDecisionProposal(): void
    {
        $decision = $this->executiveService->createDecisionProposal([
            'title'             => 'Test Ratification of Sprint Milestones',
            'summary'           => 'Board consensus on technical delivery and resource allocation.',
            'decision_category' => 'operational',
            'decision_level'    => 'B',
            'status'            => 'ratified',
        ], 'agent', 1, 'Continuous Executive Board');

        $this->assertNotEmpty($decision['id']);
        $this->assertStringStartsWith('DEC-BOARD-', $decision['decision_code']);
        $this->assertSame('B', $decision['decision_level']);

        // Verify in list
        $list = $this->executiveService->listDecisions(['decision_level' => 'B']);
        $this->assertNotEmpty($list);
    }

    public function testChannelsAndMessaging(): void
    {
        $admin = $this->db->table('users')->orderBy('id', 'ASC')->get(1)->getRowArray();
        $userId = $admin ? (int)$admin['id'] : 1;
        $userContext = ['id' => $userId, 'type' => 'human', 'roles' => ['owner', 'superadmin']];

        // 1. Create a channel
        $channel = $this->messagingService->createChannel([
            'name'           => 'Test Engineering Sub-Channel',
            'description'    => 'Channel for unit testing messaging operations.',
            'domain'         => 'technical',
            'classification' => 'internal',
            'is_private'     => 0,
        ], $userId);

        $this->assertNotEmpty($channel['id']);
        $channelId = $channel['id'];

        // 2. Transmit message
        $msg = $this->messagingService->sendMessage(
            'channel',
            $channelId,
            ['content' => 'Hello team, running automated test suite verification.'],
            'human',
            $userId,
            'Human Owner',
            $userContext
        );

        $this->assertNotEmpty($msg['id']);
        $this->assertSame('human', $msg['sender_type']);
        $this->assertStringStartsWith('MSG-', $msg['message_code']);

        // 3. Pin message
        $pinned = $this->messagingService->pinMessage($msg['id'], true);
        $this->assertTrue($pinned);

        // 4. Retrieve messages
        $messages = $this->messagingService->getMessages('channel', $channelId, [], $userId, null, $userContext);
        $this->assertNotEmpty($messages);
        $this->assertSame(1, (int)$messages[0]['is_pinned']);

        // 5. Threaded reply
        $reply = $this->messagingService->sendMessage(
            'channel',
            $channelId,
            [
                'content'           => 'Director AI acknowledging test message.',
                'parent_message_id' => $msg['id'],
            ],
            'human',
            $userId,
            'Human Owner',
            $userContext
        );
        $this->assertSame($msg['id'], (int)$reply['parent_message_id']);
    }

    public function testDirectConversationAndAccess(): void
    {
        $admin = $this->db->table('users')->orderBy('id', 'ASC')->get(1)->getRowArray();
        $userId = $admin ? (int)$admin['id'] : 1;
        $userContext = ['id' => $userId, 'type' => 'human', 'roles' => ['owner', 'superadmin']];

        // 1. Create conversation
        $conv = $this->messagingService->createConversation([
            'title'          => 'Unit Test Direct Line',
            'type'           => 'direct',
            'classification' => 'internal',
        ], 'human', $userId, [
            ['participant_type' => 'agent', 'agent_id' => 1, 'role' => 'member']
        ]);

        $this->assertNotEmpty($conv['id']);
        $convId = $conv['id'];

        // 2. Transmit direct message
        $msg = $this->messagingService->sendMessage(
            'conversation',
            $convId,
            ['content' => 'Direct ping from test suite.'],
            'human',
            $userId,
            'Human Owner',
            $userContext
        );
        $this->assertNotEmpty($msg['id']);

        // 3. Fetch messages
        $convMessages = $this->messagingService->getMessages('conversation', $convId, [], $userId, null, $userContext);
        $this->assertCount(1, $convMessages);

        // 4. Mark as read
        $readResult = $this->messagingService->markAsRead('conversation', $convId, $msg['id'], 'human', $userId);
        $this->assertTrue($readResult);
    }

    public function testAnnouncementsWorkflow(): void
    {
        $admin = $this->db->table('users')->orderBy('id', 'ASC')->get(1)->getRowArray();
        $userId = $admin ? (int)$admin['id'] : 1;
        $userContext = ['id' => $userId, 'type' => 'human', 'roles' => ['owner', 'superadmin']];

        // 1. Create announcement
        $ann = $this->announcementService->createAnnouncement([
            'title'        => 'Test Announcement: Scheduled Maintenance Window',
            'content'      => 'All services will undergo routine schema verification tonight.',
            'target_scope' => 'all_organization',
            'priority'     => 'important',
        ], $userId, 'Human Owner');

        $this->assertNotEmpty($ann['id']);
        $this->assertStringStartsWith('ANN-', $ann['announcement_code']);
        $annId = $ann['id'];

        // 2. Fetch announcement
        $fetched = $this->announcementService->getAnnouncement($annId, $userContext);
        $this->assertNotNull($fetched);
        $this->assertSame('important', $fetched['priority']);

        // 3. Update announcement
        $updated = $this->announcementService->updateAnnouncement($annId, ['priority' => 'critical'], $userId, 'Human Owner');
        $this->assertSame('critical', $updated['priority']);

        // 4. Delete announcement
        $deleted = $this->announcementService->deleteAnnouncement($annId, $userId, 'Human Owner');
        $this->assertTrue($deleted);
    }

    public function testMeetingLifecycleAgendasAndMinutes(): void
    {
        $admin = $this->db->table('users')->orderBy('id', 'ASC')->get(1)->getRowArray();
        $userId = $admin ? (int)$admin['id'] : 1;
        $userContext = ['id' => $userId, 'type' => 'human', 'roles' => ['owner', 'superadmin']];

        // Fetch Room
        $room = $this->db->table('hq_rooms')->where('room_code', 'ROOM-MAIN-BOARD')->get()->getRowArray();
        $roomId = $room ? (int)$room['id'] : null;

        // 1. Schedule meeting
        $meeting = $this->meetingService->createMeeting([
            'title'                => 'Test Boardroom Strategic Review',
            'category'             => 'executive_coordination',
            'room_id'              => $roomId,
            'scheduled_start_time' => date('Y-m-d 10:00:00', strtotime('+2 hours')),
            'scheduled_end_time'   => date('Y-m-d 11:30:00', strtotime('+2 hours')),
            'description'          => 'Unit test for meeting lifecycle and minutes finalization.',
            'participants'         => [
                ['participant_type' => 'agent', 'agent_id' => 1, 'role' => 'secretary']
            ],
            'agendas'              => [
                ['item_order' => 1, 'title' => 'Opening & Quorum Check', 'allocated_minutes' => 15],
                ['item_order' => 2, 'title' => 'Resource Review', 'allocated_minutes' => 30],
            ]
        ], 'human', $userId, 'Human Owner');

        $this->assertNotEmpty($meeting['id']);
        $this->assertStringStartsWith('MTG-', $meeting['meeting_code']);
        $meetingId = $meeting['id'];

        // 2. Verify agendas and participants
        $fetched = $this->meetingService->getMeeting($meetingId, $userContext);
        $this->assertCount(2, $fetched['participants']); // Organizer + Secretary
        $this->assertCount(2, $fetched['agendas']);

        // 3. Transition to in_progress and then completed
        $this->meetingService->updateMeetingStatus($meetingId, 'in_progress');
        $inProgress = $this->meetingService->getMeeting($meetingId, $userContext);
        $this->assertSame('in_progress', $inProgress['status']);
        $this->assertNotNull($inProgress['actual_start_time']);

        $this->meetingService->updateMeetingStatus($meetingId, 'completed');
        $completed = $this->meetingService->getMeeting($meetingId, $userContext);
        $this->assertSame('completed', $completed['status']);
        $this->assertNotNull($completed['actual_end_time']);

        // 4. Save Minutes of Meeting (MoM)
        $minutes = $this->meetingService->saveMeetingMinutes($meetingId, [
            'attendance_summary' => 'Human Owner (Chair), Director AI (Secretary)',
            'discussion_notes'   => 'Reviewed system uptime, DoA compliance, and collaboration features.',
            'formal_decisions'   => 'Approved full deployment of Prompt 26 modules.',
        ], 'agent', 1);

        $this->assertNotEmpty($minutes['id']);
        $this->assertSame(0, (int)$minutes['is_approved']);

        // 5. Approve Minutes of Meeting
        $approvedMinutes = $this->meetingService->approveMeetingMinutes($meetingId, $userId);
        $this->assertSame(1, (int)$approvedMinutes['is_approved']);

        $finalMeeting = $this->meetingService->getMeeting($meetingId, $userContext);
        $this->assertSame('minutes_finalized', $finalMeeting['status']);

        // 6. Create Action Item linked to task
        $actionItem = $this->meetingService->createActionItem($meetingId, [
            'description'   => 'Execute integration tests across all 28 endpoints',
            'assignee_type' => 'human',
            'assignee_id'   => $userId,
            'due_date'      => date('Y-m-d', strtotime('+3 days')),
        ], true);

        $this->assertNotEmpty($actionItem['id']);
        $this->assertNotEmpty($actionItem['task_id']);

        // 7. Update Action Item Status
        $updatedAI = $this->meetingService->updateActionItemStatus($actionItem['id'], 'completed');
        $this->assertSame('completed', $updatedAI['status']);
    }

    public function testCollaborationNotifications(): void
    {
        $admin = $this->db->table('users')->orderBy('id', 'ASC')->get(1)->getRowArray();
        $userId = $admin ? (int)$admin['id'] : 1;

        // 1. Send notification
        $notifId = $this->notificationService->sendNotification(
            'human',
            $userId,
            'Unit Test Notification Title',
            'Unit test notification body payload.',
            'alert',
            'urgent',
            '/collaboration/test'
        );
        $this->assertGreaterThan(0, $notifId);

        // 2. Count unread
        $unread = $this->notificationService->getUnreadCount('human', $userId);
        $this->assertGreaterThanOrEqual(1, $unread);

        // 3. Mark single as read
        $marked = $this->notificationService->markAsRead($notifId, 'human', $userId);
        $this->assertTrue($marked);

        // 4. Mark all as read
        $allMarked = $this->notificationService->markAllAsRead('human', $userId);
        $this->assertTrue($allMarked);

        $finalUnread = $this->notificationService->getUnreadCount('human', $userId);
        $this->assertSame(0, $finalUnread);
    }

    public function testCollaborationSearchService(): void
    {
        $admin = $this->db->table('users')->orderBy('id', 'ASC')->get(1)->getRowArray();
        $userId = $admin ? (int)$admin['id'] : 1;
        $userContext = ['id' => $userId, 'type' => 'human', 'roles' => ['owner', 'superadmin']];

        // Search for "Board"
        $results = $this->searchService->search('Board', $userContext);
        $this->assertIsArray($results);
        $this->assertSame('Board', $results['query']);
        $this->assertGreaterThanOrEqual(1, $results['total_results']);
        $this->assertIsArray($results['channels']);
        $this->assertIsArray($results['meetings']);
    }
}
