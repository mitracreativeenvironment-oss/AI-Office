<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

// ==========================================
// MCE REST API v1
// ==========================================
$routes->group('api/v1', static function ($routes) {
    // Health Checks
    $routes->get('health', 'Api\HealthController::index');
    $routes->get('health/database', 'Api\HealthController::database');

    // Authentication Endpoints
    $routes->group('auth', static function ($routes) {
        $routes->post('login', 'Api\AuthController::login');

        // Protected Auth Routes (Require Bearer Token)
        $routes->group('', ['filter' => 'mce_auth'], static function ($routes) {
            $routes->get('me', 'Api\AuthController::me');
            $routes->post('logout', 'Api\AuthController::logout');
            $routes->post('change-password', 'Api\AuthController::changePassword');
        });
    });

    // Identity & RBAC Administration (Protected & Permission Enforced)
    $routes->group('admin', ['filter' => 'mce_auth'], static function ($routes) {
        $routes->get('users', 'Api\AdminController::listUsers', ['filter' => 'mce_permission:users.view']);
        $routes->get('users/(:num)', 'Api\AdminController::getUser/$1', ['filter' => 'mce_permission:users.view']);
        $routes->post('users', 'Api\AdminController::createUser', ['filter' => 'mce_permission:users.create']);
        $routes->post('users/(:num)/disable', 'Api\AdminController::disableUser/$1', ['filter' => 'mce_permission:users.disable']);
        $routes->post('users/(:num)/enable', 'Api\AdminController::enableUser/$1', ['filter' => 'mce_permission:users.disable']);
        $routes->get('roles', 'Api\AdminController::listRoles', ['filter' => 'mce_permission:roles.view']);
        $routes->get('permissions', 'Api\AdminController::listPermissions', ['filter' => 'mce_permission:permissions.view']);
        $routes->post('users/(:num)/roles', 'Api\AdminController::assignUserRole/$1', ['filter' => 'mce_permission:roles.assign']);
        $routes->get('audit-logs', 'Api\AdminController::listAuditLogs', ['filter' => 'mce_permission:audit.view']);
    });

    // Company Profile & Divisions
    $routes->get('company', 'Api\CompanyController::profile');
    $routes->get('divisions', 'Api\CompanyController::divisions');

    // Organization Structure (Multi-level Hierarchy, Positions, Appointments)
    $routes->group('organization', static function ($routes) {
        $routes->get('tree', 'Api\OrganizationController::tree');
        $routes->get('units', 'Api\OrganizationController::units');
        $routes->get('units/(:num)', 'Api\OrganizationController::showUnit/$1');
        $routes->get('positions', 'Api\OrganizationController::positions');
        $routes->get('positions/(:num)', 'Api\OrganizationController::showPosition/$1');
        $routes->get('appointments', 'Api\OrganizationController::appointments');
        $routes->get('reporting', 'Api\OrganizationController::reporting');

        // Mutating endpoints (Protected by Bearer Token)
        $routes->group('', ['filter' => 'mce_auth'], static function ($routes) {
            $routes->post('units', 'Api\OrganizationController::createUnit');
            $routes->put('units/(:num)', 'Api\OrganizationController::updateUnit/$1');
            $routes->post('positions', 'Api\OrganizationController::createPosition');
            $routes->post('appointments', 'Api\OrganizationController::createAppointment');
            $routes->post('appointments/(:num)/end', 'Api\OrganizationController::endAppointment/$1');
            $routes->post('reporting', 'Api\OrganizationController::addReporting');
        });
    });

    // Corporate Governance, DoA, Owner Directions & Executive Decisions
    $routes->group('governance', static function ($routes) {
        $routes->get('core-values', 'Api\GovernanceController::coreValues');
        $routes->get('bodies', 'Api\GovernanceController::bodies');
        $routes->get('bodies/(:num)', 'Api\GovernanceController::showBody/$1');
        $routes->get('authority-profiles', 'Api\GovernanceController::authorityProfiles');
        $routes->post('authority-check', 'Api\GovernanceController::authorityCheck');
        $routes->get('delegations', 'Api\GovernanceController::delegations');
        $routes->get('delegations/(:num)', 'Api\GovernanceController::showDelegation/$1');
        $routes->get('directions', 'Api\GovernanceController::directions');
        $routes->get('directions/(:num)', 'Api\GovernanceController::showDirection/$1');
        $routes->get('decisions', 'Api\GovernanceController::decisions');
        $routes->get('decisions/(:num)', 'Api\GovernanceController::showDecision/$1');
        $routes->get('audit-logs', 'Api\GovernanceController::auditLogs');

        // Mutating endpoints (Protected by Bearer Token)
        $routes->group('', ['filter' => 'mce_auth'], static function ($routes) {
            $routes->post('bodies/(:num)/members', 'Api\GovernanceController::addBodyMember');
            $routes->post('delegations', 'Api\GovernanceController::createDelegation');
            $routes->post('delegations/(:num)/revoke', 'Api\GovernanceController::revokeDelegation/$1');
            $routes->post('directions', 'Api\GovernanceController::createDirection');
            $routes->post('directions/(:num)/acknowledge', 'Api\GovernanceController::acknowledgeDirection/$1');
            $routes->post('decisions', 'Api\GovernanceController::createDecision');
            $routes->post('decisions/(:num)/ratify', 'Api\GovernanceController::ratifyDecision/$1');
        });
    });

    // AI Agent Registry & Workforce Lifecycle Management (Prompt 19)
    $routes->group('workforce', static function ($routes) {
        // Read-only / Query
        $routes->get('metrics', 'Api\WorkforceController::metrics');
        $routes->get('agents', 'Api\WorkforceController::index');
        $routes->get('agents/(:num)', 'Api\WorkforceController::show/$1');
        $routes->get('agents/(:num)/profile', 'Api\WorkforceController::getProfile/$1');
        $routes->get('agents/(:num)/lifecycle', 'Api\WorkforceController::lifecycleHistory/$1');
        $routes->get('agents/(:num)/assignments', 'Api\WorkforceController::assignments/$1');
        $routes->get('agents/(:num)/responsibilities', 'Api\WorkforceController::responsibilities/$1');
        $routes->get('skills', 'Api\WorkforceController::skillCatalogue');
        $routes->get('agents/(:num)/skills', 'Api\WorkforceController::agentSkills/$1');
        $routes->get('agents/(:num)/performance-history', 'Api\WorkforceController::performanceHistory/$1');
        $routes->get('agents/(:num)/runtime-mappings', 'Api\WorkforceController::runtimeMappings/$1');

        // Scheduling, Shifts & Coverage Query Routes
        $routes->get('scheduling/policies', 'Api\SchedulingController::listPolicies');
        $routes->get('scheduling/shifts', 'Api\SchedulingController::listShifts');
        $routes->get('scheduling/rosters', 'Api\SchedulingController::listRosters');
        $routes->get('scheduling/rosters/(:num)', 'Api\SchedulingController::showRoster/$1');
        $routes->get('scheduling/coverage/requirements', 'Api\SchedulingController::listCoverageRequirements');
        $routes->get('scheduling/coverage/status', 'Api\SchedulingController::evaluateCoverage');
        $routes->get('scheduling/coverage/gaps', 'Api\SchedulingController::getCoverageGaps');
        $routes->get('agents/(:num)/availability', 'Api\SchedulingController::getAvailability/$1');

        // Replacement Query Routes
        $routes->get('replacements', 'Api\ReplacementController::index');
        $routes->get('replacements/candidates', 'Api\ReplacementController::findCandidates');
        $routes->get('replacements/(:num)', 'Api\ReplacementController::show/$1');

        // Task Management & Delegation Query Routes (Prompt 21)
        $routes->get('tasks', 'Api\TaskOperationsController::index');
        $routes->get('tasks/metrics', 'Api\TaskOperationsController::metrics');
        $routes->get('tasks/delegations', 'Api\TaskOperationsController::listDelegations');
        $routes->get('tasks/collaborations', 'Api\TaskOperationsController::listCollaborations');
        $routes->get('tasks/collaborations/(:num)', 'Api\TaskOperationsController::showCollaboration/$1');
        $routes->get('tasks/(:num)', 'Api\TaskOperationsController::show/$1');
        $routes->get('tasks/(:num)/dependencies', 'Api\TaskOperationsController::listDependencies/$1');
        $routes->get('tasks/(:num)/deliverables', 'Api\TaskOperationsController::listDeliverables/$1');
        $routes->get('agents/(:num)/active-tasks', 'Api\TaskOperationsController::activeTasksForAgent/$1');

        // Mutating endpoints (Protected by Bearer Token)
        $routes->group('', ['filter' => 'mce_auth'], static function ($routes) {
            $routes->post('agents', 'Api\WorkforceController::create');
            $routes->put('agents/(:num)', 'Api\WorkforceController::update/$1');
            $routes->put('agents/(:num)/profile', 'Api\WorkforceController::updateProfile/$1');
            $routes->post('agents/(:num)/transition', 'Api\WorkforceController::transition/$1');
            $routes->post('agents/(:num)/assignments', 'Api\WorkforceController::assign/$1');
            $routes->post('assignments/(:num)/end', 'Api\WorkforceController::endAssignment/$1');
            $routes->post('agents/(:num)/responsibilities', 'Api\WorkforceController::addResponsibility/$1');
            $routes->post('skills', 'Api\WorkforceController::createSkill');
            $routes->post('agents/(:num)/skills', 'Api\WorkforceController::assignSkill/$1');
            $routes->post('skills/(:num)/verify', 'Api\WorkforceController::verifySkill/$1');
            $routes->post('agents/(:num)/performance-history', 'Api\WorkforceController::recordPerformance/$1');
            $routes->post('performance-history/(:num)/verify', 'Api\WorkforceController::verifyPerformanceEvidence/$1');
            $routes->post('performance-history/(:num)/amend', 'Api\WorkforceController::amendPerformance/$1');
            $routes->post('agents/(:num)/runtime-mappings', 'Api\WorkforceController::setRuntimeMapping/$1');

            // Scheduling Mutations
            $routes->post('scheduling/policies', 'Api\SchedulingController::createPolicy');
            $routes->post('scheduling/shifts', 'Api\SchedulingController::createShift');
            $routes->post('scheduling/rosters', 'Api\SchedulingController::createRoster');
            $routes->post('scheduling/rosters/(:num)/publish', 'Api\SchedulingController::publishRoster/$1');
            $routes->post('scheduling/duty-assignments', 'Api\SchedulingController::createDutyAssignment');
            $routes->post('scheduling/duty-assignments/(:num)/complete', 'Api\SchedulingController::completeDutyAssignment/$1');
            $routes->post('agents/(:num)/off-duty-requests', 'Api\SchedulingController::requestOffDuty/$1');

            // Replacement Mutations
            $routes->post('replacements', 'Api\ReplacementController::create');
            $routes->post('replacements/(:num)/accept', 'Api\ReplacementController::accept/$1');
            $routes->post('replacements/(:num)/approve', 'Api\ReplacementController::approve/$1');
            $routes->post('replacements/(:num)/activate', 'Api\ReplacementController::activate/$1');

            // Task Management Mutations (Prompt 21)
            $routes->post('tasks', 'Api\TaskOperationsController::create');
            $routes->post('tasks/(:num)/transition', 'Api\TaskOperationsController::transition/$1');
            $routes->post('tasks/(:num)/delegations', 'Api\TaskOperationsController::createDelegation/$1');
            $routes->post('delegations/(:num)/accept', 'Api\TaskOperationsController::acceptDelegation/$1');
            $routes->post('tasks/(:num)/collaborations', 'Api\TaskOperationsController::createCollaboration/$1');
            $routes->post('tasks/collaborations/(:num)/members', 'Api\TaskOperationsController::addCollaborationMember/$1');
            $routes->post('tasks/(:num)/dependencies', 'Api\TaskOperationsController::addDependency/$1');
            $routes->post('tasks/(:num)/deliverables', 'Api\TaskOperationsController::submitDeliverable/$1');
            $routes->post('tasks/(:num)/reviews', 'Api\TaskOperationsController::submitReview/$1');
            $routes->post('tasks/(:num)/blockers', 'Api\TaskOperationsController::reportBlocker/$1');
            $routes->post('tasks/blockers/(:num)/resolve', 'Api\TaskOperationsController::resolveBlocker/$1');
        });
    });

    // Wallbook Operational Handover Management
    $routes->group('wallbook', static function ($routes) {
        $routes->get('', 'Api\WallbookController::index');
        $routes->get('handovers', 'Api\WallbookController::index');
        $routes->get('handovers/(:num)', 'Api\WallbookController::show/$1');

        $routes->group('', ['filter' => 'mce_auth'], static function ($routes) {
            $routes->post('handovers', 'Api\WallbookController::create');
            $routes->post('handovers/(:num)/items', 'Api\WallbookController::addItem/$1');
            $routes->post('handovers/(:num)/submit', 'Api\WallbookController::submit/$1');
            $routes->post('handovers/(:num)/acknowledge', 'Api\WallbookController::acknowledge/$1');
            $routes->post('handovers/(:num)/clarify', 'Api\WallbookController::clarify/$1');
            $routes->post('handovers/(:num)/accept', 'Api\WallbookController::accept/$1');
            $routes->post('handovers/escalate-overdue', 'Api\WallbookController::escalateOverdue');
        });
    });

    // Runtime Infrastructure & Hermes Orchestration (Prompt 22)
    $routes->group('runtime', static function ($routes) {
        $routes->get('health', 'Api\RuntimeController::health');
        $routes->get('status', 'Api\RuntimeController::health');
        $routes->get('environments', 'Api\RuntimeController::environments', ['filter' => ['mce_auth', 'mce_permission:runtime.view']]);
        $routes->get('profiles', 'Api\RuntimeController::profiles', ['filter' => ['mce_auth', 'mce_permission:runtime.view']]);
        $routes->get('tools', 'Api\RuntimeController::listTools', ['filter' => ['mce_auth', 'mce_permission:runtime.view']]);
        $routes->get('executions', 'Api\RuntimeController::listExecutions', ['filter' => ['mce_auth', 'mce_permission:runtime.view']]);
        $routes->get('executions/(:num)', 'Api\RuntimeController::getExecution/$1', ['filter' => ['mce_auth', 'mce_permission:runtime.view']]);
        $routes->get('agents/(:num)', 'Api\RuntimeController::getAgentRuntime/$1', ['filter' => ['mce_auth', 'mce_permission:runtime.view']]);

        $routes->group('', ['filter' => ['mce_auth', 'mce_permission:runtime.manage']], static function ($routes) {
            $routes->post('agents/(:num)/provision', 'Api\RuntimeController::provisionAgent/$1');
            $routes->post('agents/(:num)/suspend', 'Api\RuntimeController::suspendAgent/$1');
            $routes->post('agents/(:num)/validate', 'Api\RuntimeController::validateAgent/$1');
            $routes->post('executions', 'Api\RuntimeController::dispatchExecution');
            $routes->post('executions/(:num)/cancel', 'Api\RuntimeController::cancelExecution/$1');
            $routes->post('tools', 'Api\RuntimeController::createTool');
            $routes->post('tools/invoke', 'Api\RuntimeController::invokeTool', ['filter' => 'mce_permission:runtime.tools.invoke']);
        });
    });

    // 9Router Model Gateway Management (Prompt 22)
    $routes->group('model-gateway', static function ($routes) {
        $routes->get('status', 'Api\RuntimeController::gatewayStatus');
        $routes->get('routes', 'Api\RuntimeController::gatewayRoutes');
        $routes->get('usage', 'Api\RuntimeController::gatewayUsage');

        $routes->group('', ['filter' => 'mce_auth'], static function ($routes) {
            $routes->post('completions', 'Api\RuntimeController::gatewayComplete');
        });
    });

    // Agents Management
    $routes->get('agents', 'Api\AgentController::index');
    $routes->get('agents/(:segment)', 'Api\AgentController::show/$1');
    $routes->post('agents/(:segment)/status', 'Api\AgentController::updateStatus/$1');

    // Tasks Management
    $routes->get('tasks', 'Api\TaskController::index');
    $routes->post('tasks', 'Api\TaskController::create');

    // Approval Center (Human-in-the-Loop)
    $routes->get('approvals', 'Api\ApprovalController::index');
    $routes->post('approvals/(:num)/action', 'Api\ApprovalController::decide/$1');

    // 3D Virtual Office Integration
    $routes->get('office/layout', 'Api\OfficeController::layout');
    $routes->get('office/feed', 'Api\OfficeController::feed');

    // Virtual Headquarters (VHQ) 3D Spatial Operations (Prompt 23)
    $routes->group('vhq', static function ($routes) {
        $routes->get('campus', 'Api\VhqController::campus');
        $routes->get('buildings/(:num)', 'Api\VhqController::building/$1');
        $routes->get('rooms', 'Api\VhqController::rooms');
        $routes->get('workstations', 'Api\VhqController::workstations');
        $routes->get('presence', 'Api\VhqController::presence');
        $routes->get('agents/(:num)/presence', 'Api\VhqController::agentPresence/$1');
        $routes->get('bookmarks', 'Api\VhqController::bookmarks');

        $routes->group('', ['filter' => 'mce_auth'], static function ($routes) {
            $routes->post('workstations/(:num)/assign', 'Api\VhqController::assignWorkstation/$1');
        });
    });

    // Agent Welfare, Facilities & Welfare Tickets (Prompt 24)
    $routes->group('welfare', static function ($routes) {
        $routes->get('overview', 'Api\WelfareController::overview');
        $routes->get('policies', 'Api\WelfareController::listPolicies');
        $routes->get('policies/active', 'Api\WelfareController::activePolicy');
        $routes->get('periods', 'Api\WelfareController::listPeriods');
        $routes->get('periods/(:num)', 'Api\WelfareController::getPeriod/$1');
        $routes->get('task-bonuses', 'Api\WelfareController::listTaskBonuses');
        $routes->get('recognitions', 'Api\WelfareController::listRecognitions');
        $routes->get('ledger', 'Api\WelfareController::listTransactions');
        $routes->get('agents/(:num)/balance', 'Api\WelfareController::getAgentBalance/$1');
        $routes->get('facilities', 'Api\WelfareController::listFacilities');
        $routes->get('facility-logs', 'Api\WelfareController::listFacilityLogs');

        $routes->group('', ['filter' => 'mce_auth'], static function ($routes) {
            $routes->post('policies', 'Api\WelfareController::createPolicy');
            $routes->post('policies/(:num)/approve', 'Api\WelfareController::approvePolicy/$1');
            $routes->post('periods', 'Api\WelfareController::createPeriod');
            $routes->post('periods/(:num)/calculate', 'Api\WelfareController::calculatePeriod/$1');
            $routes->post('periods/(:num)/approve', 'Api\WelfareController::approvePeriod/$1');
            $routes->post('periods/(:num)/distribute', 'Api\WelfareController::distributePeriod/$1');
            $routes->post('tasks/(:num)/evaluate-bonus', 'Api\WelfareController::evaluateTaskBonus/$1');
            $routes->post('task-bonuses/(:num)/approve', 'Api\WelfareController::approveTaskBonus/$1');
            $routes->post('recognitions', 'Api\WelfareController::nominateRecognition');
            $routes->post('recognitions/(:num)/approve', 'Api\WelfareController::approveRecognition/$1');
            $routes->post('agents/(:num)/reconcile', 'Api\WelfareController::reconcileAgentBalance/$1');
            $routes->post('adjustments', 'Api\WelfareController::postAdjustment');
            $routes->post('transactions/(:num)/reverse', 'Api\WelfareController::reverseTransaction/$1');
            $routes->post('facilities/(:num)/check-in', 'Api\WelfareController::checkInFacility/$1');
            $routes->post('facility-sessions/(:num)/check-out', 'Api\WelfareController::checkOutFacility/$1');
        });
    });

    // Knowledge Management, Organizational Memory & Wallbook Bridge (Prompt 25)
    $routes->group('knowledge', static function ($routes) {
        $routes->get('overview', 'Api\KnowledgeController::overview');
        $routes->get('categories', 'Api\KnowledgeController::listCategories');
        $routes->get('records', 'Api\KnowledgeController::listRecords');
        $routes->get('records/(:num)', 'Api\KnowledgeController::showRecord/$1');
        $routes->get('contributions', 'Api\KnowledgeController::listContributions');
        $routes->get('contributions/(:num)', 'Api\KnowledgeController::showContribution/$1');
        $routes->get('documents', 'Api\KnowledgeController::listDocuments');
        $routes->get('documents/(:num)', 'Api\KnowledgeController::showDocument/$1');
        $routes->get('wallbook/(:num)/checklists', 'Api\KnowledgeController::listWallbookChecklists/$1');
        $routes->get('search', 'Api\KnowledgeController::search');

        $routes->group('', ['filter' => 'mce_auth'], static function ($routes) {
            $routes->get('agents/(:num)/memories', 'Api\KnowledgeController::listAgentMemories/$1');
            $routes->get('memories/(:num)', 'Api\KnowledgeController::showMemory/$1');
            $routes->post('categories', 'Api\KnowledgeController::createCategory');
            $routes->post('records', 'Api\KnowledgeController::createRecord');
            $routes->put('records/(:num)', 'Api\KnowledgeController::updateRecord/$1');
            $routes->post('records/(:num)/transition', 'Api\KnowledgeController::transitionRecord/$1');
            $routes->post('contributions', 'Api\KnowledgeController::submitContribution');
            $routes->post('contributions/(:num)/review', 'Api\KnowledgeController::reviewContribution/$1');
            $routes->post('documents', 'Api\KnowledgeController::registerDocument');
            $routes->post('documents/(:num)/versions', 'Api\KnowledgeController::addDocumentVersion/$1');
            $routes->post('agents/(:num)/memories', 'Api\KnowledgeController::createAgentMemory/$1');
            $routes->post('memories/(:num)/promote', 'Api\KnowledgeController::promoteMemory/$1');
            $routes->post('wallbook/checklists/(:num)', 'Api\KnowledgeController::updateWallbookChecklist/$1');
            $routes->post('wallbook/(:num)/items/(:num)/promote', 'Api\KnowledgeController::promoteWallbookItem/$1/$2');
            $routes->post('rag/retrieve', 'Api\KnowledgeController::ragRetrieve');
        });
    });

    // Internal Communication, Executive Coordination & Meetings (Prompt 26)
    $routes->group('collaboration', static function ($routes) {
        $routes->get('executive/overview', 'Api\CollaborationController::executiveOverview');
        $routes->get('executive/directives', 'Api\CollaborationController::listDirectives');
        $routes->get('executive/directives/(:num)', 'Api\CollaborationController::getDirective/$1');
        $routes->get('executive/decisions', 'Api\CollaborationController::listDecisions');
        $routes->get('channels', 'Api\CollaborationController::listChannels');
        $routes->get('channels/(:num)', 'Api\CollaborationController::getChannel/$1');
        $routes->get('channels/(:num)/messages', 'Api\CollaborationController::getChannelMessages/$1');
        $routes->get('announcements', 'Api\CollaborationController::listAnnouncements');
        $routes->get('announcements/(:num)', 'Api\CollaborationController::getAnnouncement/$1');
        $routes->get('meetings', 'Api\CollaborationController::listMeetings');
        $routes->get('meetings/(:num)', 'Api\CollaborationController::getMeeting/$1');
        $routes->get('meetings/(:num)/minutes', 'Api\CollaborationController::getMeetingMinutes/$1');
        $routes->get('action-items', 'Api\CollaborationController::listActionItems');
        $routes->get('search', 'Api\CollaborationController::searchCollaboration');

        $routes->group('', ['filter' => 'mce_auth'], static function ($routes) {
            $routes->post('executive/directives', 'Api\CollaborationController::createDirective');
            $routes->post('executive/directives/(:num)/acknowledge', 'Api\CollaborationController::acknowledgeDirective/$1');
            $routes->post('executive/decisions', 'Api\CollaborationController::createDecisionProposal');
            $routes->post('channels', 'Api\CollaborationController::createChannel');
            $routes->post('channels/(:num)/join', 'Api\CollaborationController::joinChannel/$1');
            $routes->post('channels/(:num)/leave', 'Api\CollaborationController::leaveChannel/$1');
            $routes->post('channels/(:num)/messages', 'Api\CollaborationController::sendChannelMessage/$1');
            $routes->get('conversations', 'Api\CollaborationController::listConversations');
            $routes->get('conversations/(:num)', 'Api\CollaborationController::getConversation/$1');
            $routes->post('conversations', 'Api\CollaborationController::createConversation');
            $routes->get('conversations/(:num)/messages', 'Api\CollaborationController::getConversationMessages/$1');
            $routes->post('conversations/(:num)/messages', 'Api\CollaborationController::sendConversationMessage/$1');
            $routes->post('messages/(:num)/pin', 'Api\CollaborationController::pinMessage/$1');
            $routes->post('messages/read', 'Api\CollaborationController::markMessagesRead');
            $routes->post('announcements', 'Api\CollaborationController::createAnnouncement');
            $routes->put('announcements/(:num)', 'Api\CollaborationController::updateAnnouncement/$1');
            $routes->delete('announcements/(:num)', 'Api\CollaborationController::deleteAnnouncement/$1');
            $routes->post('meetings', 'Api\CollaborationController::createMeeting');
            $routes->patch('meetings/(:num)/status', 'Api\CollaborationController::updateMeetingStatus/$1');
            $routes->post('meetings/(:num)/participants', 'Api\CollaborationController::addParticipant/$1');
            $routes->patch('meetings/(:num)/participants/(:num)', 'Api\CollaborationController::updateParticipantAttendance/$1/$2');
            $routes->post('meetings/(:num)/agendas', 'Api\CollaborationController::addAgendaItem/$1');
            $routes->post('meetings/(:num)/minutes', 'Api\CollaborationController::saveMeetingMinutes/$1');
            $routes->post('meetings/(:num)/minutes/approve', 'Api\CollaborationController::approveMeetingMinutes/$1');
            $routes->post('meetings/(:num)/action-items', 'Api\CollaborationController::createActionItem/$1');
            $routes->patch('action-items/(:num)', 'Api\CollaborationController::updateActionItemStatus/$1');
            $routes->get('notifications', 'Api\CollaborationController::listNotifications');
            $routes->get('notifications/unread-count', 'Api\CollaborationController::getUnreadCount');
            $routes->post('notifications/(:num)/read', 'Api\CollaborationController::markNotificationRead/$1');
            $routes->post('notifications/read-all', 'Api\CollaborationController::markAllNotificationsRead');
        });
    });

    // Project Management, Client Engagement, Project Lifecycle & Delivery Operations (Prompt 27)
    $routes->group('projects', static function ($routes) {
        $routes->get('portfolio-metrics', 'Api\ProjectController::portfolioMetrics');
        $routes->get('clients', 'Api\ProjectController::listClients');
        $routes->get('clients/(:num)', 'Api\ProjectController::showClient/$1');
        $routes->get('clients/(:num)/contacts', 'Api\ProjectController::listContacts/$1');
        $routes->get('', 'Api\ProjectController::index');
        $routes->get('(:num)', 'Api\ProjectController::show/$1');
        $routes->get('(:num)/status-history', 'Api\ProjectController::statusHistory/$1');
        $routes->get('(:num)/tasks', 'Api\ProjectController::getLinkedTasks/$1');
        $routes->get('(:num)/brief', 'Api\ProjectController::getBrief/$1');
        $routes->get('(:num)/scopes', 'Api\ProjectController::getScopes/$1');
        $routes->get('(:num)/milestones', 'Api\ProjectController::getMilestones/$1');
        $routes->get('(:num)/deliverables', 'Api\ProjectController::getDeliverables/$1');
        $routes->get('(:num)/risks', 'Api\ProjectController::getRisks/$1');
        $routes->get('(:num)/issues', 'Api\ProjectController::getIssues/$1');
        $routes->get('(:num)/change-requests', 'Api\ProjectController::getChangeRequests/$1');
        $routes->get('(:num)/team', 'Api\ProjectController::getTeam/$1');
        $routes->get('(:num)/resources', 'Api\ProjectController::getResources/$1');
        $routes->get('(:num)/budget', 'Api\ProjectController::getBudget/$1');

        $routes->group('', ['filter' => 'mce_auth'], static function ($routes) {
            $routes->post('', 'Api\ProjectController::create');
            $routes->put('(:num)', 'Api\ProjectController::update/$1');
            $routes->delete('(:num)', 'Api\ProjectController::delete/$1');
            $routes->post('(:num)/transition', 'Api\ProjectController::transitionStage/$1');
            $routes->post('(:num)/tasks/link', 'Api\ProjectController::linkTask/$1');
            $routes->post('(:num)/tasks/unlink', 'Api\ProjectController::unlinkTask/$1');
            $routes->post('(:num)/recalculate-progress', 'Api\ProjectController::recalculateProgress/$1');
            $routes->post('clients', 'Api\ProjectController::createClient');
            $routes->put('clients/(:num)', 'Api\ProjectController::updateClient/$1');
            $routes->post('clients/(:num)/contacts', 'Api\ProjectController::createContact/$1');
            $routes->put('clients/contacts/(:num)', 'Api\ProjectController::updateContact/$1');
            $routes->delete('clients/contacts/(:num)', 'Api\ProjectController::deleteContact/$1');
            $routes->post('(:num)/brief', 'Api\ProjectController::upsertBrief/$1');
            $routes->post('(:num)/scopes/baseline', 'Api\ProjectController::createScopeBaseline/$1');
            $routes->post('(:num)/milestones', 'Api\ProjectController::createMilestone/$1');
            $routes->put('milestones/(:num)', 'Api\ProjectController::updateMilestone/$1');
            $routes->delete('milestones/(:num)', 'Api\ProjectController::deleteMilestone/$1');
            $routes->post('(:num)/deliverables', 'Api\ProjectController::createDeliverable/$1');
            $routes->put('deliverables/(:num)', 'Api\ProjectController::updateDeliverable/$1');
            $routes->post('deliverables/(:num)/submit', 'Api\ProjectController::submitDeliverable/$1');
            $routes->post('deliverables/(:num)/review', 'Api\ProjectController::reviewDeliverable/$1');
            $routes->post('deliverables/(:num)/client-accept', 'Api\ProjectController::clientAcceptDeliverable/$1');
            $routes->post('(:num)/risks', 'Api\ProjectController::createRisk/$1');
            $routes->put('risks/(:num)', 'Api\ProjectController::updateRisk/$1');
            $routes->delete('risks/(:num)', 'Api\ProjectController::deleteRisk/$1');
            $routes->post('(:num)/issues', 'Api\ProjectController::createIssue/$1');
            $routes->put('issues/(:num)', 'Api\ProjectController::updateIssue/$1');
            $routes->post('issues/(:num)/resolve', 'Api\ProjectController::resolveIssue/$1');
            $routes->post('(:num)/change-requests', 'Api\ProjectController::createChangeRequest/$1');
            $routes->post('change-requests/(:num)/evaluate', 'Api\ProjectController::evaluateChangeRequest/$1');
            $routes->post('(:num)/team', 'Api\ProjectController::addTeamMember/$1');
            $routes->put('team/(:num)', 'Api\ProjectController::updateTeamMember/$1');
            $routes->delete('team/(:num)', 'Api\ProjectController::removeTeamMember/$1');
            $routes->post('(:num)/resources', 'Api\ProjectController::addResource/$1');
            $routes->post('resources/(:num)/utilization', 'Api\ProjectController::updateResourceUtilization/$1');
            $routes->put('(:num)/budget', 'Api\ProjectController::updateBudget/$1');
        });
    });

    // Resource & Capacity Planning, Portfolio Prioritization & Operational Allocation (Prompt 28)
    $routes->group('resource-planning', static function ($routes) {
        $routes->get('overview', 'Api\ResourcePlanningController::getOrganizationOverview');
        $routes->get('divisional-comparison', 'Api\ResourcePlanningController::getDivisionalComparison');
        $routes->get('resources', 'Api\ResourcePlanningController::getResources');
        $routes->get('resources/(:num)', 'Api\ResourcePlanningController::getResource/$1');
        $routes->get('capacity/agents', 'Api\ResourcePlanningController::getAllAgentsCapacity');
        $routes->get('capacity/agents/(:num)', 'Api\ResourcePlanningController::getAgentCapacity/$1');
        $routes->get('capacity/agents/(:num)/timeline', 'Api\ResourcePlanningController::getAgentTimeline/$1');
        $routes->get('workload/heatmap', 'Api\ResourcePlanningController::getWorkloadHeatmap');
        $routes->get('workload/summary', 'Api\ResourcePlanningController::getWorkloadSummary');
        $routes->get('skills/matrix', 'Api\ResourcePlanningController::getSkillCoverageMatrix');
        $routes->get('skills/bottlenecks', 'Api\ResourcePlanningController::getSkillBottlenecks');
        $routes->get('skills/spof', 'Api\ResourcePlanningController::getSingleAgentDependencies');
        $routes->get('reservations', 'Api\ResourcePlanningController::getReservations');
        $routes->get('reservations/(:num)', 'Api\ResourcePlanningController::getReservation/$1');
        $routes->get('requests', 'Api\ResourcePlanningController::getRequests');
        $routes->get('requests/(:num)', 'Api\ResourcePlanningController::getRequest/$1');
        $routes->get('allocations', 'Api\ResourcePlanningController::getAllocations');
        $routes->get('conflicts', 'Api\ResourcePlanningController::getConflicts');
        $routes->get('conflicts/(:num)', 'Api\ResourcePlanningController::getConflict/$1');
        $routes->get('priorities', 'Api\ResourcePlanningController::getPortfolioPriorities');
        $routes->get('priorities/project/(:num)', 'Api\ResourcePlanningController::getProjectPriority/$1');
        $routes->get('forecasts', 'Api\ResourcePlanningController::getForecastSnapshots');
        $routes->get('gaps', 'Api\ResourcePlanningController::getGapAnalyses');

        $routes->group('', ['filter' => 'mce_auth'], static function ($routes) {
            $routes->post('resources', 'Api\ResourcePlanningController::createResource');
            $routes->put('resources/(:num)', 'Api\ResourcePlanningController::updateResource/$1');
            $routes->delete('resources/(:num)', 'Api\ResourcePlanningController::deleteResource/$1');
            $routes->post('reservations', 'Api\ResourcePlanningController::createReservation');
            $routes->put('reservations/(:num)', 'Api\ResourcePlanningController::updateReservation/$1');
            $routes->post('reservations/(:num)/deactivate', 'Api\ResourcePlanningController::deactivateReservation/$1');
            $routes->post('requests', 'Api\ResourcePlanningController::createRequest');
            $routes->post('requests/(:num)/submit', 'Api\ResourcePlanningController::submitRequest/$1');
            $routes->post('requests/(:num)/assess-capacity', 'Api\ResourcePlanningController::assessRequestCapacity/$1');
            $routes->post('requests/(:num)/approve', 'Api\ResourcePlanningController::approveRequest/$1');
            $routes->post('requests/(:num)/reject', 'Api\ResourcePlanningController::rejectRequest/$1');
            $routes->post('requests/(:num)/allocate', 'Api\ResourcePlanningController::allocateRequest/$1');
            $routes->post('conflicts/detect', 'Api\ResourcePlanningController::detectConflicts');
            $routes->post('conflicts/(:num)/resolve', 'Api\ResourcePlanningController::resolveConflict/$1');
            $routes->post('conflicts/(:num)/dismiss', 'Api\ResourcePlanningController::dismissConflict/$1');
            $routes->post('priorities/evaluate', 'Api\ResourcePlanningController::evaluateProjectPriority');
            $routes->post('priorities/override', 'Api\ResourcePlanningController::overridePriority');
            $routes->post('forecasts/generate', 'Api\ResourcePlanningController::generateForecastSnapshot');
            $routes->post('gaps', 'Api\ResourcePlanningController::createGapAnalysis');
            $routes->post('gaps/(:num)/resolve', 'Api\ResourcePlanningController::resolveGapAnalysis/$1');
        });
    });

    // Operational Performance Management, KPI Framework, Agent Evaluation & Executive Reporting (Prompt 29)
    $routes->group('performance', static function ($routes) {
            // KPI Catalog & Categories
            $routes->get('categories', 'Api\PerformanceManagementController::getCategories');
            $routes->get('kpis', 'Api\PerformanceManagementController::getKpis');
            $routes->post('kpis', 'Api\PerformanceManagementController::createKpi');
            $routes->get('kpis/(:num)', 'Api\PerformanceManagementController::getKpi/$1');
            $routes->put('kpis/(:num)', 'Api\PerformanceManagementController::updateKpi/$1');
            $routes->delete('kpis/(:num)', 'Api\PerformanceManagementController::deleteKpi/$1');

            // Metric Calculations
            $routes->post('kpis/(:num)/calculate', 'Api\PerformanceManagementController::calculateKpi/$1');
            $routes->post('kpis/calculate-all', 'Api\PerformanceManagementController::calculateAllKpis');
            $routes->get('kpis/(:num)/results', 'Api\PerformanceManagementController::getKpiResults/$1');

            // Multi-tier Scorecards & Dashboards
            $routes->get('corporate-scorecard', 'Api\PerformanceManagementController::getCorporateScorecard');
            $routes->get('operational-pulse', 'Api\PerformanceManagementController::getOperationalPulse');
            $routes->get('executive-overview', 'Api\PerformanceManagementController::getExecutiveOverview');
            $routes->get('divisional-comparison', 'Api\PerformanceManagementController::getDivisionalComparison');
            $routes->get('units/(:num)', 'Api\PerformanceManagementController::getUnitPerformance/$1');
            $routes->get('project-portfolio', 'Api\PerformanceManagementController::getProjectPortfolio');

            // Agent Evaluations
            $routes->get('evaluations', 'Api\PerformanceManagementController::getEvaluations');
            $routes->post('evaluations', 'Api\PerformanceManagementController::createEvaluation');
            $routes->get('evaluations/(:num)', 'Api\PerformanceManagementController::getEvaluation/$1');
            $routes->post('evaluations/(:num)/transition', 'Api\PerformanceManagementController::transitionEvaluation/$1');
            $routes->get('agents/(:num)/profile', 'Api\PerformanceManagementController::getAgentPerformanceProfile/$1');
            $routes->post('evaluations/(:num)/evidence', 'Api\PerformanceManagementController::addEvidence/$1');
            $routes->get('evaluations/(:num)/evidence', 'Api\PerformanceManagementController::getEvidence/$1');

            // Core Values Assessments
            $routes->get('agents/(:num)/core-values', 'Api\PerformanceManagementController::getAgentCoreValues/$1');
            $routes->post('core-values', 'Api\PerformanceManagementController::assessCoreValue');
            $routes->get('core-values/summary', 'Api\PerformanceManagementController::getCoreValuesSummary');

            // Workflow Cycles
            $routes->get('cycles', 'Api\PerformanceManagementController::getCycles');
            $routes->post('cycles', 'Api\PerformanceManagementController::createCycle');
            $routes->get('cycles/(:num)', 'Api\PerformanceManagementController::getCycle/$1');
            $routes->post('cycles/(:num)/transition', 'Api\PerformanceManagementController::transitionCycle/$1');
            $routes->post('evaluations/(:num)/finalize', 'Api\PerformanceManagementController::finalizeEvaluation/$1');

            // Performance Improvement Plans (PIPs)
            $routes->get('plans', 'Api\PerformanceManagementController::getPlans');
            $routes->post('plans', 'Api\PerformanceManagementController::createPlan');
            $routes->get('plans/(:num)', 'Api\PerformanceManagementController::getPlan/$1');
            $routes->put('plans/(:num)', 'Api\PerformanceManagementController::updatePlan/$1');
            $routes->post('plans/(:num)/transition', 'Api\PerformanceManagementController::transitionPlan/$1');
            $routes->post('plans/(:num)/link-task', 'Api\PerformanceManagementController::linkPlanTask/$1');

            // Welfare Bonuses & DoA
            $routes->get('bonuses', 'Api\PerformanceManagementController::getBonuses');
            $routes->post('bonuses/recommend', 'Api\PerformanceManagementController::recommendBonus');
            $routes->get('bonuses/(:num)', 'Api\PerformanceManagementController::getBonus/$1');
            $routes->post('bonuses/(:num)/approve', 'Api\PerformanceManagementController::approveBonus/$1');
            $routes->post('bonuses/(:num)/reject', 'Api\PerformanceManagementController::rejectBonus/$1');
            $routes->post('bonuses/(:num)/disburse', 'Api\PerformanceManagementController::disburseBonus/$1');
            $routes->get('agents/(:num)/bonus-eligibility', 'Api\PerformanceManagementController::getBonusEligibility/$1');

            // Executive Reporting
            $routes->get('reports', 'Api\PerformanceManagementController::getReports');
            $routes->post('reports/generate', 'Api\PerformanceManagementController::generateReport');
            $routes->get('reports/(:num)', 'Api\PerformanceManagementController::getReport/$1');
            $routes->post('reports/(:num)/publish', 'Api\PerformanceManagementController::publishReport/$1');
            $routes->post('reports/(:num)/ratify', 'Api\PerformanceManagementController::ratifyReport/$1');
        });
    });
