<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class TaskDependencyService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function listDependencies(int $taskId): array
    {
        return $this->db->table('task_dependencies d')
            ->select('d.*, t.task_code as upstream_code, t.title as upstream_title, t.status as upstream_status')
            ->join('workforce_tasks t', 't.id = d.depends_on_task_id')
            ->where('d.task_id', $taskId)
            ->get()
            ->getResultArray();
    }

    /**
     * Check if a directed path exists in the dependency graph from $startTaskId to $targetTaskId.
     * Used to prevent circular dependency cycles.
     */
    protected function hasDirectedPath(int $startTaskId, int $targetTaskId, array &$visited = []): bool
    {
        if ($startTaskId === $targetTaskId) {
            return true;
        }

        $visited[$startTaskId] = true;

        $upstreamTasks = $this->db->table('task_dependencies')
            ->where('task_id', $startTaskId)
            ->get()
            ->getResultArray();

        foreach ($upstreamTasks as $dep) {
            $nextId = (int)$dep['depends_on_task_id'];
            if (!isset($visited[$nextId])) {
                if ($this->hasDirectedPath($nextId, $targetTaskId, $visited)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function addDependency(int $taskId, int $dependsOnTaskId, string $type = 'finish_to_start'): array
    {
        if ($taskId === $dependsOnTaskId) {
            throw new \InvalidArgumentException('A task cannot depend on itself.', 400);
        }

        // Verify tasks exist
        $task = $this->db->table('workforce_tasks')->where('id', $taskId)->get()->getRowArray();
        $upstream = $this->db->table('workforce_tasks')->where('id', $dependsOnTaskId)->get()->getRowArray();

        if (!$task || !$upstream) {
            throw new \InvalidArgumentException("Task #{$taskId} or prerequisite #{$dependsOnTaskId} not found.", 404);
        }

        // Circular Dependency Detection:
        // If $dependsOnTaskId already reaches $taskId, adding $taskId -> $dependsOnTaskId creates a cycle!
        if ($this->hasDirectedPath($dependsOnTaskId, $taskId)) {
            throw new \RuntimeException(
                "Circular dependency rejected: Prerequisite Task #{$dependsOnTaskId} ({$upstream['task_code']}) already directly or transitively depends on Task #{$taskId} ({$task['task_code']}).",
                409
            );
        }

        // Check duplicate
        $exists = $this->db->table('task_dependencies')
            ->where('task_id', $taskId)
            ->where('depends_on_task_id', $dependsOnTaskId)
            ->get()
            ->getRowArray();

        if ($exists) {
            return $exists;
        }

        // Check if upstream is already completed/verified
        $isSatisfied = in_array($upstream['status'], ['verified', 'completed']) ? 1 : 0;
        $now = date('Y-m-d H:i:s');

        $insert = [
            'task_id'            => $taskId,
            'depends_on_task_id' => $dependsOnTaskId,
            'dependency_type'    => $type,
            'is_satisfied'       => $isSatisfied,
            'satisfied_at'       => $isSatisfied ? $now : null,
            'created_at'         => $now,
            'updated_at'         => $now,
        ];

        $this->db->table('task_dependencies')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();

        return $insert;
    }

    public function areDependenciesSatisfied(int $taskId): bool
    {
        $unsatisfied = $this->db->table('task_dependencies')
            ->where('task_id', $taskId)
            ->where('is_satisfied', 0)
            ->countAllResults();

        return ($unsatisfied === 0);
    }
}
