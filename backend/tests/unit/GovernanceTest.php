<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\GovernanceService;
use App\Controllers\Api\GovernanceController;

/**
 * @internal
 */
final class GovernanceTest extends CIUnitTestCase
{
    public function testGovernanceServiceInstantiates(): void
    {
        $service = new GovernanceService();
        $this->assertInstanceOf(GovernanceService::class, $service);
    }

    public function testGovernanceControllerInstantiates(): void
    {
        $controller = new GovernanceController();
        $this->assertInstanceOf(GovernanceController::class, $controller);
    }

    public function testDoAAuthorityLimitsLogic(): void
    {
        $service = new GovernanceService();

        // Level A: Owner has unlimited strategic and capital authority
        $resA = $service->checkAuthority('A', 50000000.00, 'capital_investment');
        $this->assertTrue($resA['allowed']);
        $this->assertFalse($resA['requires_owner']);

        // Level B: Board autonomous ops (limit IDR 5.000.000)
        // Under threshold: allowed
        $resB1 = $service->checkAuthority('B', 4500000.00, 'marketing_campaign');
        $this->assertTrue($resB1['allowed']);
        $this->assertFalse($resB1['requires_owner']);

        // Over threshold (> 5.000.000): disallowed without Owner Level A gate
        $resB2 = $service->checkAuthority('B', 7500000.00, 'server_procurement');
        $this->assertFalse($resB2['allowed']);
        $this->assertTrue($resB2['requires_owner']);

        // Level C: Operational dispatch (limit IDR 1.000.000)
        $resC1 = $service->checkAuthority('C', 750000.00, 'routine_tool_subscription');
        $this->assertTrue($resC1['allowed']);

        $resC2 = $service->checkAuthority('C', 1500000.00, 'advanced_agent_burst');
        $this->assertFalse($resC2['allowed']);

        // Routine: Non-financial only
        $resR1 = $service->checkAuthority('OPERATIONAL', 0.00, 'log_rotation');
        $this->assertTrue($resR1['allowed']);

        $resR2 = $service->checkAuthority('OPERATIONAL', 50000.00, 'micro_expense');
        $this->assertFalse($resR2['allowed']);
    }

    public function testCoreValuesCountAndStructure(): void
    {
        $service = new GovernanceService();
        $values = $service->getCoreValues();

        $this->assertIsArray($values);
        $this->assertCount(7, $values);

        $codes = array_column($values, 'code');
        $this->assertContains('integrity', $codes);
        $this->assertContains('innovation', $codes);
        $this->assertContains('collaboration', $codes);
        $this->assertContains('excellence', $codes);
        $this->assertContains('adaptability', $codes);
        $this->assertContains('customer_centric', $codes);
        $this->assertContains('sustainability', $codes);
    }

    public function testGovernanceBodiesAndProfilesRetrieval(): void
    {
        $service = new GovernanceService();

        $bodies = $service->getBodies();
        $this->assertIsArray($bodies);
        $this->assertNotEmpty($bodies);

        $profiles = $service->getAuthorityProfiles();
        $this->assertIsArray($profiles);
        $this->assertCount(4, $profiles);
    }
}
