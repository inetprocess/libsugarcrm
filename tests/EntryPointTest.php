<?php
namespace Inet\SugarCRM\Tests;

use Inet\SugarCRM\EntryPoint;
use PSR\Log\Logger;
use Psr\Log\NullLogger;

class EntryPointTest extends \PHPUnit_Framework_TestCase
{
    /** Define a wrong folder: exception thrown
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessageRegExp #Wrong SugarCRM folder: /foo#
     * @runInSeparateProcess
     */
    public function testWrongInstanciationBadFolder()
    {
        $logger = new NullLogger;
        EntryPoint::createInstance($logger, '/foo', getenv('sugarUserId'));
    }

    public function rightInstanciation()
    {
        try {
            $logger = new NullLogger;
            EntryPoint::createInstance($logger, getenv('sugarDir'), getenv('sugarUserId'));
            $this->assertInstanceOf('Inet\SugarCRM\EntryPoint', EntryPoint::getInstance());
        } catch (\RuntimeException $e) {
        }
        return EntryPoint::getInstance();
    }

    public function testGettersSetters()
    {
        $this->rightInstanciation();
        $entryPoint = EntryPoint::getInstance();
        $logger = $entryPoint->getLogger();
        $this->assertInstanceOf('PSR\Log\LoggerInterface', $logger);

        $sugarDir = $entryPoint->getSugarDir();
        $this->assertEquals($sugarDir, getenv('sugarDir'));

        $sugarDB = $entryPoint->getSugarDb();
        $this->assertInstanceOf('\MysqliManager', $sugarDB);

        $currentUser = $entryPoint->getCurrentUser();
        $this->assertInstanceOf('\User', $currentUser);

        $beansList = $entryPoint->getBeansList();
        $this->assertInternalType('array', $beansList);
        $this->assertArrayHasKey('Users', $beansList);
    }

    /** Define a wrong user: exception thrown
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessageRegExp /Wrong User ID: foo/
     */
    public function testWrongInstanciationBadUser()
    {
        $entryPoint = EntryPoint::getInstance();
        $entryPoint->setSugarUser('foo');
    }

    public function testGetInstance()
    {
        chdir(__DIR__);
        $this->rightInstanciation();
        $this->assertEquals(getenv('sugarDir'), getcwd());
    }

    public function tearDown()
    {
        // Make sure sugar is not running from local dir
        $this->assertFileNotExists(__DIR__ . '/../cache');
    }
}
