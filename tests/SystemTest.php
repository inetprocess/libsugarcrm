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

    /**
     * @group sugarcrm-slow
     */
    public function testRepair()
    {
        $checkFile = realpath(getenv('SUGARCRM_PATH') . '/cache/class_map.php');
        $this->assertFileExists($checkFile, 'That file is used to test my repair');
        unlink($checkFile);
        $this->assertFileNotExists($checkFile, 'That file is used to test my repair');
        $system = new System($this->getEntryPointInstance());
        $system->repair();
        $this->assertFileExists($checkFile, 'That file is used to test my repair');
    }
}
