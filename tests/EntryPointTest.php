<?php
namespace Inet\SugarCRM\Tests;

use Inet\SugarCRM\EntryPoint;
use Psr\Log\NullLogger;

class EntryPointTest extends SugarTestCase
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

    public function testGettersSetters()
    {
        $entryPoint = $this->getEntryPointInstance();
        $logger = $entryPoint->getLogger();
        $this->assertInstanceOf('PSR\Log\LoggerInterface', $logger);


        $expectedSugarDir = getenv('sugarDir');
        if ($expectedSugarDir[0] != '/') {
            $lastCwd = $entryPoint->getLastCwd();
            $expectedSugarDir = realpath($lastCwd . '/' . $expectedSugarDir);
        }
        $sugarDir = $entryPoint->getSugarDir();
        $this->assertEquals($expectedSugarDir, $sugarDir);

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
    public function testSetBadUser()
    {
        $entryPoint = $this->getEntryPointInstance();
        $entryPoint->setSugarUser('foo');
    }

    public function testGetInstance()
    {
        chdir(__DIR__);
        $entryPoint = $this->getEntryPointInstance();
        $this->assertEquals($entryPoint->getSugarDir(), getcwd());
        $this->assertEquals(__DIR__, $entryPoint->getLastCwd());
    }

    public function tearDown()
    {
        // Make sure sugar is not running from local dir
        $this->assertFileNotExists(__DIR__ . '/../cache');
    }
}