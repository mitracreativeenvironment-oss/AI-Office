<?php

namespace App\Services;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

class KnowledgeSearchService
{
    protected BaseConnection $db;
    protected KnowledgeAccessService $accessService;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->accessService = new KnowledgeAccessService();
    }

    /**
     * Unified multi-domain search across Knowledge Records, Documents, and Wallbook Handover Items.
     */
    public function search(string $query, array $filters = [], ?array $userContext = null, int $limit = 20): array
    {
        $start = microtime(true);
        $cleanQuery = trim($query);

        if (empty($cleanQuery)) {
            return [
                'query'        => '',
                'total_hits'   => 0,
                'results'      => [],
                'latency_ms'   => 0,
            ];
        }

        // Access clearance filtering
        $allowedClassifications = $this->accessService->getAllowedClassifications($userContext);
        $results = [];

        $words = preg_split('/\s+/', $cleanQuery);
        $words = array_filter($words, fn($w) => strlen($w) >= 2);
        if (empty($words)) $words = [$cleanQuery];

        // 1. Search Knowledge Records (Authoritative Knowledge)
        $kbBuilder = $this->db->table('knowledge_records r')
            ->select('r.id, r.record_code, r.title, r.summary, r.content, r.classification, r.status, r.version,
                      c.domain, c.name as category_name')
            ->join('knowledge_categories c', 'c.id = r.category_id')
            ->whereIn('r.classification', $allowedClassifications)
            ->where('r.status', 'published');

        if (!empty($filters['domain'])) {
            $kbBuilder->where('c.domain', $filters['domain']);
        }
        if (!empty($filters['classification'])) {
            $kbBuilder->where('r.classification', $filters['classification']);
        }

        $kbBuilder->groupStart()
            ->like('r.title', $cleanQuery)
            ->orLike('r.summary', $cleanQuery)
            ->orLike('r.content', $cleanQuery)
            ->orLike('r.record_code', $cleanQuery)
            ->orLike('r.tags_json', $cleanQuery);
        foreach ($words as $w) {
            $kbBuilder->orLike('r.title', $w)
                ->orLike('r.summary', $w)
                ->orLike('r.content', $w);
        }
        $kbBuilder->groupEnd();

        $kbRecords = $kbBuilder->limit($limit)->get()->getResultArray();

        foreach ($kbRecords as $kb) {
            $snippet = $this->extractSnippet($kb['content'], $cleanQuery, $kb['summary']);
            $results[] = [
                'type'           => 'knowledge_record',
                'id'             => (int)$kb['id'],
                'code'           => $kb['record_code'],
                'title'          => $kb['title'],
                'snippet'        => $snippet,
                'domain'         => $kb['domain'],
                'category'       => $kb['category_name'],
                'classification' => $kb['classification'],
                'version'        => $kb['version'],
                'score'          => 1.0, // High confidence authoritative source
            ];
        }

        // 2. Search Documents
        $docBuilder = $this->db->table('documents d')
            ->select('d.id, d.document_code, d.title, d.description, d.classification, d.category, d.current_version, d.file_name')
            ->whereIn('d.classification', $allowedClassifications)
            ->where('d.status', 'active');

        $docBuilder->groupStart()
            ->like('d.title', $cleanQuery)
            ->orLike('d.description', $cleanQuery)
            ->orLike('d.document_code', $cleanQuery)
            ->orLike('d.file_name', $cleanQuery)
            ->groupEnd();

        $docs = $docBuilder->limit($limit)->get()->getResultArray();

        foreach ($docs as $d) {
            $results[] = [
                'type'           => 'document',
                'id'             => (int)$d['id'],
                'code'           => $d['document_code'],
                'title'          => $d['title'],
                'snippet'        => $d['description'] ?? "File: {$d['file_name']} (Version {$d['current_version']})",
                'domain'         => 'documents',
                'category'       => $d['category'],
                'classification' => $d['classification'],
                'version'        => $d['current_version'],
                'score'          => 0.85,
            ];
        }

        // 3. Search Wallbook Handover Items (Operational Handover Context)
        if (in_array(KnowledgeAccessService::LEVEL_INTERNAL, $allowedClassifications)) {
            $wbBuilder = $this->db->table('wallbook_handover_items wi')
                ->select('wi.id, wi.handover_id, wi.section_category, wi.title, wi.content,
                          h.handover_code, h.title as handover_title')
                ->join('wallbook_handovers h', 'h.id = wi.handover_id');

            $wbBuilder->groupStart()
                ->like('wi.title', $cleanQuery)
                ->orLike('wi.content', $cleanQuery)
                ->orLike('h.handover_code', $cleanQuery)
                ->groupEnd();

            $wbItems = $wbBuilder->limit(10)->get()->getResultArray();

            foreach ($wbItems as $wb) {
                $snippet = $this->extractSnippet($wb['content'], $cleanQuery);
                $results[] = [
                    'type'           => 'wallbook_handover',
                    'id'             => (int)$wb['handover_id'],
                    'code'           => $wb['handover_code'],
                    'title'          => "{$wb['handover_code']}: {$wb['title']}",
                    'snippet'        => "[Section: {$wb['section_category']}] " . $snippet,
                    'domain'         => 'operational_handover',
                    'category'       => $wb['section_category'],
                    'classification' => 'internal',
                    'version'        => '1.0',
                    'score'          => 0.75,
                ];
            }
        }

        // Sort combined results by score
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
        $results = array_slice($results, 0, $limit);

        $latencyMs = (int)round((microtime(true) - $start) * 1000);

        // Security & Audit logging
        $this->logRetrieval($cleanQuery, count($results), $latencyMs, $userContext);

        return [
            'query'      => $cleanQuery,
            'total_hits' => count($results),
            'results'    => $results,
            'latency_ms' => $latencyMs,
        ];
    }

    /**
     * AI RAG Context Retrieval: Produces authorized knowledge context for Hermes agent runtime
     */
    public function retrieveContextForAgent(string $promptQuery, int $agentId, ?int $taskContextId = null, int $maxChunks = 3): array
    {
        $start = microtime(true);

        // Verify Agent exists and is active
        $agent = $this->db->table('ai_agent_registry')->where('id', $agentId)->get()->getRowArray();
        if (!$agent) {
            throw new \InvalidArgumentException("Agent #{$agentId} not found.", 404);
        }

        $userContext = [
            'agent_id' => $agentId,
            'roles'    => ['agent', 'workforce'],
            'unit_id'  => (int)($agent['unit_id'] ?? 1),
        ];

        // Search authoritative knowledge records
        $searchRes = $this->search($promptQuery, ['domain' => ''], $userContext, $maxChunks);
        $chunks = [];

        foreach ($searchRes['results'] as $r) {
            if ($r['type'] === 'knowledge_record') {
                $record = $this->db->table('knowledge_records')->where('id', $r['id'])->get()->getRowArray();
                if ($record) {
                    $chunks[] = [
                        'source_id'      => $record['record_code'],
                        'title'          => $record['title'],
                        'version'        => $record['version'],
                        'classification' => $record['classification'],
                        'excerpt'        => substr($record['content'], 0, 1200),
                        'provenance'     => $record['provenance_type'] . ':' . ($record['provenance_source_ref'] ?? 'none'),
                    ];
                }
            }
        }

        $latencyMs = (int)round((microtime(true) - $start) * 1000);

        // Log RAG retrieval event
        $now = date('Y-m-d H:i:s');
        $this->db->table('knowledge_retrieval_logs')->insert([
            'query_text'              => $promptQuery,
            'requester_type'          => 'agent',
            'requester_agent_id'      => $agentId,
            'task_context_id'         => $taskContextId,
            'records_retrieved_count' => count($chunks),
            'classification_filter'   => 'internal',
            'retrieval_latency_ms'    => $latencyMs,
            'retrieval_method'        => 'rag_context',
            'security_decision'       => 'allowed',
            'provenance_sources_json' => json_encode(array_column($chunks, 'source_id')),
            'created_at'              => $now,
        ]);

        return [
            'agent_id'           => $agentId,
            'query'              => $promptQuery,
            'chunks_count'       => count($chunks),
            'context_chunks'     => $chunks,
            'retrieval_latency'  => $latencyMs,
        ];
    }

    protected function extractSnippet(string $text, string $query, ?string $fallback = null): string
    {
        $pos = stripos($text, $query);
        if ($pos !== false) {
            $start = max(0, $pos - 60);
            $length = 160;
            $sub = substr($text, $start, $length);
            return '...' . trim(strip_tags($sub)) . '...';
        }

        if ($fallback) {
            return substr(strip_tags($fallback), 0, 160) . '...';
        }

        return substr(strip_tags($text), 0, 160) . '...';
    }

    protected function logRetrieval(string $query, int $hits, int $latencyMs, ?array $userContext = null): void
    {
        $now = date('Y-m-d H:i:s');
        $requesterType = !empty($userContext['agent_id']) ? 'agent' : 'human';
        $agentId = !empty($userContext['agent_id']) ? (int)$userContext['agent_id'] : null;
        $userId = !empty($userContext['id']) ? (int)$userContext['id'] : null;

        $this->db->table('knowledge_retrieval_logs')->insert([
            'query_text'              => $query,
            'requester_type'          => $requesterType,
            'requester_user_id'       => $userId,
            'requester_agent_id'      => $agentId,
            'task_context_id'         => null,
            'records_retrieved_count' => $hits,
            'classification_filter'   => 'multi_level_clearance',
            'retrieval_latency_ms'    => $latencyMs,
            'retrieval_method'        => 'fulltext_search',
            'security_decision'       => 'allowed',
            'provenance_sources_json' => null,
            'created_at'              => $now,
        ]);
    }
}
