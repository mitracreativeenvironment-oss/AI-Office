<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\TaskManagementService;
use App\Services\TaskDelegationService;
use App\Services\TaskCollaborationService;
use App\Services\TaskDependencyService;
use App\Services\TaskReviewService;
use App\Controllers\Api\TaskOperationsController;

/**
 * @internal
 */
final class TaskOperationsTest extends CIUnitTestCase
{
    protected TaskManagementService $taskService;
    protected TaskDelegationService $delegationService;
    protected TaskCollaborationService $collaborationService;
    protected TaskDependencyService $dependencyService;
    protected TaskReviewService $reviewService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taskService = new TaskManagementService();
        $this->delegationService = new TaskDelegationService();
        $this->collaborationService = new TaskCollaborationService();
        $this->dependencyService = new TaskDependencyService();
        $this->reviewService = new TaskReviewService();
    }

    public function testServicesAndControllerInstantiate(): void
    {
        $this->assertInstanceOf(TaskManagementService::class, $this->taskService);
        $this->assertInstanceOf(TaskDelegationService::class, $this->delegationService);
        $this->assertInstanceOf(TaskCollaborationService::class, $this->collaborationService);
        $this->assertInstanceOf(TaskDependencyService::class, $this->dependencyService);
        $this->assertInstanceOf(TaskReviewService::class, $this->reviewService);
        $this->assertInstanceOf(TaskOperationsController::class, new TaskOperationsController());
    }

    public function testListTasksAndMetrics(): void
    {
        $res = $this->taskService->listTasks([], 10, 0);
        $this->assertIsArray($res);
        $this->assertArrayHasKey('items', $res);
        $this->assertArrayHasKey('total', $res);
        $this->assertGreaterThanOrEqual(1, $res['total']);

        $metrics = $this->taskService->getMetrics();
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('total_tasks', $metrics);
        $this->assertArrayHasKey('by_status', $metrics);
        $this->assertArrayHasKey('by_priority', $metrics);
    }

    public function testTaskCreationAndAccountableOwnership(): void
    {
        $testCode = 'TSK-TEST-' . rand(1000, 9999);
        $created = $this->taskService->createTask([
            'task_code'            => $testCode,
            'title'                => 'Unit Test Automated Task Creation',
            'description'          => 'Validating accountable owner and initial draft assignment.',
            'unit_id'              => 2, // Technology division
            'category'             => 'development',
            'priority'             => 'high',
            'origin_type'          => 'division_initiative',
            'accountable_agent_id' => 2, // Tech Head
        ], 1);

        $this->assertNotNull($created);
        $this->assertEquals($testCode, $created['task_code']);
        $this->assertEquals('draft', $created['status']);
        $this->assertEquals(2, $created['accountable_agent_id']);

        // Accountable agent must have an active assignment record
        $this->assertNotEmpty($created['assignments']);
        $this->assertEquals(2, $created['assignments'][0]['agent_id']);
        $this->assertEquals('lead_agent', $created['assignments'][0]['assignment_role']);
    }

    public function testLifecycleTransitionsAndInvalidJumps(): void
    {
        $testCode = 'TSK-TRANS-' . rand(1000, 9999);
        $task = $this->taskService->createTask([
            'task_code'            => $testCode,
            'title'                => 'Lifecycle State Machine Test Task',
            'unit_id'              => 2,
            'category'             => 'operational',
            'accountable_agent_id' => 6, // Dev AI
        ], 1);

        $taskId = (int)$task['id'];

        // Valid transition: draft -> ready -> assigned -> accepted -> in_progress
        $t1 = $this->taskService->transitionTask($taskId, 'ready', ['reason' => 'Prerequisites ready.']);
        $this->assertEquals('ready', $t1['status']);

        $t2 = $this->taskService->transitionTask($taskId, 'assigned', ['reason' => 'Assigned to Dev AI.']);
        $this->assertEquals('assigned', $t2['status']);

        $t3 = $this->taskService->transitionTask($taskId, 'accepted', ['reason' => 'Dev AI accepted.']);
        $this->assertEquals('accepted', $t3['status']);

        $t4 = $this->taskService->transitionTask($taskId, 'in_progress', ['reason' => 'Dev AI started.']);
        $this->assertEquals('in_progress', $t4['status']);

        // Invalid jump: in_progress -> completed directly must fail
        $threw = false;
        try {
            $this->taskService->transitionTask($taskId, 'completed');
        } catch (\RuntimeException $e) {
            $threw = true;
            $this->assertStringContainsString('Invalid task state transition', $e->getMessage());
        }
        $this->assertTrue($threw, 'Direct transition from in_progress to completed must be rejected.');
    }

    public function testDelegationAuthorityAndCircularPrevention(): void
    {
        $testCode = 'TSK-DEL-' . rand(1000, 9999);
        $task = $this->taskService->createTask([
            'task_code'            => $testCode,
            'title'                => 'Delegation Verification Task',
            'unit_id'              => 2,
            'accountable_agent_id' => 2, // Tech Head AI
        ], 1);

        // Tech Head delegates to Dev AI (Valid)
        $delegation = $this->delegationService->createDelegation([
            'task_id'             => $task['id'],
            'delegating_agent_id' => 2,
            'delegated_agent_id'  => 6,
            'delegated_scope'     => 'Implement test harness.',
        ], 1);

        $this->assertNotNull($delegation);
        $this->assertEquals('proposed', $delegation['status']);

        // Accept
        $accepted = $this->delegationService->acceptDelegation((int)$delegation['id'], 'AGT-DEV-001');
        $this->assertEquals('accepted', $accepted['status']);

        // Circular delegation attempt: Dev AI tries to delegate back to Tech Head
        $threw = false;
        try {
            $this->delegationService->createDelegation([
                'task_id'             => $task['id'],
                'delegating_agent_id' => 6,
                'delegated_agent_id'  => 2,
                'delegated_scope'     => 'Circular pass-back attempt.',
            ], 1);
        } catch (\RuntimeException $e) {
            $threw = true;
            $this->assertStringContainsString('Circular delegation detected', $e->getMessage());
        }
        $this->assertTrue($threw, 'Circular delegation must be rejected.');
    }

    public function testMultiAgentCollaborationWorkspace(): void
    {
        $testCode = 'TSK-COL-' . rand(1000, 9999);
        $task = $this->taskService->createTask([
            'task_code'            => $testCode,
            'title'                => 'Collaboration Task',
            'unit_id'              => 2,
            'accountable_agent_id' => 2,
        ], 1);

        $collab = $this->collaborationService->createGroup([
            'task_id'          => $task['id'],
            'lead_agent_id'    => 2,
            'title'            => 'Specialist Engineering Taskforce',
            'shared_objective' => 'Cross-functional delivery.',
        ], 1);

        $this->assertNotNull($collab);
        $this->assertEquals('active', $collab['status']);
        $this->assertEquals(1, count($collab['members']));

        // Add specialist Dev AI
        $updated = $this->collaborationService->addMember((int)$collab['id'], 6, 'developer', 'Core module engineering.');
        $this->assertEquals(2, count($updated['members']));
    }

    public function testDependencyCycleDetection(): void
    {
        $taskA = $this->taskService->createTask([
            'task_code'            => 'TSK-DEP-A-' . rand(100, 999),
            'title'                => 'Task A (Root Prerequisite)',
            'unit_id'              => 2,
            'accountable_agent_id' => 2,
        ], 1);

        $taskB = $this->taskService->createTask([
            'task_code'            => 'TSK-DEP-B-' . rand(100, 999),
            'title'                => 'Task B (Depends on A)',
            'unit_id'              => 2,
            'accountable_agent_id' => 6,
        ], 1);

        // B depends on A (Valid)
        $dep = $this->dependencyService->addDependency((int)$taskB['id'], (int)$taskA['id']);
        $this->assertNotNull($dep);
        $this->assertEquals(0, $dep['is_satisfied']);

        // Now try to make A depend on B (Circular Cycle: A -> B -> A)
        $threw = false;
        try {
            $this->dependencyService->addDependency((int)$taskA['id'], (int)$taskB['id']);
        } catch (\RuntimeException $e) {
            $threw = true;
            $this->assertStringContainsString('Circular dependency rejected', $e->getMessage());
        }
        $this->assertTrue($threw, 'Circular dependency cycle must be prevented.');
    }

    public function testDeliverableSubmissionIndependentReviewAndPerformanceHistory(): void
    {
        $testCode = 'TSK-REV-' . rand(1000, 9999);
        $task = $this->taskService->createTask([
            'task_code'            => $testCode,
            'title'                => 'Quality Verification Task',
            'unit_id'              => 2,
            'category'             => 'development',
            'accountable_agent_id' => 6, // Dev AI
        ], 1);

        // Dev AI submits deliverable
        $deliv = $this->reviewService->submitDeliverable((int)$task['id'], [
            'title'            => 'Source Code & Documentation Artifact v1.0',
            'deliverable_type' => 'source_code',
            'artifact_uri'     => 'src/services/taskService.ts',
        ], 6);

        $this->assertNotNull($deliv);
        $this->assertEquals(1, $deliv['version']);
        $this->assertEquals('pending_review', $deliv['review_status']);

        // Independent review check: Dev AI cannot review its own output
        $threw = false;
        try {
            $this->reviewService->submitReview((int)$task['id'], (int)$deliv['id'], [
                'review_outcome' => 'accepted',
            ], null, 6); // Reviewer is submitting agent (Dev AI)
        } catch (\RuntimeException $e) {
            $threw = true;
            $this->assertStringContainsString('Independent review rule violation', $e->getMessage());
        }
        $this->assertTrue($threw, 'Self-review must be forbidden.');

        // Independent review by Tech Head AI with verified contribution
        $review = $this->reviewService->submitReview((int)$task['id'], (int)$deliv['id'], [
            'review_outcome'           => 'accepted',
            'evaluation_comments'      => 'Clean implementation and thorough test coverage.',
            'is_verified_contribution' => 1,
        ], null, 2); // Reviewer: Tech Head AI

        $this->assertNotNull($review);
        $this->assertEquals('accepted', $review['review_outcome']);
        $this->assertEquals(1, $review['is_verified_contribution']);
    }

    public function testBlockerReportingAndResolution(): void
    {
        $testCode = 'TSK-BLK-' . rand(1000, 9999);
        $task = $this->taskService->createTask([
            'task_code'            => $testCode,
            'title'                => 'Blocker Test Task',
            'unit_id'              => 2,
            'accountable_agent_id' => 2,
        ], 1);

        // Move to in_progress first
        $this->taskService->transitionTask((int)$task['id'], 'ready');
        $this->taskService->transitionTask((int)$task['id'], 'assigned');
        $this->taskService->transitionTask((int)$task['id'], 'accepted');
        $this->taskService->transitionTask((int)$task['id'], 'in_progress');

        // Report blocker
        $blocker = $this->reviewService->reportBlocker((int)$task['id'], [
            'category'    => 'missing_information',
            'description' => 'Waiting for external cloud credentials.',
            'severity'    => 'high',
        ], 2);

        $this->assertNotNull($blocker);
        $this->assertEquals('active', $blocker['status']);

        // Task should now be in blocked state
        $updatedTask = $this->taskService->getTask((int)$task['id']);
        $this->assertEquals('blocked', $updatedTask['status']);

        // Resolve blocker
        $resolved = $this->reviewService->resolveBlocker((int)$blocker['id'], 'Credentials securely provisioned.');
        $this->assertEquals('resolved', $resolved['status']);

        // Task automatically unblocks back to in_progress
        $unblockedTask = $this->taskService->getTask((int)$task['id']);
        $this->assertEquals('in_progress', $unblockedTask['status']);
    }

    public function testActiveTasksForAgentWallbookContinuity(): void
    {
        $activeTasks = $this->taskService->getActiveTasksForAgent(2); // Tech Head
        $this->assertIsArray($activeTasks);
        $this->assertGreaterThanOrEqual(1, count($activeTasks));

        foreach ($activeTasks as $at) {
            $this->assertNotEquals('completed', $at['status']);
            $this->assertNotEquals('cancelled', $at['status']);
        }
    }
}
