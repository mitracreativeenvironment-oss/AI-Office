<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use App\Services\WelfareOverviewService;
use App\Services\WelfarePolicyService;
use App\Services\WelfareCompensationService;
use App\Services\WelfareBonusService;
use App\Services\WelfareRecognitionService;
use App\Services\WelfareLedgerService;
use App\Services\WelfareFacilityService;

class WelfareController extends BaseApiController
{
    protected WelfareOverviewService $overviewService;
    protected WelfarePolicyService $policyService;
    protected WelfareCompensationService $compensationService;
    protected WelfareBonusService $bonusService;
    protected WelfareRecognitionService $recognitionService;
    protected WelfareLedgerService $ledgerService;
    protected WelfareFacilityService $facilityService;

    public function __construct()
    {
        $this->overviewService = new WelfareOverviewService();
        $this->policyService = new WelfarePolicyService();
        $this->compensationService = new WelfareCompensationService();
        $this->bonusService = new WelfareBonusService();
        $this->recognitionService = new WelfareRecognitionService();
        $this->ledgerService = new WelfareLedgerService();
        $this->facilityService = new WelfareFacilityService();
    }

    private function getActorUserId(): int
    {
        return (int)($this->request->authUser['id'] ?? 1);
    }

    // ------------------------------------------------------------------------
    // 1. OVERVIEW & METRICS
    // ------------------------------------------------------------------------

