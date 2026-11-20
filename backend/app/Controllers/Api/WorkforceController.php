<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use App\Services\AgentRegistryService;
use App\Services\AgentWorkforceService;

class WorkforceController extends BaseApiController
{
    protected AgentRegistryService $registryService;
    protected AgentWorkforceService $workforceService;

    public function __construct()
    {
        $this->registryService = new AgentRegistryService();
        $this->workforceService = new AgentWorkforceService();
    }

    /**
     * GET /api/v1/workforce/metrics
     * Retrieve aggregate real-data metrics of the AI workforce.
     */
    public function metrics()
    {
        try {
            $metrics = $this->registryService->getMetrics();
            return $this->respondSuccess($metrics, 'Workforce metrics retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/v1/workforce/agents
     * List registered AI agents with filtering, search, and pagination.
     */
    public function index()
    {
        $filters = [
            'lifecycle_state'    => $this->request->getGet('lifecycle_state'),
            'operational_status' => $this->request->getGet('operational_status'),
            'classification'     => $this->request->getGet('classification'),
            'unit_id'            => $this->request->getGet('unit_id'),
            'search'             => $this->request->getGet('search'),
        ];
        $limit = (int)($this->request->getGet('limit') ?? 20);
        $offset = (int)($this->request->getGet('offset') ?? 0);

        try {
            $result = $this->registryService->listAgents($filters, $limit, $offset);
            return $this->respondSuccess($result['items'], 'AI Agent registry retrieved successfully.', ResponseInterface::HTTP_OK, [
                'total'  => $result['total'],
                'limit'  => $result['limit'],
                'offset' => $result['offset'],
            ]);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/v1/workforce/agents/(:num)
     * Retrieve single agent detail with profile, skills, and placement.
     */
    public function show($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound("Agent ID required.");
        }
        $agent = $this->registryService->getAgent((int)$id);
        if (!$agent) {
            return $this->respondNotFound("Agent #{$id} not found.");
        }
        return $this->respondSuccess($agent, 'Agent details retrieved successfully.');
    }

    /**
     * POST /api/v1/workforce/agents
     * Register a new AI Agent identity in Draft state.
     */
    public function create()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['name'])) {
            return $this->respondError('Field name is required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $agent = $this->registryService->createAgent($json, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($agent, 'AI Agent identity registered successfully in Draft state.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * PUT /api/v1/workforce/agents/(:num)
     * Update agent metadata (display name, classification, policy settings).
     */
    public function update($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound("Agent ID required.");
        }
        $json = $this->request->getJSON(true) ?? $this->request->getRawInput();
        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $agent = $this->registryService->updateAgent((int)$id, $json, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($agent, 'AI Agent updated successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/v1/workforce/agents/(:num)/profile
     */
    public function getProfile($id)
    {
        $agent = $this->registryService->getAgent((int)$id);
        if (!$agent) {
            return $this->respondNotFound("Agent #{$id} not found.");
        }
        return $this->respondSuccess($agent['profile'], 'Agent profile retrieved successfully.');
    }

    /**
     * PUT /api/v1/workforce/agents/(:num)/profile
     */
    public function updateProfile($id)
    {
        $json = $this->request->getJSON(true) ?? $this->request->getRawInput();
        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $agent = $this->registryService->updateProfile((int)$id, $json, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($agent['profile'], 'Agent profile updated successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // =========================================================================
    // Lifecycle Management Endpoints
    // =========================================================================

    /**
     * GET /api/v1/workforce/agents/(:num)/lifecycle
     */
    public function lifecycleHistory($id)
    {
        try {
            $history = $this->workforceService->getLifecycleHistory((int)$id);
            return $this->respondSuccess($history, 'Agent lifecycle history retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/v1/workforce/agents/(:num)/transition
     * Execute a validated lifecycle state transition.
     */
    public function transition($id)
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $toState = $json['to_state'] ?? null;
        if (empty($toState)) {
            return $this->respondError('Field to_state is required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $reason = $json['reason'] ?? null;
            $approvalRef = $json['approval_ref'] ?? null;

            $updated = $this->workforceService->transitionState((int)$id, $toState, $reason, $approvalRef, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($updated, "Agent lifecycle transitioned to '{$toState}'.");
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // =========================================================================
    // Organizational Assignments Endpoints
    // =========================================================================

    /**
     * GET /api/v1/workforce/agents/(:num)/assignments
     */
    public function assignments($id)
    {
        try {
            $assignments = $this->workforceService->getAssignments((int)$id);
            return $this->respondSuccess($assignments, 'Agent organizational assignments retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/v1/workforce/agents/(:num)/assignments
     */
    public function assign($id)
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['unit_id'])) {
            return $this->respondError('unit_id is required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $positionId = !empty($json['position_id']) ? (int)$json['position_id'] : null;
            $type = $json['assignment_type'] ?? 'primary';
            $reason = $json['reason'] ?? null;
            $approvalRef = $json['approval_ref'] ?? null;

            $appt = $this->workforceService->assignAgent((int)$id, (int)$json['unit_id'], $positionId, $type, $reason, $approvalRef, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($appt, 'Agent organizational assignment recorded successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/v1/workforce/assignments/(:num)/end
     */
    public function endAssignment($id)
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $reason = $json['reason'] ?? 'Assignment concluded.';

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $ended = $this->workforceService->endAssignment((int)$id, $reason, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($ended, 'Assignment concluded successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // =========================================================================
    // Skills & Responsibilities Endpoints
    // =========================================================================

    /**
     * GET /api/v1/workforce/skills
     */
    public function skillCatalogue()
    {
        $category = $this->request->getGet('category');
        try {
            $skills = $this->workforceService->getSkillCatalogue($category ?: null);
            return $this->respondSuccess($skills, 'Skill catalogue retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/v1/workforce/skills
     */
    public function createSkill()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['code']) || empty($json['name']) || empty($json['category'])) {
            return $this->respondError('code, name, and category are required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $skill = $this->workforceService->createSkill($json, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($skill, 'Skill created in catalogue.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/v1/workforce/agents/(:num)/skills
     */
    public function agentSkills($id)
    {
        try {
            $skills = $this->workforceService->getAgentSkills((int)$id);
            return $this->respondSuccess($skills, 'Agent skills retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/v1/workforce/agents/(:num)/skills
     */
    public function assignSkill($id)
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['skill_id'])) {
            return $this->respondError('skill_id is required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $prof = $json['proficiency_level'] ?? 'competent';
            $evidence = $json['evidence_reference'] ?? null;

            $assigned = $this->workforceService->assignSkill((int)$id, (int)$json['skill_id'], $prof, $evidence, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($assigned, 'Skill assigned to Agent successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/v1/workforce/skills/(:num)/verify
     */
    public function verifySkill($id)
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $status = $json['verification_status'] ?? 'verified';
        $verifiedBy = $json['verified_by'] ?? null;

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $verified = $this->workforceService->verifySkill((int)$id, $status, $verifiedBy, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($verified, 'Skill verification updated successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/v1/workforce/agents/(:num)/responsibilities
     */
    public function responsibilities($id)
    {
        try {
            $resp = $this->workforceService->getResponsibilities((int)$id);
            return $this->respondSuccess($resp, 'Agent responsibilities retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/v1/workforce/agents/(:num)/responsibilities
     */
    public function addResponsibility($id)
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['title']) || empty($json['description'])) {
            return $this->respondError('title and description are required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $resp = $this->workforceService->addResponsibility((int)$id, $json, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($resp, 'Responsibility added successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // =========================================================================
    // Individual Agent Performance History Endpoints
    // =========================================================================

    /**
     * GET /api/v1/workforce/agents/(:num)/performance-history
     */
    public function performanceHistory($id)
    {
        $category = $this->request->getGet('category');
        $limit = (int)($this->request->getGet('limit') ?? 50);
        $offset = (int)($this->request->getGet('offset') ?? 0);

        try {
            $result = $this->workforceService->getPerformanceHistory((int)$id, $category ?: null, $limit, $offset);
            return $this->respondSuccess($result['records'], 'Individual Agent Performance History retrieved.', ResponseInterface::HTTP_OK, [
                'total'  => $result['total'],
                'limit'  => $result['limit'],
                'offset' => $result['offset'],
            ]);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/v1/workforce/agents/(:num)/performance-history
     */
    public function recordPerformance($id)
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['title']) || empty($json['description'])) {
            return $this->respondError('title and description are required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $record = $this->workforceService->recordPerformanceEvent((int)$id, $json, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($record, 'Performance event recorded permanently.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/v1/workforce/performance-history/(:num)/verify
     */
    public function verifyPerformanceEvidence($id)
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $status = $json['evidence_status'] ?? 'verified';
        $actorId = $this->request->authenticatedUser->id ?? null;
        $evaluator = $json['evaluator_identity'] ?? ($this->request->authenticatedUser->display_name ?? ($actorId ? "User #{$actorId}" : 'Workforce Auditor'));
        $notes = $json['evaluation_notes'] ?? null;

        try {
            $verified = $this->workforceService->verifyEvidence((int)$id, $status, $evaluator, $notes, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($verified, 'Performance evidence verification status updated.');
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/v1/workforce/performance-history/(:num)/amend
     * Amend record preserving historical immutability.
     */
    public function amendPerformance($id)
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $reason = $json['reason'] ?? 'Formal correction';

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $amended = $this->workforceService->amendPerformanceRecord((int)$id, $json, $reason, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($amended, 'Performance record amended with historical provenance preserved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // =========================================================================
    // Runtime Identity Mapping Endpoints
    // =========================================================================

    /**
     * GET /api/v1/workforce/agents/(:num)/runtime-mappings
     */
    public function runtimeMappings($id)
    {
        try {
            $mappings = $this->registryService->getRuntimeMappings((int)$id);
            return $this->respondSuccess($mappings, 'Runtime technical identity mappings retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/v1/workforce/agents/(:num)/runtime-mappings
     */
    public function setRuntimeMapping($id)
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $mapping = $this->registryService->setRuntimeMapping((int)$id, $json, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($mapping, 'Runtime identity mapping configured.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
