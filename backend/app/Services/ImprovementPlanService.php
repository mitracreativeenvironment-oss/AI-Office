<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class ImprovementPlanService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function listPlans(array $filters = []): array
    {
        $builder = $this->db->table('performance_improvement_plans pip')
            ->select('pip.*, a.agent_code, a.name as agent_name, wt.title as bound_task_title, wt.status as bound_task_status')
            ->join('ai_agent_registry a', 'a.id = pip.responsible_agent_id', 'left')
            ->join('workforce_tasks wt', 'wt.id = pip.bound_task_id', 'left')
            ->orderBy('pip.id', 'DESC');

        if (!empty($filters['target_type'])) {
            $builder->where('pip.target_type', $filters['target_type']);
        }
        if (!empty($filters['target_id'])) {
            $builder->where('pip.target_id', (int)$filters['target_id']);
        }
        if (!empty($filters['status'])) {
            $builder->where('pip.status', $filters['status']);
        }
        if (!empty($filters['responsible_agent_id'])) {
            $builder->where('pip.responsible_agent_id', (int)$filters['responsible_agent_id']);
        }

        return $builder->get()->getResultArray();
    }

    public function getPlan(int $id): ?array
    {
        $plan = $this->db->table('performance_improvement_plans pip')
            ->select('pip.*, a.agent_code, a.name as agent_name, wt.title as bound_task_title, wt.status as bound_task_status, ape.evaluation_code, ape.composite_score')
            ->join('ai_agent_registry a', 'a.id = pip.responsible_agent_id', 'left')
            ->join('workforce_tasks wt', 'wt.id = pip.bound_task_id', 'left')
            ->join('agent_performance_evaluations ape', 'ape.id = pip.evaluation_id', 'left')
            ->where('pip.id', $id)
            ->get()
            ->getRowArray();

        return $plan ?: null;
    }

    public function createPlan(array $data, ?int $userId = null): array
    {
        $now = date('Y-m-d H:i:s');
        $code = trim($data['plan_code'] ?? 'PIP-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4)));

        $insert = [
            'plan_code'              => $code,
            'target_type'            => $data['target_type'] ?? 'agent',
            'target_id'              => (int)($data['target_id'] ?? $data['responsible_agent_id'] ?? 1),
            'evaluation_id'          => !empty($data['evaluation_id']) ? (int)$data['evaluation_id'] : null,
            'objective'              => trim($data['objective'] ?? 'Remediate performance gaps and attain target benchmark'),
            'identified_gap'         => trim($data['identified_gap'] ?? 'Delivery latency or quality deficit below acceptable SLA threshold'),
            'recommended_action'     => trim($data['recommended_action'] ?? 'Structured peer mentoring and dedicated skill calibration modules'),
            'bound_task_id'          => !empty($data['bound_task_id']) ? (int)$data['bound_task_id'] : null,
            'responsible_agent_id'   => !empty($data['responsible_agent_id']) ? (int)$data['responsible_agent_id'] : (int)($data['target_id'] ?? 1),
            'responsible_user_id'    => $userId,
            'target_completion_date' => $data['target_completion_date'] ?? date('Y-m-d', strtotime('+30 days')),
            'success_criteria'       => trim($data['success_criteria'] ?? 'Attain >= 80 score on quality and 100% adherence to deadlines'),
            'progress_percent'       => (int)($data['progress_percent'] ?? 0),
            'status'                 => $data['status'] ?? 'proposed',
            'resolution_evidence'    => $data['resolution_evidence'] ?? null,
            'created_at'             => $now,
            'updated_at'             => $now,
        ];

        $this->db->table('performance_improvement_plans')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();

        // Audit Trail
        $this->db->table('performance_audit_trail')->insert([
            'event_type'     => 'pip_created',
            'target_entity'  => 'performance_improvement_plans',
            'target_id'      => $insert['id'],
            'actor_type'     => $userId ? 'human' : 'system',
            'actor_id'       => $userId,
            'action_summary' => "Created Performance Improvement Plan {$code} for target #{$insert['target_id']}",
            'created_at'     => $now,
        ]);

        return $insert;
    }

    public function updatePlan(int $id, array $data, ?int $userId = null): ?array
    {
        $plan = $this->getPlan($id);
        if (!$plan) return null;

        $now = date('Y-m-d H:i:s');
        $update = ['updated_at' => $now];

        $allowed = [
            'objective', 'identified_gap', 'recommended_action', 'bound_task_id',
            'target_completion_date', 'success_criteria', 'progress_percent',
            'resolution_evidence', 'status'
        ];

        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $update[$f] = $data[$f];
            }
        }

        if (isset($update['status']) && $update['status'] === 'completed' && empty($plan['completed_at'])) {
            $update['completed_at'] = $now;
            $update['reviewed_by_user_id'] = $userId;
            $update['progress_percent'] = 100;
        }

        $this->db->table('performance_improvement_plans')->where('id', $id)->update($update);

        // Audit Trail
        $this->db->table('performance_audit_trail')->insert([
            'event_type'     => 'pip_updated',
            'target_entity'  => 'performance_improvement_plans',
            'target_id'      => $id,
            'actor_type'     => $userId ? 'human' : 'system',
            'actor_id'       => $userId,
            'action_summary' => "Updated Performance Improvement Plan {$plan['plan_code']}",
            'created_at'     => $now,
        ]);

        return $this->getPlan($id);
    }

    public function transitionStatus(int $id, string $targetStatus, ?int $userId = null, ?string $resolutionEvidence = null): ?array
    {
        $plan = $this->getPlan($id);
        if (!$plan) return null;

        $validStatuses = ['proposed', 'approved', 'in_progress', 'under_review', 'completed', 'cancelled'];
        if (!in_array($targetStatus, $validStatuses, true)) {
            throw new \InvalidArgumentException("Invalid PIP status '{$targetStatus}'.", 400);
        }

        $now = date('Y-m-d H:i:s');
        $update = [
            'status'     => $targetStatus,
            'updated_at' => $now,
        ];

        if ($resolutionEvidence !== null) {
            $update['resolution_evidence'] = $resolutionEvidence;
        }

        if ($targetStatus === 'completed') {
            $update['completed_at'] = $now;
            $update['reviewed_by_user_id'] = $userId;
            $update['progress_percent'] = 100;
        }

        $this->db->table('performance_improvement_plans')->where('id', $id)->update($update);

        // Audit Trail
        $this->db->table('performance_audit_trail')->insert([
            'event_type'     => 'pip_status_transition',
            'target_entity'  => 'performance_improvement_plans',
            'target_id'      => $id,
            'actor_type'     => $userId ? 'human' : 'system',
            'actor_id'       => $userId,
            'action_summary' => "Transitioned PIP {$plan['plan_code']} status from '{$plan['status']}' to '{$targetStatus}'",
            'created_at'     => $now,
        ]);

        return $this->getPlan($id);
    }

    public function linkTask(int $planId, int $taskId, ?int $userId = null): ?array
    {
        $plan = $this->getPlan($planId);
        if (!$plan) return null;

        $task = $this->db->table('workforce_tasks')->where('id', $taskId)->get()->getRowArray();
        if (!$task) {
            throw new \InvalidArgumentException("Workforce task #{$taskId} does not exist.", 404);
        }

        $this->db->table('performance_improvement_plans')->where('id', $planId)->update([
            'bound_task_id' => $taskId,
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        return $this->getPlan($planId);
    }
}
