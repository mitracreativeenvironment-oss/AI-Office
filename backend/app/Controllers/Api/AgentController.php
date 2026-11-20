<?php

namespace App\Controllers\Api;

class AgentController extends BaseApiController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $agents = $db->table('agents')->orderBy('id', 'ASC')->get()->getResultArray();

        return $this->respondSuccess($agents, 'Agents list retrieved successfully');
    }

    public function show($key = null)
    {
        $db = \Config\Database::connect();
        $agent = $db->table('agents')->where('key', $key)->get()->getRowArray();

        if (!$agent) {
            return $this->respondNotFound("Agent with key '{$key}' not found");
        }

        $activeTasks = $db->table('tasks')
            ->where('agent_key', $key)
            ->whereIn('status', ['assigned', 'working', 'waiting_approval'])
            ->get()->getResultArray();

        $agent['tasks'] = $activeTasks;

        return $this->respondSuccess($agent, "Agent details for '{$key}'");
    }

    public function updateStatus($key = null)
    {
        $db = \Config\Database::connect();
        $agent = $db->table('agents')->where('key', $key)->get()->getRowArray();

        if (!$agent) {
            return $this->respondNotFound("Agent '{$key}' not found");
        }

        $json = $this->request->getJSON(true);
        $status = $json['status'] ?? null;
        $currentTask = $json['current_task'] ?? null;

        if (!$status) {
            return $this->respondError('Field status is required');
        }

        $updateData = [
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($currentTask !== null) {
            $updateData['current_task'] = $currentTask;
        }

        $db->table('agents')->where('key', $key)->update($updateData);

        return $this->respondSuccess($updateData, "Agent status updated successfully");
    }
}
