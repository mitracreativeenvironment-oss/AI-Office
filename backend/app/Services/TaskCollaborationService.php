<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class TaskCollaborationService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function listGroups(?int $taskId = null): array
    {
        $builder = $this->db->table('task_collaboration_groups g')
            ->select('g.*, t.task_code, t.title as task_title, ag.name as lead_agent_name, ag.agent_code as lead_agent_code')
            ->join('workforce_tasks t', 't.id = g.task_id')
            ->join('ai_agent_registry ag', 'ag.id = g.lead_agent_id');

        if ($taskId) {
            $builder->where('g.task_id', $taskId);
        }

        $groups = $builder->orderBy('g.id', 'DESC')->get()->getResultArray();

        foreach ($groups as &$group) {
            $group['members_count'] = $this->db->table('task_collaboration_members')
                ->where('group_id', $group['id'])
                ->where('status', 'active')
                ->countAllResults();
        }

        return $groups;
    }

    public function getGroup(int $id): ?array
    {
        $group = $this->db->table('task_collaboration_groups g')
            ->select('g.*, t.task_code, t.title as task_title, ag.name as lead_agent_name, ag.agent_code as lead_agent_code')
            ->join('workforce_tasks t', 't.id = g.task_id')
            ->join('ai_agent_registry ag', 'ag.id = g.lead_agent_id')
            ->where('g.id', $id)
            ->get()
            ->getRowArray();

        if (!$group) return null;

        $group['members'] = $this->db->table('task_collaboration_members m')
            ->select('m.*, ag.name as agent_name, ag.agent_code, ag.classification')
            ->join('ai_agent_registry ag', 'ag.id = m.agent_id')
            ->where('m.group_id', $id)
            ->orderBy('m.id', 'ASC')
            ->get()
            ->getResultArray();

        return $group;
    }

    public function createGroup(array $data, ?int $actorUserId = null): array
    {
        $taskId = (int)($data['task_id'] ?? 0);
        $leadAgentId = (int)($data['lead_agent_id'] ?? 0);
        $title = trim($data['title'] ?? '');

        if (!$taskId || !$leadAgentId || empty($title)) {
            throw new \InvalidArgumentException('Task ID, lead agent ID, and group title are required.', 422);
        }

        $code = trim($data['collaboration_code'] ?? 'COL-' . date('Y') . '-' . rand(100, 999));
        $now = date('Y-m-d H:i:s');

        $insert = [
            'collaboration_code' => $code,
            'task_id'            => $taskId,
            'lead_agent_id'      => $leadAgentId,
            'title'              => $title,
            'shared_objective'   => trim($data['shared_objective'] ?? 'Cross-agent coordinated delivery.'),
            'status'             => 'active',
            'created_at'         => $now,
            'updated_at'         => $now,
        ];

        $this->db->table('task_collaboration_groups')->insert($insert);
        $groupId = (int)$this->db->insertID();

        // Add lead agent as member
        $this->db->table('task_collaboration_members')->insert([
            'group_id'                  => $groupId,
            'agent_id'                  => $leadAgentId,
            'role'                      => 'lead',
            'assigned_responsibilities' => 'Team lead for coordination and deliverable synthesis.',
            'status'                    => 'active',
            'created_at'                => $now,
            'updated_at'                => $now,
        ]);

        return $this->getGroup($groupId);
    }

    public function addMember(int $groupId, int $agentId, string $role, string $responsibilities): array
    {
        $group = $this->getGroup($groupId);
        if (!$group) {
            throw new \InvalidArgumentException("Collaboration group #{$groupId} not found.", 404);
        }

        // Check active agent
        $agent = $this->db->table('ai_agent_registry')->where('id', $agentId)->get()->getRowArray();
        if (!$agent || $agent['lifecycle_state'] !== 'active') {
            throw new \InvalidArgumentException("Agent #{$agentId} is not active in the registry.", 400);
        }

        $now = date('Y-m-d H:i:s');

        // Check if member already in group
        $exists = $this->db->table('task_collaboration_members')
            ->where('group_id', $groupId)
            ->where('agent_id', $agentId)
            ->get()
            ->getRowArray();

        if ($exists) {
            $this->db->table('task_collaboration_members')->where('id', $exists['id'])->update([
                'role'                      => $role,
                'assigned_responsibilities' => $responsibilities,
                'status'                    => 'active',
                'updated_at'                => $now,
            ]);
        } else {
            $this->db->table('task_collaboration_members')->insert([
                'group_id'                  => $groupId,
                'agent_id'                  => $agentId,
                'role'                      => $role,
                'assigned_responsibilities' => $responsibilities,
                'status'                    => 'active',
                'created_at'                => $now,
                'updated_at'                => $now,
            ]);
        }

        // Also ensure a task assignment exists
        $assignExists = $this->db->table('task_assignments')
            ->where('task_id', $group['task_id'])
            ->where('agent_id', $agentId)
            ->countAllResults();

        if ($assignExists === 0) {
            $this->db->table('task_assignments')->insert([
                'task_id'              => $group['task_id'],
                'agent_id'             => $agentId,
                'assignment_role'      => 'collaborator',
                'responsibility_scope' => $responsibilities,
                'acceptance_status'    => 'accepted',
                'accepted_at'          => $now,
                'status'               => 'active',
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);
        }

        return $this->getGroup($groupId);
    }
}
