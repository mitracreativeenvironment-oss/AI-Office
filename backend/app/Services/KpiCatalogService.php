<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class KpiCatalogService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function listCategories(): array
    {
        return $this->db->table('kpi_categories')
            ->where('is_active', 1)
            ->orderBy('display_order', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function listKpis(array $filters = []): array
    {
        $builder = $this->db->table('kpi_definitions k')
            ->select('k.*, c.name as category_name, c.category_code, u.name as unit_name')
            ->join('kpi_categories c', 'c.id = k.category_id', 'left')
            ->join('organization_units u', 'u.id = k.responsible_unit_id', 'left');

        if (!empty($filters['category_id'])) {
            $builder->where('k.category_id', (int)$filters['category_id']);
        }
        if (!empty($filters['performance_level'])) {
            $builder->where('k.performance_level', $filters['performance_level']);
        }
        if (!empty($filters['status'])) {
            $builder->where('k.status', $filters['status']);
        }
        if (isset($filters['is_active'])) {
            $builder->where('k.is_active', (int)$filters['is_active']);
        }

        return $builder->orderBy('k.id', 'ASC')->get()->getResultArray();
    }

    public function getKpi(int $id): ?array
    {
        return $this->db->table('kpi_definitions k')
            ->select('k.*, c.name as category_name, c.category_code, u.name as unit_name')
            ->join('kpi_categories c', 'c.id = k.category_id', 'left')
            ->join('organization_units u', 'u.id = k.responsible_unit_id', 'left')
            ->where('k.id', $id)
            ->get()
            ->getRowArray();
    }

    public function createKpi(array $data, ?int $userId = null): array
    {
        $now = date('Y-m-d H:i:s');
        $code = trim($data['kpi_code'] ?? 'KPI-' . strtoupper(substr(uniqid(), -6)));

        $insert = [
            'kpi_code'                 => $code,
            'category_id'              => (int)($data['category_id'] ?? 1),
            'name'                     => trim($data['name'] ?? 'New Metric'),
            'description'              => $data['description'] ?? null,
            'measurement_objective'    => $data['measurement_objective'] ?? null,
            'performance_level'        => $data['performance_level'] ?? 'corporate',
            'formula_expression'       => $data['formula_expression'] ?? 'COUNT(id)',
            'formula_version'          => $data['formula_version'] ?? 'v1.0',
            'unit_of_measure'          => $data['unit_of_measure'] ?? 'percentage',
            'data_source'              => $data['data_source'] ?? 'workforce_tasks',
            'measurement_frequency'    => $data['measurement_frequency'] ?? 'monthly',
            'target_value'             => isset($data['target_value']) ? (float)$data['target_value'] : null,
            'warning_threshold'        => isset($data['warning_threshold']) ? (float)$data['warning_threshold'] : null,
            'critical_threshold'       => isset($data['critical_threshold']) ? (float)$data['critical_threshold'] : null,
            'default_weight'           => isset($data['default_weight']) ? (float)$data['default_weight'] : 1.00,
            'responsible_unit_id'      => !empty($data['responsible_unit_id']) ? (int)$data['responsible_unit_id'] : null,
            'responsible_role'         => $data['responsible_role'] ?? null,
            'verification_requirement' => $data['verification_requirement'] ?? null,
            'approval_authority'       => $data['approval_authority'] ?? 'continuous_executive_board',
            'status'                   => $data['status'] ?? 'active',
            'is_active'                => 1,
            'created_by_user_id'       => $userId,
            'created_at'               => $now,
            'updated_at'               => $now,
        ];

        $this->db->table('kpi_definitions')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();

        // Audit log
        $this->logAudit('kpi_created', 'kpi_definitions', $insert['id'], $userId, "Created KPI {$code}: {$insert['name']}", null, $insert);

        return $insert;
    }

    public function updateKpi(int $id, array $data, ?int $userId = null): ?array
    {
        $existing = $this->getKpi($id);
        if (!$existing) {
            return null;
        }

        $now = date('Y-m-d H:i:s');
        $allowed = [
            'name', 'description', 'measurement_objective', 'performance_level',
            'formula_expression', 'formula_version', 'unit_of_measure', 'data_source',
            'measurement_frequency', 'target_value', 'warning_threshold', 'critical_threshold',
            'default_weight', 'responsible_unit_id', 'responsible_role', 'verification_requirement',
            'approval_authority', 'status', 'is_active'
        ];

        $update = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }
        $update['updated_at'] = $now;

        $this->db->table('kpi_definitions')->where('id', $id)->update($update);

        $fresh = $this->getKpi($id);
        $this->logAudit('kpi_updated', 'kpi_definitions', $id, $userId, "Updated KPI {$existing['kpi_code']}", $existing, $fresh);

        return $fresh;
    }

    public function deleteKpi(int $id, ?int $userId = null): bool
    {
        $existing = $this->getKpi($id);
        if (!$existing) {
            return false;
        }

        $this->db->table('kpi_definitions')->where('id', $id)->update([
            'is_active'  => 0,
            'status'     => 'deprecated',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logAudit('kpi_deprecated', 'kpi_definitions', $id, $userId, "Deprecated KPI {$existing['kpi_code']}", $existing, null);
        return true;
    }

    protected function logAudit(string $eventType, string $entity, int $entityId, ?int $userId, string $summary, ?array $pre, ?array $post): void
    {
        $this->db->table('performance_audit_trail')->insert([
            'event_type'      => $eventType,
            'target_entity'   => $entity,
            'target_id'       => $entityId,
            'actor_type'      => $userId ? 'human' : 'system',
            'actor_id'        => $userId,
            'action_summary'  => $summary,
            'pre_state_json'  => $pre ? json_encode($pre) : null,
            'post_state_json' => $post ? json_encode($post) : null,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }
}
