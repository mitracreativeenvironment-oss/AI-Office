<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\OrganizationService;
use App\Controllers\Api\OrganizationController;

/**
 * @internal
 */
final class OrganizationTest extends CIUnitTestCase
{
    public function testOrganizationServiceInstantiates(): void
    {
        $service = new OrganizationService();
        $this->assertInstanceOf(OrganizationService::class, $service);
    }

    public function testOrganizationControllerInstantiates(): void
    {
        $controller = new OrganizationController();
        $this->assertInstanceOf(OrganizationController::class, $controller);
    }

    public function testCircularHierarchyDetection(): void
    {
        $service = new OrganizationService();

        // 1. A unit cannot be its own parent (self-cycle)
        $this->assertTrue($service->isCircularParent(5, 5));

        // 2. Test tree loop: if unit 2 is parent of unit 3, setting unit 3 as parent of unit 2 must fail
        // Using seeded data: ORG-MCE (id 1) is root. DIV-TECH (id 2) has parent 1.
        // Trying to make ORG-MCE child of DIV-TECH must be detected as circular:
        $this->assertTrue($service->isCircularParent(1, 2));

        // 3. Setting a valid parent (e.g. root unit 1 as parent of another node) is not circular
        $this->assertFalse($service->isCircularParent(6, 1));
    }

    public function testTreeAssemblyStructure(): void
    {
        $service = new OrganizationService();
        $tree = $service->getTree();

        $this->assertIsArray($tree);
        $this->assertNotEmpty($tree);

        // Seeded root unit is ORG-MCE
        $rootNode = $tree[0];
        $this->assertEquals('ORG-MCE', $rootNode['code']);
        $this->assertArrayHasKey('children', $rootNode);
        $this->assertNotEmpty($rootNode['children']);
    }

    public function testPositionsAndAppointmentsRetrieval(): void
    {
        $service = new OrganizationService();
        $positions = $service->getPositions();

        $this->assertIsArray($positions);
        $this->assertNotEmpty($positions);

        $appointments = $service->getAppointments();
        $this->assertIsArray($appointments);
    }
}
