<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use App\Services\KnowledgeBaseService;
use App\Services\KnowledgeContributionService;
use App\Services\DocumentManagementService;
use App\Services\AgentMemoryService;
use App\Services\WallbookKnowledgeBridgeService;
use App\Services\KnowledgeSearchService;

class KnowledgeController extends BaseApiController
{
    protected KnowledgeBaseService $kbService;
    protected KnowledgeContributionService $contributionService;
    protected DocumentManagementService $docService;
    protected AgentMemoryService $memoryService;
    protected WallbookKnowledgeBridgeService $wbBridgeService;
    protected KnowledgeSearchService $searchService;

    public function __construct()
    {
        $this->kbService = new KnowledgeBaseService();
        $this->contributionService = new KnowledgeContributionService();
        $this->docService = new DocumentManagementService();
        $this->memoryService = new AgentMemoryService();
        $this->wbBridgeService = new WallbookKnowledgeBridgeService();
        $this->searchService = new KnowledgeSearchService();
    }

    protected function getUserContext(): array
    {
        $u = $this->request->authenticatedUser ?? null;
        if (!$u) {
            return ['roles' => []];
        }

        return [
            'id'       => $u->id ?? null,
            'username' => $u->username ?? null,
            'roles'    => $u->roles ?? [],
            'unit_id'  => $u->unit_id ?? null,
            'agent_id' => $u->agent_id ?? null,
        ];
    }

