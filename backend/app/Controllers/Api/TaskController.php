<?php

namespace App\Controllers\Api;

class TaskController extends BaseApiController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tasks');

        $division = $this->request->getGet('division');
        $status = $this->request->getGet('status');
        $priority = $this->request->getGet('priority');
        $track = $this->request->getGet('track');

        if ($division) $builder->where('division_key', $division);
        if ($status) $builder->where('status', $status);
        if ($priority) $builder->where('priority', $priority);
        if ($track) $builder->where('track', $track);

        $tasks = $builder->orderBy('id', 'DESC')->get()->getResultArray();

        return $this->respondSuccess($tasks, 'Tasks list retrieved successfully');
    }

    public function create()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();

        if (empty($json['title']) || empty($json['division_key']) || empty($json['agent_key'])) {
            return $this->respondError('Missing required fields: title, division_key, agent_key');
        }

        $db = \Config\Database::connect();
        $code = 'TSK-MCE-' . str_pad((string)(mt_rand(100, 999)), 3, '0', STR_PAD_LEFT);
        $now = date('Y-m-d H:i:s');

        $data = [
            'task_code'      => $code,
            'title'          => $json['title'],
            'description'    => $json['description'] ?? null,
            'division_key'   => $json['division_key'],
            'agent_key'      => $json['agent_key'],
            'track'          => $json['track'] ?? 'project_delivery',
            'priority'       => $json['priority'] ?? 'project',
            'approval_level' => $json['approval_level'] ?? 'A',
            'status'         => 'pending',
            'progress'       => 0,
            'created_at'     => $now,
            'updated_at'     => $now,
        ];

        $db->table('tasks')->insert($data);
        $data['id'] = $db->insertID();

        return $this->respondSuccess($data, 'Task created successfully', 201);
    }
}
