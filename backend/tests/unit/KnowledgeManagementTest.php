<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\KnowledgeBaseService;
use App\Services\KnowledgeAccessService;
use App\Services\KnowledgeContributionService;
use App\Services\DocumentManagementService;
use App\Services\AgentMemoryService;
use App\Services\WallbookKnowledgeBridgeService;
use App\Services\KnowledgeSearchService;
use App\Controllers\Api\KnowledgeController;
use Config\Database;

/**
 * @internal
 */
final class KnowledgeManagementTest extends CIUnitTestCase
{
    protected KnowledgeBaseService $kbService;
    protected KnowledgeAccessService $accessService;
    protected KnowledgeContributionService $contributionService;
    protected DocumentManagementService $docService;
    protected AgentMemoryService $memoryService;
    protected WallbookKnowledgeBridgeService $wbBridgeService;
    protected KnowledgeSearchService $searchService;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kbService = new KnowledgeBaseService();
        $this->accessService = new KnowledgeAccessService();
        $this->contributionService = new KnowledgeContributionService();
        $this->docService = new DocumentManagementService();
        $this->memoryService = new AgentMemoryService();
        $this->wbBridgeService = new WallbookKnowledgeBridgeService();
        $this->searchService = new KnowledgeSearchService();
        $this->db = Database::connect();
    }

    public function testServicesAndControllerInstantiate(): void
    {
        $this->assertInstanceOf(KnowledgeBaseService::class, $this->kbService);
        $this->assertInstanceOf(KnowledgeAccessService::class, $this->accessService);
        $this->assertInstanceOf(KnowledgeContributionService::class, $this->contributionService);
        $this->assertInstanceOf(DocumentManagementService::class, $this->docService);
        $this->assertInstanceOf(AgentMemoryService::class, $this->memoryService);
        $this->assertInstanceOf(WallbookKnowledgeBridgeService::class, $this->wbBridgeService);
        $this->assertInstanceOf(KnowledgeSearchService::class, $this->searchService);
        $this->assertInstanceOf(KnowledgeController::class, new KnowledgeController());
    }

    public function testOverviewMetrics(): void
    {
        $overview = $this->kbService->getOverview();

        $this->assertArrayHasKey('total_records', $overview);
        $this->assertArrayHasKey('published_records', $overview);
        $this->assertArrayHasKey('total_documents', $overview);
        $this->assertArrayHasKey('active_memories', $overview);
        $this->assertArrayHasKey('by_domain', $overview);

        $this->assertGreaterThan(0, $overview['total_records']);
        $this->assertGreaterThan(0, $overview['published_records']);
        $this->assertGreaterThan(0, $overview['total_documents']);
        $this->assertGreaterThan(0, $overview['active_memories']);
        $this->assertArrayHasKey('corporate', $overview['by_domain']);
        $this->assertArrayHasKey('operational', $overview['by_domain']);
    }

    public function testCategoriesListingAndCreation(): void
    {
        $categories = $this->kbService->listCategories();
        $this->assertNotEmpty($categories);

        $catCode = 'CAT-TEST-' . rand(1000, 9999);
        $created = $this->kbService->createCategory([
            'category_code' => $catCode,
            'domain'        => 'operational',
            'name'          => 'Unit Test Knowledge Category',
            'description'   => 'Category created during automated testing.',
            'default_classification' => 'internal',
        ]);

        $this->assertEquals($catCode, $created['category_code']);
        $this->assertGreaterThan(0, $created['id']);

        $fetched = $this->kbService->getCategory($created['id']);
        $this->assertNotNull($fetched);
        $this->assertEquals('Unit Test Knowledge Category', $fetched['name']);
    }

    public function testKnowledgeRecordLifecycleAndVersioning(): void
    {
        // 1. Create Draft
        $recCode = 'KB-TEST-' . rand(1000, 9999);
        $record = $this->kbService->createRecord([
            'record_code'    => $recCode,
            'category_id'    => 1,
            'title'          => 'Lifecycle Test Knowledge Article',
            'summary'        => 'Testing status transitions and version increments.',
            'content'        => 'Initial markdown content of article version 1.0.',
            'classification' => 'internal',
            'status'         => 'draft',
            'version'        => '1.0',
        ], 1, null);

        $this->assertEquals($recCode, $record['record_code']);
        $this->assertEquals('draft', $record['status']);
        $this->assertEquals('1.0', $record['version']);
        $this->assertCount(1, $record['versions']);

        // 2. Transition draft -> submitted
        $submitted = $this->kbService->transitionStatus($record['id'], 'submitted', 'Ready for review', 1);
        $this->assertEquals('submitted', $submitted['status']);

        // 3. Transition submitted -> under_review
        $underReview = $this->kbService->transitionStatus($record['id'], 'under_review', 'Reviewing article', 1);
        $this->assertEquals('under_review', $underReview['status']);

        // 4. Transition under_review -> approved
        $approved = $this->kbService->transitionStatus($record['id'], 'approved', 'Approved by lead', 1);
        $this->assertEquals('approved', $approved['status']);

        // 5. Transition approved -> published
        $published = $this->kbService->transitionStatus($record['id'], 'published', 'Published to organization', 1);
        $this->assertEquals('published', $published['status']);

        // 6. Update record with version bump
        $updated = $this->kbService->updateRecord($record['id'], [
            'content'        => 'Updated markdown content for version 1.1 with revisions.',
            'version'        => '1.1',
            'change_summary' => 'Added detailed section on operational heuristics.',
            'bump_version'   => true,
        ], 1, null);

        $this->assertEquals('1.1', $updated['version']);
        $this->assertCount(2, $updated['versions']);
    }

    public function testClassificationClearanceFiltering(): void
    {
        // Public is open
        $this->assertTrue($this->accessService->canAccessClassification('public', null));

        // Internal requires auth
        $this->assertFalse($this->accessService->canAccessClassification('internal', null));
        $this->assertTrue($this->accessService->canAccessClassification('internal', ['roles' => ['user']]));

        // Confidential requires admin or matching unit
        $this->assertFalse($this->accessService->canAccessClassification('confidential', ['roles' => ['user'], 'unit_id' => 3], 2));
        $this->assertTrue($this->accessService->canAccessClassification('confidential', ['roles' => ['user'], 'unit_id' => 2], 2));
        $this->assertTrue($this->accessService->canAccessClassification('confidential', ['roles' => ['admin'], 'unit_id' => 9], 2));

        // Restricted requires executive / board / director
        $this->assertFalse($this->accessService->canAccessClassification('restricted', ['roles' => ['user']]));
        $this->assertTrue($this->accessService->canAccessClassification('restricted', ['roles' => ['director_ai']]));
        $this->assertTrue($this->accessService->canAccessClassification('restricted', ['roles' => ['executive']]));

        // Highly restricted requires owner / superadmin
        $this->assertFalse($this->accessService->canAccessClassification('highly_restricted', ['roles' => ['admin']]));
        $this->assertTrue($this->accessService->canAccessClassification('highly_restricted', ['roles' => ['owner']]));
        $this->assertTrue($this->accessService->canAccessClassification('highly_restricted', ['roles' => ['superadmin']]));
    }

    public function testDocumentRegistrationAndVersioning(): void
    {
        $docCode = 'DOC-TEST-' . rand(1000, 9999);
        $doc = $this->docService->registerDocument([
            'document_code'   => $docCode,
            'title'           => 'Architecture Blueprints Test Document',
            'description'     => 'PDF document describing micro-service contracts.',
            'classification'  => 'internal',
            'category'        => 'architecture_spec',
            'file_name'       => 'blueprints.pdf',
            'file_path'       => 'uploads/docs/blueprints.pdf',
            'file_size_bytes' => 54200,
            'mime_type'       => 'application/pdf',
        ], 1, null);

        $this->assertEquals($docCode, $doc['document_code']);
        $this->assertEquals('1.0', $doc['current_version']);
        $this->assertCount(1, $doc['versions']);

        // Add version 1.1
        $updatedDoc = $this->docService->addDocumentVersion($doc['id'], [
            'version_number'  => '1.1',
            'file_path'       => 'uploads/docs/blueprints_v1.1.pdf',
            'file_size_bytes' => 61000,
            'change_summary'  => 'Incorporated gateway routing revisions.',
        ], 1, null);

        $this->assertEquals('1.1', $updatedDoc['current_version']);
        $this->assertCount(2, $updatedDoc['versions']);
    }

    public function testAgentMemoriesLifecycleAndPrivacy(): void
    {
        // 1. Create Working Memory for Agent 2 (Tech Lead)
        $mem = $this->memoryService->createMemory(2, [
            'memory_type'      => 'working_memory',
            'title'            => 'Active Task Execution Scratchpad',
            'content'          => 'Temporary intermediate context for 9Router cache test.',
            'retention_policy' => 'session_ephemeral',
            'importance_score' => 0.65,
            'tags'             => ['scratchpad', 'cache'],
        ]);

        $this->assertEquals(2, $mem['agent_id']);
        $this->assertEquals('working_memory', $mem['memory_type']);
        $this->assertEquals('active', $mem['status']);

        // 2. Test Privacy: Agent 2 can access its own memory
        $accessed = $this->memoryService->getMemory($mem['id'], ['agent_id' => 2]);
        $this->assertNotNull($accessed);

        // 3. Test Privacy: Agent 3 is denied from inspecting Agent 2's private working memory
        $this->expectException(\RuntimeException::class);
        $this->memoryService->getMemory($mem['id'], ['agent_id' => 3, 'roles' => ['agent']]);
    }

    public function testZeroSelfReviewInContributions(): void
    {
        // Agent 1 submits contribution
        $contrib = $this->contributionService->submitContribution([
            'title'                => 'Proposed Tool Heuristic',
            'content'              => 'Heuristic details...',
            'proposed_category_id' => 7,
        ], 1, null);

        $this->assertEquals('submitted', $contrib['status']);
        $this->assertEquals(1, $contrib['contributor_agent_id']);

        // Agent 1 attempts to review/approve its own contribution -> MUST FAIL (Zero-Self-Review)
        $this->expectException(\RuntimeException::class);
        $this->contributionService->reviewContribution($contrib['id'], 'approved', 'Looks good to me', null, 1);
    }

    public function testApprovedContributionAwardsPerformanceHistory(): void
    {
        // Agent 2 submits contribution
        $contrib = $this->contributionService->submitContribution([
            'title'                => 'Multi-Agent Retry Backoff Best Practice',
            'content'              => 'Detailed procedural recommendations for handling socket timeouts...',
            'proposed_category_id' => 7,
            'proposed_classification' => 'internal',
        ], 2, null);

        // Independent reviewer (Human User 1 or Agent 1) approves
        $reviewed = $this->contributionService->reviewContribution(
            $contrib['id'],
            'approved',
            'Verified and accepted into agent heuristics catalogue.',
            1, // Reviewer User #1 (independent)
            null
        );

        $this->assertEquals('approved', $reviewed['status']);
        $this->assertNotNull($reviewed['published_record_id']);
        $this->assertNotNull($reviewed['performance_history_id']);

        // Check ai_agent_performance_history
        $perf = $this->db->table('ai_agent_performance_history')->where('id', $reviewed['performance_history_id'])->get()->getRowArray();
        $this->assertNotNull($perf);
        $this->assertEquals(2, (int)$perf['agent_id']);
        $this->assertEquals('recognition', $perf['category']);
    }

    public function testWallbookBridgeChecklistAndPromotion(): void
    {
        // List checklists for Handover #1
        $checklists = $this->wbBridgeService->listHandoverChecklists(1);
        $this->assertNotEmpty($checklists);

        $firstChk = $checklists[0];
        $updated = $this->wbBridgeService->updateChecklistItem($firstChk['id'], true, 'Checklist verified in unit test');
        $this->assertEquals(1, (int)$updated['is_satisfied']);

        // Promote Wallbook Item #1 to Knowledge Contribution
        $wbItem = $this->db->table('wallbook_handover_items')->where('handover_id', 1)->get()->getRowArray();
        if ($wbItem) {
            $contrib = $this->wbBridgeService->promoteHandoverItemToKnowledge(
                1,
                (int)$wbItem['id'],
                ['title' => 'Promoted Wallbook Handover Operational Note'],
                1,
                null
            );
            $this->assertNotNull($contrib);
            $this->assertEquals('submitted', $contrib['status']);
            $this->assertStringContainsString('wallbook', $contrib['evidence_references']);
        }
    }

    public function testUnifiedSearchAndQueryLogging(): void
    {
        $searchRes = $this->searchService->search('Framework', [], ['roles' => ['admin']]);

        $this->assertArrayHasKey('total_hits', $searchRes);
        $this->assertArrayHasKey('results', $searchRes);
        $this->assertGreaterThan(0, $searchRes['total_hits']);

        $first = $searchRes['results'][0];
        $this->assertArrayHasKey('code', $first);
        $this->assertArrayHasKey('snippet', $first);

        // Verify retrieval log was created
        $log = $this->db->table('knowledge_retrieval_logs')->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertNotNull($log);
        $this->assertEquals('Framework', $log['query_text']);
    }

    public function testRagContextRetrievalWithProvenance(): void
    {
        $rag = $this->searchService->retrieveContextForAgent('DoA Framework', 1);

        $this->assertEquals(1, $rag['agent_id']);
        $this->assertArrayHasKey('context_chunks', $rag);
        $this->assertGreaterThan(0, $rag['chunks_count']);

        $chunk = $rag['context_chunks'][0];
        $this->assertArrayHasKey('source_id', $chunk);
        $this->assertArrayHasKey('version', $chunk);
        $this->assertArrayHasKey('provenance', $chunk);
    }
}
