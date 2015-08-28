<?php

namespace Inet\SugarCRM\Tests\Sugar;

use Psr\Log\NullLogger;

use Inet\SugarCRM\Application;
use Inet\SugarCRM\LangFileCleaner;
use Inet\SugarCRM\Tests\TestsUtil\TestLogger;

class LangFileCleanerTest extends \PHPUnit_Framework_TestCase
{
    public function testCleanEmpty()
    {
        $logger = new TestLogger();
        $cleaner = new LangFileCleaner(new Application($logger, __DIR__));
        $this->assertFalse($cleaner->clean());
        $this->assertEquals('[notice] No lang files found to process.' . PHP_EOL, $logger->getLines('notice'));
    }

    public function testClean()
    {
        $fake_sugar = __DIR__ . '/fake_sugar';
        $logger = new TestLogger();
        $cleaner = new LangFileCleaner(new Application($logger, $fake_sugar));
        $this->assertTrue($cleaner->clean());
    }
}
