<?php

namespace App\Controllers\Api;

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
use CodeIgniter\HTTP\ResponseInterface;

class ResourcePlanningController extends BaseApiController
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

    public function __construct()
    {
        $this->registryService = new ResourceRegistryService();
        $this->agentCapacityService = new AgentCapacityService();
        $this->orgCapacityService = new OrganizationalCapacityService(null, $this->agentCapacityService);
        $this->skillCoverageService = new SkillCoverageService();
        $this->workloadService = new WorkloadManagementService(null, $this->agentCapacityService);
        $this->requestService = new ResourceRequestService(null, $this->agentCapacityService, $this->registryService);
        $this->reservationService = new CapacityReservationService();
        $this->conflictService = new ResourceConflictService(null, $this->agentCapacityService);
        $this->prioritizationService = new PortfolioPrioritizationService();
        $this->forecastService = new CapacityForecastService(null, $this->orgCapacityService);
    }

    protected function getUserContext(): array
    {
        $u = $this->request->authenticatedUser ?? null;
        if (!$u) {
            return [
                'id'       => 1,
                'username' => 'system_owner',
                'name'     => 'System Owner',
                'roles'    => ['superadmin', 'owner'],
                'unit_id'  => 1,
                'agent_id' => null,
                'type'     => 'user',
            ];
        }

        if (is_object($u)) {
            $id = $u->id ?? 1;
            $username = $u->username ?? 'user';
            $name = $u->full_name ?? ($u->username ?? 'User');
            $roles = $u->roles ?? [];
            $unitId = $u->unit_id ?? null;
            $agentId = $u->agent_id ?? null;
        } else {
            $id = $u['id'] ?? 1;
            $username = $u['username'] ?? 'user';
            $name = $u['full_name'] ?? ($u['username'] ?? 'User');
            $roles = $u['roles'] ?? [];
            $unitId = $u['unit_id'] ?? null;
            $agentId = $u['agent_id'] ?? null;
        }

        return [
            'id'       => (int)$id,
            'username' => $username,
            'name'     => $name,
            'roles'    => $roles,
            'unit_id'  => $unitId,
            'agent_id' => $agentId,
            'type'     => !empty($agentId) ? 'agent' : 'user',
        ];
    }

    // ==========================================
    // 1. ORGANIZATIONAL OVERVIEW & COMPARISON
    // ==========================================

    public function getOrganizationOverview()
    {
        $overview = $this->orgCapacityService->getOrganizationCapacityOverview();
        return $this->respondSuccess($overview, 'Organization capacity overview retrieved');
    }

    public function getDivisionalComparison()
    {
        $comparison = $this->orgCapacityService->getDivisionalComparison();
        return $this->respondSuccess($comparison, 'Divisional capacity comparison retrieved');
    }

    // ==========================================
    // 2. RESOURCE REGISTRY (Operational Assets)
    // ==========================================

    public function getResources()
    {
        $filters = [
            'category'      => $this->request->getGet('category'),
            'status'        => $this->request->getGet('status'),
            'owner_unit_id' => $this->request->getGet('owner_unit_id'),
            'search'        => $this->request->getGet('search'),
        ];
        $resources = $this->registryService->getResources($filters);
        return $this->respondSuccess($resources, 'Resources retrieved successfully');
    }

    public function getResource(int $id)
    {
        $res = $this->registryService->getResourceById($id);
        if (!$res) {
            return $this->respondNotFound('Resource not found');
        }
        return $this->respondSuccess($res, 'Resource retrieved successfully');
    }

    public function createResource()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['name'])) {
            return $this->respondError('Resource name is required');
        }

        $actor = $this->getUserContext();
        $created = $this->registryService->createResource($json, $actor);
        return $this->respondSuccess($created, 'Resource created successfully', ResponseInterface::HTTP_CREATED);
    }

    public function updateResource(int $id)
    {
        $json = $this->request->getJSON(true) ?? $this->request->getRawInput();
        $actor = $this->getUserContext();

        $updated = $this->registryService->updateResource($id, $json, $actor);
        if (!$updated) {
            return $this->respondNotFound('Resource not found');
        }
        return $this->respondSuccess($updated, 'Resource updated successfully');
    }

    public function deleteResource(int $id)
    {
        $actor = $this->getUserContext();
        $deleted = $this->registryService->deleteResource($id, $actor);
        if (!$deleted) {
            return $this->respondNotFound('Resource not found');
        }
        return $this->respondSuccess(['id' => $id], 'Resource deleted successfully');
    }

    // ==========================================
    // 3. WORKFORCE CAPACITY & WORKLOAD
    // ==========================================

    public function getAllAgentsCapacity()
    {
        $unitId = $this->request->getGet('unit_id') ? (int)$this->request->getGet('unit_id') : null;
        $agents = $this->agentCapacityService->getAllAgentsCapacity($unitId);
        return $this->respondSuccess($agents, 'Agents capacity metrics retrieved');
    }

    public function getAgentCapacity(int $id)
    {
        $startDate = $this->request->getGet('start_date');
        $endDate = $this->request->getGet('end_date');
        $capacity = $this->agentCapacityService->getAgentCapacity($id, $startDate, $endDate);
        if (!$capacity) {
            return $this->respondNotFound('Agent capacity record not found');
        }
        return $this->respondSuccess($capacity, 'Agent capacity metrics retrieved');
    }

    public function getAgentTimeline(int $id)
    {
        $weeks = (int)($this->request->getGet('weeks') ?? 4);
        $timeline = $this->agentCapacityService->getAgentWorkloadTimeline($id, $weeks);
        return $this->respondSuccess($timeline, 'Agent workload timeline retrieved');
    }

    public function getWorkloadHeatmap()
    {
        $weeks = (int)($this->request->getGet('weeks') ?? 4);
        $unitId = $this->request->getGet('unit_id') ? (int)$this->request->getGet('unit_id') : null;
        $heatmap = $this->workloadService->getWorkloadHeatmap($weeks, $unitId);
        return $this->respondSuccess($heatmap, 'Workload heatmap retrieved');
    }

    public function getWorkloadSummary()
    {
        $summary = $this->workloadService->getWorkloadSummary();
        return $this->respondSuccess($summary, 'Workload summary retrieved');
    }

    // ==========================================
    // 4. SKILL COVERAGE & BOTTLENECK ANALYSIS
    // ==========================================

    public function getSkillCoverageMatrix()
    {
        $unitId = $this->request->getGet('unit_id') ? (int)$this->request->getGet('unit_id') : null;
        $matrix = $this->skillCoverageService->getSkillCoverageMatrix($unitId);
        return $this->respondSuccess($matrix, 'Skill coverage matrix retrieved');
    }

    public function getSkillBottlenecks()
    {
        $bottlenecks = $this->skillCoverageService->getSkillBottlenecks();
        return $this->respondSuccess($bottlenecks, 'Skill bottlenecks retrieved');
    }

    public function getSingleAgentDependencies()
    {
        $spof = $this->skillCoverageService->getSingleAgentDependencies();
        return $this->respondSuccess($spof, 'Single agent dependencies retrieved');
    }

    // ==========================================
    // 5. CAPACITY RESERVATIONS (Internal Ops)
    // ==========================================

    public function getReservations()
    {
        $filters = [
            'status'           => $this->request->getGet('status'),
            'reservation_type' => $this->request->getGet('reservation_type'),
            'scope_unit_id'    => $this->request->getGet('scope_unit_id'),
        ];
        $reservations = $this->reservationService->getReservations($filters);
        return $this->respondSuccess($reservations, 'Capacity reservations retrieved');
    }

    public function getReservation(int $id)
    {
        $res = $this->reservationService->getReservationById($id);
        if (!$res) {
            return $this->respondNotFound('Capacity reservation not found');
        }
        return $this->respondSuccess($res, 'Capacity reservation retrieved');
    }

    public function createReservation()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['title'])) {
            return $this->respondError('Reservation title is required');
        }

        $actor = $this->getUserContext();
        $created = $this->reservationService->createReservation($json, $actor);
        return $this->respondSuccess($created, 'Capacity reservation created', ResponseInterface::HTTP_CREATED);
    }

    public function updateReservation(int $id)
    {
        $json = $this->request->getJSON(true) ?? $this->request->getRawInput();
        $actor = $this->getUserContext();

        $updated = $this->reservationService->updateReservation($id, $json, $actor);
        if (!$updated) {
            return $this->respondNotFound('Capacity reservation not found');
        }
        return $this->respondSuccess($updated, 'Capacity reservation updated');
    }

    public function deactivateReservation(int $id)
    {
        $actor = $this->getUserContext();
        $ok = $this->reservationService->deactivateReservation($id, $actor);
        if (!$ok) {
            return $this->respondNotFound('Capacity reservation not found');
        }
        return $this->respondSuccess(['id' => $id, 'status' => 'concluded'], 'Reservation concluded');
    }

    // ==========================================
    // 6. RESOURCE REQUESTS & WORKFLOW
    // ==========================================

    public function getRequests()
    {
        $filters = [
            'lifecycle_status'  => $this->request->getGet('lifecycle_status'),
            'project_id'        => $this->request->getGet('project_id'),
            'resource_category' => $this->request->getGet('resource_category'),
            'priority'          => $this->request->getGet('priority'),
        ];
        $requests = $this->requestService->getRequests($filters);
        return $this->respondSuccess($requests, 'Resource requests retrieved');
    }

    public function getRequest(int $id)
    {
        $req = $this->requestService->getRequestById($id);
        if (!$req) {
            return $this->respondNotFound('Resource request not found');
        }
        return $this->respondSuccess($req, 'Resource request retrieved');
    }

    public function createRequest()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['title'])) {
            return $this->respondError('Request title is required');
        }

        $actor = $this->getUserContext();
        $created = $this->requestService->createRequest($json, $actor);
        return $this->respondSuccess($created, 'Resource request created', ResponseInterface::HTTP_CREATED);
    }

    public function submitRequest(int $id)
    {
        $actor = $this->getUserContext();
        $sub = $this->requestService->submitRequest($id, $actor);
        if (!$sub) {
            return $this->respondNotFound('Resource request not found');
        }
        return $this->respondSuccess($sub, 'Resource request submitted');
    }

    public function assessRequestCapacity(int $id)
    {
        $json = $this->request->getJSON(true) ?? [];
        $notes = $json['notes'] ?? null;
        $actor = $this->getUserContext();

        $assessed = $this->requestService->assessCapacity($id, $notes, $actor);
        if (!$assessed) {
            return $this->respondNotFound('Resource request not found');
        }
        return $this->respondSuccess($assessed, 'Capacity assessment completed');
    }

    public function approveRequest(int $id)
    {
        $actor = $this->getUserContext();
        $approved = $this->requestService->approveRequest($id, $actor);
        if (!$approved) {
            return $this->respondNotFound('Resource request not found');
        }
        return $this->respondSuccess($approved, 'Resource request approved');
    }

    public function rejectRequest(int $id)
    {
        $json = $this->request->getJSON(true) ?? [];
        $reason = $json['reason'] ?? 'Request does not meet operational criteria or exceeds available budget.';
        $actor = $this->getUserContext();

        $rejected = $this->requestService->rejectRequest($id, $reason, $actor);
        if (!$rejected) {
            return $this->respondNotFound('Resource request not found');
        }
        return $this->respondSuccess($rejected, 'Resource request rejected');
    }

    public function allocateRequest(int $id)
    {
        $json = $this->request->getJSON(true) ?? [];
        $actor = $this->getUserContext();

        $allocated = $this->requestService->allocateRequest($id, $json, $actor);
        if (!$allocated) {
            return $this->respondNotFound('Resource request not found');
        }
        return $this->respondSuccess($allocated, 'Resource allocated successfully');
    }

    // ==========================================
    // 7. ALLOCATIONS MASTER
    // ==========================================

    public function getAllocations()
    {
        $filters = [
            'status'     => $this->request->getGet('status'),
            'project_id' => $this->request->getGet('project_id'),
            'agent_id'   => $this->request->getGet('agent_id'),
        ];
        $allocations = $this->requestService->getAllocations($filters);
        return $this->respondSuccess($allocations, 'Resource allocations retrieved');
    }

    // ==========================================
    // 8. RESOURCE CONFLICTS
    // ==========================================

    public function getConflicts()
    {
        $filters = [
            'conflict_type'     => $this->request->getGet('conflict_type'),
            'severity'          => $this->request->getGet('severity'),
            'resolution_status' => $this->request->getGet('resolution_status'),
            'agent_id'          => $this->request->getGet('agent_id'),
        ];
        $conflicts = $this->conflictService->getConflicts($filters);
        return $this->respondSuccess($conflicts, 'Resource conflicts retrieved');
    }

    public function getConflict(int $id)
    {
        $cnf = $this->conflictService->getConflictById($id);
        if (!$cnf) {
            return $this->respondNotFound('Resource conflict not found');
        }
        return $this->respondSuccess($cnf, 'Resource conflict retrieved');
    }

    public function detectConflicts()
    {
        $actor = $this->getUserContext();
        $conflicts = $this->conflictService->detectAllConflicts($actor);
        return $this->respondSuccess($conflicts, 'Conflict detection scan executed');
    }

    public function resolveConflict(int $id)
    {
        $json = $this->request->getJSON(true) ?? [];
        $notes = $json['notes'] ?? 'Conflict verified and resolved by human coordinator.';
        $actor = $this->getUserContext();

        $resolved = $this->conflictService->resolveConflict($id, $notes, $actor);
        if (!$resolved) {
            return $this->respondNotFound('Resource conflict not found');
        }
        return $this->respondSuccess($resolved, 'Conflict resolved successfully');
    }

    public function dismissConflict(int $id)
    {
        $json = $this->request->getJSON(true) ?? [];
        $reason = $json['reason'] ?? 'Acceptable risk/overlap under temporary variance.';
        $actor = $this->getUserContext();

        $dismissed = $this->conflictService->dismissConflict($id, $reason, $actor);
        if (!$dismissed) {
            return $this->respondNotFound('Resource conflict not found');
        }
        return $this->respondSuccess($dismissed, 'Conflict dismissed');
    }

    // ==========================================
    // 9. PORTFOLIO PRIORITIZATION
    // ==========================================

    public function getPortfolioPriorities()
    {
        $period = $this->request->getGet('period');
        $priorities = $this->prioritizationService->getPortfolioPriorities($period);
        return $this->respondSuccess($priorities, 'Portfolio priorities retrieved');
    }

    public function getProjectPriority(int $projectId)
    {
        $priority = $this->prioritizationService->getPriorityByProjectId($projectId);
        if (!$priority) {
            return $this->respondNotFound('Project priority evaluation not found');
        }
        return $this->respondSuccess($priority, 'Project priority retrieved');
    }

    public function evaluateProjectPriority()
    {
        $json = $this->request->getJSON(true) ?? [];
        if (empty($json['project_id'])) {
            return $this->respondError('Project ID is required');
        }

        $actor = $this->getUserContext();
        $evaluated = $this->prioritizationService->evaluateProjectPriority((int)$json['project_id'], $json, $actor);
        return $this->respondSuccess($evaluated, 'Project priority evaluated successfully');
    }

    public function overridePriority()
    {
        $json = $this->request->getJSON(true) ?? [];
        if (empty($json['project_id']) || empty($json['new_rank']) || empty($json['priority_tier'])) {
            return $this->respondError('project_id, new_rank, and priority_tier are required');
        }

        $reason = $json['reason'] ?? 'Executive Board manual strategic rebalancing.';
        $actor = $this->getUserContext();

        $overridden = $this->prioritizationService->overridePriority(
            (int)$json['project_id'],
            (int)$json['new_rank'],
            $json['priority_tier'],
            $reason,
            $actor
        );

        if (!$overridden) {
            return $this->respondNotFound('Project priority record not found');
        }
        return $this->respondSuccess($overridden, 'Priority ranking overridden successfully');
    }

    // ==========================================
    // 10. FORECASTING & GAP ANALYSIS
    // ==========================================

    public function getForecastSnapshots()
    {
        $periodType = $this->request->getGet('period_type') ?? 'weekly';
        $snapshots = $this->forecastService->getForecastSnapshots($periodType);
        return $this->respondSuccess($snapshots, 'Capacity forecast snapshots retrieved');
    }

    public function generateForecastSnapshot()
    {
        $json = $this->request->getJSON(true) ?? [];
        $startDate = $json['start_date'] ?? date('Y-m-d');
        $endDate = $json['end_date'] ?? date('Y-m-d', strtotime('+6 days'));
        $unitId = !empty($json['unit_id']) ? (int)$json['unit_id'] : null;

        $snapshot = $this->forecastService->generateForecastSnapshot($startDate, $endDate, $unitId);
        return $this->respondSuccess($snapshot, 'Forecast snapshot generated');
    }

    public function getGapAnalyses()
    {
        $filters = [
            'status'       => $this->request->getGet('status'),
            'severity'     => $this->request->getGet('severity'),
            'gap_category' => $this->request->getGet('gap_category'),
        ];
        $gaps = $this->forecastService->getGapAnalyses($filters);
        return $this->respondSuccess($gaps, 'Capacity gap analyses retrieved');
    }

    public function createGapAnalysis()
    {
        $json = $this->request->getJSON(true) ?? [];
        $actor = $this->getUserContext();
        $created = $this->forecastService->createGapAnalysis($json, $actor);
        return $this->respondSuccess($created, 'Gap analysis created', ResponseInterface::HTTP_CREATED);
    }

    public function resolveGapAnalysis(int $id)
    {
        $json = $this->request->getJSON(true) ?? [];
        $notes = $json['action_notes'] ?? 'Action plan implemented.';
        $actor = $this->getUserContext();

        $resolved = $this->forecastService->resolveGapAnalysis($id, $notes, $actor);
        if (!$resolved) {
            return $this->respondNotFound('Gap analysis not found');
        }
        return $this->respondSuccess($resolved, 'Gap analysis resolved');
    }
}
