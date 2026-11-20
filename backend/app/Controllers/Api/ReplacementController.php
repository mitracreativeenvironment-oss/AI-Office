<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use App\Services\AgentReplacementService;

class ReplacementController extends BaseApiController
{
    protected AgentReplacementService $replacementService;

    public function __construct()
    {
        $this->replacementService = new AgentReplacementService();
    }

    public function index()
    {
        $filters = [
            'status'   => $this->request->getGet('status'),
            'agent_id' => $this->request->getGet('agent_id'),
        ];
        $limit = max(1, min(100, (int)($this->request->getGet('limit') ?? 20)));
        $offset = max(0, (int)($this->request->getGet('offset') ?? 0));

        try {
            $res = $this->replacementService->listProposals($filters, $limit, $offset);
            return $this->respondSuccess($res['items'], 'Replacement proposals retrieved successfully.', ResponseInterface::HTTP_OK, [
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
            return $this->respondNotFound('Proposal ID required.');
        }

        try {
            $prop = $this->replacementService->getProposal((int)$id);
            if (!$prop) {
                return $this->respondNotFound("Replacement proposal #{$id} not found.");
            }
            return $this->respondSuccess($prop, 'Replacement proposal details retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findCandidates()
    {
        $dutyId = (int)$this->request->getGet('duty_assignment_id');
        if (!$dutyId) {
            return $this->respondError('Query parameter duty_assignment_id is required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $candidates = $this->replacementService->findReplacementCandidates($dutyId);
            return $this->respondSuccess($candidates, 'Replacement candidates discovered successfully.');
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['duty_assignment_id']) || empty($json['incoming_agent_id'])) {
            return $this->respondError('Duty Assignment ID and Incoming Agent ID are required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $proposal = $this->replacementService->createProposal($json, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($proposal, 'Replacement proposal created successfully in Proposed state.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function accept($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Proposal ID required.');
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $identity = $json['agent_identity'] ?? 'AGT-AI-SYSTEM';

        try {
            $updated = $this->replacementService->acceptProposal((int)$id, $identity);
            return $this->respondSuccess($updated, 'Replacement proposal accepted by incoming agent.');
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approve($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Proposal ID required.');
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $updated = $this->replacementService->approveProposal((int)$id, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($updated, 'Replacement proposal formally approved by supervisor.');
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function activate($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Proposal ID required.');
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $activated = $this->replacementService->activateReplacement((int)$id, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($activated, 'Replacement duty successfully activated with historical assignment preservation.');
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