    // 1. Overview
    public function overview()
    {
        try {
            $data = $this->kbService->getOverview();
            return $this->respondSuccess($data, 'Knowledge management overview retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // 2. Categories
    public function listCategories()
    {
        try {
            $filters = [
                'domain'    => $this->request->getGet('domain'),
                'is_active' => $this->request->getGet('is_active'),
            ];
            $items = $this->kbService->listCategories($filters);
            return $this->respondSuccess($items, 'Knowledge categories retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createCategory()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['name']) || empty($json['domain'])) {
            return $this->respondError('Category name and domain are required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $cat = $this->kbService->createCategory($json);
            return $this->respondSuccess($cat, 'Knowledge category created successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // 3. Knowledge Records
    public function listRecords()
    {
        try {
            $filters = [
                'domain'         => $this->request->getGet('domain'),
                'category_id'    => $this->request->getGet('category_id'),
                'classification' => $this->request->getGet('classification'),
                'status'         => $this->request->getGet('status'),
                'owner_unit_id'  => $this->request->getGet('owner_unit_id'),
                'search'         => $this->request->getGet('search'),
            ];
            $limit = max(1, min(100, (int)($this->request->getGet('limit') ?? 20)));
            $offset = max(0, (int)($this->request->getGet('offset') ?? 0));

            $res = $this->kbService->listRecords($filters, $limit, $offset, $this->getUserContext());
            return $this->respondSuccess($res['items'], 'Knowledge records retrieved successfully.', ResponseInterface::HTTP_OK, [
                'total'  => $res['total'],
                'limit'  => $limit,
                'offset' => $offset,
            ]);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function showRecord($id = null)
    {
        if ($id === null) return $this->respondNotFound('Record ID required.');

        try {
            $record = $this->kbService->getRecord((int)$id, $this->getUserContext());
            if (!$record) return $this->respondNotFound("Knowledge record #{$id} not found.");
            return $this->respondSuccess($record, 'Knowledge record details retrieved successfully.');
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), $e->getCode() ?: ResponseInterface::HTTP_FORBIDDEN);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createRecord()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['title']) || empty($json['content'])) {
            return $this->respondError('Title and content are required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $actorAgentId = $this->request->authenticatedUser->agent_id ?? null;
            $record = $this->kbService->createRecord($json, $actorId ? (int)$actorId : null, $actorAgentId ? (int)$actorAgentId : null);
            return $this->respondSuccess($record, 'Knowledge record draft created successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateRecord($id = null)
    {
        if ($id === null) return $this->respondNotFound('Record ID required.');

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $actorAgentId = $this->request->authenticatedUser->agent_id ?? null;
            $updated = $this->kbService->updateRecord((int)$id, $json, $actorId ? (int)$actorId : null, $actorAgentId ? (int)$actorAgentId : null);
            return $this->respondSuccess($updated, 'Knowledge record updated successfully.');
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function transitionRecord($id = null)
    {
        if ($id === null) return $this->respondNotFound('Record ID required.');

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $targetStatus = $json['target_status'] ?? $json['status'] ?? null;
        if (!$targetStatus) {
            return $this->respondError('Target status is required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $updated = $this->kbService->transitionStatus((int)$id, $targetStatus, $json['reason'] ?? null, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($updated, "Record transitioned to '{$targetStatus}' successfully.");
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // 4. Contributions
    public function listContributions()
    {
        try {
            $filters = [
                'status'               => $this->request->getGet('status'),
                'contributor_agent_id' => $this->request->getGet('contributor_agent_id'),
                'proposed_category_id' => $this->request->getGet('proposed_category_id'),
            ];
            $limit = max(1, min(100, (int)($this->request->getGet('limit') ?? 20)));
            $offset = max(0, (int)($this->request->getGet('offset') ?? 0));

            $res = $this->contributionService->listContributions($filters, $limit, $offset);
            return $this->respondSuccess($res['items'], 'Knowledge contributions retrieved successfully.', ResponseInterface::HTTP_OK, [
                'total'  => $res['total'],
                'limit'  => $limit,
                'offset' => $offset,
            ]);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function showContribution($id = null)
    {
        if ($id === null) return $this->respondNotFound('Contribution ID required.');

        try {
            $item = $this->contributionService->getContribution((int)$id);
            if (!$item) return $this->respondNotFound("Contribution #{$id} not found.");
            return $this->respondSuccess($item, 'Contribution details retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function submitContribution()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['title']) || empty($json['content'])) {
            return $this->respondError('Title and content are required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $actorAgentId = $this->request->authenticatedUser->agent_id ?? null;
            $created = $this->contributionService->submitContribution($json, $actorAgentId ? (int)$actorAgentId : null, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($created, 'Knowledge contribution submitted for review.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function reviewContribution($id = null)
    {
        if ($id === null) return $this->respondNotFound('Contribution ID required.');

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $decision = $json['decision'] ?? 'approved';

        try {
            $reviewerId = $this->request->authenticatedUser->id ?? null;
            $reviewerAgentId = $this->request->authenticatedUser->agent_id ?? null;
            $reviewed = $this->contributionService->reviewContribution(
                (int)$id,
                $decision,
                $json['feedback'] ?? null,
                $reviewerId ? (int)$reviewerId : null,
                $reviewerAgentId ? (int)$reviewerAgentId : null
            );
            return $this->respondSuccess($reviewed, "Knowledge contribution reviewed ({$decision}).");
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_FORBIDDEN);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // 5. Documents
    public function listDocuments()
    {
        try {
            $filters = [
                'category'       => $this->request->getGet('category'),
                'classification' => $this->request->getGet('classification'),
                'status'         => $this->request->getGet('status'),
                'owner_unit_id'  => $this->request->getGet('owner_unit_id'),
                'search'         => $this->request->getGet('search'),
            ];
            $limit = max(1, min(100, (int)($this->request->getGet('limit') ?? 20)));
            $offset = max(0, (int)($this->request->getGet('offset') ?? 0));

            $res = $this->docService->listDocuments($filters, $limit, $offset, $this->getUserContext());
            return $this->respondSuccess($res['items'], 'Documents retrieved successfully.', ResponseInterface::HTTP_OK, [
                'total'  => $res['total'],
                'limit'  => $limit,
                'offset' => $offset,
            ]);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function showDocument($id = null)
    {
        if ($id === null) return $this->respondNotFound('Document ID required.');

        try {
            $doc = $this->docService->getDocument((int)$id, $this->getUserContext());
            if (!$doc) return $this->respondNotFound("Document #{$id} not found.");
            return $this->respondSuccess($doc, 'Document details retrieved successfully.');
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), $e->getCode() ?: ResponseInterface::HTTP_FORBIDDEN);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function registerDocument()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['title'])) {
            return $this->respondError('Document title is required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $actorAgentId = $this->request->authenticatedUser->agent_id ?? null;
            $doc = $this->docService->registerDocument($json, $actorId ? (int)$actorId : null, $actorAgentId ? (int)$actorAgentId : null);
            return $this->respondSuccess($doc, 'Document registered successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function addDocumentVersion($id = null)
    {
        if ($id === null) return $this->respondNotFound('Document ID required.');

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $actorAgentId = $this->request->authenticatedUser->agent_id ?? null;
            $doc = $this->docService->addDocumentVersion((int)$id, $json, $actorId ? (int)$actorId : null, $actorAgentId ? (int)$actorAgentId : null);
            return $this->respondSuccess($doc, 'Document version revision added successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // 6. Agent Memories
    public function listAgentMemories($agentId = null)
    {
        if ($agentId === null) return $this->respondNotFound('Agent ID required.');

        try {
            $filters = [
                'memory_type'    => $this->request->getGet('memory_type'),
                'status'         => $this->request->getGet('status'),
                'classification' => $this->request->getGet('classification'),
                'search'         => $this->request->getGet('search'),
            ];
            $limit = max(1, min(100, (int)($this->request->getGet('limit') ?? 50)));
            $res = $this->memoryService->listMemories((int)$agentId, $filters, $limit, $this->getUserContext());
            return $this->respondSuccess($res['items'], "Agent #{$agentId} memories retrieved successfully.", ResponseInterface::HTTP_OK, [
                'total' => $res['total'],
            ]);
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), $e->getCode() ?: ResponseInterface::HTTP_FORBIDDEN);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function showMemory($id = null)
    {
        if ($id === null) return $this->respondNotFound('Memory ID required.');

        try {
            $mem = $this->memoryService->getMemory((int)$id, $this->getUserContext());
            if (!$mem) return $this->respondNotFound("Memory #{$id} not found.");
            return $this->respondSuccess($mem, 'Agent memory retrieved successfully.');
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), $e->getCode() ?: ResponseInterface::HTTP_FORBIDDEN);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createAgentMemory($agentId = null)
    {
        if ($agentId === null) return $this->respondNotFound('Agent ID required.');

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['title']) || empty($json['content'])) {
            return $this->respondError('Memory title and content are required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $mem = $this->memoryService->createMemory((int)$agentId, $json);
            return $this->respondSuccess($mem, 'Agent memory consolidated successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function promoteMemory($id = null)
    {
        if ($id === null) return $this->respondNotFound('Memory ID required.');

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        try {
            $actorAgentId = $this->request->authenticatedUser->agent_id ?? null;
            $proposal = $this->memoryService->promoteMemoryToContribution((int)$id, $json, $actorAgentId ? (int)$actorAgentId : null);
            return $this->respondSuccess($proposal, 'Memory promoted to knowledge contribution proposal.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // 7. Wallbook Bridge
    public function listWallbookChecklists($handoverId = null)
    {
        if ($handoverId === null) return $this->respondNotFound('Handover ID required.');

        try {
            $items = $this->wbBridgeService->listHandoverChecklists((int)$handoverId);
            return $this->respondSuccess($items, "Handover #{$handoverId} checklists retrieved.");
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateWallbookChecklist($checklistId = null)
    {
        if ($checklistId === null) return $this->respondNotFound('Checklist ID required.');

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $isSatisfied = !empty($json['is_satisfied']);

        try {
            $updated = $this->wbBridgeService->updateChecklistItem((int)$checklistId, $isSatisfied, $json['notes'] ?? null);
            return $this->respondSuccess($updated, 'Handover checklist item updated.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function promoteWallbookItem($handoverId = null, $itemId = null)
    {
        if ($handoverId === null || $itemId === null) {
            return $this->respondNotFound('Handover ID and Item ID are required.');
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $actorAgentId = $this->request->authenticatedUser->agent_id ?? null;
            $contribution = $this->wbBridgeService->promoteHandoverItemToKnowledge(
                (int)$handoverId,
                (int)$itemId,
                $json,
                $actorId ? (int)$actorId : null,
                $actorAgentId ? (int)$actorAgentId : null
            );
            return $this->respondSuccess($contribution, 'Wallbook insight promoted to knowledge contribution proposal.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // 8. Search & RAG Context Retrieval
    public function search()
    {
        $query = $this->request->getGet('q') ?? '';
        $filters = [
            'domain'         => $this->request->getGet('domain'),
            'classification' => $this->request->getGet('classification'),
        ];
        $limit = max(1, min(50, (int)($this->request->getGet('limit') ?? 20)));

        try {
            $res = $this->searchService->search($query, $filters, $this->getUserContext(), $limit);
            return $this->respondSuccess($res, 'Knowledge search executed successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function ragRetrieve()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $query = $json['query'] ?? '';
        $agentId = (int)($json['agent_id'] ?? 0);
        $taskId = !empty($json['task_id']) ? (int)$json['task_id'] : null;

        if (empty($query) || !$agentId) {
            return $this->respondError('Query and Agent ID are required for AI RAG retrieval.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $data = $this->searchService->retrieveContextForAgent($query, $agentId, $taskId);
            return $this->respondSuccess($data, 'AI RAG context retrieved successfully with authorized provenance.');
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
