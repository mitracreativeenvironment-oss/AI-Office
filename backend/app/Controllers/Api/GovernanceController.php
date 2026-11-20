<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use App\Services\GovernanceService;

class GovernanceController extends BaseApiController
{
    protected GovernanceService $govService;

    public function __construct()
    {
        $this->govService = new GovernanceService();
    }

    /**
     * GET /api/v1/governance/core-values
     * List all 7 MCE Core Values (Controlled Reference Data).
     */
    public function coreValues()
    {
        try {
            $values = $this->govService->getCoreValues();
            return $this->respondSuccess($values, 'Core values retrieved successfully.', ResponseInterface::HTTP_OK, [
                'total' => count($values),
            ]);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/v1/governance/bodies
     * List governance bodies with active members.
     */
    public function bodies()
    {
        try {
            $bodies = $this->govService->getBodies();
            return $this->respondSuccess($bodies, 'Governance bodies retrieved successfully.', ResponseInterface::HTTP_OK, [
                'total' => count($bodies),
            ]);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/v1/governance/bodies/(:num)
     * Retrieve single governance body with full membership details.
     */
    public function showBody($id)
    {
        $body = $this->govService->getBody((int)$id);
        if (!$body) {
            return $this->respondNotFound("Governance body #{$id} not found.");
        }
        return $this->respondSuccess($body, 'Governance body retrieved successfully.');
    }

    /**
     * POST /api/v1/governance/bodies/(:num)/members
     * Add member to governance body.
     */
    public function addBodyMember($id)
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['member_id']) || empty($json['member_name'])) {
            return $this->respondError('member_id and member_name are required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $member = $this->govService->addBodyMembership((int)$id, $json, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($member, 'Member added to governance body successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/v1/governance/authority-profiles
     * List DoA authority profiles (Levels A, B, C, Operational).
     */
    public function authorityProfiles()
    {
        try {
            $profiles = $this->govService->getAuthorityProfiles();
            return $this->respondSuccess($profiles, 'Delegation of Authority (DoA) profiles retrieved successfully.', ResponseInterface::HTTP_OK, [
                'total' => count($profiles),
            ]);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/v1/governance/authority-check
     * Evaluate authority limits for an action and amount under DoA rules.
     */
    public function authorityCheck()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $level = $json['authority_level'] ?? 'C';
        $amount = (float)($json['amount'] ?? 0.0);
        $action = (string)($json['action'] ?? 'routine_dispatch');

        try {
            $result = $this->govService->checkAuthority($level, $amount, $action);
            return $this->respondSuccess($result, 'Authority check completed.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/v1/governance/delegations
     * List delegation records.
     */
    public function delegations()
    {
        $status = $this->request->getGet('status');
        try {
            $delegations = $this->govService->getDelegations($status ?: null);
            return $this->respondSuccess($delegations, 'Delegation records retrieved successfully.', ResponseInterface::HTTP_OK, [
                'total' => count($delegations),
            ]);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/v1/governance/delegations/(:num)
     * Retrieve single delegation record.
     */
    public function showDelegation($id)
    {
        $d = $this->govService->getDelegation((int)$id);
        if (!$d) {
            return $this->respondNotFound("Delegation record #{$id} not found.");
        }
        return $this->respondSuccess($d, 'Delegation record retrieved successfully.');
    }

    /**
     * POST /api/v1/governance/delegations
     * Create a formal Delegation of Authority (DoA) record.
     */
    public function createDelegation()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['authority_profile_id']) || empty($json['grantor_name']) || empty($json['grantee_name'])) {
            return $this->respondError('Missing required fields (authority_profile_id, grantor_name, grantee_name).', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $created = $this->govService->createDelegation($json, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($created, 'Delegation record created successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/v1/governance/delegations/(:num)/revoke
     * Revoke an active delegation.
     */
    public function revokeDelegation($id)
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $reason = trim($json['reason'] ?? 'Revoked by authority');

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $actorName = $this->request->authenticatedUser->full_name ?? ($this->request->authenticatedUser->username ?? null);
            $revoked = $this->govService->revokeDelegation((int)$id, $reason, $actorId ? (int)$actorId : null, $actorName);
            return $this->respondSuccess($revoked, 'Delegation revoked successfully.');
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/v1/governance/directions
     * List Owner Directions (advisory vs binding).
     */
    public function directions()
    {
        $status = $this->request->getGet('status');
        $type = $this->request->getGet('type');

        try {
            $directions = $this->govService->getOwnerDirections($status ?: null, $type ?: null);
            return $this->respondSuccess($directions, 'Owner Directions retrieved successfully.', ResponseInterface::HTTP_OK, [
                'total' => count($directions),
            ]);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/v1/governance/directions/(:num)
     * Retrieve single Owner Direction.
     */
    public function showDirection($id)
    {
        $d = $this->govService->getOwnerDirection((int)$id);
        if (!$d) {
            return $this->respondNotFound("Owner Direction #{$id} not found.");
        }
        return $this->respondSuccess($d, 'Owner Direction retrieved successfully.');
    }

    /**
     * POST /api/v1/governance/directions
     * Issue an Owner Direction.
     */
    public function createDirection()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['subject']) || empty($json['description'])) {
            return $this->respondError('subject and description are required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $actorName = $this->request->authenticatedUser->full_name ?? ($this->request->authenticatedUser->username ?? 'MCE Human Owner');
            $json['issued_by_name'] = $json['issued_by_name'] ?? $actorName;
            $json['issued_by_id'] = $json['issued_by_id'] ?? ($actorId ? (int)$actorId : 1);

            $created = $this->govService->createOwnerDirection($json, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($created, 'Owner Direction issued successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/v1/governance/directions/(:num)/acknowledge
     * Acknowledge an Owner Direction.
     */
    public function acknowledgeDirection($id)
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $actorId = $this->request->authenticatedUser->id ?? null;
        $actorName = $this->request->authenticatedUser->full_name ?? ($this->request->authenticatedUser->username ?? 'Executive Board');
        $ackBy = $json['acknowledged_by'] ?? $actorName;
        $notes = $json['notes'] ?? null;

        try {
            $updated = $this->govService->acknowledgeOwnerDirection((int)$id, $ackBy, $notes, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($updated, 'Owner Direction acknowledged successfully.');
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/v1/governance/decisions
     * List Executive Decisions.
     */
    public function decisions()
    {
        $status = $this->request->getGet('status');
        $category = $this->request->getGet('category');

        try {
            $decisions = $this->govService->getExecutiveDecisions($status ?: null, $category ?: null);
            return $this->respondSuccess($decisions, 'Executive Decisions retrieved successfully.', ResponseInterface::HTTP_OK, [
                'total' => count($decisions),
            ]);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/v1/governance/decisions/(:num)
     * Retrieve single Executive Decision.
     */
    public function showDecision($id)
    {
        $d = $this->govService->getExecutiveDecision((int)$id);
        if (!$d) {
            return $this->respondNotFound("Executive Decision #{$id} not found.");
        }
        return $this->respondSuccess($d, 'Executive Decision retrieved successfully.');
    }

    /**
     * POST /api/v1/governance/decisions
     * Record an Executive Decision.
     */
    public function createDecision()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['title']) || empty($json['summary']) || empty($json['governance_body_id'])) {
            return $this->respondError('title, summary, and governance_body_id are required.', ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $actorId = $this->request->authenticatedUser->id ?? null;
            $created = $this->govService->createExecutiveDecision($json, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($created, 'Executive Decision recorded successfully.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/v1/governance/decisions/(:num)/ratify
     * Ratify an Executive Decision.
     */
    public function ratifyDecision($id)
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $actorId = $this->request->authenticatedUser->id ?? null;
        $actorName = $this->request->authenticatedUser->full_name ?? ($this->request->authenticatedUser->username ?? 'Continuous Executive Board');
        $ratifiedBy = $json['ratified_by'] ?? $actorName;
        $notes = $json['notes'] ?? null;

        try {
            $updated = $this->govService->ratifyExecutiveDecision((int)$id, $ratifiedBy, $notes, $actorId ? (int)$actorId : null);
            return $this->respondSuccess($updated, 'Executive Decision ratified successfully.');
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/v1/governance/audit-logs
     * List governance audit trail entries.
     */
    public function auditLogs()
    {
        $limit = (int)($this->request->getGet('limit') ?? 50);
        $offset = (int)($this->request->getGet('offset') ?? 0);

        try {
            $logs = $this->govService->getAuditLogs($limit, $offset);
            return $this->respondSuccess($logs, 'Governance audit logs retrieved successfully.', ResponseInterface::HTTP_OK, [
                'total' => count($logs),
                'limit' => $limit,
                'offset' => $offset,
            ]);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
