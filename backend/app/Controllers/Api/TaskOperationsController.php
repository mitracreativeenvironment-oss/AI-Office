<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use App\Services\TaskManagementService;
use App\Services\TaskDelegationService;
use App\Services\TaskCollaborationService;
use App\Services\TaskDependencyService;
use App\Services\TaskReviewService;

class TaskOperationsController extends BaseApiController
{
    protected TaskManagementService $taskService;
    protected TaskDelegationService $delegationService;
    protected TaskCollaborationService $collaborationService;
    protected TaskDependencyService $dependencyService;
    protected TaskReviewService $reviewService;

    public function __construct()
    {
        $this->taskService = new TaskManagementService();
        $this->delegationService = new TaskDelegationService();
        $this->collaborationService = new TaskCollaborationService();
        $this->dependencyService = new TaskDependencyService();
        $this->reviewService = new TaskReviewService();
    }

    // ------------------------------------------------------------------------
    // TASKS
    // ------------------------------------------------------------------------

    public function index()
    {
        $filters = [
            'unit_id'              => $this->request->getGet('unit_id'),
            'status'               => $this->request->getGet('status'),
            'priority'             => $this->request->getGet('priority'),
            'category'             => $this->request->getGet('category'),
            'accountable_agent_id' => $this->request->getGet('accountable_agent_id'),
            'search'               => $this->request->getGet('search'),
        ];
        $limit = max(1, min(100, (int)($this->request->getGet('limit') ?? 20)));
        $offset = max(0, (int)($this->request->getGet('offset') ?? 0));

        try {
            $res = $this->taskService->listTasks($filters, $limit, $offset);
            return $this->respondSuccess($res['items'], 'Workforce tasks retrieved successfully.', ResponseInterface::HTTP_OK, [
                'total'  => $res['total'],
                'limit'  => $limit,
                'offset' => $offset,
            ]);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function metrics()
    {
        try {
            $metrics = $this->taskService->getMetrics();
            return $this->respondSuccess($metrics, 'Task metrics retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Task ID required.');
        }

        try {
            $task = $this->taskService->getTask((int)$id);
            if (!$task) {
                return $this->respondNotFound("Task #{$id} not found.");
            }
            return $this->respondSuccess($task, 'Task details retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();

        try {
            $actorUserId = $this->request->authenticatedUser->id ?? null;
            $task = $this->taskService->createTask($json, $actorUserId ? (int)$actorUserId : null);
            return $this->respondSuccess($task, 'Task created successfully in Task Control Plane.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function transition($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Task ID required.');
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $targetStatus = $json['target_status'] ?? $json['status'] ?? '';

        if (empty($targetStatus)) {
            return $this->respondError('Target status is required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $actorUserId = $this->request->authenticatedUser->id ?? null;
            $updated = $this->taskService->transitionTask(
                (int)$id,
                $targetStatus,
                ['reason' => $json['reason'] ?? null],
                $actorUserId ? (int)$actorUserId : null
            );
            return $this->respondSuccess($updated, "Task successfully transitioned to '{$targetStatus}'.");
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function activeTasksForAgent($agentId = null)
    {
        if ($agentId === null) {
            return $this->respondNotFound('Agent ID required.');
        }

        try {
            $tasks = $this->taskService->getActiveTasksForAgent((int)$agentId);
            return $this->respondSuccess($tasks, 'Active tasks for agent retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ------------------------------------------------------------------------
    // DELEGATIONS
    // ------------------------------------------------------------------------

    public function listDelegations()
    {
        $filters = [
            'task_id' => $this->request->getGet('task_id'),
            'status'  => $this->request->getGet('status'),
        ];

        try {
            $delegations = $this->delegationService->listDelegations($filters);
            return $this->respondSuccess($delegations, 'Task delegations retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createDelegation($taskId = null)
    {
        if ($taskId === null) {
            return $this->respondNotFound('Task ID required.');
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $json['task_id'] = (int)$taskId;

        try {
            $actorUserId = $this->request->authenticatedUser->id ?? null;
            $delegation = $this->delegationService->createDelegation($json, $actorUserId ? (int)$actorUserId : null);
            return $this->respondSuccess($delegation, 'Task delegation proposed successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_FORBIDDEN);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function acceptDelegation($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Delegation ID required.');
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $identity = $json['agent_identity'] ?? 'AGT-DELEGATED';

        try {
            $updated = $this->delegationService->acceptDelegation((int)$id, $identity);
            return $this->respondSuccess($updated, 'Delegation accepted successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ------------------------------------------------------------------------
    // COLLABORATION GROUPS
    // ------------------------------------------------------------------------

    public function listCollaborations()
    {
        $taskId = $this->request->getGet('task_id');

        try {
            $groups = $this->collaborationService->listGroups($taskId ? (int)$taskId : null);
            return $this->respondSuccess($groups, 'Collaboration groups retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function showCollaboration($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Collaboration Group ID required.');
        }

        try {
            $group = $this->collaborationService->getGroup((int)$id);
            if (!$group) {
                return $this->respondNotFound("Collaboration group #{$id} not found.");
            }
            return $this->respondSuccess($group, 'Collaboration workspace details retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createCollaboration($taskId = null)
    {
        if ($taskId === null) {
            return $this->respondNotFound('Task ID required.');
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $json['task_id'] = (int)$taskId;

        try {
            $actorUserId = $this->request->authenticatedUser->id ?? null;
            $group = $this->collaborationService->createGroup($json, $actorUserId ? (int)$actorUserId : null);
            return $this->respondSuccess($group, 'Collaboration group created successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function addCollaborationMember($groupId = null)
    {
        if ($groupId === null) {
            return $this->respondNotFound('Collaboration Group ID required.');
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $agentId = (int)($json['agent_id'] ?? 0);
        $role = $json['role'] ?? 'developer';
        $responsibilities = trim($json['assigned_responsibilities'] ?? 'Active collaboration participant.');

        try {
            $updated = $this->collaborationService->addMember((int)$groupId, $agentId, $role, $responsibilities);
            return $this->respondSuccess($updated, 'Member added to collaboration group.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ------------------------------------------------------------------------
    // DEPENDENCIES
    // ------------------------------------------------------------------------

    public function listDependencies($taskId = null)
    {
        if ($taskId === null) {
            return $this->respondNotFound('Task ID required.');
        }

        try {
            $deps = $this->dependencyService->listDependencies((int)$taskId);
            return $this->respondSuccess($deps, 'Task dependencies retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function addDependency($taskId = null)
    {
        if ($taskId === null) {
            return $this->respondNotFound('Task ID required.');
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $dependsOnTaskId = (int)($json['depends_on_task_id'] ?? 0);
        $type = $json['dependency_type'] ?? 'finish_to_start';

        try {
            $dep = $this->dependencyService->addDependency((int)$taskId, $dependsOnTaskId, $type);
            return $this->respondSuccess($dep, 'Task dependency established successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_CONFLICT);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ------------------------------------------------------------------------
    // DELIVERABLES & REVIEWS
    // ------------------------------------------------------------------------

    public function listDeliverables($taskId = null)
    {
        if ($taskId === null) {
            return $this->respondNotFound('Task ID required.');
        }

        try {
            $delivs = $this->reviewService->listDeliverables((int)$taskId);
            return $this->respondSuccess($delivs, 'Task deliverables retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function submitDeliverable($taskId = null)
    {
        if ($taskId === null) {
            return $this->respondNotFound('Task ID required.');
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $submittingAgentId = (int)($json['submitting_agent_id'] ?? 1);

        try {
            $deliverable = $this->reviewService->submitDeliverable((int)$taskId, $json, $submittingAgentId);
            return $this->respondSuccess($deliverable, 'Deliverable submitted for review.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function submitReview($taskId = null)
    {
        if ($taskId === null) {
            return $this->respondNotFound('Task ID required.');
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $deliverableId = (int)($json['deliverable_id'] ?? 0);
        $reviewerUserId = !empty($json['reviewer_user_id']) ? (int)$json['reviewer_user_id'] : ($this->request->authenticatedUser->id ?? null);
        $reviewerAgentId = !empty($json['reviewer_agent_id']) ? (int)$json['reviewer_agent_id'] : null;

        try {
            $review = $this->reviewService->submitReview(
                (int)$taskId,
                $deliverableId,
                $json,
                $reviewerUserId ? (int)$reviewerUserId : null,
                $reviewerAgentId ? (int)$reviewerAgentId : null
            );
            return $this->respondSuccess($review, 'Task review and quality verification submitted successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_FORBIDDEN);
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ------------------------------------------------------------------------
    // BLOCKERS
    // ------------------------------------------------------------------------

    public function reportBlocker($taskId = null)
    {
        if ($taskId === null) {
            return $this->respondNotFound('Task ID required.');
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $actorUserId = $this->request->authenticatedUser->id ?? null;
        $agentId = !empty($json['reported_by_agent_id']) ? (int)$json['reported_by_agent_id'] : null;

        try {
            $blocker = $this->reviewService->reportBlocker((int)$taskId, $json, $agentId, $actorUserId ? (int)$actorUserId : null);
            return $this->respondSuccess($blocker, 'Task blocker recorded. Task moved to Blocked state.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function resolveBlocker($blockerId = null)
    {
        if ($blockerId === null) {
            return $this->respondNotFound('Blocker ID required.');
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $notes = trim($json['resolution_notes'] ?? 'Blocker successfully resolved.');

        try {
            $resolved = $this->reviewService->resolveBlocker((int)$blockerId, $notes);
            return $this->respondSuccess($resolved, 'Task blocker resolved. Task returned to active progress.');
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