    public function overview()
    {
        try {
            $data = $this->overviewService->getOverview();
            return $this->respondSuccess($data, 'Agent Welfare campus overview retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ------------------------------------------------------------------------
    // 2. WELFARE POLICIES
    // ------------------------------------------------------------------------

    public function listPolicies()
    {
        try {
            $data = $this->policyService->listPolicies();
            return $this->respondSuccess($data, 'Welfare policies retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function activePolicy()
    {
        try {
            $data = $this->policyService->getActivePolicy();
            return $this->respondSuccess($data, 'Active welfare policy retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createPolicy()
    {
        try {
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $data = $this->policyService->createPolicy($body, $this->getActorUserId());
            return $this->respondSuccess($data, 'Welfare policy draft created.', ResponseInterface::HTTP_CREATED);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approvePolicy($id = null)
    {
        try {
            if (!$id) return $this->respondError('Policy ID required.');
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $data = $this->policyService->approvePolicy((int)$id, $this->getActorUserId(), $body['notes'] ?? null);
            return $this->respondSuccess($data, "Welfare policy #{$id} approved and activated.");
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ------------------------------------------------------------------------
    // 3. BASE COMPENSATION PERIODS
    // ------------------------------------------------------------------------

    public function listPeriods()
    {
        try {
            $filters = $this->request->getGet();
            $data = $this->compensationService->listPeriods($filters);
            return $this->respondSuccess($data, 'Compensation periods retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getPeriod($id = null)
    {
        try {
            if (!$id) return $this->respondError('Period ID required.');
            $data = $this->compensationService->getPeriodDetail((int)$id);
            if (!$data) return $this->respondNotFound("Period #{$id} not found.");
            return $this->respondSuccess($data, "Period #{$id} details retrieved.");
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createPeriod()
    {
        try {
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $data = $this->compensationService->createPeriod($body, $this->getActorUserId());
            return $this->respondSuccess($data, 'Compensation period created and calculated.', ResponseInterface::HTTP_CREATED);
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function calculatePeriod($id = null)
    {
        try {
            if (!$id) return $this->respondError('Period ID required.');
            $data = $this->compensationService->calculatePeriod((int)$id, $this->getActorUserId());
            return $this->respondSuccess($data, "Period #{$id} recalculated.");
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approvePeriod($id = null)
    {
        try {
            if (!$id) return $this->respondError('Period ID required.');
            $data = $this->compensationService->approvePeriod((int)$id, $this->getActorUserId());
            return $this->respondSuccess($data, "Period #{$id} approved for distribution.");
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function distributePeriod($id = null)
    {
        try {
            if (!$id) return $this->respondError('Period ID required.');
            $data = $this->compensationService->distributePeriodTickets((int)$id, $this->getActorUserId());
            return $this->respondSuccess($data, "Tickets for Period #{$id} distributed to agent balances.");
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ------------------------------------------------------------------------
    // 4. VERIFIED TASK BONUSES
    // ------------------------------------------------------------------------

    public function listTaskBonuses()
    {
        try {
            $filters = $this->request->getGet();
            $data = $this->bonusService->listTaskBonuses($filters);
            return $this->respondSuccess($data, 'Task bonus evaluations retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function evaluateTaskBonus($taskId = null)
    {
        try {
            if (!$taskId) return $this->respondError('Task ID required.');
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $data = $this->bonusService->evaluateTaskBonus((int)$taskId, $this->getActorUserId(), $body ?? []);
            return $this->respondSuccess($data, "Task #{$taskId} evaluated for bonus eligibility.", ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approveTaskBonus($id = null)
    {
        try {
            if (!$id) return $this->respondError('Evaluation ID required.');
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $approvedAmount = isset($body['amount']) ? (float)$body['amount'] : null;
            $data = $this->bonusService->approveTaskBonus((int)$id, $this->getActorUserId(), $approvedAmount);
            return $this->respondSuccess($data, "Task bonus evaluation #{$id} approved and credited.");
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ------------------------------------------------------------------------
    // 5. SPECIAL RECOGNITIONS
    // ------------------------------------------------------------------------

    public function listRecognitions()
    {
        try {
            $filters = $this->request->getGet();
            $data = $this->recognitionService->listRecognitions($filters);
            return $this->respondSuccess($data, 'Special recognitions retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function nominateRecognition()
    {
        try {
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $data = $this->recognitionService->nominateRecognition($body, $this->getActorUserId());
            return $this->respondSuccess($data, 'Special recognition nominated.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approveRecognition($id = null)
    {
        try {
            if (!$id) return $this->respondError('Recognition ID required.');
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $approvedAmount = isset($body['amount']) ? (float)$body['amount'] : null;
            $data = $this->recognitionService->approveRecognition((int)$id, $this->getActorUserId(), $approvedAmount);
            return $this->respondSuccess($data, "Special recognition #{$id} approved and credited.");
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ------------------------------------------------------------------------
    // 6. WELFARE TICKET LEDGER & TRANSACTIONS
    // ------------------------------------------------------------------------

    public function listTransactions()
    {
        try {
            $filters = $this->request->getGet();
            $limit = isset($filters['limit']) ? (int)$filters['limit'] : 50;
            $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
            $data = $this->ledgerService->listTransactions($filters, $limit, $offset);
            return $this->respondSuccess($data, 'Ledger transactions retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getAgentBalance($agentId = null)
    {
        try {
            if (!$agentId) return $this->respondError('Agent ID required.');
            $data = $this->ledgerService->getAgentBalance((int)$agentId);
            return $this->respondSuccess($data, "Agent #{$agentId} balance retrieved.");
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function reconcileAgentBalance($agentId = null)
    {
        try {
            if (!$agentId) return $this->respondError('Agent ID required.');
            $data = $this->ledgerService->reconcileLedger((int)$agentId);
            return $this->respondSuccess($data, "Agent #{$agentId} balance reconciled with immutable ledger.");
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function postAdjustment()
    {
        try {
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $agentId = (int)($body['agent_id'] ?? 0);
            $amount = (float)($body['amount'] ?? 0);
            $reason = trim($body['reason'] ?? 'Administrative balance adjustment');
            $direction = $body['direction'] ?? 'credit';

            $data = $this->ledgerService->postAdjustment($agentId, $amount, $reason, $this->getActorUserId(), $direction);
            return $this->respondSuccess($data, 'Adjustment transaction posted to ledger.', ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function reverseTransaction($id = null)
    {
        try {
            if (!$id) return $this->respondError('Transaction ID required.');
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $reason = trim($body['reason'] ?? 'Reversal of erroneous transaction');

            $data = $this->ledgerService->postReversal((int)$id, $reason, $this->getActorUserId());
            return $this->respondSuccess($data, "Transaction #{$id} reversed.");
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ------------------------------------------------------------------------
    // 7. WELFARE FACILITIES & USAGE
    // ------------------------------------------------------------------------

    public function listFacilities()
    {
        try {
            $data = $this->facilityService->listFacilities();
            return $this->respondSuccess($data, 'Welfare facilities retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function checkInFacility($id = null)
    {
        try {
            if (!$id) return $this->respondError('Facility ID required.');
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $agentId = (int)($body['agent_id'] ?? 0);
            $sessionType = $body['session_type'] ?? 'duty_rest_recovery';
            $wsId = isset($body['workstation_id']) ? (int)$body['workstation_id'] : null;
            $notes = $body['notes'] ?? null;

            $data = $this->facilityService->checkInFacility((int)$id, $agentId, $sessionType, $wsId, $notes);
            return $this->respondSuccess($data, "Agent #{$agentId} checked into facility #{$id}.", ResponseInterface::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function checkOutFacility($sessionId = null)
    {
        try {
            if (!$sessionId) return $this->respondError('Session ID required.');
            $data = $this->facilityService->checkOutFacility((int)$sessionId);
            return $this->respondSuccess($data, "Facility session #{$sessionId} concluded.");
        } catch (\InvalidArgumentException $e) {
            return $this->respondNotFound($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function listFacilityLogs()
    {
        try {
            $filters = $this->request->getGet();
            $data = $this->facilityService->listUsageLogs($filters);
            return $this->respondSuccess($data, 'Facility usage logs retrieved.');
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
