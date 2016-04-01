<?php

namespace Inet\SugarCRM\Tests\Sugar;

use Psr\Log\NullLogger;

use Inet\SugarCRM\Application;
use Inet\SugarCRM\LangFileCleaner;
use Inet\SugarCRM\Tests\TestsUtil\TestLogger;

class LangFileCleanerTest extends \PHPUnit_Framework_TestCase
{
    public static $invalid_file = '';

    public static function setUpBeforeClass()
    {
        self::$invalid_file = __DIR__ . '/fake_sugar/custom/include/language/invalid.lang.php';
    }

    public static function tearDownAfterClass()
    {
        if (file_exists(self::$invalid_file)) {
            unlink(self::$invalid_file);
        }
    }

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

    /**
     * @expectedException \Exception
     * @expectedExceptionMessageRegexp /Unable to load the file contents of/
     */
    public function testCleanFailure()
    {
        \PHPUnit_Framework_Error_Warning::$enabled = false;
        $err_level = error_reporting();
        error_reporting($err_level &~ E_WARNING);
        $fake_sugar = __DIR__ . '/fake_sugar';
        touch(self::$invalid_file);
        chmod(self::$invalid_file, 0000);
        $logger = new TestLogger();
        $cleaner = new LangFileCleaner(new Application($logger, $fake_sugar));
        $this->assertTrue($cleaner->clean());
        error_reporting($err_level);
    }
}
