<?php
namespace Inet\SugarCRM\Tests;

use Inet\SugarCRM\EntryPoint;
use Inet\SugarCRM\DB;
use Inet\SugarCRM\System;

class SystemTest extends SugarTestCase
{
    public function testRightInstanciation()
    {
        // first load a bean
        $entryPoint = $this->getEntryPointInstance();

        $system = new System($entryPoint);
        $this->assertTrue($system->isActivityEnabled());
        $system->disableActivity();
        $this->assertFalse($system->isActivityEnabled());
    }
}
