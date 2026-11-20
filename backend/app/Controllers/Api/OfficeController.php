<?php

namespace App\Controllers\Api;

class OfficeController extends BaseApiController
{
    public function layout()
    {
        $db = \Config\Database::connect();
        $divisions = $db->table('divisions')->where('is_active', 1)->get()->getResultArray();
        $agents = $db->table('agents')->where('is_online', 1)->get()->getResultArray();

        $rooms = array_map(function ($d) {
            return [
                'key'       => $d['key'],
                'label'     => $d['label'],
                'x0'        => (float)$d['x0'],
                'x1'        => (float)$d['x1'],
                'warna'     => $d['color'],
                'karpet'    => $d['rug_color'],
            ];
        }, $divisions);

        $desks = array_map(function ($a) {
            return [
                'key'   => $a['key'],
                'nama'  => $a['name'],
                'tugas' => $a['current_task'] ?? $a['role_title'],
                'ruang' => $a['division_key'],
                'warna' => $a['color'],
                'level' => $a['level'],
                'x'     => (float)$a['pos_x'],
                'z'     => (float)$a['pos_z'],
            ];
        }, $agents);

        return $this->respondSuccess([
            'kantor' => [
                'nama'     => 'MCE Virtual Office',
                'subjudul' => 'Mitra Creative Environment — AI Company OS',
            ],
            'ruangan' => $rooms,
            'agen'    => $desks,
        ], '3D Office layout data retrieved');
    }

    public function feed()
    {
        $db = \Config\Database::connect();
        $agents = $db->table('agents')->get()->getResultArray();
        $feed = [];

        foreach ($agents as $a) {
            $pendingApprovals = $db->table('approval_requests')
                ->where('requester_agent', $a['key'])
                ->where('status', 'pending')
                ->get()->getResultArray();

            $urgentTasks = $db->table('tasks')
                ->where('agent_key', $a['key'])
                ->where('priority', 'critical')
                ->where('status !=', 'completed')
                ->get()->getResultArray();

            $feed[$a['key']] = [
                'ringkasan' => $a['current_task'] ?? $a['role_title'],
                'menunggu'  => array_map(fn($r) => "[Level C] " . $r['title'], $pendingApprovals),
                'mendesak'  => array_map(fn($t) => $t['title'], $urgentTasks),
                'angka'     => [
                    ['label' => 'Status', 'nilai' => ucfirst($a['status'])],
                    ['label' => 'Kewenangan', 'nilai' => 'Level ' . $a['approval_level']],
                ],
            ];
        }

        return $this->respondSuccess($feed, 'Live agent reports feed retrieved');
    }
}
