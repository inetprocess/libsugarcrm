<?php
namespace Inet\Tests;

use Inet\SugarCRM\EntryPoint;
use PSR\Log\Logger;
use Psr\Log\NullLogger;

class EntryPointTest extends \PHPUnit_Framework_TestCase
{
    private $entryPoint = null;

    public function rightInstanciation()
    {
        if (is_null($this->entryPoint)) {
            $logger = new NullLogger;
            $this->entryPoint = new EntryPoint($logger, getenv('sugarDir'), getenv('sugarUserId'));
            $this->assertInstanceOf('Inet\SugarCRM\EntryPoint', $this->entryPoint);
        }

        return $this->entryPoint;
    }

    public function testGettersSetters()
    {
        $entryPoint = $this->rightInstanciation();
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

    /** Define a wrong folder: exception thrown
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessageRegExp #Wrong SugarCRM folder: /foo#
     */
    public function testWrongInstanciationBadFolder()
    {
        $logger = new NullLogger;
        new EntryPoint($logger, '/foo', getenv('sugarUserId'));
    }

    /** Define a wrong user: exception thrown
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessageRegExp /Wrong User ID: foo/
     */
    public function testWrongInstanciationBadUser()
    {
        $logger = new NullLogger;
        new EntryPoint($logger, getenv('sugarDir'), 'foo');
    }
}
