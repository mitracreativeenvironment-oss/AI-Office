<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use App\Services\VirtualHeadquartersService;

class VhqController extends BaseApiController
{
    protected VirtualHeadquartersService $vhqService;

    public function __construct()
    {
        $this->vhqService = new VirtualHeadquartersService();
    }

    /**
     * Campus-wide overview: buildings, summaries, metrics.
     * GET /api/v1/vhq/campus
     */
    public function campus()
    {
        try {
            $data = $this->vhqService->getCampusOverview();
            return $this->respondSuccess($data, 'Virtual Headquarters campus overview retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Building details with floors, rooms, and workstations.
     * GET /api/v1/vhq/buildings/{id}
     */
    public function building($id = null)
    {
        try {
            if (!$id) {
                return $this->respondError('Building ID required.', ResponseInterface::HTTP_BAD_REQUEST);
            }
            $data = $this->vhqService->getBuildingDetail((int)$id);
            if (!$data) {
                return $this->respondNotFound("Building #{$id} not found.");
            }
            return $this->respondSuccess($data, "Building #{$id} details retrieved.");
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * List rooms with optional filters.
     * GET /api/v1/vhq/rooms
     */
    public function rooms()
    {
        try {
            $filters = $this->request->getGet();
            $data = $this->vhqService->listRooms($filters);
            return $this->respondSuccess($data, 'Virtual Headquarters rooms retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * List workstations with optional filters.
     * GET /api/v1/vhq/workstations
     */
    public function workstations()
    {
        try {
            $filters = $this->request->getGet();
            $data = $this->vhqService->listWorkstations($filters);
            return $this->respondSuccess($data, 'Virtual Headquarters workstations retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * List real-time operational presence of all active agents.
     * GET /api/v1/vhq/presence
     */
    public function presence()
    {
        try {
            $data = $this->vhqService->listAllAgentPresences();
            return $this->respondSuccess($data, 'All agent presences retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get presence and spatial placement of a specific agent.
     * GET /api/v1/vhq/agents/{id}/presence
     */
    public function agentPresence($id = null)
    {
        try {
            if (!$id) {
                return $this->respondError('Agent ID required.', ResponseInterface::HTTP_BAD_REQUEST);
            }
            $data = $this->vhqService->getAgentPresence((int)$id);
            return $this->respondSuccess($data, "Agent #{$id} presence retrieved.");
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * List camera navigation bookmarks.
     * GET /api/v1/vhq/bookmarks
     */
    public function bookmarks()
    {
        try {
            $data = $this->vhqService->getCameraBookmarks();
            return $this->respondSuccess($data, 'Camera navigation bookmarks retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Reassign or vacate a workstation.
     * POST /api/v1/vhq/workstations/{id}/assign
     */
    public function assignWorkstation($id = null)
    {
        try {
            if (!$id) {
                return $this->respondError('Workstation ID required.', ResponseInterface::HTTP_BAD_REQUEST);
            }
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $agentId = isset($body['agent_id']) && $body['agent_id'] !== '' ? (int)$body['agent_id'] : null;

            $updated = $this->vhqService->assignWorkstation((int)$id, $agentId);
            return $this->respondSuccess($updated, "Workstation #{$id} assignment updated.");
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
