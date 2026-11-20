<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\ProjectAccessService;
use App\Services\ProjectService;
use App\Services\ProjectLifecycleService;
use App\Services\ClientManagementService;
use App\Services\ScopeDeliverableService;
use App\Services\ProjectRiskIssueService;
use App\Services\ProjectResourceBudgetService;
use App\Controllers\Api\ProjectController;
use Config\Database;

/**
 * @internal
 */
final class ProjectManagementTest extends CIUnitTestCase
{
    protected ProjectAccessService $accessService;
    protected ProjectService $projectService;
    protected ProjectLifecycleService $lifecycleService;
    protected ClientManagementService $clientService;
    protected ScopeDeliverableService $scopeService;
    protected ProjectRiskIssueService $riskIssueService;
    protected ProjectResourceBudgetService $resourceBudgetService;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accessService = new ProjectAccessService();
        $this->projectService = new ProjectService($this->accessService);
        $this->lifecycleService = new ProjectLifecycleService($this->projectService);
        $this->clientService = new ClientManagementService();
        $this->scopeService = new ScopeDeliverableService($this->projectService);
        $this->riskIssueService = new ProjectRiskIssueService($this->projectService);
        $this->resourceBudgetService = new ProjectResourceBudgetService($this->projectService);
        $this->db = Database::connect();
    }

    public function testServicesAndControllerInstantiate(): void
    {
        $this->assertInstanceOf(ProjectAccessService::class, $this->accessService);
        $this->assertInstanceOf(ProjectService::class, $this->projectService);
        $this->assertInstanceOf(ProjectLifecycleService::class, $this->lifecycleService);
        $this->assertInstanceOf(ClientManagementService::class, $this->clientService);
        $this->assertInstanceOf(ScopeDeliverableService::class, $this->scopeService);
        $this->assertInstanceOf(ProjectRiskIssueService::class, $this->riskIssueService);
        $this->assertInstanceOf(ProjectResourceBudgetService::class, $this->resourceBudgetService);

        $controller = new ProjectController();
        $this->assertInstanceOf(ProjectController::class, $controller);
    }

    public function testProjectAccessServiceClassificationsAndPermissions(): void
    {
        $ownerContext = ['id' => 1, 'username' => 'owner', 'roles' => ['owner', 'superadmin']];
        $execContext = ['id' => 2, 'username' => 'exec', 'roles' => ['executive', 'board']];
        $devContext = ['id' => 3, 'username' => 'dev', 'roles' => ['developer']];

        // Public & Internal
        $this->assertTrue($this->accessService->canAccessClassification('public', $devContext));
        $this->assertTrue($this->accessService->canAccessClassification('internal', $devContext));

        // Confidential
        $this->assertTrue($this->accessService->canAccessClassification('confidential', $ownerContext));
        $this->assertTrue($this->accessService->canAccessClassification('confidential', $execContext));
        $this->assertFalse($this->accessService->canAccessClassification('confidential', $devContext));

        // Restricted
        $this->assertTrue($this->accessService->canAccessClassification('restricted', $ownerContext));
        $this->assertTrue($this->accessService->canAccessClassification('restricted', $execContext));
        $this->assertFalse($this->accessService->canAccessClassification('restricted', $devContext));
    }

    public function testClientManagementServiceCrud(): void
    {
        $actor = ['id' => 1, 'username' => 'test_owner', 'roles' => ['owner']];

        // 1. Create client
        $client = $this->clientService->createClient([
            'company_name' => 'PT Test Innovation Nusantara',
            'industry'     => 'Artificial Intelligence',
            'status'       => 'active',
            'notes'        => 'Test client unit testing',
        ], $actor);

        $this->assertIsArray($client);
        $this->assertNotEmpty($client['id']);
        $clientId = $client['id'];
        $this->assertEquals('PT Test Innovation Nusantara', $client['company_name']);

        // 2. Add Contact
        $contact = $this->clientService->createContact($clientId, [
            'full_name'  => 'Test Contact Person',
            'job_title'  => 'VP of Tech',
            'email'      => 'vp.tech@test-innovation.id',
            'phone'      => '+62 899-1111-2222',
            'is_primary' => 1,
        ], $actor);

        $this->assertIsArray($contact);
        $this->assertEquals('Test Contact Person', $contact['full_name']);

        // 3. Get Contacts & Detail
        $contacts = $this->clientService->getContacts($clientId);
        $this->assertNotEmpty($contacts);

        $detail = $this->clientService->getClientById($clientId);
        $this->assertNotNull($detail);
        $this->assertCount(1, $detail['contacts']);

        // 4. Update Client
        $updated = $this->clientService->updateClient($clientId, [
            'industry' => 'DeepTech & Automation',
        ], $actor);
        $this->assertEquals('DeepTech & Automation', $updated['industry']);
    }

    public function testProjectCreationAndListingCustomerVsInternal(): void
    {
        $actor = ['id' => 1, 'username' => 'test_owner', 'roles' => ['owner']];

        // Find or create test client
        $client = $this->db->table('project_clients')->get(1)->getRowArray();
        $clientId = $client ? (int)$client['id'] : 1;

        // 1. Create Customer Project
        $custProj = $this->projectService->createProject([
            'name'                  => 'Test Customer Project Automation',
            'project_type'          => 'customer_project',
            'category'              => 'ai_automation',
            'client_id'             => $clientId,
            'description'           => 'Automated test customer project description',
            'proposed_budget'       => 25000000.00,
            'approved_budget_limit' => 20000000.00,
        ], $actor);

        $this->assertNotNull($custProj);
        $this->assertEquals('customer_project', $custProj['project_type']);
        $this->assertStringStartsWith('PRJ-CUST-', $custProj['project_code']);

        // 2. Create Internal Initiative
        $intProj = $this->projectService->createProject([
            'name'                  => 'Test Internal Platform Initiative',
            'project_type'          => 'internal_initiative',
            'category'              => 'internal_platform',
            'description'           => 'Internal test platform R&D',
            'proposed_budget'       => 3500000.00,
            'approved_budget_limit' => 3500000.00,
        ], $actor);

        $this->assertNotNull($intProj);
        $this->assertEquals('internal_initiative', $intProj['project_type']);
        $this->assertStringStartsWith('PRJ-INT-', $intProj['project_code']);

        // 3. Filter Listing
        $custOnly = $this->projectService->getProjects(['project_type' => 'customer_project'], $actor);
        $this->assertNotEmpty($custOnly);
        foreach ($custOnly as $p) {
            $this->assertEquals('customer_project', $p['project_type']);
        }

        $intOnly = $this->projectService->getProjects(['project_type' => 'internal_initiative'], $actor);
        $this->assertNotEmpty($intOnly);
        foreach ($intOnly as $p) {
            $this->assertEquals('internal_initiative', $p['project_type']);
        }
    }

    public function testPortfolioMetricsCalculation(): void
    {
        $actor = ['id' => 1, 'username' => 'owner', 'roles' => ['owner']];
        $metrics = $this->projectService->getPortfolioMetrics($actor);

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('total_projects', $metrics);
        $this->assertArrayHasKey('customer_projects', $metrics);
        $this->assertArrayHasKey('internal_initiatives', $metrics);
        $this->assertArrayHasKey('health_distribution', $metrics);
        $this->assertArrayHasKey('financials', $metrics);

        $this->assertGreaterThan(0, $metrics['total_projects']);
        $this->assertEquals(
            $metrics['total_projects'],
            $metrics['customer_projects'] + $metrics['internal_initiatives']
        );
    }

    public function testProjectLifecycleStateTransitionsAndConstraints(): void
    {
        $actor = ['id' => 1, 'username' => 'owner', 'roles' => ['owner']];
        $client = $this->db->table('project_clients')->get(1)->getRowArray();
        $clientId = $client ? (int)$client['id'] : 1;

        // Create project with budget
        $proj = $this->projectService->createProject([
            'name'                  => 'Lifecycle State Machine Test',
            'project_type'          => 'customer_project',
            'client_id'             => $clientId,
            'lifecycle_stage'       => 'lead',
            'approved_budget_limit' => 10000000.00,
        ], $actor);

        $projectId = $proj['id'];

        // Transition: lead -> qualification
        $t1 = $this->lifecycleService->transitionStage($projectId, 'qualification', 'Qualified with client', $actor);
        $this->assertEquals('qualification', $t1['lifecycle_stage']);

        // Transition: qualification -> proposal
        $t2 = $this->lifecycleService->transitionStage($projectId, 'proposal', 'Proposal presented', $actor);
        $this->assertEquals('proposal', $t2['lifecycle_stage']);

        // Transition: proposal -> contracted
        $t3 = $this->lifecycleService->transitionStage($projectId, 'contracted', 'Contract executed', $actor);
        $this->assertEquals('contracted', $t3['lifecycle_stage']);

        // Verify status history
        $history = $this->lifecycleService->getStatusHistory($projectId);
        $this->assertGreaterThanOrEqual(3, count($history));

        // Attempt invalid stage name should throw InvalidArgumentException
        $this->expectException(\InvalidArgumentException::class);
        $this->lifecycleService->transitionStage($projectId, 'non_existent_stage', 'Invalid test', $actor);
    }

    public function testProjectBriefAndScopeBaselining(): void
    {
        $actor = ['id' => 1, 'username' => 'owner', 'roles' => ['owner']];
        $p = $this->db->table('projects')->get(1)->getRowArray();
        $projectId = (int)$p['id'];

        // 1. Brief Upsert
        $brief = $this->scopeService->upsertProjectBrief($projectId, [
            'background'          => 'Comprehensive test background narrative',
            'business_objectives' => 'Test objective achieving 100% test coverage',
            'success_criteria'    => 'Pass all assertions without warning',
        ], $actor);

        $this->assertIsArray($brief);
        $this->assertEquals('Comprehensive test background narrative', $brief['background']);

        // 2. Scope Baseline Creation
        $scope = $this->scopeService->createScopeBaseline($projectId, [
            'version_number'    => '1.1',
            'scope_summary'     => 'Scope baseline test iteration',
            'in_scope_items'    => ['Feature A', 'Feature B'],
            'out_of_scope_items'=> ['Feature Z'],
            'is_baseline'       => 1,
        ], $actor);

        $this->assertIsArray($scope);
        $this->assertEquals('1.1', $scope['version_number']);
        $this->assertEquals(1, $scope['is_baseline']);

        // Verify only 1 baseline is active
        $allScopes = $this->scopeService->getProjectScopes($projectId);
        $activeCount = 0;
        foreach ($allScopes as $s) {
            if ((int)$s['is_baseline'] === 1) {
                $activeCount++;
            }
        }
        $this->assertEquals(1, $activeCount);
    }

    public function testMilestonesAndProgressRecalculation(): void
    {
        $actor = ['id' => 1, 'username' => 'owner', 'roles' => ['owner']];
        $p = $this->db->table('projects')->get(1)->getRowArray();
        $projectId = (int)$p['id'];

        // 1. Create Milestone
        $ms = $this->scopeService->createMilestone($projectId, [
            'title'            => 'Test Milestone Sprint Completion',
            'is_critical_path' => 1,
            'status'           => 'pending',
            'target_date'      => date('Y-m-d', strtotime('+7 days')),
        ], $actor);

        $this->assertIsArray($ms);
        $this->assertEquals('Test Milestone Sprint Completion', $ms['title']);
        $msId = (int)$ms['id'];

        // 2. Update Milestone to achieved
        $updatedMs = $this->scopeService->updateMilestone($msId, [
            'status' => 'achieved',
        ], $actor);
        $this->assertEquals('achieved', $updatedMs['status']);

        // 3. Recalculate progress
        $progress = $this->projectService->recalculateProjectProgress($projectId);
        $this->assertGreaterThanOrEqual(0, $progress);
        $this->assertLessThanOrEqual(100, $progress);
    }

    public function testDeliverablesLifecycleAndClientAcceptance(): void
    {
        $actor = ['id' => 1, 'username' => 'owner', 'roles' => ['owner']];
        $p = $this->db->table('projects')->get(1)->getRowArray();
        $projectId = (int)$p['id'];

        // 1. Create deliverable
        $del = $this->scopeService->createDeliverable($projectId, [
            'title'               => 'Test Architecture Pack Artifact',
            'acceptance_criteria' => 'Zero defects in test review',
            'due_date'            => date('Y-m-d', strtotime('+10 days')),
        ], $actor);

        $this->assertIsArray($del);
        $delId = (int)$del['id'];
        $this->assertEquals('draft', $del['review_status']);

        // 2. Submit deliverable
        $submitted = $this->scopeService->submitDeliverableForReview($delId, 'https://storage.mce.local/deliverable-v1.zip', $actor);
        $this->assertEquals('submitted', $submitted['review_status']);

        // 3. Review deliverable
        $reviewed = $this->scopeService->reviewDeliverable($delId, 'accepted', 'Approved by Tech Lead', $actor);
        $this->assertEquals('accepted', $reviewed['review_status']);

        // 4. Client Accept
        $clientAccepted = $this->scopeService->clientAcceptDeliverable($delId, $actor);
        $this->assertEquals(1, (int)$clientAccepted['client_accepted']);
        $this->assertNotNull($clientAccepted['client_accepted_at']);
    }

    public function testRisksAndIssuesManagement(): void
    {
        $actor = ['id' => 1, 'username' => 'owner', 'roles' => ['owner']];
        $p = $this->db->table('projects')->get(1)->getRowArray();
        $projectId = (int)$p['id'];

        // 1. Create Risk
        $risk = $this->riskIssueService->createRisk($projectId, [
            'title'           => 'Vendor API Rate Limit Bottleneck',
            'probability'     => 'medium',
            'impact'          => 'high',
            'mitigation_plan' => 'Implement local redis query caching',
        ], $actor);

        $this->assertIsArray($risk);
        $riskId = (int)$risk['id'];

        $updatedRisk = $this->riskIssueService->updateRisk($riskId, ['status' => 'mitigated'], $actor);
        $this->assertEquals('mitigated', $updatedRisk['status']);

        // 2. Create Issue
        $issue = $this->riskIssueService->createIssue($projectId, [
            'title'    => 'Database Connection Pool Exhaustion on Load Test',
            'severity' => 'major',
            'priority' => 'high',
        ], $actor);

        $this->assertIsArray($issue);
        $issueId = (int)$issue['id'];
        $this->assertEquals('open', $issue['status']);

        // 3. Resolve Issue
        $resolvedIssue = $this->riskIssueService->resolveIssue($issueId, 'Max connections tuned from 50 to 200', $actor);
        $this->assertEquals('resolved', $resolvedIssue['status']);
        $this->assertNotNull($resolvedIssue['resolved_at']);
    }

    public function testChangeRequestGovernanceAndDoA(): void
    {
        $actor = ['id' => 1, 'username' => 'owner', 'roles' => ['owner', 'superadmin']];
        $p = $this->db->table('projects')->get(1)->getRowArray();
        $projectId = (int)$p['id'];

        // Create Change Request
        $cr = $this->riskIssueService->createChangeRequest($projectId, [
            'title'                => 'Additional Security Hardening Scope',
            'description'          => 'Add penetration testing and SOC2 audit preparation',
            'budget_impact_amount' => 4000000.00,
            'schedule_impact_days' => 5,
        ], $actor);

        $this->assertIsArray($cr);
        $crId = (int)$cr['id'];
        $this->assertEquals('requested', $cr['status']);

        // Evaluate CR (Approve)
        $approvedCr = $this->riskIssueService->evaluateChangeRequest($crId, 'approved', 'Authorized by owner', $actor);
        $this->assertEquals('approved', $approvedCr['status']);
        $this->assertNotNull($approvedCr['approved_at']);
    }

    public function testTeamAssignmentAndTaskIntegration(): void
    {
        $actor = ['id' => 1, 'username' => 'owner', 'roles' => ['owner']];
        $p = $this->db->table('projects')->get(1)->getRowArray();
        $projectId = (int)$p['id'];

        // 1. Team Members Query
        $team = $this->resourceBudgetService->getTeamMembers($projectId);
        $this->assertIsArray($team);

        // 2. Resource Allocations Query
        $resources = $this->resourceBudgetService->getResourceAllocations($projectId);
        $this->assertIsArray($resources);

        // 3. Budget Summary
        $budgetSummary = $this->resourceBudgetService->getBudgetSummary($projectId);
        $this->assertIsArray($budgetSummary);
        $this->assertArrayHasKey('proposed_budget', $budgetSummary);
        $this->assertArrayHasKey('approved_budget', $budgetSummary);
        $this->assertArrayHasKey('actual_cost_spent', $budgetSummary);

        // 4. Task Linkage
        $linkedTasks = $this->projectService->getLinkedTasks($projectId);
        $this->assertIsArray($linkedTasks);
    }
}
