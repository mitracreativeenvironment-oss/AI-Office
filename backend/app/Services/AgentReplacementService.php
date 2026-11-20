<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class AgentReplacementService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function listProposals(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $builder = $this->db->table('workforce_replacement_proposals p')
            ->select('p.*, 
                      out_ag.name as outgoing_agent_name, out_ag.agent_code as outgoing_agent_code,
                      in_ag.name as incoming_agent_name, in_ag.agent_code as incoming_agent_code,
                      da.assignment_date, da.duty_type, s.name as shift_name, s.shift_code')
            ->join('ai_agent_registry out_ag', 'out_ag.id = p.outgoing_agent_id')
            ->join('ai_agent_registry in_ag', 'in_ag.id = p.incoming_agent_id')
            ->join('workforce_duty_assignments da', 'da.id = p.duty_assignment_id')
            ->join('workforce_shifts s', 's.id = da.shift_id', 'left');

        if (!empty($filters['status'])) {
            $builder->where('p.status', $filters['status']);
        }
        if (!empty($filters['agent_id'])) {
            $builder->groupStart()
                ->where('p.outgoing_agent_id', (int)$filters['agent_id'])
                ->orWhere('p.incoming_agent_id', (int)$filters['agent_id'])
                ->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $builder->orderBy('p.id', 'DESC');
        $builder->limit($limit, $offset);
        $items = $builder->get()->getResultArray();

        return ['items' => $items, 'total' => $total];
    }

    public function getProposal(int $id): ?array
    {
        return $this->db->table('workforce_replacement_proposals p')
            ->select('p.*, 
                      out_ag.name as outgoing_agent_name, out_ag.agent_code as outgoing_agent_code,
                      in_ag.name as incoming_agent_name, in_ag.agent_code as incoming_agent_code,
                      da.assignment_date, da.duty_type, s.name as shift_name, s.shift_code')
            ->join('ai_agent_registry out_ag', 'out_ag.id = p.outgoing_agent_id')
            ->join('ai_agent_registry in_ag', 'in_ag.id = p.incoming_agent_id')
            ->join('workforce_duty_assignments da', 'da.id = p.duty_assignment_id')
            ->join('workforce_shifts s', 's.id = da.shift_id', 'left')
            ->where('p.id', $id)
            ->get()
            ->getRowArray() ?: null;
    }

    /**
     * Discover qualified replacement candidates based on skills, unit compatibility,
     * active lifecycle state, and zero schedule/leave conflicts.
     */
    public function findReplacementCandidates(int $dutyAssignmentId): array
    {
        $duty = $this->db->table('workforce_duty_assignments da')
            ->select('da.*, ag.primary_unit_id, ag.name as agent_name, ag.agent_code')
            ->join('ai_agent_registry ag', 'ag.id = da.agent_id')
            ->where('da.id', $dutyAssignmentId)
            ->get()
            ->getRowArray();

        if (!$duty) {
            throw new \InvalidArgumentException("Duty assignment #{$dutyAssignmentId} not found.", 404);
        }

        $outgoingAgentId = (int)$duty['agent_id'];
        $assignmentDate = $duty['assignment_date'];
        $unitId = (int)$duty['primary_unit_id'];

        // Get outgoing agent skills
        $outgoingSkills = $this->db->table('ai_agent_skills')
            ->where('agent_id', $outgoingAgentId)
            ->where('is_active', 1)
            ->get()
            ->getResultArray();
        $outgoingSkillIds = array_column($outgoingSkills, 'skill_id');

        // Query active candidate agents
        $candidates = $this->db->table('ai_agent_registry')
            ->where('lifecycle_state', 'active')
            ->where('id !=', $outgoingAgentId)
            ->where('deleted_at IS NULL')
            ->get()
            ->getResultArray();

        $rankedList = [];

        foreach ($candidates as $cand) {
            $candId = (int)$cand['id'];

            // 1. Conflict check: existing working assignment on that date?
            $hasWorkingDuty = $this->db->table('workforce_duty_assignments')
                ->where('agent_id', $candId)
                ->where('assignment_date', $assignmentDate)
                ->where('duty_type', 'working')
                ->countAllResults() > 0;

            if ($hasWorkingDuty) {
                // Conflicting duty already scheduled
                continue;
            }

            // 2. Conflict check: approved leave?
            $hasLeave = $this->db->table('workforce_agent_availability')
                ->where('agent_id', $candId)
                ->where('approval_status', 'approved')
                ->where("DATE(start_time) <=", $assignmentDate)
                ->where("(end_time IS NULL OR DATE(end_time) >= '{$assignmentDate}')")
                ->whereIn('availability_type', ['planned_leave', 'temporary_unavailability'])
                ->countAllResults() > 0;

            if ($hasLeave) {
                continue;
            }

            // 3. Skill match calculation
            $candSkills = $this->db->table('ai_agent_skills s')
                ->select('s.*, sc.name as skill_name, sc.code as skill_code')
                ->join('skill_catalogue sc', 'sc.id = s.skill_id')
                ->where('s.agent_id', $candId)
                ->where('s.is_active', 1)
                ->get()
                ->getResultArray();

            $matchingSkills = [];
            foreach ($candSkills as $cs) {
                if (in_array($cs['skill_id'], $outgoingSkillIds)) {
                    $matchingSkills[] = $cs['skill_name'];
                }
            }

            // 4. Organizational compatibility
            $unitScore = ($cand['primary_unit_id'] == $unitId) ? 40 : 20;
            $skillScore = count($matchingSkills) * 20;
            $totalScore = min(100, $unitScore + $skillScore);

            $rankedList[] = [
                'candidate_agent_id'   => $candId,
                'agent_code'           => $cand['agent_code'],
                'name'                 => $cand['name'],
                'classification'       => $cand['classification'],
                'primary_unit_id'      => $cand['primary_unit_id'] ? (int)$cand['primary_unit_id'] : null,
                'is_same_unit'         => ($cand['primary_unit_id'] == $unitId),
                'compatibility_score'  => $totalScore,
                'matching_skills'      => $matchingSkills,
                'total_skills_count'   => count($candSkills),
                'is_available'         => true,
            ];
        }

        // Sort descending by compatibility score
        usort($rankedList, function ($a, $b) {
            return $b['compatibility_score'] <=> $a['compatibility_score'];
        });

        return [
            'duty_assignment_id'   => $dutyAssignmentId,
            'assignment_date'      => $assignmentDate,
            'outgoing_agent_id'    => $outgoingAgentId,
            'outgoing_agent_name'  => $duty['agent_name'],
            'candidates_count'     => count($rankedList),
            'candidates'           => $rankedList,
        ];
    }

    public function createProposal(array $data, ?int $actorUserId = null): array
    {
        $dutyId = (int)($data['duty_assignment_id'] ?? 0);
        $incomingAgentId = (int)($data['incoming_agent_id'] ?? 0);

        $duty = $this->db->table('workforce_duty_assignments')->where('id', $dutyId)->get()->getRowArray();
        if (!$duty) {
            throw new \InvalidArgumentException("Duty assignment #{$dutyId} not found.", 404);
        }

        $outgoingAgentId = (int)$duty['agent_id'];
        $code = trim($data['proposal_code'] ?? 'REP-' . date('Y') . '-' . rand(100, 999));
        $now = date('Y-m-d H:i:s');

        $insert = [
            'proposal_code'           => $code,
            'outgoing_agent_id'       => $outgoingAgentId,
            'incoming_agent_id'       => $incomingAgentId,
            'duty_assignment_id'      => $dutyId,
            'original_responsibility' => $data['original_responsibility'] ?? 'Operational Duty Coverage',
            'start_time'              => $data['start_time'] ?? "{$duty['assignment_date']} 08:00:00",
            'end_time'                => $data['end_time'] ?? "{$duty['assignment_date']} 16:00:00",
            'reason'                  => trim($data['reason'] ?? 'Operational replacement coverage'),
            'qualification_notes'     => $data['qualification_notes'] ?? null,
            'status'                  => 'proposed',
            'proposed_by_user_id'     => $actorUserId,
            'accepted_at'             => null,
            'accepted_by_agent_identity' => null,
            'approved_by_user_id'     => null,
            'approved_at'             => null,
            'activated_at'            => null,
            'created_at'              => $now,
            'updated_at'              => $now,
        ];

        $this->db->table('workforce_replacement_proposals')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();

        // Audit
        $this->db->table('workforce_scheduling_audit')->insert([
            'event_type'     => 'replacement_proposed',
            'target_type'    => 'replacement',
            'target_id'      => $insert['id'],
            'agent_id'       => $incomingAgentId,
            'actor_user_id'  => $actorUserId,
            'actor_name'     => $actorUserId ? "User #{$actorUserId}" : 'Lead Architect',
            'action_details' => "Replacement proposal {$code} proposed: Incoming Agent #{$incomingAgentId} to replace Outgoing Agent #{$outgoingAgentId}.",
            'created_at'     => $now,
        ]);

        return $insert;
    }

    public function acceptProposal(int $id, string $agentIdentity): array
    {
        $proposal = $this->getProposal($id);
        if (!$proposal) {
            throw new \InvalidArgumentException("Proposal #{$id} not found.", 404);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('workforce_replacement_proposals')->where('id', $id)->update([
            'status'                     => 'accepted',
            'accepted_at'                => $now,
            'accepted_by_agent_identity' => $agentIdentity,
            'updated_at'                 => $now,
        ]);

        // Audit
        $this->db->table('workforce_scheduling_audit')->insert([
            'event_type'     => 'replacement_accepted',
            'target_type'    => 'replacement',
            'target_id'      => $id,
            'agent_id'       => $proposal['incoming_agent_id'],
            'actor_name'     => $agentIdentity,
            'action_details' => "Proposal {$proposal['proposal_code']} formally accepted by incoming agent {$agentIdentity}.",
            'created_at'     => $now,
        ]);

        return $this->getProposal($id);
    }

    public function approveProposal(int $id, ?int $actorUserId = null): array
    {
        $proposal = $this->getProposal($id);
        if (!$proposal) {
            throw new \InvalidArgumentException("Proposal #{$id} not found.", 404);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('workforce_replacement_proposals')->where('id', $id)->update([
            'status'              => 'approved',
            'approved_by_user_id' => $actorUserId,
            'approved_at'         => $now,
            'updated_at'          => $now,
        ]);

        // Audit
        $this->db->table('workforce_scheduling_audit')->insert([
            'event_type'     => 'replacement_approved',
            'target_type'    => 'replacement',
            'target_id'      => $id,
            'actor_user_id'  => $actorUserId,
            'actor_name'     => $actorUserId ? "User #{$actorUserId}" : 'Supervisor',
            'action_details' => "Proposal {$proposal['proposal_code']} approved by supervisor.",
            'created_at'     => $now,
        ]);

        return $this->getProposal($id);
    }

    /**
     * Atomic activation of replacement:
     * - Preserves historical outgoing duty assignment by marking it 'reassigned'.
     * - Creates active duty assignment for the replacement agent.
     * - Marks proposal 'activated'.
     */
    public function activateReplacement(int $id, ?int $actorUserId = null): array
    {
        $proposal = $this->getProposal($id);
        if (!$proposal) {
            throw new \InvalidArgumentException("Proposal #{$id} not found.", 404);
        }

        if (!in_array($proposal['status'], ['accepted', 'approved'])) {
            throw new \RuntimeException("Cannot activate proposal #{$id}. Status must be 'accepted' or 'approved' (current: {$proposal['status']}).", 400);
        }

        $this->db->transStart();
        $now = date('Y-m-d H:i:s');

        // 1. Update outgoing duty assignment
        $duty = $this->db->table('workforce_duty_assignments')->where('id', $proposal['duty_assignment_id'])->get()->getRowArray();
        $this->db->table('workforce_duty_assignments')->where('id', $proposal['duty_assignment_id'])->update([
            'status'                  => 'reassigned',
            'replacement_proposal_id' => $id,
            'notes'                   => ($duty['notes'] ?? '') . " [Reassigned to Agent #{$proposal['incoming_agent_id']} via Proposal #{$proposal['proposal_code']}]",
            'updated_at'              => $now,
        ]);

        // 2. Insert new duty assignment for incoming agent
        $this->db->table('workforce_duty_assignments')->insert([
            'roster_id'               => $duty['roster_id'],
            'agent_id'                => $proposal['incoming_agent_id'],
            'shift_id'                => $duty['shift_id'],
            'assignment_date'         => $duty['assignment_date'],
            'duty_type'               => 'working',
            'status'                  => 'scheduled',
            'is_actual_completed'     => 0,
            'completed_at'            => null,
            'replacement_proposal_id' => $id,
            'notes'                   => "Activated replacement duty for Outgoing Agent #{$proposal['outgoing_agent_id']} under Proposal #{$proposal['proposal_code']}.",
            'created_at'              => $now,
            'updated_at'              => $now,
        ]);
        $newDutyId = (int)$this->db->insertID();

        // 3. Update proposal status
        $this->db->table('workforce_replacement_proposals')->where('id', $id)->update([
            'status'       => 'activated',
            'activated_at' => $now,
            'updated_at'   => $now,
        ]);

        // 4. Audit
        $this->db->table('workforce_scheduling_audit')->insert([
            'event_type'     => 'replacement_activated',
            'target_type'    => 'replacement',
            'target_id'      => $id,
            'agent_id'       => $proposal['incoming_agent_id'],
            'actor_user_id'  => $actorUserId,
            'actor_name'     => $actorUserId ? "User #{$actorUserId}" : 'Operations Lead',
            'action_details' => "Replacement activated! Outgoing duty #{$duty['id']} reassigned; New duty #{$newDutyId} created for incoming Agent #{$proposal['incoming_agent_id']}.",
            'created_at'     => $now,
        ]);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new \RuntimeException("Transaction failed during replacement activation.", 500);
        }

        return $this->getProposal($id);
    }
}
