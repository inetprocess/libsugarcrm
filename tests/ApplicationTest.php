<?php
namespace Inet\SugarCRM\Tests;

use Inet\SugarCRM\Application;

class ApplicationTest extends SugarTestCase
{
    public function testRightInstanciation()
    {
        $entryPoint = $this->getEntryPointInstance();

        $app = new Application($entryPoint);
        $this->assertInstanceOf('Inet\SugarCRM\Application', $app);
        $this->assertEquals(getenv('sugarDir'), $app->getSugarPath());
    }
}
