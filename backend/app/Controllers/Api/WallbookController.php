<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use App\Services\WallbookHandoverService;

class WallbookController extends BaseApiController
{
    protected WallbookHandoverService $wallbookService;

    public function __construct()
    {
        $this->wallbookService = new WallbookHandoverService();
    }

    public function index()
    {
        $filters = [
            'status'   => $this->request->getGet('status'),
            'unit_id'  => $this->request->getGet('unit_id'),
            'agent_id' => $this->request->getGet('agent_id'),
        ];
        $limit = max(1, min(100, (int)($this->request->getGet('limit') ?? 20)));
        $offset = max(0, (int)($this->request->getGet('offset') ?? 0));

        try {
            $res = $this->wallbookService->listHandovers($filters, $limit, $offset);
            return $this->respondSuccess($res['items'], 'Wallbook handovers retrieved successfully.', ResponseInterface::HTTP_OK, [
                'total'  => $res['total'],
                'limit'  => $limit,
                'offset' => $offset,
            ]);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Handover ID required.');
        }

        try {
            $handover = $this->wallbookService->getHandover((int)$id);
            if (!$handover) {
                return $this->respondNotFound("Wallbook handover #{$id} not found.");
            }
            return $this->respondSuccess($handover, 'Wallbook handover details retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['outgoing_agent_id']) || empty($json['incoming_agent_id'])) {
            return $this->respondError('Outgoing and Incoming Agent IDs are required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $handover = $this->wallbookService->createHandover($json, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($handover, 'Wallbook handover draft initiated successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function addItem($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Handover ID required.');
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['title']) || empty($json['content'])) {
            return $this->respondError('Title and content are required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $item = $this->wallbookService->addHandoverItem((int)$id, $json);
            return $this->respondSuccess($item, 'Structured item added to handover.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function submit($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Handover ID required.');
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $submitted = $this->wallbookService->submitHandover((int)$id, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($submitted, 'Wallbook handover successfully submitted for acknowledgement.');
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function acknowledge($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Handover ID required.');
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $incomingAgentId = (int)($json['incoming_agent_id'] ?? 0);
        if (!$incomingAgentId) {
            return $this->respondError('Incoming Agent ID is required for formal acknowledgement.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $acknowledged = $this->wallbookService->acknowledgeHandover((int)$id, $incomingAgentId, $json['comments'] ?? null);
            return $this->respondSuccess($acknowledged, 'Wallbook handover context acknowledged by incoming agent.');
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function clarify($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Handover ID required.');
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $incomingAgentId = (int)($json['incoming_agent_id'] ?? 0);
        $questions = trim($json['questions'] ?? '');
        if (!$incomingAgentId || empty($questions)) {
            return $this->respondError('Incoming Agent ID and clarification questions are required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $clarified = $this->wallbookService->requestClarification((int)$id, $incomingAgentId, $questions);
            return $this->respondSuccess($clarified, 'Clarification request logged for handover.');
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function accept($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Handover ID required.');
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $incomingAgentId = (int)($json['incoming_agent_id'] ?? 0);
        if (!$incomingAgentId) {
            return $this->respondError('Incoming Agent ID is required for formal acceptance.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $accepted = $this->wallbookService->acceptHandover((int)$id, $incomingAgentId, $json['comments'] ?? null);
            return $this->respondSuccess($accepted, 'Wallbook handover formally accepted. Incoming agent assumes full operational duty.');
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function escalateOverdue()
    {
        try {
            $res = $this->wallbookService->escalateOverdueHandovers();
            return $this->respondSuccess($res, 'Overdue handovers scan completed.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
