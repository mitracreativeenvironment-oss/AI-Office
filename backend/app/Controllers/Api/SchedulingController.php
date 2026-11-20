<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use App\Services\WorkforceSchedulingService;
use App\Services\OperationalCoverageService;

class SchedulingController extends BaseApiController
{
    protected WorkforceSchedulingService $schedulingService;
    protected OperationalCoverageService $coverageService;

    public function __construct()
    {
        $this->schedulingService = new WorkforceSchedulingService();
        $this->coverageService = new OperationalCoverageService();
    }

    // ------------------------------------------------------------------------
    // POLICIES
    // ------------------------------------------------------------------------

    public function listPolicies()
    {
        try {
            $policies = $this->schedulingService->listPolicies();
            return $this->respondSuccess($policies, 'Workforce schedule policies retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createPolicy()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['name'])) {
            return $this->respondError('Policy name is required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $policy = $this->schedulingService->createPolicy($json);
            return $this->respondSuccess($policy, 'Schedule policy created successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ------------------------------------------------------------------------
    // SHIFTS
    // ------------------------------------------------------------------------

    public function listShifts()
    {
        try {
            $shifts = $this->schedulingService->listShifts();
            return $this->respondSuccess($shifts, 'Workforce shifts retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createShift()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['name']) || empty($json['start_time']) || empty($json['end_time'])) {
            return $this->respondError('Shift name, start time, and end time are required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $shift = $this->schedulingService->createShift($json);
            return $this->respondSuccess($shift, 'Shift created successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ------------------------------------------------------------------------
    // ROSTERS
    // ------------------------------------------------------------------------

    public function listRosters()
    {
        $filters = [
            'status'  => $this->request->getGet('status'),
            'unit_id' => $this->request->getGet('unit_id'),
        ];
        $limit = max(1, min(100, (int)($this->request->getGet('limit') ?? 20)));
        $offset = max(0, (int)($this->request->getGet('offset') ?? 0));

        try {
            $res = $this->schedulingService->listRosters($filters, $limit, $offset);
            return $this->respondSuccess($res['items'], 'Rosters retrieved successfully.', ResponseInterface::HTTP_OK, [
                'total'  => $res['total'],
                'limit'  => $limit,
                'offset' => $offset,
            ]);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function showRoster($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Roster ID required.');
        }

        try {
            $roster = $this->schedulingService->getRoster((int)$id);
            if (!$roster) {
                return $this->respondNotFound("Roster #{$id} not found.");
            }
            return $this->respondSuccess($roster, 'Roster details retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createRoster()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $roster = $this->schedulingService->createRoster($json, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($roster, 'Roster created successfully in Draft.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function publishRoster($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Roster ID required.');
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $roster = $this->schedulingService->publishRoster((int)$id, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($roster, 'Roster published successfully.');
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ------------------------------------------------------------------------
    // DUTY ASSIGNMENTS
    // ------------------------------------------------------------------------

    public function createDutyAssignment()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['agent_id']) || empty($json['roster_id']) || empty($json['assignment_date'])) {
            return $this->respondError('Agent ID, Roster ID, and Assignment Date are required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $assignment = $this->schedulingService->createDutyAssignment($json);
            return $this->respondSuccess($assignment, 'Duty assignment created successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_CONFLICT);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function completeDutyAssignment($id = null)
    {
        if ($id === null) {
            return $this->respondNotFound('Duty assignment ID required.');
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $assignment = $this->schedulingService->completeDutyAssignment((int)$id, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($assignment, 'Duty assignment marked as completed.');
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ------------------------------------------------------------------------
    // AGENT AVAILABILITY & OFF-DUTY
    // ------------------------------------------------------------------------

    public function getAvailability($agentId = null)
    {
        if ($agentId === null) {
            return $this->respondNotFound('Agent ID required.');
        }

        $startDate = $this->request->getGet('start_date');
        $endDate = $this->request->getGet('end_date');

        try {
            $avail = $this->schedulingService->getAgentAvailability((int)$agentId, $startDate, $endDate);
            return $this->respondSuccess($avail, 'Agent availability timeline retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function requestOffDuty($agentId = null)
    {
        if ($agentId === null) {
            return $this->respondNotFound('Agent ID required.');
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $res = $this->schedulingService->requestOffDuty((int)$agentId, $json, $actorId ? (int)$actorId : null);
            $msg = $res['is_fully_approved']
                ? 'Off-duty request approved (prerequisites satisfied).'
                : 'Off-duty request registered in Pending state (mandatory replacement or Wallbook handover required).';
            return $this->respondSuccess($res, $msg, ResponseInterface::HTTP_OK);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ------------------------------------------------------------------------
    // COVERAGE MANAGEMENT
    // ------------------------------------------------------------------------

    public function listCoverageRequirements()
    {
        $unitId = $this->request->getGet('unit_id');

        try {
            $reqs = $this->coverageService->listRequirements($unitId ? (int)$unitId : null);
            return $this->respondSuccess($reqs, 'Coverage requirements retrieved successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function evaluateCoverage()
    {
        $date = $this->request->getGet('date') ?? date('Y-m-d');
        $unitId = $this->request->getGet('unit_id');

        try {
            $eval = $this->coverageService->evaluateCoverage($date, $unitId ? (int)$unitId : null);
            return $this->respondSuccess($eval, 'Operational coverage evaluated successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getCoverageGaps()
    {
        $date = $this->request->getGet('date') ?? date('Y-m-d');
        $unitId = $this->request->getGet('unit_id');

        try {
            $gaps = $this->coverageService->getCoverageGaps($date, $unitId ? (int)$unitId : null);
            return $this->respondSuccess($gaps, 'Coverage gaps calculated successfully.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
