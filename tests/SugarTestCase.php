<?php

namespace Inet\SugarCRM\Tests;

use Inet\SugarCRM\EntryPoint;
use Psr\Log\NullLogger;

class SugarTestCase extends \PHPUnit_Framework_TestCase
{
    public function getEntryPointInstance()
    {
        try {
            $logger = new NullLogger;
            EntryPoint::createInstance($logger, getenv('sugarDir'), getenv('sugarUserId'));
            $this->assertInstanceOf('Inet\SugarCRM\EntryPoint', EntryPoint::getInstance());
        } catch (\RuntimeException $e) {
        }
        return EntryPoint::getInstance();
    }
}
