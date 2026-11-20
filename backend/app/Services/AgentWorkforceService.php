<?php

namespace App\Services;

use Config\Database;

class AgentWorkforceService
{
    protected $db;

    // 10-state validated lifecycle transition graph
    protected static array $permittedTransitions = [
        'draft'                 => ['proposed', 'archived'],
        'proposed'              => ['under_review', 'draft'],
        'under_review'         => ['approved', 'draft', 'proposed'],
        'approved'              => ['provisioning_pending', 'active'],
        'provisioning_pending' => ['active', 'under_reconfiguration'],
        'active'                => ['temporarily_suspended', 'under_reconfiguration', 'retired'],
        'temporarily_suspended' => ['active', 'under_reconfiguration', 'retired'],
        'under_reconfiguration' => ['active', 'temporarily_suspended', 'retired'],
        'retired'               => ['archived'],
        'archived'              => [], // Terminal historical state
    ];

    public function __construct($db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    // =========================================================================
    // 1. Agent Lifecycle State Machine
    // =========================================================================

    public function getPermittedTransitions(string $currentState): array
    {
        return self::$permittedTransitions[$currentState] ?? [];
    }

    public function transitionState(int $agentId, string $toState, ?string $reason = null, ?string $approvalRef = null, ?int $actorUserId = null): array
    {
        $agent = $this->db->table('ai_agent_registry')->where('id', $agentId)->where('deleted_at IS NULL')->get()->getRowArray();
        if (!$agent) {
            throw new \RuntimeException("Agent #{$agentId} not found.", 404);
        }

        $currentState = $agent['lifecycle_state'];
        if ($currentState === $toState) {
            return $agent;
        }

        $allowed = self::$permittedTransitions[$currentState] ?? [];
        if (!in_array($toState, $allowed, true)) {
            throw new \InvalidArgumentException(
                "Invalid lifecycle state transition from '{$currentState}' to '{$toState}'. Permitted transitions: " . implode(', ', $allowed),
                400
            );
        }

        $now = date('Y-m-d H:i:s');
        $update = [
            'lifecycle_state' => $toState,
            'updated_at'      => $now,
        ];

        if ($toState === 'approved') {
            $update['approved_at'] = $now;
            $update['approved_by_user_id'] = $actorUserId;
        } elseif ($toState === 'active') {
            $update['activated_at'] = $now;
        } elseif ($toState === 'temporarily_suspended') {
            $update['suspended_at'] = $now;
        } elseif ($toState === 'retired') {
            $update['retired_at'] = $now;
            // Conclude active assignments gracefully on retirement
            $this->db->table('ai_agent_assignments')
                ->where('agent_id', $agentId)
                ->where('status', 'active')
                ->update([
                    'status'     => 'concluded',
                    'end_date'   => $now,
                    'updated_at' => $now,
                    'reason_for_change' => 'Concluded upon Agent retirement.',
                ]);
        }

        $this->db->table('ai_agent_registry')->where('id', $agentId)->update($update);

        // Record Lifecycle Event Audit
        $actorName = 'System';
        if ($actorUserId) {
            $user = $this->db->table('users')->select('username')->where('id', $actorUserId)->get()->getRow();
            $actorName = $user ? $user->username : "User #{$actorUserId}";
        }

        $this->db->table('ai_agent_lifecycle_events')->insert([
            'agent_id'             => $agentId,
            'from_state'           => $currentState,
            'to_state'             => $toState,
            'initiated_by_user_id' => $actorUserId,
            'initiated_by_name'    => $actorName,
            'approval_ref'         => $approvalRef,
            'reason'               => $reason,
            'created_at'           => $now,
        ]);

        // Append to Permanent Individual Agent Performance History
        $this->db->table('ai_agent_performance_history')->insert([
            'agent_id'            => $agentId,
            'agent_uuid'          => $agent['agent_uuid'],
            'category'            => 'lifecycle_transition',
            'title'               => "Transisi Status Lifecycle: {$currentState} -> {$toState}",
            'description'         => "Status siklus kerja agen diperbarui dari '{$currentState}' menjadi '{$toState}'." . ($reason ? " Alasan: {$reason}" : ''),
            'task_ref'            => $approvalRef ?: 'LIFECYCLE',
            'evidence_status'     => 'verified',
            'evaluator_identity'  => $actorName,
            'unit_id_at_time'     => $agent['primary_unit_id'],
            'position_id_at_time' => $agent['primary_position_id'],
            'created_at'          => $now,
        ]);

        return $this->db->table('ai_agent_registry')->where('id', $agentId)->get()->getRowArray();
    }

    public function getLifecycleHistory(int $agentId): array
    {
        return $this->db->table('ai_agent_lifecycle_events')
            ->where('agent_id', $agentId)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();
    }

    // =========================================================================
    // 2. Organizational Assignments & Historical Transfers
    // =========================================================================

    public function getAssignments(int $agentId): array
    {
        return $this->db->table('ai_agent_assignments a')
            ->select('a.*, u.name as unit_name, u.code as unit_code, p.title as position_title, p.code as position_code')
            ->join('organization_units u', 'u.id = a.unit_id', 'left')
            ->join('organization_positions p', 'p.id = a.position_id', 'left')
            ->where('a.agent_id', $agentId)
            ->orderBy('a.id', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function assignAgent(int $agentId, int $unitId, ?int $positionId = null, string $type = 'primary', ?string $reason = null, ?string $approvalRef = null, ?int $actorUserId = null): array
    {
        $agent = $this->db->table('ai_agent_registry')->where('id', $agentId)->where('deleted_at IS NULL')->get()->getRowArray();
        if (!$agent) {
            throw new \RuntimeException("Agent #{$agentId} not found.", 404);
        }

        $unit = $this->db->table('organization_units')->where('id', $unitId)->get()->getRowArray();
        if (!$unit) {
            throw new \InvalidArgumentException("Organizational Unit #{$unitId} not found.", 404);
        }

        if ($positionId) {
            $pos = $this->db->table('organization_positions')->where('id', $positionId)->get()->getRowArray();
            if (!$pos) {
                throw new \InvalidArgumentException("Position #{$positionId} not found.", 404);
            }
        }

        $now = date('Y-m-d H:i:s');
        $actorName = $actorUserId ? "User #{$actorUserId}" : 'Continuous Executive Board';

        // Conclude previous active primary assignment safely without erasing history
        if ($type === 'primary') {
            $this->db->table('ai_agent_assignments')
                ->where('agent_id', $agentId)
                ->where('assignment_type', 'primary')
                ->where('status', 'active')
                ->update([
                    'status'            => 'transferred',
                    'end_date'          => $now,
                    'updated_at'        => $now,
                    'reason_for_change' => $reason ?: "Transferred to unit {$unit['code']}",
                ]);

            // Update primary pointers on agent registry
            $this->db->table('ai_agent_registry')->where('id', $agentId)->update([
                'primary_unit_id'     => $unitId,
                'primary_position_id' => $positionId,
                'updated_at'          => $now,
            ]);
        }

        $insert = [
            'agent_id'          => $agentId,
            'unit_id'           => $unitId,
            'position_id'       => $positionId,
            'assignment_type'   => $type,
            'start_date'        => $now,
            'status'            => 'active',
            'assigned_by'       => $actorName,
            'approval_ref'      => $approvalRef,
            'reason_for_change' => $reason,
            'created_at'        => $now,
            'updated_at'        => $now,
        ];

        $this->db->table('ai_agent_assignments')->insert($insert);
        $insertId = $this->db->insertID();

        // Record in Individual Agent Performance History
        $this->db->table('ai_agent_performance_history')->insert([
            'agent_id'            => $agentId,
            'agent_uuid'          => $agent['agent_uuid'],
            'category'            => 'assignment_change',
            'title'               => "Penugasan Unit Organisasi: {$unit['name']}",
            'description'         => "Agen ditugaskan pada unit {$unit['name']} ({$unit['code']}) dengan tipe {$type}." . ($reason ? " Alasan: {$reason}" : ''),
            'task_ref'            => $approvalRef ?: 'ASSIGNMENT',
            'evidence_status'     => 'verified',
            'evaluator_identity'  => $actorName,
            'unit_id_at_time'     => $unitId,
            'position_id_at_time' => $positionId,
            'created_at'          => $now,
        ]);

        return $this->db->table('ai_agent_assignments')->where('id', $insertId)->get()->getRowArray();
    }

    public function endAssignment(int $assignmentId, string $reason, ?int $actorUserId = null): array
    {
        $appt = $this->db->table('ai_agent_assignments')->where('id', $assignmentId)->get()->getRowArray();
        if (!$appt) {
            throw new \RuntimeException("Assignment #{$assignmentId} not found.", 404);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('ai_agent_assignments')->where('id', $assignmentId)->update([
            'status'            => 'concluded',
            'end_date'          => $now,
            'reason_for_change' => $reason,
            'updated_at'        => $now,
        ]);

        return $this->db->table('ai_agent_assignments')->where('id', $assignmentId)->get()->getRowArray();
    }

    // =========================================================================
    // 3. Structured Responsibilities
    // =========================================================================

    public function getResponsibilities(int $agentId): array
    {
        return $this->db->table('ai_agent_responsibilities')
            ->where('agent_id', $agentId)
            ->where('status', 'active')
            ->orderBy('priority', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function addResponsibility(int $agentId, array $data, ?int $actorUserId = null): array
    {
        $now = date('Y-m-d H:i:s');
        $insert = [
            'agent_id'             => $agentId,
            'title'                => $data['title'],
            'description'          => $data['description'],
            'category'             => $data['category'] ?? 'primary',
            'scope_unit_id'        => !empty($data['scope_unit_id']) ? (int)$data['scope_unit_id'] : null,
            'priority'             => $data['priority'] ?? 'medium',
            'status'               => 'active',
            'effective_start_date' => $data['effective_start_date'] ?? date('Y-m-d'),
            'effective_end_date'   => $data['effective_end_date'] ?? null,
            'approval_ref'         => $data['approval_ref'] ?? null,
            'created_at'           => $now,
            'updated_at'           => $now,
        ];

        $this->db->table('ai_agent_responsibilities')->insert($insert);
        $insert['id'] = $this->db->insertID();
        return $insert;
    }

    // =========================================================================
    // 4. Skills & Qualifications Catalogue
    // =========================================================================

    public function getSkillCatalogue(?string $category = null): array
    {
        $builder = $this->db->table('skill_catalogue')->where('is_active', 1);
        if ($category) {
            $builder->where('category', $category);
        }
        return $builder->orderBy('category', 'ASC')->orderBy('name', 'ASC')->get()->getResultArray();
    }

    public function createSkill(array $data, ?int $actorUserId = null): array
    {
        $now = date('Y-m-d H:i:s');
        $code = trim($data['code']);
        $insert = [
            'code'        => $code,
            'name'        => trim($data['name']),
            'category'    => trim($data['category']),
            'description' => $data['description'] ?? null,
            'version'     => $data['version'] ?? '1.0',
            'is_active'   => 1,
            'created_at'  => $now,
        ];

        $this->db->table('skill_catalogue')->insert($insert);
        $insert['id'] = $this->db->insertID();
        return $insert;
    }

    public function getAgentSkills(int $agentId): array
    {
        return $this->db->table('ai_agent_skills s')
            ->select('s.*, sc.code as skill_code, sc.name as skill_name, sc.category as skill_category, sc.description as skill_description')
            ->join('skill_catalogue sc', 'sc.id = s.skill_id')
            ->where('s.agent_id', $agentId)
            ->where('s.is_active', 1)
            ->get()
            ->getResultArray();
    }

    public function assignSkill(int $agentId, int $skillId, string $proficiency = 'competent', ?string $evidence = null, ?int $actorUserId = null): array
    {
        $now = date('Y-m-d H:i:s');
        $existing = $this->db->table('ai_agent_skills')
            ->where('agent_id', $agentId)
            ->where('skill_id', $skillId)
            ->get()
            ->getRowArray();

        if ($existing) {
            $update = [
                'proficiency_level'  => $proficiency,
                'evidence_reference' => $evidence ?: $existing['evidence_reference'],
                'is_active'          => 1,
                'updated_at'         => $now,
            ];
            $this->db->table('ai_agent_skills')->where('id', $existing['id'])->update($update);
            $skillRowId = $existing['id'];
        } else {
            $insert = [
                'agent_id'            => $agentId,
                'skill_id'            => $skillId,
                'proficiency_level'   => $proficiency,
                'verification_status' => 'pending',
                'evidence_reference'  => $evidence,
                'is_active'           => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ];
            $this->db->table('ai_agent_skills')->insert($insert);
            $skillRowId = $this->db->insertID();
        }

        return $this->db->table('ai_agent_skills')->where('id', $skillRowId)->get()->getRowArray();
    }

    public function verifySkill(int $agentSkillId, string $status = 'verified', ?string $verifiedBy = null, ?int $actorUserId = null): array
    {
        $now = date('Y-m-d H:i:s');
        $verifier = $verifiedBy ?: ($actorUserId ? "User #{$actorUserId}" : 'Workforce QA Committee');

        $this->db->table('ai_agent_skills')->where('id', $agentSkillId)->update([
            'verification_status' => $status,
            'verified_by'         => $verifier,
            'last_assessed_at'    => $now,
            'updated_at'          => $now,
        ]);

        return $this->db->table('ai_agent_skills')->where('id', $agentSkillId)->get()->getRowArray();
    }

    // =========================================================================
    // 5. Individual Agent Performance History
    // =========================================================================

    public function getPerformanceHistory(int $agentId, ?string $category = null, int $limit = 50, int $offset = 0): array
    {
        $builder = $this->db->table('ai_agent_performance_history')
            ->where('agent_id', $agentId);

        if ($category) {
            $builder->where('category', $category);
        }

        $total = $builder->countAllResults(false);

        $builder->orderBy('id', 'DESC');
        $builder->limit($limit, $offset);
        $records = $builder->get()->getResultArray();

        return [
            'records' => $records,
            'total'   => $total,
            'limit'   => $limit,
            'offset'  => $offset,
        ];
    }

    public function recordPerformanceEvent(int $agentId, array $data, ?int $actorUserId = null): array
    {
        $agent = $this->db->table('ai_agent_registry')->where('id', $agentId)->where('deleted_at IS NULL')->get()->getRowArray();
        if (!$agent) {
            throw new \RuntimeException("Agent #{$agentId} not found.", 404);
        }

        $now = date('Y-m-d H:i:s');
        $evaluator = $data['evaluator_identity'] ?? ($actorUserId ? "User #{$actorUserId}" : null);

        $insert = [
            'agent_id'            => $agentId,
            'agent_uuid'          => $agent['agent_uuid'],
            'category'            => $data['category'] ?? 'activity',
            'title'               => $data['title'],
            'description'         => $data['description'],
            'task_ref'            => $data['task_ref'] ?? null,
            'output_ref'          => $data['output_ref'] ?? null,
            'evidence_ref'        => $data['evidence_ref'] ?? null,
            'evidence_status'     => $data['evidence_status'] ?? 'submitted',
            'evaluator_identity'  => $evaluator,
            'evaluation_notes'    => $data['evaluation_notes'] ?? null,
            'unit_id_at_time'     => $agent['primary_unit_id'],
            'position_id_at_time' => $agent['primary_position_id'],
            'created_at'          => $now,
        ];

        $this->db->table('ai_agent_performance_history')->insert($insert);
        $insert['id'] = $this->db->insertID();

        return $insert;
    }

    public function verifyEvidence(int $historyId, string $status, string $evaluatorIdentity, ?string $notes = null, ?int $actorUserId = null): array
    {
        $allowed = ['submitted', 'under_review', 'verified', 'rejected', 'disputed'];
        if (!in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException("Invalid evidence verification status '{$status}'.", 400);
        }

        $record = $this->db->table('ai_agent_performance_history')->where('id', $historyId)->get()->getRowArray();
        if (!$record) {
            throw new \RuntimeException("Performance record #{$historyId} not found.", 404);
        }

        $this->db->table('ai_agent_performance_history')->where('id', $historyId)->update([
            'evidence_status'    => $status,
            'evaluator_identity' => $evaluatorIdentity,
            'evaluation_notes'   => $notes ?: $record['evaluation_notes'],
        ]);

        return $this->db->table('ai_agent_performance_history')->where('id', $historyId)->get()->getRowArray();
    }

    /**
     * Correct or amend an existing performance record while maintaining immutability.
     * The old record is preserved and points to the new record via superseded_by_id.
     */
    public function amendPerformanceRecord(int $historyId, array $newData, string $reason, ?int $actorUserId = null): array
    {
        $oldRecord = $this->db->table('ai_agent_performance_history')->where('id', $historyId)->get()->getRowArray();
        if (!$oldRecord) {
            throw new \RuntimeException("Performance record #{$historyId} not found.", 404);
        }

        $now = date('Y-m-d H:i:s');
        $actorName = $actorUserId ? "User #{$actorUserId}" : 'System Audit';

        // Insert new superseding record
        $amended = [
            'agent_id'            => $oldRecord['agent_id'],
            'agent_uuid'          => $oldRecord['agent_uuid'],
            'category'            => $newData['category'] ?? $oldRecord['category'],
            'title'               => $newData['title'] ?? $oldRecord['title'],
            'description'         => ($newData['description'] ?? $oldRecord['description']) . " [Amended from record #{$historyId}. Reason: {$reason}]",
            'task_ref'            => $newData['task_ref'] ?? $oldRecord['task_ref'],
            'output_ref'          => $newData['output_ref'] ?? $oldRecord['output_ref'],
            'evidence_ref'        => $newData['evidence_ref'] ?? $oldRecord['evidence_ref'],
            'evidence_status'     => $newData['evidence_status'] ?? $oldRecord['evidence_status'],
            'evaluator_identity'  => $actorName,
            'evaluation_notes'    => "Amended: {$reason}",
            'unit_id_at_time'     => $oldRecord['unit_id_at_time'],
            'position_id_at_time' => $oldRecord['position_id_at_time'],
            'created_at'          => $now,
        ];

        $this->db->table('ai_agent_performance_history')->insert($amended);
        $newId = $this->db->insertID();

        // Mark old record as superseded
        $this->db->table('ai_agent_performance_history')->where('id', $historyId)->update([
            'superseded_by_id' => $newId,
        ]);

        return $this->db->table('ai_agent_performance_history')->where('id', $newId)->get()->getRowArray();
    }
}
