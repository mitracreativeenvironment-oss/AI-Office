<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\KpiCatalogService;
use App\Services\MetricCalculationService;
use App\Services\CorporatePerformanceService;
use App\Services\ExecutivePerformanceService;
use App\Services\OrganizationalPerformanceService;
use App\Services\ProjectPerformanceService;
use App\Services\AgentEvaluationService;
use App\Services\CoreValuesAssessmentService;
use App\Services\EvaluationWorkflowService;
use App\Services\ImprovementPlanService;
use App\Services\WelfareBonusAdapterService;
use App\Services\ExecutiveReportingService;
use App\Controllers\Api\PerformanceManagementController;
use Config\Database;

/**
 * @internal
 */
final class PerformanceManagementTest extends CIUnitTestCase
{
    protected KpiCatalogService $kpiService;
    protected MetricCalculationService $calcService;
    protected CorporatePerformanceService $corpService;
    protected ExecutivePerformanceService $execService;
    protected OrganizationalPerformanceService $orgService;
    protected ProjectPerformanceService $projService;
    protected AgentEvaluationService $agentService;
    protected CoreValuesAssessmentService $coreValuesService;
    protected EvaluationWorkflowService $workflowService;
    protected ImprovementPlanService $pipService;
    protected WelfareBonusAdapterService $bonusService;
    protected ExecutiveReportingService $reportService;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect();
        $this->kpiService = new KpiCatalogService();
        $this->calcService = new MetricCalculationService();
        $this->corpService = new CorporatePerformanceService();
        $this->execService = new ExecutivePerformanceService();
        $this->orgService = new OrganizationalPerformanceService();
        $this->projService = new ProjectPerformanceService();
        $this->agentService = new AgentEvaluationService();
        $this->coreValuesService = new CoreValuesAssessmentService();
        $this->workflowService = new EvaluationWorkflowService();
        $this->pipService = new ImprovementPlanService();
        $this->bonusService = new WelfareBonusAdapterService();
        $this->reportService = new ExecutiveReportingService();
    }

    public function testServicesAndControllerInstantiate(): void
    {
        $this->assertInstanceOf(KpiCatalogService::class, $this->kpiService);
        $this->assertInstanceOf(MetricCalculationService::class, $this->calcService);
        $this->assertInstanceOf(CorporatePerformanceService::class, $this->corpService);
        $this->assertInstanceOf(ExecutivePerformanceService::class, $this->execService);
        $this->assertInstanceOf(OrganizationalPerformanceService::class, $this->orgService);
        $this->assertInstanceOf(ProjectPerformanceService::class, $this->projService);
        $this->assertInstanceOf(AgentEvaluationService::class, $this->agentService);
        $this->assertInstanceOf(CoreValuesAssessmentService::class, $this->coreValuesService);
        $this->assertInstanceOf(EvaluationWorkflowService::class, $this->workflowService);
        $this->assertInstanceOf(ImprovementPlanService::class, $this->pipService);
        $this->assertInstanceOf(WelfareBonusAdapterService::class, $this->bonusService);
        $this->assertInstanceOf(ExecutiveReportingService::class, $this->reportService);

        $controller = new PerformanceManagementController();
        $this->assertInstanceOf(PerformanceManagementController::class, $controller);
    }

    public function testKpiCategoriesAndCatalog(): void
    {
        $categories = $this->kpiService->listCategories();
        $this->assertNotEmpty($categories);
        $this->assertGreaterThanOrEqual(10, count($categories));

        $kpis = $this->kpiService->listKpis();
        $this->assertNotEmpty($kpis);
        $this->assertGreaterThanOrEqual(10, count($kpis));

        $firstKpi = $this->kpiService->getKpi((int)$kpis[0]['id']);
        $this->assertNotNull($firstKpi);
        $this->assertEquals($kpis[0]['kpi_code'], $firstKpi['kpi_code']);
    }

    public function testMetricCalculationServiceExecution(): void
    {
        $kpi = $this->db->table('kpi_definitions')->where('kpi_code', 'KPI-CORP-PRJ-ONTIME')->get()->getRowArray();
        $this->assertNotNull($kpi, "KPI-CORP-PRJ-ONTIME must exist from seeder.");

        $start = date('Y-m-01');
        $end = date('Y-m-t');
        $result = $this->calcService->calculateKpi((int)$kpi['id'], $start, $end, 'UnitTester');

        $this->assertArrayHasKey('actual_value', $result);
        $this->assertArrayHasKey('variance_pct', $result);
        $this->assertEquals(1, $result['is_double_count_safe']);
        $this->assertEquals('v1.0', $result['calculation_version']);

        $history = $this->calcService->getKpiResultsHistory((int)$kpi['id']);
        $this->assertNotEmpty($history);
    }

    public function testCorporatePerformanceAndOperationalPulse(): void
    {
        $scorecard = $this->corpService->getCorporateScorecard();
        $this->assertArrayHasKey('period', $scorecard);
        $this->assertArrayHasKey('metrics', $scorecard);
        $this->assertArrayHasKey('scorecard_categories', $scorecard);

        $pulse = $this->corpService->getOperationalPulse();
        $this->assertArrayHasKey('overall_health_pct', $pulse);
        $this->assertArrayHasKey('operational_status', $pulse);
        $this->assertArrayHasKey('active_agent_count', $pulse);
        $this->assertGreaterThanOrEqual(0, $pulse['overall_health_pct']);
    }

    public function testExecutivePerformanceDistinctMandates(): void
    {
        $overview = $this->execService->getExecutiveOverview();
        $this->assertArrayHasKey('mandates', $overview);
        $this->assertArrayHasKey('human_owner', $overview['mandates']);
        $this->assertArrayHasKey('executive_board', $overview['mandates']);
        $this->assertArrayHasKey('director_ai', $overview['mandates']);
        $this->assertArrayHasKey('doa_governance', $overview);

        $this->assertEquals('Strategic Direction & Final Ratification', $overview['mandates']['human_owner']['title']);
        $this->assertEquals('Continuous Executive Operations & Autonomous Governance', $overview['mandates']['executive_board']['title']);
        $this->assertEquals('Chief Operational AI Executive & System Leadership', $overview['mandates']['director_ai']['title']);
    }

    public function testOrganizationalPerformanceAndDivisionalComparison(): void
    {
        $comparison = $this->orgService->getDivisionalComparison();
        $this->assertNotEmpty($comparison);
        $this->assertGreaterThanOrEqual(1, count($comparison));

        $first = $comparison[0];
        $this->assertArrayHasKey('division_id', $first);
        $this->assertArrayHasKey('division_name', $first);
        $this->assertArrayHasKey('agent_count', $first);
        $this->assertArrayHasKey('efficiency_score', $first);
        $this->assertArrayHasKey('health_status', $first);
    }

    public function testProjectPortfolioPerformance(): void
    {
        $portfolio = $this->projService->getPortfolioPerformance();
        $this->assertArrayHasKey('portfolio_summary', $portfolio);
        $this->assertArrayHasKey('projects', $portfolio);
        $this->assertArrayHasKey('total_projects', $portfolio['portfolio_summary']);
    }

    public function testAgentEvaluationFairScoringAndScheduleProtection(): void
    {
        $agent5 = $this->db->table('ai_agent_registry')->where('id', 5)->get()->getRowArray();
        $this->assertNotNull($agent5, "Agent 5 must exist.");

        $profile = $this->agentService->getAgentPerformanceProfile(5);
        $this->assertNotNull($profile);
        $this->assertEquals(5, $profile['agent']['id']);
        $this->assertArrayHasKey('recent_evaluations', $profile);
        $this->assertArrayHasKey('core_values', $profile);

        // Verify SHA-256 audit hash and scores on latest evaluation
        if (!empty($profile['recent_evaluations'])) {
            $latest = $profile['recent_evaluations'][0];
            $this->assertNotEmpty($latest['audit_hash']);
            $this->assertEquals(64, strlen($latest['audit_hash']), "Audit hash must be 64-char SHA-256.");
            $this->assertGreaterThanOrEqual(0, $latest['composite_score']);
        }
    }

    public function testCoreValuesAssessmentsSevenValues(): void
    {
        $expectedValues = [
            'integrity', 'innovation', 'collaboration', 'excellence',
            'adaptability', 'customer_centric', 'sustainability'
        ];

        $assessments = $this->coreValuesService->getAgentAssessments(5);
        $this->assertNotEmpty($assessments);

        $foundValues = array_column($assessments, 'core_value');
        foreach ($expectedValues as $val) {
            $this->assertContains($val, $foundValues, "Core value '{$val}' must be assessed.");
        }

        $summary = $this->coreValuesService->getCoreValuesSummary();
        $this->assertNotEmpty($summary);
    }

    public function testEvaluationWorkflowCycleTransitions(): void
    {
        $cycles = $this->workflowService->listCycles();
        $this->assertNotEmpty($cycles);

        $cycle = $this->workflowService->getCycle((int)$cycles[0]['id']);
        $this->assertNotNull($cycle);
        $this->assertArrayHasKey('evaluations', $cycle);

        // Create new draft cycle and transition
        $newCycle = $this->workflowService->createCycle([
            'title'        => 'Test Cycle ' . uniqid(),
            'cycle_type'   => 'monthly',
            'period_start' => date('Y-m-01'),
            'period_end'   => date('Y-m-t'),
            'status'       => 'draft',
        ], 1);

        $this->assertNotEmpty($newCycle['id']);

        $transitioned = $this->workflowService->transitionCycleStatus($newCycle['id'], 'open', 1);
        $this->assertEquals('open', $transitioned['status']);

        $toCollection = $this->workflowService->transitionCycleStatus($newCycle['id'], 'data_collection', 1);
        $this->assertEquals('data_collection', $toCollection['status']);
    }

    public function testImprovementPlanLifecycleAndTaskLinkage(): void
    {
        // 1. Create PIP
        $plan = $this->pipService->createPlan([
            'target_type'            => 'agent',
            'target_id'              => 5,
            'responsible_agent_id'   => 5,
            'objective'              => 'Unit Test PIP Objective',
            'identified_gap'         => 'Minor documentation latency',
            'recommended_action'     => 'Complete review documentation modules',
            'target_completion_date' => date('Y-m-d', strtotime('+14 days')),
            'success_criteria'       => 'Documentation completed with 100% SLA',
            'status'                 => 'proposed',
        ], 1);

        $this->assertNotEmpty($plan['id']);
        $this->assertEquals('proposed', $plan['status']);

        // 2. Transition to approved then in_progress
        $approved = $this->pipService->transitionStatus($plan['id'], 'approved', 1);
        $this->assertEquals('approved', $approved['status']);

        $inProgress = $this->pipService->transitionStatus($plan['id'], 'in_progress', 1);
        $this->assertEquals('in_progress', $inProgress['status']);

        // 3. Update Progress
        $updated = $this->pipService->updatePlan($plan['id'], [
            'progress_percent' => 75,
        ], 1);
        $this->assertEquals(75, $updated['progress_percent']);

        // 4. Complete Plan
        $completed = $this->pipService->transitionStatus($plan['id'], 'completed', 1, 'All modules verified in sandbox.');
        $this->assertEquals('completed', $completed['status']);
        $this->assertEquals(100, $completed['progress_percent']);
        $this->assertNotEmpty($completed['completed_at']);
    }

    public function testWelfareBonusFinancialDoAGatekeeper(): void
    {
        // 1. Recommend Level A bonus (> IDR 5M)
        $bonusLevelA = $this->bonusService->recommendBonus([
            'evaluation_id'            => 1,
            'agent_id'                 => 5,
            'bonus_category'           => 'high_complexity_delivery',
            'recommended_ticket_bonus' => 25.00,
            'financial_impact_idr'     => 6250000.00, // > 5M IDR -> Level A
            'justification'            => 'Critical complex deployment delivery',
        ], 1);

        $this->assertEquals('level_a', $bonusLevelA['financial_approval_level']);
        $this->assertEquals('pending', $bonusLevelA['approval_status']);

        // 2. Attempt unauthorized approval by non-owner role should fail
        $thrown = false;
        try {
            $this->bonusService->approveBonus($bonusLevelA['id'], 2, 'team_lead');
        } catch (\RuntimeException $e) {
            $thrown = true;
            $this->assertStringContainsString('Delegated Authority Violation', $e->getMessage());
        }
        $this->assertTrue($thrown, "Non-owner role must not be allowed to approve Level A bonus.");

        // 3. Authorized approval by Human Owner succeeds
        $approvedA = $this->bonusService->approveBonus($bonusLevelA['id'], 1, 'human_owner');
        $this->assertEquals('approved', $approvedA['approval_status']);

        // 4. Disburse bonus
        $disbursed = $this->bonusService->disburseBonus($bonusLevelA['id'], null, 1);
        $this->assertEquals('disbursed', $disbursed['approval_status']);
        $this->assertNotEmpty($disbursed['disbursed_at']);

        // 5. Check eligibility algorithm
        $eligibility = $this->bonusService->evaluateEligibility(5, 1);
        $this->assertArrayHasKey('eligible', $eligibility);
    }

    public function testExecutiveReportingGenerationAndPublishing(): void
    {
        $report = $this->reportService->generateReport([
            'report_type'  => 'corporate_overview',
            'period_start' => date('Y-m-01'),
            'period_end'   => date('Y-m-t'),
            'title'        => 'Test Corporate Overview Unit Test',
        ], 1);

        $this->assertNotEmpty($report['id']);
        $this->assertEquals('corporate_overview', $report['report_type']);
        $this->assertNotEmpty($report['highlights']);
        $this->assertNotEmpty($report['kpi_summary']);

        // Publish and Ratify
        $published = $this->reportService->publishReport($report['id'], 1);
        $this->assertEquals('published', $published['approval_status']);

        $ratified = $this->reportService->ratifyReport($report['id'], 1);
        $this->assertEquals('ratified', $ratified['approval_status']);
    }
}
