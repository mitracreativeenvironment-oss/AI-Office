<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use App\Services\ProjectAccessService;
use App\Services\ProjectService;
use App\Services\ProjectLifecycleService;
use App\Services\ClientManagementService;
use App\Services\ScopeDeliverableService;
use App\Services\ProjectRiskIssueService;
use App\Services\ProjectResourceBudgetService;
use Exception;

class ProjectController extends BaseApiController
{
    protected ProjectAccessService $accessService;
    protected ProjectService $projectService;
    protected ProjectLifecycleService $lifecycleService;
    protected ClientManagementService $clientService;
    protected ScopeDeliverableService $scopeService;
    protected ProjectRiskIssueService $riskIssueService;
    protected ProjectResourceBudgetService $resourceBudgetService;

    public function __construct()
    {
        $this->accessService = new ProjectAccessService();
        $this->projectService = new ProjectService($this->accessService);
        $this->lifecycleService = new ProjectLifecycleService($this->projectService);
        $this->clientService = new ClientManagementService();
        $this->scopeService = new ScopeDeliverableService($this->projectService);
        $this->riskIssueService = new ProjectRiskIssueService($this->projectService);
        $this->resourceBudgetService = new ProjectResourceBudgetService($this->projectService);
    }

    protected function getUserContext(): array
    {
        $u = $this->request->authenticatedUser ?? null;
        if (!$u) {
            return [
                'id'       => 1,
                'username' => 'system_owner',
                'roles'    => ['superadmin', 'owner'],
                'unit_id'  => 1,
                'agent_id' => null,
                'type'     => 'human',
            ];
        }

        return [
            'id'       => $u->id ?? null,
            'username' => $u->username ?? null,
            'name'     => $u->full_name ?? ($u->username ?? 'User'),
            'roles'    => $u->roles ?? [],
            'unit_id'  => $u->unit_id ?? null,
            'agent_id' => $u->agent_id ?? null,
            'type'     => !empty($u->agent_id) ? 'agent' : 'human',
        ];
    }

    // ==========================================
    // 1. Projects & Portfolio Overview
    // ==========================================

    public function index(): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $filters = [
                'project_type'    => $this->request->getGet('project_type'),
                'category'        => $this->request->getGet('category'),
                'status'          => $this->request->getGet('status'),
                'lifecycle_stage' => $this->request->getGet('lifecycle_stage'),
                'health_status'   => $this->request->getGet('health_status'),
                'client_id'       => $this->request->getGet('client_id'),
                'unit_id'         => $this->request->getGet('unit_id'),
                'search'          => $this->request->getGet('search'),
            ];

