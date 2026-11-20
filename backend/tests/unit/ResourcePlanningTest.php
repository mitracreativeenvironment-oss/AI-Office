<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\ResourceRegistryService;
use App\Services\AgentCapacityService;
use App\Services\OrganizationalCapacityService;
use App\Services\SkillCoverageService;
use App\Services\WorkloadManagementService;
use App\Services\ResourceRequestService;
use App\Services\CapacityReservationService;
use App\Services\ResourceConflictService;
use App\Services\PortfolioPrioritizationService;
use App\Services\CapacityForecastService;
use App\Controllers\Api\ResourcePlanningController;
use Config\Database;

/**
 * @internal
 */
final class ResourcePlanningTest extends CIUnitTestCase
{
    protected ResourceRegistryService $registryService;
    protected AgentCapacityService $agentCapacityService;
    protected OrganizationalCapacityService $orgCapacityService;
    protected SkillCoverageService $skillCoverageService;
    protected WorkloadManagementService $workloadService;
    protected ResourceRequestService $requestService;
    protected CapacityReservationService $reservationService;
    protected ResourceConflictService $conflictService;
    protected PortfolioPrioritizationService $prioritizationService;
    protected CapacityForecastService $forecastService;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect();
        $this->registryService = new ResourceRegistryService($this->db);
        $this->agentCapacityService = new AgentCapacityService($this->db);
        $this->orgCapacityService = new OrganizationalCapacityService($this->db, $this->agentCapacityService);
        $this->skillCoverageService = new SkillCoverageService($this->db);
        $this->workloadService = new WorkloadManagementService($this->db, $this->agentCapacityService);
        $this->requestService = new ResourceRequestService($this->db, $this->agentCapacityService, $this->registryService);
        $this->reservationService = new CapacityReservationService($this->db);
        $this->conflictService = new ResourceConflictService($this->db, $this->agentCapacityService);
        $this->prioritizationService = new PortfolioPrioritizationService($this->db);
        $this->forecastService = new CapacityForecastService($this->db, $this->orgCapacityService);
    }

    public function testServicesAndControllerInstantiate(): void
    {
        $this->assertInstanceOf(ResourceRegistryService::class, $this->registryService);
        $this->assertInstanceOf(AgentCapacityService::class, $this->agentCapacityService);
        $this->assertInstanceOf(OrganizationalCapacityService::class, $this->orgCapacityService);
        $this->assertInstanceOf(SkillCoverageService::class, $this->skillCoverageService);
        $this->assertInstanceOf(WorkloadManagementService::class, $this->workloadService);
        $this->assertInstanceOf(ResourceRequestService::class, $this->requestService);
        $this->assertInstanceOf(CapacityReservationService::class, $this->reservationService);
        $this->assertInstanceOf(ResourceConflictService::class, $this->conflictService);
        $this->assertInstanceOf(PortfolioPrioritizationService::class, $this->prioritizationService);
        $this->assertInstanceOf(CapacityForecastService::class, $this->forecastService);

        $controller = new ResourcePlanningController();
        $this->assertInstanceOf(ResourcePlanningController::class, $controller);
    }

    public function testResourceRegistryCrudAndCapacity(): void
    {
        $actor = ['id' => 1, 'username' => 'test_owner', 'name' => 'Test Owner', 'type' => 'user'];
        $code = 'RES-TEST-' . strtoupper(substr(uniqid(), -4));

        $created = $this->registryService->createResource([
            'resource_code'   => $code,
            'name'            => 'Test AI Model Service Pool',
            'category'        => 'ai_model_service',
            'unit_of_measure' => 'tokens',
            'total_capacity'  => 500000.00,
            'unit_cost_idr'   => 120.00,
        ], $actor);

        $this->assertNotNull($created);
        $this->assertEquals($code, $created['resource_code']);
        $this->assertEquals(500000.00, (float)$created['total_capacity']);
        $this->assertEquals(500000.00, (float)$created['available_capacity']);

        // Update resource
        $updated = $this->registryService->updateResource((int)$created['id'], [
            'description'        => 'Updated test description',
            'allocated_capacity' => 100000.00,
        ], $actor);

        $this->assertEquals('Updated test description', $updated['description']);
        $this->assertEquals(400000.00, (float)$updated['available_capacity']);
        $this->assertEquals(20.00, (float)$updated['utilization_percent']);

        // Delete resource
        $deleted = $this->registryService->deleteResource((int)$created['id'], $actor);
        $this->assertTrue($deleted);
        $this->assertNull($this->registryService->getResourceById((int)$created['id']));
    }

    public function testAgentCapacityCalculationFormula(): void
    {
        $agents = $this->agentCapacityService->getAllAgentsCapacity();
        $this->assertNotEmpty($agents);

        $first = $agents[0];
        $this->assertArrayHasKey('agent_id', $first);
        $this->assertArrayHasKey('gross_capacity_hours', $first);
        $this->assertArrayHasKey('scheduled_capacity_hours', $first);
        $this->assertArrayHasKey('allocated_capacity_hours', $first);
        $this->assertArrayHasKey('reserved_capacity_hours', $first);
        $this->assertArrayHasKey('unavailable_capacity_hours', $first);
        $this->assertArrayHasKey('available_capacity_hours', $first);
        $this->assertArrayHasKey('utilization_percent', $first);
        $this->assertArrayHasKey('utilization_classification', $first);

        // Verify formula: Available = Scheduled - Allocated - Reserved - Unavailable
        $scheduled = (float)$first['scheduled_capacity_hours'];
        $allocated = (float)$first['allocated_capacity_hours'];
        $reserved = (float)$first['reserved_capacity_hours'];
        $available = (float)$first['available_capacity_hours'];

        $expectedAvailable = max(0.00, $scheduled - $allocated - $reserved);
        $this->assertEquals($expectedAvailable, $available);
    }

    public function testAgentCapacityUtilizationClassification(): void
    {
        $agents = $this->agentCapacityService->getAllAgentsCapacity();
        $validClasses = ['underutilized', 'balanced', 'high_utilization', 'overallocated', 'unavailable'];

        foreach ($agents as $agent) {
            $this->assertContains($agent['utilization_classification'], $validClasses);
        }
    }

    public function testOrganizationalCapacitySummaryAndRollup(): void
    {
        $summary = $this->orgCapacityService->getUnitCapacitySummary();
        $this->assertGreaterThan(0, $summary['headcount']);
        $this->assertGreaterThan(0, $summary['gross_capacity_hours']);
        $this->assertGreaterThanOrEqual(0, $summary['utilization_percent']);
        $this->assertArrayHasKey('classification_counts', $summary);

        $comparison = $this->orgCapacityService->getDivisionalComparison();
        $this->assertIsArray($comparison);
        $this->assertNotEmpty($comparison);
        $this->assertArrayHasKey('division_code', $comparison[0]);

        $overview = $this->orgCapacityService->getOrganizationCapacityOverview();
        $this->assertArrayHasKey('overview', $overview);
        $this->assertArrayHasKey('divisional_breakdown', $overview);
        $this->assertArrayHasKey('operational_resources', $overview);
    }

    public function testSkillCoverageMatrixAndBottlenecks(): void
    {
        $matrix = $this->skillCoverageService->getSkillCoverageMatrix();
        $this->assertNotEmpty($matrix);
        $this->assertArrayHasKey('skill_code', $matrix[0]);
        $this->assertArrayHasKey('total_agents_count', $matrix[0]);
        $this->assertArrayHasKey('verified_agents_count', $matrix[0]);
        $this->assertArrayHasKey('proficiency_breakdown', $matrix[0]);

        $bottlenecks = $this->skillCoverageService->getSkillBottlenecks();
        $this->assertIsArray($bottlenecks);
    }

    public function testSingleAgentDependenciesDetection(): void
    {
        $spof = $this->skillCoverageService->getSingleAgentDependencies();
        $this->assertIsArray($spof);
        foreach ($spof as $item) {
            $this->assertArrayHasKey('skill_code', $item);
            $this->assertArrayHasKey('sole_agent', $item);
            $this->assertArrayHasKey('recommendation', $item);
        }
    }

    public function testWorkloadHeatmapAndSummary(): void
    {
        $heatmap = $this->workloadService->getWorkloadHeatmap(4);
        $this->assertNotEmpty($heatmap);
        $this->assertArrayHasKey('agent_code', $heatmap[0]);
        $this->assertArrayHasKey('timeline', $heatmap[0]);
        $this->assertCount(4, $heatmap[0]['timeline']);

        $summary = $this->workloadService->getWorkloadSummary();
        $this->assertArrayHasKey('total_agents_evaluated', $summary);
        $this->assertArrayHasKey('counts_by_tier', $summary);
        $this->assertArrayHasKey('overall_utilization_pct', $summary);
    }

    public function testCapacityReservationsLifecycle(): void
    {
        $actor = ['id' => 1, 'username' => 'test_owner', 'name' => 'Test Owner', 'type' => 'user'];
        $code = 'RSV-TEST-' . strtoupper(substr(uniqid(), -4));

        $created = $this->reservationService->createReservation([
            'reservation_code'        => $code,
            'title'                   => 'Test Internal Platform R&D',
            'reservation_type'        => 'platform_rnd',
            'reserved_percentage'     => 15,
            'reserved_hours_per_week' => 6.00,
        ], $actor);

        $this->assertNotNull($created);
        $this->assertEquals($code, $created['reservation_code']);
        $this->assertEquals('active', $created['status']);

        // Update
        $updated = $this->reservationService->updateReservation((int)$created['id'], [
            'reserved_percentage' => 20,
        ], $actor);
        $this->assertEquals(20, (int)$updated['reserved_percentage']);

        // Conclude / Deactivate
        $ok = $this->reservationService->deactivateReservation((int)$created['id'], $actor);
        $this->assertTrue($ok);
        $concluded = $this->reservationService->getReservationById((int)$created['id']);
        $this->assertEquals('concluded', $concluded['status']);
    }

    public function testResourceRequestLifecycleAndApproval(): void
    {
        $actor = ['id' => 1, 'username' => 'test_owner', 'name' => 'Test Owner', 'type' => 'user'];
        $code = 'REQ-TEST-' . strtoupper(substr(uniqid(), -4));

        // Create under DoA Level B (<= 5,000,000 IDR)
        $reqB = $this->requestService->createRequest([
            'request_code'       => $code,
            'title'              => 'Junior Developer Support',
            'resource_category'  => 'workforce_agent',
            'hours_per_week'     => 10.00,
            'estimated_cost_idr' => 2000000.00,
        ], $actor);

        $this->assertEquals('draft', $reqB['lifecycle_status']);
        $this->assertEquals('B', $reqB['approval_authority_level']);

        // Submit
        $submitted = $this->requestService->submitRequest((int)$reqB['id'], $actor);
        $this->assertEquals('submitted', $submitted['lifecycle_status']);

        // Assess capacity
        $assessed = $this->requestService->assessCapacity((int)$reqB['id'], 'Verified capacity sufficient', $actor);
        $this->assertEquals('capacity_assessed', $assessed['lifecycle_status']);
        $this->assertEquals(1, (int)$assessed['is_capacity_sufficient']);

        // Approve
        $approved = $this->requestService->approveRequest((int)$reqB['id'], $actor);
        $this->assertEquals('approved', $approved['lifecycle_status']);
        $this->assertEquals(1, (int)$approved['approved_by_user_id']);

        // Allocate
        $allocated = $this->requestService->allocateRequest((int)$reqB['id'], [
            'allocated_hours_per_week' => 10.00,
            'allocated_percentage'     => 25,
            'unit_cost_idr'            => 200000.00,
        ], $actor);
        $this->assertEquals('allocated', $allocated['lifecycle_status']);

        // Verify DoA Level A for > 5M IDR
        $codeA = 'REQ-TEST-A-' . strtoupper(substr(uniqid(), -4));
        $reqA = $this->requestService->createRequest([
            'request_code'       => $codeA,
            'title'              => 'Large Scale Cluster Procurement',
            'estimated_cost_idr' => 8000000.00,
        ], $actor);
        $this->assertEquals('A', $reqA['approval_authority_level']);
    }

    public function testResourceConflictDetectionEngine(): void
    {
        $actor = ['id' => 1, 'username' => 'test_owner', 'name' => 'Test Owner', 'type' => 'user'];
        $conflicts = $this->conflictService->detectAllConflicts($actor);
        $this->assertIsArray($conflicts);

        if (!empty($conflicts)) {
            $first = $conflicts[0];
            $this->assertArrayHasKey('conflict_type', $first);
            $this->assertArrayHasKey('severity', $first);
            $this->assertArrayHasKey('resolution_status', $first);

            // Resolve conflict
            $resolved = $this->conflictService->resolveConflict((int)$first['id'], 'Mitigated by test runner', $actor);
            $this->assertEquals('resolved', $resolved['resolution_status']);
        }
    }

    public function testPortfolioPrioritizationScoringAndRank(): void
    {
        $actor = ['id' => 1, 'username' => 'test_owner', 'name' => 'Test Owner', 'type' => 'user'];

        // Fetch a project
        $project = $this->db->table('projects')->get(1)->getRowArray();
        $this->assertNotNull($project);
        $projectId = (int)$project['id'];

        $evaluated = $this->prioritizationService->evaluateProjectPriority($projectId, [
            'evaluation_period'         => '2026-Q4',
            'strategic_alignment_score' => 90.00,
            'customer_commitment_score' => 95.00,
            'business_value_score'      => 85.00,
            'urgency_score'             => 80.00,
            'feasibility_score'         => 85.00,
            'risk_penalty_score'        => 10.00,
        ], $actor);

        $this->assertNotNull($evaluated);
        $this->assertGreaterThan(80.00, (float)$evaluated['composite_priority_score']);
        $this->assertEquals('tier_1_critical', $evaluated['priority_tier']);

        // Override
        $overridden = $this->prioritizationService->overridePriority($projectId, 5, 'tier_2_high', 'Board adjustment', $actor);
        $this->assertEquals(5, (int)$overridden['calculated_rank']);
        $this->assertEquals('tier_2_high', $overridden['priority_tier']);
        $this->assertEquals(1, (int)$overridden['is_overridden']);
    }

    public function testCapacityForecastAndGapAnalysis(): void
    {
        $actor = ['id' => 1, 'username' => 'test_owner', 'name' => 'Test Owner', 'type' => 'user'];

        // Generate forecast snapshot
        $snapshot = $this->forecastService->generateForecastSnapshot('2026-12-14', '2026-12-20');
        $this->assertNotNull($snapshot);
        $this->assertEquals(1, (int)$snapshot['is_forecast_data']);
        $this->assertArrayHasKey('scheduled_capacity_hours', $snapshot);

        // Gap analysis
        $code = 'GAP-TEST-' . strtoupper(substr(uniqid(), -4));
        $gap = $this->forecastService->createGapAnalysis([
            'gap_code'                 => $code,
            'gap_category'             => 'skill_shortage',
            'severity'                 => 'high',
            'required_capacity_hours'  => 30.00,
            'available_capacity_hours' => 10.00,
            'recommended_action'       => 'train_existing_agent',
        ], $actor);

        $this->assertNotNull($gap);
        $this->assertEquals(20.00, (float)$gap['deficit_hours']);
        $this->assertEquals('identified', $gap['status']);

        // Resolve gap
        $resolved = $this->forecastService->resolveGapAnalysis((int)$gap['id'], 'Agent assigned to training', $actor);
        $this->assertEquals('resolved', $resolved['status']);
    }
}
