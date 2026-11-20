<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class WorkforceSchedulingService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ------------------------------------------------------------------------
    // 1. POLICIES
    // ------------------------------------------------------------------------

    public function listPolicies(): array
    {
        return $this->db->table('workforce_schedule_policies')
            ->orderBy('is_default', 'DESC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getPolicy(int $id): ?array
    {
        return $this->db->table('workforce_schedule_policies')->where('id', $id)->get()->getRowArray() ?: null;
    }

    public function getDefaultPolicy(): ?array
    {
        return $this->db->table('workforce_schedule_policies')->where('is_default', 1)->get()->getRowArray() ?: null;
    }

    public function createPolicy(array $data): array
    {
        $code = trim($data['policy_code'] ?? 'POL-' . rand(100, 999));
        $now = date('Y-m-d H:i:s');

        $insert = [
            'policy_code'             => $code,
            'name'                    => trim($data['name'] ?? 'Custom Schedule Policy'),
            'description'             => $data['description'] ?? null,
            'cycle_days'              => (int)($data['cycle_days'] ?? 7),
            'working_days_count'      => (int)($data['working_days_count'] ?? 5),
            'rest_days_count'         => (int)($data['rest_days_count'] ?? 2),
            'min_rest_interval_hours' => (int)($data['min_rest_interval_hours'] ?? 12),
            'applicable_unit_id'      => !empty($data['applicable_unit_id']) ? (int)$data['applicable_unit_id'] : null,
            'is_default'              => !empty($data['is_default']) ? 1 : 0,
            'is_active'               => 1,
            'effective_from'          => $data['effective_from'] ?? date('Y-m-d'),
            'effective_to'            => $data['effective_to'] ?? null,
            'created_at'              => $now,
            'updated_at'              => $now,
        ];

        $this->db->table('workforce_schedule_policies')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();
        return $insert;
    }

    // ------------------------------------------------------------------------
    // 2. SHIFTS
    // ------------------------------------------------------------------------

    public function listShifts(): array
    {
        return $this->db->table('workforce_shifts')
            ->where('is_active', 1)
            ->orderBy('start_time', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getShift(int $id): ?array
    {
        return $this->db->table('workforce_shifts')->where('id', $id)->get()->getRowArray() ?: null;
    }

    public function createShift(array $data): array
    {
        $code = trim($data['shift_code'] ?? 'SFT-' . strtoupper(substr(uniqid(), -4)));
        $now = date('Y-m-d H:i:s');

        $insert = [
            'shift_code'         => $code,
            'name'               => trim($data['name'] ?? 'Standard Shift'),
            'start_time'         => $data['start_time'] ?? '08:00:00',
            'end_time'           => $data['end_time'] ?? '16:00:00',
            'timezone'           => $data['timezone'] ?? 'Asia/Jakarta',
            'duration_hours'     => (float)($data['duration_hours'] ?? 8.00),
            'crosses_midnight'   => !empty($data['crosses_midnight']) ? 1 : 0,
            'applicable_unit_id' => !empty($data['applicable_unit_id']) ? (int)$data['applicable_unit_id'] : null,
            'coverage_category'  => $data['coverage_category'] ?? 'business_hours',
            'is_active'          => 1,
            'created_at'         => $now,
            'updated_at'         => $now,
        ];

        $this->db->table('workforce_shifts')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();
        return $insert;
    }

    // ------------------------------------------------------------------------
    // 3. ROSTERS
    // ------------------------------------------------------------------------

    public function listRosters(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $builder = $this->db->table('workforce_rosters r')
            ->select('r.*, p.name as policy_name, p.working_days_count, p.rest_days_count, u.name as unit_name')
            ->join('workforce_schedule_policies p', 'p.id = r.policy_id', 'left')
            ->join('organization_units u', 'u.id = r.unit_id', 'left');

        if (!empty($filters['status'])) {
            $builder->where('r.status', $filters['status']);
        }
        if (!empty($filters['unit_id'])) {
            $builder->where('r.unit_id', (int)$filters['unit_id']);
        }

        $total = $builder->countAllResults(false);
        $builder->orderBy('r.start_date', 'DESC');
        $builder->limit($limit, $offset);
        $items = $builder->get()->getResultArray();

        foreach ($items as &$item) {
            $item['id'] = (int)$item['id'];
            $item['assignments_count'] = $this->db->table('workforce_duty_assignments')
                ->where('roster_id', $item['id'])
                ->countAllResults();
        }

        return ['items' => $items, 'total' => $total];
    }

    public function getRoster(int $id): ?array
    {
        $roster = $this->db->table('workforce_rosters r')
            ->select('r.*, p.name as policy_name, p.working_days_count, p.rest_days_count, u.name as unit_name')
            ->join('workforce_schedule_policies p', 'p.id = r.policy_id', 'left')
            ->join('organization_units u', 'u.id = r.unit_id', 'left')
            ->where('r.id', $id)
            ->get()
            ->getRowArray();

        if (!$roster) return null;

        $roster['id'] = (int)$roster['id'];

        // Assignments grouped by agent
        $assignments = $this->db->table('workforce_duty_assignments a')
            ->select('a.*, ag.name as agent_name, ag.agent_code, ag.classification, s.name as shift_name, s.shift_code, s.start_time, s.end_time')
            ->join('ai_agent_registry ag', 'ag.id = a.agent_id')
            ->join('workforce_shifts s', 's.id = a.shift_id', 'left')
            ->where('a.roster_id', $id)
            ->orderBy('ag.name', 'ASC')
            ->orderBy('a.assignment_date', 'ASC')
            ->get()
            ->getResultArray();

        $roster['assignments'] = $assignments;
        return $roster;
    }

    public function createRoster(array $data, ?int $actorUserId = null): array
    {
        $startDate = $data['start_date'] ?? date('Y-m-d');
        $endDate = $data['end_date'] ?? date('Y-m-d', strtotime('+6 days', strtotime($startDate)));
        $policyId = (int)($data['policy_id'] ?? 1);
        $now = date('Y-m-d H:i:s');

        $code = trim($data['roster_code'] ?? '');
        if (empty($code)) {
            $code = 'RST-' . date('Y', strtotime($startDate)) . '-W' . date('W', strtotime($startDate)) . '-' . rand(10, 99);
        }

        $insert = [
            'roster_code'          => $code,
            'title'                => trim($data['title'] ?? "Workforce Roster ({$startDate} - {$endDate})"),
            'policy_id'            => $policyId,
            'start_date'           => $startDate,
            'end_date'             => $endDate,
            'unit_id'              => !empty($data['unit_id']) ? (int)$data['unit_id'] : null,
            'status'               => 'draft',
            'published_by_user_id' => null,
            'published_at'         => null,
            'created_at'           => $now,
            'updated_at'           => $now,
        ];

        $this->db->table('workforce_rosters')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();

        // Audit
        $this->db->table('workforce_scheduling_audit')->insert([
            'event_type'     => 'roster_created',
            'target_type'    => 'roster',
            'target_id'      => $insert['id'],
            'actor_user_id'  => $actorUserId,
            'actor_name'     => $actorUserId ? "User #{$actorUserId}" : 'System Architect',
            'action_details' => "Draft roster {$code} created for period {$startDate} to {$endDate}.",
            'created_at'     => $now,
        ]);

        return $insert;
    }

    public function publishRoster(int $id, ?int $actorUserId = null): array
    {
        $roster = $this->getRoster($id);
        if (!$roster) {
            throw new \InvalidArgumentException("Roster #{$id} not found.", 404);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('workforce_rosters')->where('id', $id)->update([
            'status'               => 'published',
            'published_by_user_id' => $actorUserId,
            'published_at'         => $now,
            'updated_at'           => $now,
        ]);

        // Audit
        $this->db->table('workforce_scheduling_audit')->insert([
            'event_type'     => 'roster_published',
            'target_type'    => 'roster',
            'target_id'      => $id,
            'actor_user_id'  => $actorUserId,
            'actor_name'     => $actorUserId ? "User #{$actorUserId}" : 'Operations Manager',
            'action_details' => "Roster {$roster['roster_code']} officially published with {$roster['assignments_count']} assignments.",
            'created_at'     => $now,
        ]);

        return $this->getRoster($id);
    }

    // ------------------------------------------------------------------------
    // 4. DUTY ASSIGNMENTS & CONFLICT CHECKING
    // ------------------------------------------------------------------------

    public function checkAssignmentConflicts(int $agentId, string $date, ?int $shiftId, ?int $excludeId = null): array
    {
        $conflicts = [];

        // Check if agent already has an assignment on this date
        $builder = $this->db->table('workforce_duty_assignments')
            ->where('agent_id', $agentId)
            ->where('assignment_date', $date);

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        $existing = $builder->get()->getRowArray();
        if ($existing) {
            $conflicts[] = "Agent already has an existing '{$existing['duty_type']}' assignment on {$date} (#{$existing['id']}).";
        }

        // Check availability block / planned leave
        $unavailability = $this->db->table('workforce_agent_availability')
            ->where('agent_id', $agentId)
            ->where('approval_status', 'approved')
            ->where("DATE(start_time) <=", $date)
            ->where("(end_time IS NULL OR DATE(end_time) >= '{$date}')")
            ->whereIn('availability_type', ['planned_leave', 'temporary_unavailability'])
            ->get()
            ->getRowArray();

        if ($unavailability) {
            $conflicts[] = "Agent is unavailable on {$date} due to approved {$unavailability['availability_type']}: {$unavailability['reason']}.";
        }

        return $conflicts;
    }

    public function createDutyAssignment(array $data): array
    {
        $agentId = (int)($data['agent_id'] ?? 0);
        $rosterId = (int)($data['roster_id'] ?? 0);
        $date = $data['assignment_date'] ?? date('Y-m-d');
        $shiftId = !empty($data['shift_id']) ? (int)$data['shift_id'] : null;
        $dutyType = $data['duty_type'] ?? 'working';

        if (!$agentId || !$rosterId) {
            throw new \InvalidArgumentException('Agent ID and Roster ID are required.', 422);
        }

        // Validate conflicts
        $conflicts = $this->checkAssignmentConflicts($agentId, $date, $shiftId);
        if (!empty($conflicts)) {
            throw new \RuntimeException(implode(' ', $conflicts), 409);
        }

        $now = date('Y-m-d H:i:s');
        $insert = [
            'roster_id'               => $rosterId,
            'agent_id'                => $agentId,
            'shift_id'                => $shiftId,
            'assignment_date'         => $date,
            'duty_type'               => $dutyType,
            'status'                  => 'scheduled',
            'is_actual_completed'     => 0,
            'completed_at'            => null,
            'replacement_proposal_id' => null,
            'notes'                   => $data['notes'] ?? null,
            'created_at'              => $now,
            'updated_at'              => $now,
        ];

        $this->db->table('workforce_duty_assignments')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();

        return $insert;
    }

    public function completeDutyAssignment(int $id, ?int $actorUserId = null): array
    {
        $assignment = $this->db->table('workforce_duty_assignments')->where('id', $id)->get()->getRowArray();
        if (!$assignment) {
            throw new \InvalidArgumentException("Duty assignment #{$id} not found.", 404);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('workforce_duty_assignments')->where('id', $id)->update([
            'status'              => 'completed',
            'is_actual_completed' => 1,
            'completed_at'        => $now,
            'updated_at'          => $now,
        ]);

        return $this->db->table('workforce_duty_assignments')->where('id', $id)->get()->getRowArray();
    }

    // ------------------------------------------------------------------------
    // 5. AGENT AVAILABILITY & OFF-DUTY REQUEST VALIDATION
    // ------------------------------------------------------------------------

    public function getAgentAvailability(int $agentId, ?string $startDate = null, ?string $endDate = null): array
    {
        $builder = $this->db->table('workforce_agent_availability')
            ->where('agent_id', $agentId);

        if ($startDate) {
            $builder->where('start_time >=', "{$startDate} 00:00:00");
        }
        if ($endDate) {
            $builder->where('start_time <=', "{$endDate} 23:59:59");
        }

        return $builder->orderBy('start_time', 'ASC')->get()->getResultArray();
    }

    /**
     * Controlled off-duty transition validation:
     * Mandates that if the agent has active duties, a qualified replacement proposal
     * must be accepted and mandatory Wallbook handover must be completed before off-duty takes effect!
     */
    public function requestOffDuty(int $agentId, array $data, ?int $actorUserId = null): array
    {
        $startDate = $data['start_date'] ?? date('Y-m-d');
        $endDate = $data['end_date'] ?? $startDate;
        $reason = trim($data['reason'] ?? 'Planned leave / off-duty transition');
        $now = date('Y-m-d H:i:s');

        // Check if agent has active duty assignments during this period
        $activeDuties = $this->db->table('workforce_duty_assignments')
            ->where('agent_id', $agentId)
            ->where('duty_type', 'working')
            ->where('status', 'scheduled')
            ->where('assignment_date >=', $startDate)
            ->where('assignment_date <=', $endDate)
            ->get()
            ->getResultArray();

        $replacementRequired = !empty($activeDuties);
        $replacementSatisfied = false;
        $handoverSatisfied = false;

        if ($replacementRequired) {
            foreach ($activeDuties as $duty) {
                // Check if replacement proposal exists and is at least 'accepted' or 'activated'
                $replacement = $this->db->table('workforce_replacement_proposals')
                    ->where('duty_assignment_id', $duty['id'])
                    ->whereIn('status', ['accepted', 'approved', 'activated'])
                    ->get()
                    ->getRowArray();

                if ($replacement) {
                    $replacementSatisfied = true;
                }

                // Check Wallbook handover
                $handover = $this->db->table('wallbook_handovers')
                    ->where('duty_assignment_id', $duty['id'])
                    ->whereIn('status', ['acknowledged', 'accepted', 'completed'])
                    ->get()
                    ->getRowArray();

                if ($handover) {
                    $handoverSatisfied = true;
                }
            }

            // Enforce policy: Cannot mark off-duty as approved if replacement or handover missing
            if (!$replacementSatisfied || !$handoverSatisfied) {
                $status = 'pending';
                $approvalStatus = 'pending';
                $blockReason = "Off-duty request pending mandatory prerequisites: Replacement Satisfied=" . ($replacementSatisfied ? 'YES' : 'NO') . ", Wallbook Handover Satisfied=" . ($handoverSatisfied ? 'YES' : 'NO');
            } else {
                $status = 'approved';
                $approvalStatus = 'approved';
                $blockReason = null;
            }
        } else {
            // No scheduled working duties (e.g. regular rest days)
            $status = 'approved';
            $approvalStatus = 'approved';
            $blockReason = null;
        }

        $insert = [
            'agent_id'            => $agentId,
            'availability_type'   => 'planned_leave',
            'start_time'          => "{$startDate} 00:00:00",
            'end_time'            => "{$endDate} 23:59:59",
            'reason'              => $reason . ($blockReason ? " [{$blockReason}]" : ''),
            'approval_status'     => $approvalStatus,
            'approved_by_user_id' => $approvalStatus === 'approved' ? $actorUserId : null,
            'approved_at'         => $approvalStatus === 'approved' ? $now : null,
            'created_by_user_id'  => $actorUserId,
            'created_at'          => $now,
            'updated_at'          => $now,
        ];

        $this->db->table('workforce_agent_availability')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();

        // Audit
        $this->db->table('workforce_scheduling_audit')->insert([
            'event_type'     => $approvalStatus === 'approved' ? 'off_duty_approved' : 'off_duty_pending_handover',
            'target_type'    => 'availability',
            'target_id'      => $insert['id'],
            'agent_id'       => $agentId,
            'actor_user_id'  => $actorUserId,
            'actor_name'     => $actorUserId ? "User #{$actorUserId}" : 'Workforce System',
            'action_details' => "Off-duty request for Agent #{$agentId} from {$startDate} to {$endDate}. Status: {$approvalStatus}. " . ($blockReason ?? 'Mandatory conditions satisfied.'),
            'created_at'     => $now,
        ]);

        return [
            'request'               => $insert,
            'is_fully_approved'     => ($approvalStatus === 'approved'),
            'replacement_satisfied' => $replacementSatisfied,
            'handover_satisfied'    => $handoverSatisfied,
            'block_reason'          => $blockReason,
        ];
    }
}
