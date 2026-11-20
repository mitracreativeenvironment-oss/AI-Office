<?php

namespace App\Controllers\Api;

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
use CodeIgniter\HTTP\ResponseInterface;

class PerformanceManagementController extends BaseApiController
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

    public function __construct()
    {
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

    protected function getUserContext(): array
    {
        $u = $this->request->authenticatedUser ?? null;
        if (!$u) {
            return [
                'id'       => 1,
                'username' => 'system_owner',
                'name'     => 'System Owner',
                'roles'    => ['superadmin', 'owner', 'human_owner'],
                'unit_id'  => 1,
                'agent_id' => null,
                'type'     => 'user',
            ];
        }

        if (is_object($u)) {
            $id = $u->id ?? 1;
            $username = $u->username ?? 'user';
            $name = $u->name ?? 'User';
            $roles = $u->roles ?? ['human_owner'];
            $unitId = $u->unit_id ?? 1;
            $agentId = $u->agent_id ?? null;
            $type = $u->type ?? 'user';
        } else {
            $id = $u['id'] ?? 1;
            $username = $u['username'] ?? 'user';
            $name = $u['name'] ?? 'User';
            $roles = $u['roles'] ?? ['human_owner'];
            $unitId = $u['unit_id'] ?? 1;
            $agentId = $u['agent_id'] ?? null;
            $type = $u['type'] ?? 'user';
        }

        return [
            'id'       => (int)$id,
            'username' => $username,
            'name'     => $name,
            'roles'    => (array)$roles,
            'unit_id'  => (int)$unitId,
            'agent_id' => $agentId ? (int)$agentId : null,
            'type'     => $type,
        ];
    }

    // -------------------------------------------------------------------------
    // 1. KPI Catalog & Categories
    // -------------------------------------------------------------------------

    public function getCategories(): ResponseInterface
    {
        try {
            $categories = $this->kpiService->listCategories();
            return $this->response->setJSON(['status' => 'success', 'data' => $categories]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getKpis(): ResponseInterface
    {
        try {
            $filters = $this->request->getGet();
            $kpis = $this->kpiService->listKpis($filters);
            return $this->response->setJSON(['status' => 'success', 'data' => $kpis]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getKpi($id): ResponseInterface
    {
        try {
            $kpi = $this->kpiService->getKpi((int)$id);
            if (!$kpi) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => "KPI #{$id} not found."]);
            }
            return $this->response->setJSON(['status' => 'success', 'data' => $kpi]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function createKpi(): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $created = $this->kpiService->createKpi($data, $user['id']);
            return $this->response->setStatusCode(201)->setJSON(['status' => 'success', 'data' => $created]);
        } catch (\InvalidArgumentException $e) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function updateKpi($id): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $updated = $this->kpiService->updateKpi((int)$id, $data, $user['id']);
            if (!$updated) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => "KPI #{$id} not found."]);
            }
            return $this->response->setJSON(['status' => 'success', 'data' => $updated]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function deleteKpi($id): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $deleted = $this->kpiService->deleteKpi((int)$id, $user['id']);
            if (!$deleted) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => "KPI #{$id} not found."]);
            }
            return $this->response->setJSON(['status' => 'success', 'message' => "KPI #{$id} deactivated successfully."]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // -------------------------------------------------------------------------
    // 2. Metric Calculations & Execution
    // -------------------------------------------------------------------------

    public function calculateKpi($id): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $start = $data['period_start'] ?? date('Y-m-01');
            $end = $data['period_end'] ?? date('Y-m-t');

            $result = $this->calcService->calculateKpi((int)$id, $start, $end, $user['username']);
            return $this->response->setJSON(['status' => 'success', 'data' => $result]);
        } catch (\InvalidArgumentException $e) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function calculateAllKpis(): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $start = $data['period_start'] ?? date('Y-m-01');
            $end = $data['period_end'] ?? date('Y-m-t');

            $results = $this->calcService->calculateAllActiveKpis($start, $end, $user['username']);
            return $this->response->setJSON(['status' => 'success', 'data' => $results]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getKpiResults($id): ResponseInterface
    {
        try {
            $limit = (int)($this->request->getGet('limit') ?? 20);
            $results = $this->calcService->getKpiResultsHistory((int)$id, $limit);
            return $this->response->setJSON(['status' => 'success', 'data' => $results]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // -------------------------------------------------------------------------
    // 3. Multi-Tier Scorecards & Dashboards
    // -------------------------------------------------------------------------

    public function getCorporateScorecard(): ResponseInterface
    {
        try {
            $scorecard = $this->corpService->getCorporateScorecard();
            return $this->response->setJSON(['status' => 'success', 'data' => $scorecard]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getOperationalPulse(): ResponseInterface
    {
        try {
            $pulse = $this->corpService->getOperationalPulse();
            return $this->response->setJSON(['status' => 'success', 'data' => $pulse]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getExecutiveOverview(): ResponseInterface
    {
        try {
            $overview = $this->execService->getExecutiveOverview();
            return $this->response->setJSON(['status' => 'success', 'data' => $overview]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getDivisionalComparison(): ResponseInterface
    {
        try {
            $comparison = $this->orgService->getDivisionalComparison();
            return $this->response->setJSON(['status' => 'success', 'data' => $comparison]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getUnitPerformance($id): ResponseInterface
    {
        try {
            $perf = $this->orgService->getUnitPerformance((int)$id);
            if (!$perf) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => "Unit #{$id} not found."]);
            }
            return $this->response->setJSON(['status' => 'success', 'data' => $perf]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getProjectPortfolio(): ResponseInterface
    {
        try {
            $portfolio = $this->projService->getPortfolioPerformance();
            return $this->response->setJSON(['status' => 'success', 'data' => $portfolio]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // -------------------------------------------------------------------------
    // 4. Agent Performance Evaluations
    // -------------------------------------------------------------------------

    public function getEvaluations(): ResponseInterface
    {
        try {
            $filters = $this->request->getGet();
            $evaluations = $this->agentService->listEvaluations($filters);
            return $this->response->setJSON(['status' => 'success', 'data' => $evaluations]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getEvaluation($id): ResponseInterface
    {
        try {
            $eval = $this->agentService->getEvaluation((int)$id);
            if (!$eval) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => "Evaluation #{$id} not found."]);
            }
            return $this->response->setJSON(['status' => 'success', 'data' => $eval]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function createEvaluation(): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $eval = $this->agentService->createEvaluation($data, $user['id']);
            return $this->response->setStatusCode(201)->setJSON(['status' => 'success', 'data' => $eval]);
        } catch (\InvalidArgumentException $e) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function transitionEvaluation($id): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $status = $data['status'] ?? '';
            $feedback = $data['reviewer_feedback'] ?? null;

            $updated = $this->agentService->transitionEvaluationStatus((int)$id, $status, $user['id'], $feedback);
            if (!$updated) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => "Evaluation #{$id} not found."]);
            }
            return $this->response->setJSON(['status' => 'success', 'data' => $updated]);
        } catch (\InvalidArgumentException $e) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getAgentPerformanceProfile($id): ResponseInterface
    {
        try {
            $profile = $this->agentService->getAgentPerformanceProfile((int)$id);
            if (!$profile) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => "Agent #{$id} not found."]);
            }
            return $this->response->setJSON(['status' => 'success', 'data' => $profile]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function addEvidence($id): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $created = $this->agentService->addEvidence((int)$id, $data, $user['username']);
            return $this->response->setStatusCode(201)->setJSON(['status' => 'success', 'data' => $created]);
        } catch (\InvalidArgumentException $e) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getEvidence($id): ResponseInterface
    {
        try {
            $eval = $this->agentService->getEvaluation((int)$id);
            if (!$eval) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => "Evaluation #{$id} not found."]);
            }
            return $this->response->setJSON(['status' => 'success', 'data' => $eval['evidence'] ?? []]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // -------------------------------------------------------------------------
    // 5. Core Values Assessments
    // -------------------------------------------------------------------------

    public function getAgentCoreValues($id): ResponseInterface
    {
        try {
            $cycleId = $this->request->getGet('cycle_id');
            $assessments = $this->coreValuesService->getAgentAssessments((int)$id, $cycleId ? (int)$cycleId : null);
            return $this->response->setJSON(['status' => 'success', 'data' => $assessments]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function assessCoreValue(): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $evaluator = $data['evaluator_identity'] ?? $user['name'];
            $assessed = $this->coreValuesService->assessCoreValue($data, $evaluator);
            return $this->response->setStatusCode(201)->setJSON(['status' => 'success', 'data' => $assessed]);
        } catch (\InvalidArgumentException $e) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getCoreValuesSummary(): ResponseInterface
    {
        try {
            $cycleId = $this->request->getGet('cycle_id');
            $summary = $this->coreValuesService->getCoreValuesSummary($cycleId ? (int)$cycleId : null);
            return $this->response->setJSON(['status' => 'success', 'data' => $summary]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // -------------------------------------------------------------------------
    // 6. Evaluation Workflow Cycles
    // -------------------------------------------------------------------------

    public function getCycles(): ResponseInterface
    {
        try {
            $filters = $this->request->getGet();
            $cycles = $this->workflowService->listCycles($filters);
            return $this->response->setJSON(['status' => 'success', 'data' => $cycles]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getCycle($id): ResponseInterface
    {
        try {
            $cycle = $this->workflowService->getCycle((int)$id);
            if (!$cycle) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => "Cycle #{$id} not found."]);
            }
            return $this->response->setJSON(['status' => 'success', 'data' => $cycle]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function createCycle(): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $cycle = $this->workflowService->createCycle($data, $user['id']);
            return $this->response->setStatusCode(201)->setJSON(['status' => 'success', 'data' => $cycle]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function transitionCycle($id): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $target = $data['status'] ?? '';
            $cycle = $this->workflowService->transitionCycleStatus((int)$id, $target, $user['id']);
            if (!$cycle) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => "Cycle #{$id} not found."]);
            }
            return $this->response->setJSON(['status' => 'success', 'data' => $cycle]);
        } catch (\InvalidArgumentException $e) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function finalizeEvaluation($id): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $comments = $data['comments'] ?? '';
            $finalized = $this->workflowService->finalizeEvaluation((int)$id, $user['id'], $comments);
            return $this->response->setJSON(['status' => 'success', 'data' => $finalized]);
        } catch (\InvalidArgumentException $e) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // -------------------------------------------------------------------------
    // 7. Performance Improvement Plans (PIPs)
    // -------------------------------------------------------------------------

    public function getPlans(): ResponseInterface
    {
        try {
            $filters = $this->request->getGet();
            $plans = $this->pipService->listPlans($filters);
            return $this->response->setJSON(['status' => 'success', 'data' => $plans]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getPlan($id): ResponseInterface
    {
        try {
            $plan = $this->pipService->getPlan((int)$id);
            if (!$plan) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => "Improvement plan #{$id} not found."]);
            }
            return $this->response->setJSON(['status' => 'success', 'data' => $plan]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function createPlan(): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $plan = $this->pipService->createPlan($data, $user['id']);
            return $this->response->setStatusCode(201)->setJSON(['status' => 'success', 'data' => $plan]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function updatePlan($id): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $updated = $this->pipService->updatePlan((int)$id, $data, $user['id']);
            if (!$updated) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => "Improvement plan #{$id} not found."]);
            }
            return $this->response->setJSON(['status' => 'success', 'data' => $updated]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function transitionPlan($id): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $status = $data['status'] ?? '';
            $evidence = $data['resolution_evidence'] ?? null;
            $updated = $this->pipService->transitionStatus((int)$id, $status, $user['id'], $evidence);
            if (!$updated) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => "Improvement plan #{$id} not found."]);
            }
            return $this->response->setJSON(['status' => 'success', 'data' => $updated]);
        } catch (\InvalidArgumentException $e) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function linkPlanTask($id): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $taskId = (int)($data['task_id'] ?? 0);
            $updated = $this->pipService->linkTask((int)$id, $taskId, $user['id']);
            return $this->response->setJSON(['status' => 'success', 'data' => $updated]);
        } catch (\InvalidArgumentException $e) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // -------------------------------------------------------------------------
    // 8. Welfare Bonuses & DoA Governance
    // -------------------------------------------------------------------------

    public function getBonuses(): ResponseInterface
    {
        try {
            $filters = $this->request->getGet();
            $bonuses = $this->bonusService->listBonuses($filters);
            return $this->response->setJSON(['status' => 'success', 'data' => $bonuses]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getBonus($id): ResponseInterface
    {
        try {
            $bonus = $this->bonusService->getBonus((int)$id);
            if (!$bonus) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => "Bonus reference #{$id} not found."]);
            }
            return $this->response->setJSON(['status' => 'success', 'data' => $bonus]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function recommendBonus(): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $bonus = $this->bonusService->recommendBonus($data, $user['id']);
            return $this->response->setStatusCode(201)->setJSON(['status' => 'success', 'data' => $bonus]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function approveBonus($id): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $role = in_array('human_owner', $user['roles'], true) ? 'human_owner' : 'executive';
            $approved = $this->bonusService->approveBonus((int)$id, $user['id'], $role);
            return $this->response->setJSON(['status' => 'success', 'data' => $approved]);
        } catch (\InvalidArgumentException $e) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            return $this->response->setStatusCode($e->getCode() ?: 403)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function rejectBonus($id): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $reason = $data['reason'] ?? 'Rejected during executive review';
            $rejected = $this->bonusService->rejectBonus((int)$id, $user['id'], $reason);
            return $this->response->setJSON(['status' => 'success', 'data' => $rejected]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function disburseBonus($id): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $periodId = !empty($data['welfare_period_id']) ? (int)$data['welfare_period_id'] : null;
            $disbursed = $this->bonusService->disburseBonus((int)$id, $periodId, $user['id']);
            return $this->response->setJSON(['status' => 'success', 'data' => $disbursed]);
        } catch (\RuntimeException $e) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getBonusEligibility($id): ResponseInterface
    {
        try {
            $cycleId = (int)($this->request->getGet('cycle_id') ?? 1);
            $eligibility = $this->bonusService->evaluateEligibility((int)$id, $cycleId);
            return $this->response->setJSON(['status' => 'success', 'data' => $eligibility]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // -------------------------------------------------------------------------
    // 9. Executive Reporting
    // -------------------------------------------------------------------------

    public function getReports(): ResponseInterface
    {
        try {
            $filters = $this->request->getGet();
            $reports = $this->reportService->listReports($filters);
            return $this->response->setJSON(['status' => 'success', 'data' => $reports]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function getReport($id): ResponseInterface
    {
        try {
            $report = $this->reportService->getReport((int)$id);
            if (!$report) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => "Executive report #{$id} not found."]);
            }
            return $this->response->setJSON(['status' => 'success', 'data' => $report]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function generateReport(): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $report = $this->reportService->generateReport($data, $user['id']);
            return $this->response->setStatusCode(201)->setJSON(['status' => 'success', 'data' => $report]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function publishReport($id): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $published = $this->reportService->publishReport((int)$id, $user['id']);
            if (!$published) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => "Executive report #{$id} not found."]);
            }
            return $this->response->setJSON(['status' => 'success', 'data' => $published]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function ratifyReport($id): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $ratified = $this->reportService->ratifyReport((int)$id, $user['id']);
            if (!$ratified) {
                return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => "Executive report #{$id} not found."]);
            }
            return $this->response->setJSON(['status' => 'success', 'data' => $ratified]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
