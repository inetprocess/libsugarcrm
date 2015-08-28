<?php

namespace Inet\SugarCRM\Tests;

use Inet\SugarCRM\Application;
use Inet\SugarCRM\EntryPoint;
use Psr\Log\NullLogger;

class SugarTestCase extends \PHPUnit_Framework_TestCase
{
    public function getEntryPointInstance()
    {
        if (!EntryPoint::isCreated()) {
            $logger = new NullLogger;
            EntryPoint::createInstance($logger, new Application(getenv('sugarDir')), getenv('sugarUserId'));
            $this->assertInstanceOf('Inet\SugarCRM\EntryPoint', EntryPoint::getInstance());
        }
        return EntryPoint::getInstance();
    }

    public function tearDown()
    {
        // Make sure sugar is not running from local dir
        $this->assertFileNotExists(__DIR__ . '/../cache');
    }
}
