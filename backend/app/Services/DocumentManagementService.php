<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class DocumentManagementService
{
    protected BaseConnection $db;
    protected KnowledgeAccessService $accessService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->accessService = new KnowledgeAccessService();
    }

    public function listDocuments(array $filters = [], int $limit = 20, int $offset = 0, ?array $userContext = null): array
    {
        $builder = $this->db->table('documents d')
            ->select('d.*, u.name as unit_name,
                      author_user.full_name as uploaded_by_user_name,
                      author_agent.name as uploaded_by_agent_name, author_agent.agent_code as uploaded_by_agent_code')
            ->join('organization_units u', 'u.id = d.owner_unit_id', 'left')
            ->join('users author_user', 'author_user.id = d.uploaded_by_user_id', 'left')
            ->join('ai_agent_registry author_agent', 'author_agent.id = d.uploaded_by_agent_id', 'left');

        // Access Control Filter
        $allowedClassifications = $this->accessService->getAllowedClassifications($userContext);
        $builder->whereIn('d.classification', $allowedClassifications);

        if (!empty($filters['category'])) {
            $builder->where('d.category', $filters['category']);
        }
        if (!empty($filters['classification'])) {
            $builder->where('d.classification', $filters['classification']);
        }
        if (!empty($filters['status'])) {
            $builder->where('d.status', $filters['status']);
        }
        if (!empty($filters['owner_unit_id'])) {
            $builder->where('d.owner_unit_id', (int)$filters['owner_unit_id']);
        }
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $builder->groupStart()
                ->like('d.title', $search)
                ->orLike('d.description', $search)
                ->orLike('d.document_code', $search)
                ->orLike('d.file_name', $search)
                ->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $builder->orderBy('d.id', 'DESC');
        $builder->limit($limit, $offset);
        $items = $builder->get()->getResultArray();

        foreach ($items as &$item) {
            $item['id'] = (int)$item['id'];
            $item['file_size_bytes'] = (int)$item['file_size_bytes'];
        }

        return ['items' => $items, 'total' => $total];
    }

    public function getDocument(int $id, ?array $userContext = null): ?array
    {
        $doc = $this->db->table('documents d')
            ->select('d.*, u.name as unit_name,
                      author_user.full_name as uploaded_by_user_name,
                      author_agent.name as uploaded_by_agent_name, author_agent.agent_code as uploaded_by_agent_code')
            ->join('organization_units u', 'u.id = d.owner_unit_id', 'left')
            ->join('users author_user', 'author_user.id = d.uploaded_by_user_id', 'left')
            ->join('ai_agent_registry author_agent', 'author_agent.id = d.uploaded_by_agent_id', 'left')
            ->where('d.id', $id)
            ->get()
            ->getRowArray();

        if (!$doc) return null;

        // Check clearance if user context is provided
        if ($userContext !== null && !$this->accessService->canAccessClassification($doc['classification'], $userContext, (int)($doc['owner_unit_id'] ?? 0))) {
            throw new \RuntimeException("Access denied: Insufficient clearance for document '{$doc['document_code']}'.", 403);
        }

        $doc['id'] = (int)$doc['id'];
        $doc['file_size_bytes'] = (int)$doc['file_size_bytes'];

        // Attach versions
        $doc['versions'] = $this->db->table('document_versions')
            ->where('document_id', $id)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        return $doc;
    }

    public function registerDocument(array $data, ?int $actorUserId = null, ?int $actorAgentId = null): array
    {
        $code = trim($data['document_code'] ?? 'DOC-' . date('Y') . '-' . rand(1000, 9999));
        $now = date('Y-m-d H:i:s');

        $fileName = trim($data['file_name'] ?? 'document.pdf');
        $filePath = trim($data['file_path'] ?? 'uploads/documents/' . $fileName);
        $fileSize = (int)($data['file_size_bytes'] ?? 1024);
        $mime = $data['mime_type'] ?? 'application/pdf';
        $checksum = $data['sha256_checksum'] ?? hash('sha256', $fileName . $now);

        $uploadedByType = $actorAgentId ? 'agent' : 'human';
        $version = $data['current_version'] ?? '1.0';

        $insert = [
            'document_code'        => $code,
            'title'                => trim($data['title'] ?? 'Untitled Document'),
            'description'          => $data['description'] ?? null,
            'classification'       => $data['classification'] ?? 'internal',
            'category'             => $data['category'] ?? 'standard_procedure',
            'owner_unit_id'        => !empty($data['owner_unit_id']) ? (int)$data['owner_unit_id'] : 1,
            'current_version'      => $version,
            'status'               => $data['status'] ?? 'active',
            'file_path'            => $filePath,
            'file_name'            => $fileName,
            'file_size_bytes'      => $fileSize,
            'mime_type'            => $mime,
            'sha256_checksum'      => $checksum,
            'uploaded_by_type'     => $uploadedByType,
            'uploaded_by_user_id'  => $actorUserId,
            'uploaded_by_agent_id' => $actorAgentId,
            'is_latest'            => 1,
            'created_at'           => $now,
            'updated_at'           => $now,
        ];

        $this->db->table('documents')->insert($insert);
        $insert['id'] = (int)$this->db->insertID();

        // Write initial version
        $this->db->table('document_versions')->insert([
            'document_id'        => $insert['id'],
            'version_number'     => $version,
            'file_path'          => $filePath,
            'file_size_bytes'    => $fileSize,
            'sha256_checksum'    => $checksum,
            'change_summary'     => 'Initial document registration.',
            'created_by_user_id' => $actorUserId,
            'created_by_agent_id'=> $actorAgentId,
            'created_at'         => $now,
        ]);

        return $this->getDocument($insert['id']);
    }

    public function addDocumentVersion(int $id, array $data, ?int $actorUserId = null, ?int $actorAgentId = null): array
    {
        $existing = $this->getDocument($id);
        if (!$existing) {
            throw new \InvalidArgumentException("Document #{$id} not found.", 404);
        }

        $now = date('Y-m-d H:i:s');
        $newVersion = trim($data['version_number'] ?? '1.1');
        $filePath = trim($data['file_path'] ?? $existing['file_path']);
        $fileSize = (int)($data['file_size_bytes'] ?? $existing['file_size_bytes']);
        $checksum = $data['sha256_checksum'] ?? hash('sha256', $filePath . $now);

        $this->db->table('document_versions')->insert([
            'document_id'        => $id,
            'version_number'     => $newVersion,
            'file_path'          => $filePath,
            'file_size_bytes'    => $fileSize,
            'sha256_checksum'    => $checksum,
            'change_summary'     => $data['change_summary'] ?? 'Updated document revision.',
            'created_by_user_id' => $actorUserId,
            'created_by_agent_id'=> $actorAgentId,
            'created_at'         => $now,
        ]);

        $this->db->table('documents')->where('id', $id)->update([
            'current_version' => $newVersion,
            'file_path'       => $filePath,
            'file_size_bytes' => $fileSize,
            'sha256_checksum' => $checksum,
            'updated_at'      => $now,
        ]);

        return $this->getDocument($id);
    }
}
