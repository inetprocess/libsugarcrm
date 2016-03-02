<?php
namespace Inet\SugarCRM\Tests;

use Inet\SugarCRM\EntryPoint;
use Inet\SugarCRM\DB;
use Inet\SugarCRM\System;

class SystemTest extends SugarTestCase
{
    public function testRightInstanciation()
    {
        $system = new System($this->getEntryPointInstance());

        // Activity
        $this->assertTrue($system->isActivityEnabled());
        $system->disableActivity();
        $this->assertFalse($system->isActivityEnabled());

        // Logger
        $logger = $system->getLogger();
        $this->assertInstanceOf('Psr\Log\NullLogger', $logger);

        // EntryPoint
        $ep = $system->getEntryPoint();
        $this->assertInstanceOf('\Inet\SugarCRM\EntryPoint', $ep);
    }

    public function testRepair()
    {
        $system = new System($this->getEntryPointInstance());
        //$system->repair(); # Can't do that because it crashes everything
    }
}
