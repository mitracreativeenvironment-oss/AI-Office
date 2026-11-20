<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\VirtualHeadquartersService;
use App\Controllers\Api\VhqController;
use Config\Database;

/**
 * @internal
 */
final class VirtualHeadquartersTest extends CIUnitTestCase
{
    protected VirtualHeadquartersService $vhqService;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vhqService = new VirtualHeadquartersService();
        $this->db = Database::connect();
    }

    public function testServiceAndControllerInstantiate(): void
    {
        $this->assertInstanceOf(VirtualHeadquartersService::class, $this->vhqService);
        $this->assertInstanceOf(VhqController::class, new VhqController());
    }

    public function testCampusOverviewReturnsCorrectStructureAndBuildings(): void
    {
        $overview = $this->vhqService->getCampusOverview();

        $this->assertIsArray($overview);
        $this->assertArrayHasKey('campus_name', $overview);
        $this->assertArrayHasKey('summary', $overview);
        $this->assertArrayHasKey('buildings', $overview);

        $summary = $overview['summary'];
        $this->assertGreaterThanOrEqual(6, $summary['total_buildings']);
        $this->assertGreaterThanOrEqual(10, $summary['total_rooms']);
        $this->assertGreaterThanOrEqual(8, $summary['total_workstations']);
        $this->assertGreaterThanOrEqual(6, $summary['total_active_agents']);

        $buildingCodes = array_column($overview['buildings'], 'building_code');
        $this->assertContains('BLD-MAIN', $buildingCodes);
        $this->assertContains('BLD-DIV-TECH', $buildingCodes);
        $this->assertContains('BLD-DIV-SRV', $buildingCodes);
        $this->assertContains('BLD-DIV-ACAD', $buildingCodes);
        $this->assertContains('BLD-DIV-HW', $buildingCodes);
        $this->assertContains('BLD-WELFARE', $buildingCodes);
    }

    public function testAgentWelfareBuildingExistsWithWelfareRooms(): void
    {
        $welfare = $this->db->table('hq_buildings')
            ->where('building_code', 'BLD-WELFARE')
            ->get()
            ->getRowArray();

        $this->assertNotNull($welfare, 'Agent Welfare Building BLD-WELFARE must exist.');
        $this->assertEquals('welfare_center', $welfare['building_type']);

        $detail = $this->vhqService->getBuildingDetail((int)$welfare['id']);
        $this->assertNotNull($detail);
        $this->assertNotEmpty($detail['floors']);

        $welfareRooms = $this->vhqService->listRooms(['building_id' => $welfare['id']]);
        $this->assertCount(2, $welfareRooms);

        $categories = array_column($welfareRooms, 'category');
        $this->assertContains('welfare_rest', $categories);
        $this->assertContains('welfare_learning', $categories);
    }

    public function testBuildingDetailWithFloorsAndRooms(): void
    {
        $mainBuilding = $this->db->table('hq_buildings')
            ->where('building_code', 'BLD-MAIN')
            ->get()
            ->getRowArray();

        $this->assertNotNull($mainBuilding);
        $detail = $this->vhqService->getBuildingDetail((int)$mainBuilding['id']);

        $this->assertIsArray($detail);
        $this->assertEquals('BLD-MAIN', $detail['building_code']);
        $this->assertNotEmpty($detail['floors']);

        $firstFloor = $detail['floors'][0];
        $this->assertArrayHasKey('rooms', $firstFloor);
        $this->assertNotEmpty($firstFloor['rooms']);

        $firstRoom = $firstFloor['rooms'][0];
        $this->assertArrayHasKey('workstations', $firstRoom);
    }

    public function testListRoomsWithFilters(): void
    {
        $allRooms = $this->vhqService->listRooms();
        $this->assertGreaterThanOrEqual(10, count($allRooms));

        $executiveRooms = $this->vhqService->listRooms(['category' => 'executive']);
        $this->assertNotEmpty($executiveRooms);
        foreach ($executiveRooms as $rm) {
            $this->assertEquals('executive', $rm['category']);
        }
    }

    public function testListWorkstationsWithAgentMapping(): void
    {
        $workstations = $this->vhqService->listWorkstations();
        $this->assertGreaterThanOrEqual(8, count($workstations));

        $occupiedWorkstations = $this->vhqService->listWorkstations(['is_occupied' => true]);
        $this->assertGreaterThanOrEqual(6, count($occupiedWorkstations));

        foreach ($occupiedWorkstations as $ws) {
            $this->assertNotNull($ws['assigned_agent_id']);
            $this->assertNotEmpty($ws['agent_code']);
            $this->assertNotEmpty($ws['presence_status']);
        }
    }

    public function testAgentPresenceResolutionForActiveAgents(): void
    {
        $agent = $this->db->table('ai_agent_registry')
            ->where('agent_code', 'AGT-DIR-001')
            ->get()
            ->getRowArray();

        $this->assertNotNull($agent);

        $presence = $this->vhqService->getAgentPresence((int)$agent['id']);
        $this->assertIsArray($presence);
        $this->assertEquals('AGT-DIR-001', $presence['agent_code']);
        $this->assertNotEmpty($presence['presence_status']);
        $this->assertNotEmpty($presence['status_reason']);
        $this->assertTrue($presence['has_spatial_placement']);
        $this->assertNotNull($presence['placement']);
        $this->assertEquals('BLD-MAIN', $presence['placement']['building_code']);
    }

    public function testListAllAgentPresences(): void
    {
        $presences = $this->vhqService->listAllAgentPresences();
        $this->assertGreaterThanOrEqual(6, count($presences));

        $agentCodes = array_column($presences, 'agent_code');
        $this->assertContains('AGT-DIR-001', $agentCodes);
        $this->assertContains('AGT-TECH-001', $agentCodes);
        $this->assertContains('AGT-SRV-001', $agentCodes);
        $this->assertContains('AGT-ACD-001', $agentCodes);
        $this->assertContains('AGT-DEV-001', $agentCodes);
        $this->assertContains('AGT-HDW-001', $agentCodes);
    }

    public function testCameraBookmarks(): void
    {
        $bookmarks = $this->vhqService->getCameraBookmarks();
        $this->assertGreaterThanOrEqual(6, count($bookmarks));

        $codes = array_column($bookmarks, 'bookmark_code');
        $this->assertContains('BM-CAMPUS', $codes);
        $this->assertContains('BM-WELFARE', $codes);

        $overviewBm = null;
        foreach ($bookmarks as $bm) {
            if ($bm['bookmark_code'] === 'BM-CAMPUS') {
                $overviewBm = $bm;
                break;
            }
        }
        $this->assertNotNull($overviewBm);
        $this->assertEquals(1, $overviewBm['is_default']);
    }

    public function testWorkstationAssignmentAndVacate(): void
    {
        // Find an unassigned workstation or recovery pod
        $ws = $this->db->table('hq_workstations')
            ->where('workstation_code', 'WS-WLF-POD01')
            ->get()
            ->getRowArray();

        $this->assertNotNull($ws);
        $wsId = (int)$ws['id'];

        $agent = $this->db->table('ai_agent_registry')->where('agent_code', 'AGT-DEV-001')->get()->getRowArray();
        $this->assertNotNull($agent);
        $agentId = (int)$agent['id'];

        // Assign AGT-DEV-001 to WS-WLF-POD01
        $assigned = $this->vhqService->assignWorkstation($wsId, $agentId);
        $this->assertEquals($agentId, $assigned['assigned_agent_id']);

        // Vacate WS-WLF-POD01
        $vacated = $this->vhqService->assignWorkstation($wsId, null);
        $this->assertNull($vacated['assigned_agent_id']);

        // Restore AGT-DEV-001 to their original workstation WS-TECH-DEV01
        $originalWs = $this->db->table('hq_workstations')
            ->where('workstation_code', 'WS-TECH-DEV01')
            ->get()
            ->getRowArray();
        if ($originalWs) {
            $this->vhqService->assignWorkstation((int)$originalWs['id'], $agentId);
        }
    }
}
