<?php
namespace Inet\SugarCRM\Tests;

use Inet\SugarCRM\Application;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function testRightInstanciation()
    {
        $entryPointTest = new EntryPointTest;
        $entryPoint = $entryPointTest->rightInstanciation();

        $app = new Application($entryPoint);
        $this->assertInstanceOf('Inet\SugarCRM\Application', $app);
        $this->assertEquals(getenv('sugarDir'), $app->getSugarPath());
    }
}
