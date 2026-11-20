<?php

namespace App\Controllers\Api;

class ApprovalController extends BaseApiController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $status = $this->request->getGet('status') ?? 'pending';

        $builder = $db->table('approval_requests');
        if ($status !== 'all') {
            $builder->where('status', $status);
        }

        $requests = $builder->orderBy('id', 'DESC')->get()->getResultArray();

        return $this->respondSuccess($requests, 'Approval requests retrieved');
    }

    public function decide($id = null)
    {
        $db = \Config\Database::connect();
        $request = $db->table('approval_requests')->where('id', $id)->get()->getRowArray();

        if (!$request) {
            return $this->respondNotFound("Approval request #{$id} not found");
        }

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $action = strtolower($json['action'] ?? '');
        $notes = $json['notes'] ?? null;
        $decidedBy = $json['decided_by'] ?? 'Human Owner';

        if (!in_array($action, ['approved', 'rejected'])) {
            return $this->respondError("Action must be 'approved' or 'rejected'");
        }

        $now = date('Y-m-d H:i:s');
        $db->table('approval_requests')->where('id', $id)->update([
            'status'         => $action,
            'decision_notes' => $notes,
            'decided_by'     => $decidedBy,
            'decided_at'     => $now,
            'updated_at'     => $now,
        ]);

        // Jika diapprove dan ada task terkait, update status task
        if ($action === 'approved' && $request['task_id']) {
            $db->table('tasks')->where('id', $request['task_id'])->update([
                'status'     => 'working',
                'updated_at' => $now,
            ]);
        }

        return $this->respondSuccess([
            'id'     => $id,
            'status' => $action,
            'notes'  => $notes,
        ], "Approval request #{$id} has been {$action}");
    }
}
