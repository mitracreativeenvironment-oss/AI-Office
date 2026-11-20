<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class WorkforceSchedulingSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');

        // Fetch agents and units for FKs
        $agents = [];
        $agentRows = $this->db->table('ai_agent_registry')->get()->getResultArray();
        foreach ($agentRows as $row) {
            $key = $row['legacy_agent_key'] ?? $row['agent_code'];
            $agents[$key] = (int)$row['id'];
        }

        $units = [];
        $unitRows = $this->db->table('organization_units')->get()->getResultArray();
        foreach ($unitRows as $row) {
            $units[$row['code']] = (int)$row['id'];
        }

        $skills = [];
        $skillRows = $this->db->table('skill_catalogue')->get()->getResultArray();
        foreach ($skillRows as $row) {
            $skills[$row['code']] = (int)$row['id'];
        }

        // 1. Workforce Schedule Policies
        $policies = [
            [
                'policy_code'             => 'POL-5-2-STD',
                'name'                    => 'MCE Standard 5/2 Workforce Roster Policy',
                'description'             => 'Standard corporate policy: 5 working days and 2 rest days per cycle, staggered across teams to preserve continuous coverage.',
                'cycle_days'              => 7,
                'working_days_count'      => 5,
                'rest_days_count'         => 2,
                'min_rest_interval_hours' => 12,
                'applicable_unit_id'      => null, // Company-wide
                'is_default'              => 1,
                'is_active'               => 1,
                'effective_from'          => '2026-01-01',
                'effective_to'            => null,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
            [
                'policy_code'             => 'POL-24-7-STG',
                'name'                    => 'Technology Division 24/7 Operational Roster Policy',
                'description'             => 'Continuous operational policy for critical engineering services with rotating 3-shift coverage maintaining 5/2 rest constraints.',
                'cycle_days'              => 7,
                'working_days_count'      => 5,
                'rest_days_count'         => 2,
                'min_rest_interval_hours' => 12,
                'applicable_unit_id'      => $units['DIV-TECH'] ?? null,
                'is_default'              => 0,
                'is_active'               => 1,
                'effective_from'          => '2026-01-01',
                'effective_to'            => null,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
        ];

        foreach ($policies as $policy) {
            $exists = $this->db->table('workforce_schedule_policies')->where('policy_code', $policy['policy_code'])->countAllResults();
            if ($exists === 0) {
                $this->db->table('workforce_schedule_policies')->insert($policy);
            }
        }

        $defaultPolicyId = (int)$this->db->table('workforce_schedule_policies')->where('policy_code', 'POL-5-2-STD')->get()->getRowArray()['id'];

        // 2. Workforce Shifts
        $shifts = [
            [
                'shift_code'         => 'SFT-MORN',
                'name'               => 'Morning Shift',
                'start_time'         => '08:00:00',
                'end_time'           => '16:00:00',
                'timezone'           => 'Asia/Jakarta',
                'duration_hours'     => 8.00,
                'crosses_midnight'   => 0,
                'applicable_unit_id' => null,
                'coverage_category'  => 'business_hours',
                'is_active'          => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'shift_code'         => 'SFT-AFTN',
                'name'               => 'Afternoon & Evening Shift',
                'start_time'         => '16:00:00',
                'end_time'           => '00:00:00',
                'timezone'           => 'Asia/Jakarta',
                'duration_hours'     => 8.00,
                'crosses_midnight'   => 0,
                'applicable_unit_id' => null,
                'coverage_category'  => 'extended_hours',
                'is_active'          => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'shift_code'         => 'SFT-NGHT',
                'name'               => 'Night Shift (Overnight Monitoring)',
                'start_time'         => '00:00:00',
                'end_time'           => '08:00:00',
                'timezone'           => 'Asia/Jakarta',
                'duration_hours'     => 8.00,
                'crosses_midnight'   => 0,
                'applicable_unit_id' => null,
                'coverage_category'  => 'continuous_24_7',
                'is_active'          => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'shift_code'         => 'SFT-STD-DAY',
                'name'               => 'Standard Corporate Day Shift',
                'start_time'         => '09:00:00',
                'end_time'           => '17:00:00',
                'timezone'           => 'Asia/Jakarta',
                'duration_hours'     => 8.00,
                'crosses_midnight'   => 0,
                'applicable_unit_id' => null,
                'coverage_category'  => 'business_hours',
                'is_active'          => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
        ];

        foreach ($shifts as $s) {
            $exists = $this->db->table('workforce_shifts')->where('shift_code', $s['shift_code'])->countAllResults();
            if ($exists === 0) {
                $this->db->table('workforce_shifts')->insert($s);
            }
        }

        $shiftMap = [];
        $shiftRows = $this->db->table('workforce_shifts')->get()->getResultArray();
        foreach ($shiftRows as $s) {
            $shiftMap[$s['shift_code']] = (int)$s['id'];
        }

        // 3. Operational Coverage Requirements
        $coverageReqs = [
            [
                'requirement_code'    => 'REQ-COV-EXEC',
                'name'                => 'Executive Office Daily Operational Presence',
                'unit_id'             => $units['ORG-MCE'] ?? 1,
                'shift_id'            => $shiftMap['SFT-STD-DAY'],
                'day_of_week'         => 'weekday',
                'min_agents_required' => 1,
                'required_skill_id'   => $skills['SKL-CORP-GOV'] ?? null,
                'min_proficiency'     => 'advanced',
                'criticality'         => 'critical',
                'is_continuous_24_7'  => 0,
                'is_active'           => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'requirement_code'    => 'REQ-COV-TECH-DAY',
                'name'                => 'Technology Division Daytime Systems Coverage',
                'unit_id'             => $units['DIV-TECH'] ?? 2,
                'shift_id'            => $shiftMap['SFT-MORN'],
                'day_of_week'         => 'all',
                'min_agents_required' => 1,
                'required_skill_id'   => $skills['SKL-SYS-ARCH'] ?? null,
                'min_proficiency'     => 'advanced',
                'criticality'         => 'critical',
                'is_continuous_24_7'  => 1,
                'is_active'           => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'requirement_code'    => 'REQ-COV-TECH-EVE',
                'name'                => 'Technology Division Evening Operations Coverage',
                'unit_id'             => $units['DIV-TECH'] ?? 2,
                'shift_id'            => $shiftMap['SFT-AFTN'],
                'day_of_week'         => 'all',
                'min_agents_required' => 1,
                'required_skill_id'   => $skills['SKL-FULLSTACK'] ?? null,
                'min_proficiency'     => 'intermediate',
                'criticality'         => 'high',
                'is_continuous_24_7'  => 0,
                'is_active'           => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'requirement_code'    => 'REQ-COV-SRV',
                'name'                => 'Services Division Daily Client Operations',
                'unit_id'             => $units['DIV-SRV'] ?? 4,
                'shift_id'            => $shiftMap['SFT-MORN'],
                'day_of_week'         => 'weekday',
                'min_agents_required' => 1,
                'required_skill_id'   => $skills['SKL-AGILE-COORD'] ?? null,
                'min_proficiency'     => 'intermediate',
                'criticality'         => 'high',
                'is_continuous_24_7'  => 0,
                'is_active'           => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'requirement_code'    => 'REQ-COV-ACD',
                'name'                => 'Academy Curriculum Delivery Coverage',
                'unit_id'             => $units['DIV-ACD'] ?? 7,
                'shift_id'            => $shiftMap['SFT-MORN'],
                'day_of_week'         => 'weekday',
                'min_agents_required' => 1,
                'required_skill_id'   => $skills['SKL-CURR-DES'] ?? null,
                'min_proficiency'     => 'advanced',
                'criticality'         => 'medium',
                'is_continuous_24_7'  => 0,
                'is_active'           => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'requirement_code'    => 'REQ-COV-HDW',
                'name'                => 'Hardware Systems Lab Monitoring',
                'unit_id'             => $units['DIV-HDW'] ?? 5,
                'shift_id'            => $shiftMap['SFT-MORN'],
                'day_of_week'         => 'weekday',
                'min_agents_required' => 1,
                'required_skill_id'   => $skills['SKL-IOT-EMBED'] ?? null,
                'min_proficiency'     => 'advanced',
                'criticality'         => 'medium',
                'is_continuous_24_7'  => 0,
                'is_active'           => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
        ];

        foreach ($coverageReqs as $cr) {
            $exists = $this->db->table('workforce_coverage_requirements')->where('requirement_code', $cr['requirement_code'])->countAllResults();
            if ($exists === 0) {
                $this->db->table('workforce_coverage_requirements')->insert($cr);
            }
        }

        // 4. Current Week Roster (Published Staggered 5/2 Schedule)
        // Let's compute Monday of current week
        $currentMonday = date('Y-m-d', strtotime('monday this week', strtotime($today)));
        $currentSunday = date('Y-m-d', strtotime('sunday this week', strtotime($today)));
        $rosterCode = 'RST-' . date('Y', strtotime($currentMonday)) . '-W' . date('W', strtotime($currentMonday));

        $rosterExists = $this->db->table('workforce_rosters')->where('roster_code', $rosterCode)->get()->getRowArray();
        if (!$rosterExists) {
            $this->db->table('workforce_rosters')->insert([
                'roster_code'          => $rosterCode,
                'title'                => 'Master Workforce Staggered Roster (Week ' . date('W', strtotime($currentMonday)) . ', ' . date('Y', strtotime($currentMonday)) . ')',
                'policy_id'            => $defaultPolicyId,
                'start_date'           => $currentMonday,
                'end_date'             => $currentSunday,
                'unit_id'              => null, // Enterprise-wide
                'status'               => 'published',
                'published_by_user_id' => 1,
                'published_at'         => $now,
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);
            $rosterId = (int)$this->db->insertID();
        } else {
            $rosterId = (int)$rosterExists['id'];
        }

        // 5. Duty Assignments for the 6 Agents across 7 Days
        // Staggered 5/2 Configuration:
        // - Director: Mon-Fri Work (SFT-STD-DAY), Sat-Sun Rest
        // - Tech Head: Mon-Fri Work (SFT-MORN), Sat-Sun Rest
        // - Dev AI: Wed-Sun Work (SFT-MORN), Mon-Tue Rest (Staggered to cover Tech Division on weekends!)
        // - Services Head: Mon-Fri Work (SFT-MORN), Sat-Sun Rest
        // - Academy Head: Tue-Sat Work (SFT-MORN), Sun-Mon Rest (Staggered for weekend learning!)
        // - Hardware Head: Mon-Fri Work (SFT-MORN), Sat-Sun Rest

        $agentSchedules = [
            'director' => [
                'work_days'  => [1, 2, 3, 4, 5], // Mon-Fri
                'rest_days'  => [6, 7],          // Sat-Sun
                'shift_code' => 'SFT-STD-DAY',
            ],
            'tech_head' => [
                'work_days'  => [1, 2, 3, 4, 5],
                'rest_days'  => [6, 7],
                'shift_code' => 'SFT-MORN',
            ],
            'dev_ai' => [
                'work_days'  => [3, 4, 5, 6, 7], // Wed-Sun (covers weekend!)
                'rest_days'  => [1, 2],          // Mon-Tue
                'shift_code' => 'SFT-MORN',
            ],
            'services_head' => [
                'work_days'  => [1, 2, 3, 4, 5],
                'rest_days'  => [6, 7],
                'shift_code' => 'SFT-MORN',
            ],
            'academy_head' => [
                'work_days'  => [2, 3, 4, 5, 6], // Tue-Sat (covers Saturday!)
                'rest_days'  => [7, 1],          // Sun-Mon
                'shift_code' => 'SFT-MORN',
            ],
            'hardware_head' => [
                'work_days'  => [1, 2, 3, 4, 5],
                'rest_days'  => [6, 7],
                'shift_code' => 'SFT-MORN',
            ],
        ];

        for ($dayIndex = 0; $dayIndex < 7; $dayIndex++) {
            $assignmentDate = date('Y-m-d', strtotime("{$currentMonday} +{$dayIndex} days"));
            $dayOfWeekIso = (int)date('N', strtotime($assignmentDate)); // 1 (Mon) to 7 (Sun)

            foreach ($agentSchedules as $agentKey => $sched) {
                $agentId = $agents[$agentKey] ?? null;
                if (!$agentId) continue;

                $isWorkDay = in_array($dayOfWeekIso, $sched['work_days']);
                $dutyType = $isWorkDay ? 'working' : 'rest';
                $shiftId = $isWorkDay ? ($shiftMap[$sched['shift_code']] ?? null) : null;

                $existingAssignment = $this->db->table('workforce_duty_assignments')
                    ->where('agent_id', $agentId)
                    ->where('assignment_date', $assignmentDate)
                    ->get()
                    ->getRowArray();

                if (!$existingAssignment) {
                    $this->db->table('workforce_duty_assignments')->insert([
                        'roster_id'               => $rosterId,
                        'agent_id'                => $agentId,
                        'shift_id'                => $shiftId,
                        'assignment_date'         => $assignmentDate,
                        'duty_type'               => $dutyType,
                        'status'                  => 'scheduled',
                        'is_actual_completed'     => 0,
                        'completed_at'            => null,
                        'replacement_proposal_id' => null,
                        'notes'                   => $isWorkDay ? "Scheduled regular duty under 5/2 roster" : "Scheduled 5/2 mandatory rest day",
                        'created_at'              => $now,
                        'updated_at'              => $now,
                    ]);
                    $dutyId = (int)$this->db->insertID();

                    // Also seed availability record
                    $this->db->table('workforce_agent_availability')->insert([
                        'agent_id'            => $agentId,
                        'availability_type'   => $isWorkDay ? 'scheduled_duty' : 'scheduled_rest',
                        'start_time'          => "{$assignmentDate} 08:00:00",
                        'end_time'            => "{$assignmentDate} 16:00:00",
                        'reason'              => $isWorkDay ? "Assigned to shift duty" : "Designated 5/2 rest period",
                        'roster_id'           => $rosterId,
                        'duty_assignment_id'  => $dutyId,
                        'approval_status'     => 'approved',
                        'approved_by_user_id' => 1,
                        'approved_at'         => $now,
                        'created_by_user_id'  => 1,
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ]);
                } else {
                    $this->db->table('workforce_duty_assignments')->where('id', $existingAssignment['id'])->update([
                        'duty_type' => $dutyType,
                        'shift_id'  => $shiftId,
                    ]);
                }
            }
        }

        // 6. Sample Qualified Replacement Proposal
        // e.g. Dev AI proposed to replace Tech Head AI for an upcoming maintenance shift
        $techHeadId = $agents['tech_head'] ?? 2;
        $devAiId = $agents['dev_ai'] ?? 6;

        $targetAssignment = $this->db->table('workforce_duty_assignments')
            ->where('agent_id', $techHeadId)
            ->where('duty_type', 'working')
            ->orderBy('assignment_date', 'DESC')
            ->get()
            ->getRowArray();

        $proposalCode = 'REP-2026-001';
        $propExists = $this->db->table('workforce_replacement_proposals')->where('proposal_code', $proposalCode)->get()->getRowArray();
        $replacementId = null;

        if (!$propExists && $targetAssignment) {
            $this->db->table('workforce_replacement_proposals')->insert([
                'proposal_code'              => $proposalCode,
                'outgoing_agent_id'          => $techHeadId,
                'incoming_agent_id'          => $devAiId,
                'duty_assignment_id'         => $targetAssignment['id'],
                'original_responsibility'    => 'Technology Systems Operational Leadership & Architecture Oversight',
                'start_time'                 => "{$targetAssignment['assignment_date']} 08:00:00",
                'end_time'                   => "{$targetAssignment['assignment_date']} 16:00:00",
                'reason'                     => 'Tech Head scheduled for formal corporate governance review; Dev AI qualified with SKL-SYS-ARCH & SKL-FULLSTACK to cover shift.',
                'qualification_notes'        => 'Candidate verified: Dev AI possesses required Full-Stack and System Architecture competencies with zero conflicting shift assignments.',
                'status'                     => 'accepted',
                'proposed_by_user_id'        => 1,
                'accepted_at'                => $now,
                'accepted_by_agent_identity' => 'AGT-DEV-001',
                'approved_by_user_id'        => null,
                'approved_at'                => null,
                'activated_at'               => null,
                'created_at'                 => $now,
                'updated_at'                 => $now,
            ]);
            $replacementId = (int)$this->db->insertID();
        } else if ($propExists) {
            $replacementId = (int)$propExists['id'];
        }

        // 7. Sample Formal Wallbook Handover
        $handoverCode = 'WB-2026-001';
        $wbExists = $this->db->table('wallbook_handovers')->where('handover_code', $handoverCode)->get()->getRowArray();

        if (!$wbExists) {
            $this->db->table('wallbook_handovers')->insert([
                'handover_code'           => $handoverCode,
                'title'                   => 'Technology Operations Handover: CI4 API & System Monitoring',
                'outgoing_agent_id'       => $techHeadId,
                'incoming_agent_id'       => $devAiId,
                'unit_id'                 => $units['DIV-TECH'] ?? 2,
                'duty_assignment_id'      => $targetAssignment['id'] ?? null,
                'replacement_proposal_id' => $replacementId,
                'shift_id'                => $shiftMap['SFT-MORN'] ?? null,
                'deadline_at'             => date('Y-m-d H:i:s', strtotime('+24 hours')),
                'status'                  => 'awaiting_acknowledgement',
                'submitted_at'            => $now,
                'acknowledged_at'         => null,
                'accepted_at'             => null,
                'escalated_at'            => null,
                'escalation_reason'       => null,
                'created_at'              => $now,
                'updated_at'              => $now,
            ]);
            $handoverId = (int)$this->db->insertID();

            // Insert Structured Handover Items across mandatory sections
            $items = [
                [
                    'handover_id'      => $handoverId,
                    'section_category' => 'responsibilities',
                    'item_order'       => 1,
                    'title'            => 'Primary Shift Responsibilities',
                    'content'          => 'Maintain CI4 REST API health monitoring, verify MySQL connection pools, review incoming PRs for backend modules.',
                    'item_status'      => 'transferred',
                    'reference_type'   => 'project',
                    'reference_id'     => 'MCE-API-CORE',
                ],
                [
                    'handover_id'      => $handoverId,
                    'section_category' => 'active_tasks',
                    'item_order'       => 2,
                    'title'            => 'Active Task Execution',
                    'content'          => 'Verify Prompt 20 scheduling unit test assertions and audit log verification.',
                    'item_status'      => 'in_progress',
                    'reference_type'   => 'task',
                    'reference_id'     => 'TASK-WFD-001',
                ],
                [
                    'handover_id'      => $handoverId,
                    'section_category' => 'completed_work',
                    'item_order'       => 3,
                    'title'            => 'Completed Migration Batch 5',
                    'content'          => 'Successfully applied 11 relational scheduling and Wallbook tables in mce_db.',
                    'item_status'      => 'resolved',
                    'reference_type'   => 'task',
                    'reference_id'     => 'MIG-BATCH-005',
                ],
                [
                    'handover_id'      => $handoverId,
                    'section_category' => 'operational_risks',
                    'item_order'       => 4,
                    'title'            => 'Overdue Handover Risk',
                    'content'          => 'Ensure receiving agent acknowledges receipt before the shift start to avoid automated supervisory escalation.',
                    'item_status'      => 'pending',
                    'reference_type'   => 'incident',
                    'reference_id'     => 'RISK-TIME-001',
                ],
                [
                    'handover_id'      => $handoverId,
                    'section_category' => 'context_instructions',
                    'item_order'       => 5,
                    'title'            => 'Operational Guidelines & Escalation Trigger',
                    'content'          => 'In case of database lock or API 500 error, notify Director AI immediately and record event in workforce_scheduling_audit.',
                    'item_status'      => 'transferred',
                    'reference_type'   => 'doc',
                    'reference_id'     => 'DOC-OPS-GUIDE',
                ],
            ];

            foreach ($items as $item) {
                $item['created_at'] = $now;
                $item['updated_at'] = $now;
                $this->db->table('wallbook_handover_items')->insert($item);
            }
        }

        // 8. Audit event
        $this->db->table('workforce_scheduling_audit')->insert([
            'event_type'     => 'schedule_published',
            'target_type'    => 'roster',
            'target_id'      => $rosterId,
            'agent_id'       => null,
            'actor_user_id'  => 1,
            'actor_name'     => 'System Seeder',
            'action_details' => "Master 5/2 staggered roster {$rosterCode} published with 42 duty assignments and Wallbook handover {$handoverCode}.",
            'ip_address'     => '127.0.0.1',
            'created_at'     => $now,
        ]);
    }
}