            $data = $this->projectService->getProjects($filters, $user);
            return $this->respondSuccess($data, 'Projects retrieved successfully');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function portfolioMetrics(): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $data = $this->projectService->getPortfolioMetrics($user);
            return $this->respondSuccess($data, 'Portfolio metrics retrieved successfully');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function show($id = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $project = $this->projectService->getProjectById((int)$id, $user);
            if (!$project) {
                return $this->respondNotFound("Project #{$id} not found or access restricted");
            }
            return $this->respondSuccess($project, 'Project details retrieved successfully');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function create(): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $created = $this->projectService->createProject($body, $user);
            return $this->respondSuccess($created, 'Project created successfully', ResponseInterface::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function update($id = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getRawInput();
            $updated = $this->projectService->updateProject((int)$id, $body, $user);
            return $this->respondSuccess($updated, 'Project updated successfully');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function delete($id = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $deleted = $this->projectService->deleteProject((int)$id, $user);
            if (!$deleted) {
                return $this->respondNotFound("Project #{$id} not found");
            }
            return $this->respondSuccess(['id' => (int)$id], 'Project marked as cancelled');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    // ==========================================
    // 2. Lifecycle & Stage Transitions
    // ==========================================

    public function transitionStage($id = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $targetStage = $body['target_stage'] ?? '';
            $reason = $body['reason'] ?? 'Stage transition requested';
            $options = $body['options'] ?? [];

            $result = $this->lifecycleService->transitionStage((int)$id, $targetStage, $reason, $user, $options);
            return $this->respondSuccess($result, 'Lifecycle stage transitioned successfully');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function statusHistory($id = null): ResponseInterface
    {
        try {
            $data = $this->lifecycleService->getStatusHistory((int)$id);
            return $this->respondSuccess($data, 'Status history retrieved successfully');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    // ==========================================
    // 3. Tasks Linkage (Integration with workforce_tasks)
    // ==========================================

    public function getLinkedTasks($id = null): ResponseInterface
    {
        try {
            $tasks = $this->projectService->getLinkedTasks((int)$id);
            return $this->respondSuccess($tasks, 'Linked workforce tasks retrieved successfully');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function linkTask($id = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $taskId = (int)($body['task_id'] ?? 0);
            $milestoneId = !empty($body['milestone_id']) ? (int)$body['milestone_id'] : null;

            $this->projectService->linkTask((int)$id, $taskId, $milestoneId, $user);
            return $this->respondSuccess(['task_id' => $taskId, 'project_id' => (int)$id], 'Task linked to project successfully');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function unlinkTask($id = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $taskId = (int)($body['task_id'] ?? 0);

            $this->projectService->unlinkTask((int)$id, $taskId, $user);
            return $this->respondSuccess(['task_id' => $taskId], 'Task unlinked successfully');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function recalculateProgress($id = null): ResponseInterface
    {
        try {
            $newProgress = $this->projectService->recalculateProjectProgress((int)$id);
            return $this->respondSuccess(['progress_percent' => $newProgress], 'Project progress recalculated');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    // ==========================================
    // 4. Clients & Contacts
    // ==========================================

    public function listClients(): ResponseInterface
    {
        try {
            $filters = [
                'status' => $this->request->getGet('status'),
                'search' => $this->request->getGet('search'),
            ];
            $data = $this->clientService->getClients($filters);
            return $this->respondSuccess($data, 'Clients retrieved successfully');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function showClient($id = null): ResponseInterface
    {
        try {
            $client = $this->clientService->getClientById((int)$id);
            if (!$client) {
                return $this->respondNotFound("Client #{$id} not found");
            }
            return $this->respondSuccess($client, 'Client details retrieved');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function createClient(): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $created = $this->clientService->createClient($body, $user);
            return $this->respondSuccess($created, 'Client created successfully', ResponseInterface::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function updateClient($id = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getRawInput();
            $updated = $this->clientService->updateClient((int)$id, $body, $user);
            return $this->respondSuccess($updated, 'Client updated successfully');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function listContacts($clientId = null): ResponseInterface
    {
        try {
            $contacts = $this->clientService->getContacts((int)$clientId);
            return $this->respondSuccess($contacts, 'Contacts retrieved');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function createContact($clientId = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $contact = $this->clientService->createContact((int)$clientId, $body, $user);
            return $this->respondSuccess($contact, 'Contact added successfully', ResponseInterface::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function updateContact($contactId = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getRawInput();
            $contact = $this->clientService->updateContact((int)$contactId, $body, $user);
            return $this->respondSuccess($contact, 'Contact updated successfully');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function deleteContact($contactId = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $this->clientService->deleteContact((int)$contactId, $user);
            return $this->respondSuccess(['id' => (int)$contactId], 'Contact deleted');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    // ==========================================
    // 5. Briefs & Scopes
    // ==========================================

    public function getBrief($id = null): ResponseInterface
    {
        try {
            $brief = $this->scopeService->getProjectBrief((int)$id);
            return $this->respondSuccess($brief, 'Project brief retrieved');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function upsertBrief($id = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $brief = $this->scopeService->upsertProjectBrief((int)$id, $body, $user);
            return $this->respondSuccess($brief, 'Project brief saved successfully');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function getScopes($id = null): ResponseInterface
    {
        try {
            $scopes = $this->scopeService->getProjectScopes((int)$id);
            return $this->respondSuccess($scopes, 'Project scopes retrieved');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function createScopeBaseline($id = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $scope = $this->scopeService->createScopeBaseline((int)$id, $body, $user);
            return $this->respondSuccess($scope, 'Scope baseline created successfully', ResponseInterface::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    // ==========================================
    // 6. Milestones & Deliverables
    // ==========================================

    public function getMilestones($id = null): ResponseInterface
    {
        try {
            $milestones = $this->scopeService->getMilestones((int)$id);
            return $this->respondSuccess($milestones, 'Milestones retrieved');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function createMilestone($id = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $milestone = $this->scopeService->createMilestone((int)$id, $body, $user);
            return $this->respondSuccess($milestone, 'Milestone created successfully', ResponseInterface::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function updateMilestone($milestoneId = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getRawInput();
            $milestone = $this->scopeService->updateMilestone((int)$milestoneId, $body, $user);
            return $this->respondSuccess($milestone, 'Milestone updated');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function deleteMilestone($milestoneId = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $this->scopeService->deleteMilestone((int)$milestoneId, $user);
            return $this->respondSuccess(['id' => (int)$milestoneId], 'Milestone deleted');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function getDeliverables($id = null): ResponseInterface
    {
        try {
            $milestoneId = $this->request->getGet('milestone_id') ? (int)$this->request->getGet('milestone_id') : null;
            $deliverables = $this->scopeService->getDeliverables((int)$id, $milestoneId);
            return $this->respondSuccess($deliverables, 'Deliverables retrieved');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function createDeliverable($id = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $deliverable = $this->scopeService->createDeliverable((int)$id, $body, $user);
            return $this->respondSuccess($deliverable, 'Deliverable created successfully', ResponseInterface::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function updateDeliverable($deliverableId = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getRawInput();
            $deliverable = $this->scopeService->updateDeliverable((int)$deliverableId, $body, $user);
            return $this->respondSuccess($deliverable, 'Deliverable updated');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function submitDeliverable($deliverableId = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $evidenceUrl = $body['supporting_evidence_url'] ?? '';
            $deliverable = $this->scopeService->submitDeliverableForReview((int)$deliverableId, $evidenceUrl, $user);
            return $this->respondSuccess($deliverable, 'Deliverable submitted for review');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function reviewDeliverable($deliverableId = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $status = $body['review_status'] ?? 'accepted';
            $feedback = $body['feedback'] ?? null;
            $deliverable = $this->scopeService->reviewDeliverable((int)$deliverableId, $status, $feedback, $user);
            return $this->respondSuccess($deliverable, 'Deliverable review submitted');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function clientAcceptDeliverable($deliverableId = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $deliverable = $this->scopeService->clientAcceptDeliverable((int)$deliverableId, $user);
            return $this->respondSuccess($deliverable, 'Deliverable accepted by client');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    // ==========================================
    // 7. Risks, Issues & Change Requests
    // ==========================================

    public function getRisks($id = null): ResponseInterface
    {
        try {
            $risks = $this->riskIssueService->getRisks((int)$id);
            return $this->respondSuccess($risks, 'Risks retrieved');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function createRisk($id = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $risk = $this->riskIssueService->createRisk((int)$id, $body, $user);
            return $this->respondSuccess($risk, 'Risk registered successfully', ResponseInterface::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function updateRisk($riskId = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getRawInput();
            $risk = $this->riskIssueService->updateRisk((int)$riskId, $body, $user);
            return $this->respondSuccess($risk, 'Risk updated');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function deleteRisk($riskId = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $this->riskIssueService->deleteRisk((int)$riskId, $user);
            return $this->respondSuccess(['id' => (int)$riskId], 'Risk removed');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function getIssues($id = null): ResponseInterface
    {
        try {
            $issues = $this->riskIssueService->getIssues((int)$id);
            return $this->respondSuccess($issues, 'Issues retrieved');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function createIssue($id = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $issue = $this->riskIssueService->createIssue((int)$id, $body, $user);
            return $this->respondSuccess($issue, 'Issue logged successfully', ResponseInterface::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function updateIssue($issueId = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getRawInput();
            $issue = $this->riskIssueService->updateIssue((int)$issueId, $body, $user);
            return $this->respondSuccess($issue, 'Issue updated');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function resolveIssue($issueId = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $plan = $body['resolution_plan'] ?? 'Issue resolved.';
            $issue = $this->riskIssueService->resolveIssue((int)$issueId, $plan, $user);
            return $this->respondSuccess($issue, 'Issue resolved successfully');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function getChangeRequests($id = null): ResponseInterface
    {
        try {
            $cr = $this->riskIssueService->getChangeRequests((int)$id);
            return $this->respondSuccess($cr, 'Change requests retrieved');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function createChangeRequest($id = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $cr = $this->riskIssueService->createChangeRequest((int)$id, $body, $user);
            return $this->respondSuccess($cr, 'Change request submitted', ResponseInterface::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function evaluateChangeRequest($crId = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $decision = $body['decision'] ?? 'approved';
            $rationale = $body['rationale'] ?? null;
            $cr = $this->riskIssueService->evaluateChangeRequest((int)$crId, $decision, $rationale, $user);
            return $this->respondSuccess($cr, "Change request {$decision}");
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    // ==========================================
    // 8. Team, Resource Allocations & Budget
    // ==========================================

    public function getTeam($id = null): ResponseInterface
    {
        try {
            $team = $this->resourceBudgetService->getTeamMembers((int)$id);
            return $this->respondSuccess($team, 'Team members retrieved');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function addTeamMember($id = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $member = $this->resourceBudgetService->addTeamMember((int)$id, $body, $user);
            return $this->respondSuccess($member, 'Team member assigned successfully', ResponseInterface::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function updateTeamMember($memberId = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getRawInput();
            $member = $this->resourceBudgetService->updateTeamMember((int)$memberId, $body, $user);
            return $this->respondSuccess($member, 'Team member updated');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function removeTeamMember($memberId = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $this->resourceBudgetService->removeTeamMember((int)$memberId, $user);
            return $this->respondSuccess(['id' => (int)$memberId], 'Team member released');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function getResources($id = null): ResponseInterface
    {
        try {
            $resources = $this->resourceBudgetService->getResourceAllocations((int)$id);
            return $this->respondSuccess($resources, 'Resource allocations retrieved');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function addResource($id = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $res = $this->resourceBudgetService->addResourceAllocation((int)$id, $body, $user);
            return $this->respondSuccess($res, 'Resource allocation registered', ResponseInterface::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function updateResourceUtilization($allocId = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getPost();
            $utilized = (float)($body['utilized_quantity'] ?? 0.0);
            $res = $this->resourceBudgetService->updateResourceUtilization((int)$allocId, $utilized, $user);
            return $this->respondSuccess($res, 'Resource utilization recorded');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function getBudget($id = null): ResponseInterface
    {
        try {
            $budget = $this->resourceBudgetService->getBudgetSummary((int)$id);
            return $this->respondSuccess($budget, 'Budget summary retrieved');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function updateBudget($id = null): ResponseInterface
    {
        try {
            $user = $this->getUserContext();
            $body = $this->request->getJSON(true) ?? $this->request->getRawInput();
            $proposed = (float)($body['proposed_budget'] ?? 0.0);
            $approved = (float)($body['approved_budget_limit'] ?? 0.0);
            $res = $this->resourceBudgetService->updateBudget((int)$id, $proposed, $approved, $user);
            return $this->respondSuccess($res, 'Budget limits updated');
        } catch (Exception $e) {
            return $this->respondError($e->getMessage(), ResponseInterface::HTTP_BAD_REQUEST);
        }
    }
}
