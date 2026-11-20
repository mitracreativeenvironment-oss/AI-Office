<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use App\Services\OrganizationService;

class OrganizationController extends BaseApiController
{
    protected OrganizationService $orgService;

    public function __construct()
    {
        $this->orgService = new OrganizationService();
    }

    /**
     * GET /api/v1/organization/tree
     * Retrieve the hierarchical organizational tree.
     */
    public function tree()
    {
        try {
            $tree = $this->orgService->getTree();
            return $this->respondSuccess($tree, 'Organizational hierarchy tree retrieved successfully.');
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching org tree: ' . $e->getMessage());
            return $this->respondError('Failed to fetch organizational tree.', ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/v1/organization/units
     * Flat list of all organizational units.
     */
    public function units()
    {
        $status = $this->request->getGet('status');
        try {
            $units = $this->orgService->getUnits($status ?: null);
            return $this->respondSuccess($units, 'Organizational units retrieved successfully.', ResponseInterface::HTTP_OK, [
                'total' => count($units),
            ]);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/v1/organization/units/(:num)
     * Retrieve single unit with parent, sub-units, and positions.
     */
    public function showUnit($id)
    {
        $unit = $this->orgService->getUnit((int)$id);
        if (!$unit) {
            return $this->respondNotFound("Organizational unit #{$id} not found.");
        }
        return $this->respondSuccess($unit, 'Organizational unit retrieved successfully.');
    }

    /**
     * POST /api/v1/organization/units
     * Create a new organizational unit.
     */
    public function createUnit()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['code']) || empty($json['name']) || empty($json['type'])) {
            return $this->respondError(
                'Missing required fields (code, name, type).',
                ResponseInterface::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $unit = $this->orgService->createUnit($json, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($unit, 'Organizational unit created successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * PUT /api/v1/organization/units/(:num)
     * Update an organizational unit.
     */
    public function updateUnit($id)
    {
        $json = $this->request->getJSON(true) ?? $this->request->getRawInput();
        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $unit = $this->orgService->updateUnit((int)$id, $json, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($unit, 'Organizational unit updated successfully.');
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/v1/organization/positions
     * List organizational positions.
     */
    public function positions()
    {
        $unitId = $this->request->getGet('unit_id');
        try {
            $positions = $this->orgService->getPositions($unitId ? (int)$unitId : null);
            return $this->respondSuccess($positions, 'Organizational positions retrieved successfully.', ResponseInterface::HTTP_OK, [
                'total' => count($positions),
            ]);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/v1/organization/positions/(:num)
     * Retrieve single position.
     */
    public function showPosition($id)
    {
        $pos = $this->orgService->getPosition((int)$id);
        if (!$pos) {
            return $this->respondNotFound("Position #{$id} not found.");
        }
        return $this->respondSuccess($pos, 'Position details retrieved successfully.');
    }

    /**
     * POST /api/v1/organization/positions
     * Create a new position.
     */
    public function createPosition()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['code']) || empty($json['title']) || empty($json['unit_id'])) {
            return $this->respondError('Missing required fields: code, title, unit_id', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $pos = $this->orgService->createPosition($json, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($pos, 'Position created successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/v1/organization/appointments
     * List appointments.
     */
    public function appointments()
    {
        $unitId = $this->request->getGet('unit_id');
        $positionId = $this->request->getGet('position_id');
        $status = $this->request->getGet('status');

        try {
            $appointments = $this->orgService->getAppointments(
                $unitId ? (int)$unitId : null,
                $positionId ? (int)$positionId : null,
                $status ?: null
            );
            return $this->respondSuccess($appointments, 'Appointments retrieved successfully.', ResponseInterface::HTTP_OK, [
                'total' => count($appointments),
            ]);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/v1/organization/appointments
     * Assign a human or AI agent to a position.
     */
    public function createAppointment()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['position_id']) || empty($json['appointee_id']) || empty($json['appointee_name'])) {
            return $this->respondError('Missing required fields: position_id, appointee_id, appointee_name', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $appt = $this->orgService->createAppointment($json, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($appt, 'Appointment created successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/v1/organization/appointments/(:num)/end
     * End an active appointment.
     */
    public function endAppointment($id)
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $reason = $json['reason'] ?? 'Normal appointment rotation or conclusion.';

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $ended = $this->orgService->endAppointment((int)$id, $reason, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($ended, 'Appointment ended successfully.');
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/v1/organization/reporting
     * List reporting relationships.
     */
    public function reporting()
    {
        $posId = $this->request->getGet('position_id');
        try {
            $relationships = $this->orgService->getReportingRelationships($posId ? (int)$posId : null);
            return $this->respondSuccess($relationships, 'Reporting relationships retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/v1/organization/reporting
     * Add a reporting relationship.
     */
    public function addReporting()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['subordinate_position_id']) || empty($json['supervisor_position_id'])) {
            return $this->respondError('subordinate_position_id and supervisor_position_id are required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $rel = $this->orgService->addReportingRelationship(
                (int)$json['subordinate_position_id'],
                (int)$json['supervisor_position_id'],
                $json['relationship_type'] ?? 'direct',
                $json['description'] ?? null,
                $actorId ? (int)$actorId : null
            );
            return $this->respondSuccess($rel, 'Reporting relationship created successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
