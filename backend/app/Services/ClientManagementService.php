<?php

namespace App\Services;

use Config\Database;
use InvalidArgumentException;
use RuntimeException;

class ClientManagementService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Retrieve all clients with filtering.
     */
    public function getClients(array $filters = []): array
    {
        $builder = $this->db->table('project_clients c')
            ->select('c.*, 
                      COUNT(DISTINCT ct.id) as contact_count,
                      COUNT(DISTINCT p.id) as project_count,
                      SUM(CASE WHEN p.status = "active" THEN 1 ELSE 0 END) as active_project_count')
            ->join('project_client_contacts ct', 'ct.client_id = c.id', 'left')
            ->join('projects p', 'p.client_id = c.id', 'left')
            ->groupBy('c.id');

        if (!empty($filters['status'])) {
            $builder->where('c.status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $kw = $filters['search'];
            $builder->groupStart()
                ->like('c.company_name', $kw)
                ->orLike('c.client_code', $kw)
                ->orLike('c.industry', $kw)
                ->groupEnd();
        }

        $builder->orderBy('c.company_name', 'ASC');
        $rows = $builder->get()->getResultArray();

        $result = [];
        foreach ($rows as $r) {
            $result[] = [
                'id'                   => (int)$r['id'],
                'client_code'          => $r['client_code'],
                'company_name'         => $r['company_name'],
                'industry'             => $r['industry'],
                'status'               => $r['status'],
                'address'              => $r['address'],
                'website'              => $r['website'],
                'notes'                => $r['notes'],
                'contact_count'        => (int)$r['contact_count'],
                'project_count'        => (int)$r['project_count'],
                'active_project_count' => (int)$r['active_project_count'],
                'created_at'           => $r['created_at'],
                'updated_at'           => $r['updated_at'],
            ];
        }

        return $result;
    }

    /**
     * Get single client by ID.
     */
    public function getClientById(int $id): ?array
    {
        $client = $this->db->table('project_clients')->where('id', $id)->get()->getRowArray();
        if (!$client) {
            return null;
        }

        $contacts = $this->db->table('project_client_contacts')
            ->where('client_id', $id)
            ->orderBy('is_primary', 'DESC')
            ->orderBy('full_name', 'ASC')
            ->get()->getResultArray();

        $projects = $this->db->table('projects')
            ->where('client_id', $id)
            ->orderBy('id', 'DESC')
            ->get()->getResultArray();

        return [
            'id'           => (int)$client['id'],
            'client_code'  => $client['client_code'],
            'company_name' => $client['company_name'],
            'industry'     => $client['industry'],
            'status'       => $client['status'],
            'address'      => $client['address'],
            'website'      => $client['website'],
            'notes'        => $client['notes'],
            'contacts'     => $contacts,
            'projects'     => $projects,
            'created_at'   => $client['created_at'],
            'updated_at'   => $client['updated_at'],
        ];
    }

    /**
     * Create client.
     */
    public function createClient(array $data, array $actor): array
    {
        $companyName = trim($data['company_name'] ?? '');
        if (empty($companyName)) {
            throw new InvalidArgumentException('Company name is required.');
        }

        $code = trim($data['client_code'] ?? '');
        if (empty($code)) {
            $count = $this->db->table('project_clients')->countAllResults();
            $code = sprintf("CLI-%03d", $count + 1);
        }

        $now = date('Y-m-d H:i:s');
        $insertData = [
            'client_code'  => $code,
            'company_name' => $companyName,
            'industry'     => $data['industry'] ?? null,
            'status'       => $data['status'] ?? 'active',
            'address'      => $data['address'] ?? null,
            'website'      => $data['website'] ?? null,
            'notes'        => $data['notes'] ?? null,
            'created_at'   => $now,
            'updated_at'   => $now,
        ];

        $this->db->table('project_clients')->insert($insertData);
        $clientId = (int)$this->db->insertID();

        return $this->getClientById($clientId);
    }

    /**
     * Update client.
     */
    public function updateClient(int $id, array $data, array $actor): ?array
    {
        $existing = $this->db->table('project_clients')->where('id', $id)->get()->getRowArray();
        if (!$existing) {
            throw new RuntimeException("Client #{$id} not found.");
        }

        $fields = ['company_name', 'industry', 'status', 'address', 'website', 'notes'];
        $updateData = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $updateData[$f] = $data[$f];
            }
        }
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        $this->db->table('project_clients')->where('id', $id)->update($updateData);

        return $this->getClientById($id);
    }

    /**
     * Client Contacts CRUD.
     */
    public function getContacts(int $clientId): array
    {
        return $this->db->table('project_client_contacts')
            ->where('client_id', $clientId)
            ->orderBy('is_primary', 'DESC')
            ->orderBy('full_name', 'ASC')
            ->get()->getResultArray();
    }

    public function createContact(int $clientId, array $data, array $actor): array
    {
        $fullName = trim($data['full_name'] ?? '');
        if (empty($fullName)) {
            throw new InvalidArgumentException('Full name is required.');
        }

        $now = date('Y-m-d H:i:s');
        $insertData = [
            'client_id'  => $clientId,
            'full_name'  => $fullName,
            'job_title'  => $data['job_title'] ?? null,
            'email'      => $data['email'] ?? null,
            'phone'      => $data['phone'] ?? null,
            'is_primary' => !empty($data['is_primary']) ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->db->table('project_client_contacts')->insert($insertData);
        $id = (int)$this->db->insertID();

        return $this->db->table('project_client_contacts')->where('id', $id)->get()->getRowArray();
    }

    public function updateContact(int $contactId, array $data, array $actor): array
    {
        $fields = ['full_name', 'job_title', 'email', 'phone', 'is_primary'];
        $updateData = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $updateData[$f] = $data[$f];
            }
        }
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        $this->db->table('project_client_contacts')->where('id', $contactId)->update($updateData);

        return $this->db->table('project_client_contacts')->where('id', $contactId)->get()->getRowArray();
    }

    public function deleteContact(int $contactId, array $actor): bool
    {
        return $this->db->table('project_client_contacts')->where('id', $contactId)->delete();
    }
}
