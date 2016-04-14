<?php

namespace Inet\SugarCRM\Tests\Sugar;

// TODO: Test for callUrl

use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

use Inet\SugarCRM\Application;
use Inet\SugarCRM\Installer;

class InstallerTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__ . '/install_sugar');
    }
    public function newApp($path)
    {
        return new Application(new NullLogger(), $path);
    }

    public function testConfigSiCopy()
    {
        $fake_sugar = __DIR__ . '/fake_sugar';
        $config_path = __DIR__ . '/installer/config_si.php';
        $installer = new Installer(new Application(new NullLogger(), $fake_sugar), '', $config_path);
        $installer->copyConfigSi();
        $this->assertFileEquals($config_path, $fake_sugar . '/config_si.php');
        $installer->deleteConfigSi();
        $this->assertFileNotExists($fake_sugar . '/config_si.php');
    }

    public function testPathManipulation()
    {
        $new_path = __DIR__ . '/new_sugar';
        $installer = new Installer(new Application(new NullLogger(), $new_path), '', '');
        $installer->createPath();
        $this->assertFileExists($new_path);
        $installer->deletePath();
        $this->assertFileNotExists($new_path);
    }

    /**
     * @dataProvider junkParentProvider
     */
    public function testJunkParent($expected, $path)
    {
        $this->assertEquals($expected, Installer::junkParent($path));
    }

    public function junkParentProvider()
    {
        return array(
            array('soap.php', 'SugarPro-Full-7.2.1/soap.php'),
            array('foo/bar/soap.php', 'SugarPro-Full-7.2.1/foo/bar/soap.php'),
            array('foo/bar/soap.php', '/SugarPro-Full-7.2.1/foo/bar/soap.php'),
            array('', '/SugarPro-Full-7.2.1/'),
        );
    }

    /**
     * @expectedException Inet\SugarCRM\Exception\InstallerException
     * @expectedExceptionMessageRegExp /is not a directory/
     */
    public function testExtractNotEmpty()
    {
        $installer = new Installer($this->newApp(__DIR__));
        $installer->extract();
    }

    /**
     * @expectedException Inet\SugarCRM\Exception\InstallerException
     * @expectedExceptionMessageRegExp /is not a directory/
     */
    public function testExtractNotDir()
    {
        $installer = new Installer($this->newApp(__FILE__));
        $installer->extract();
    }

    /**
     * @expectedException Inet\SugarCRM\Exception\InstallerException
     * @expectedExceptionMessageRegExp /doesn't exists/
     */
    public function testExtractInvalidSource()
    {
        $installer = new Installer($this->newApp(__DIR__ . '/empty'));
        $installer->createPath();
        $installer->extract();
    }

    /**
     * @expectedException Inet\SugarCRM\Exception\InstallerException
     * @expectedExceptionMessageRegExp /Unable to open zip/
     */
    public function testExtractInvalidZip()
    {
        $installer = new Installer($this->newApp(__DIR__ . '/empty'), __FILE__, '');
        $installer->createPath();
        $installer->extract();
    }

    public function testExtract()
    {
        $installer = new Installer($this->newApp(__DIR__ . '/empty'), __DIR__ . '/installer/Fake_Sugar.zip', '');
        $installer->createPath();
        $installer->extract();
        $this->assertFileExists(__DIR__ . '/empty/sugar_version.php');
        $installer->deletePath();
    }

    /**
     * Stub the installer
     */
    public function testRun()
    {
        $new_path = __DIR__ . '/install_sugar';
        $installer_dir = __DIR__ . '/installer';
        $stub = $this->getMock(
            'Inet\SugarCRM\Installer',
            array('runSugarInstaller'),
            array(
                $this->newApp($new_path),
                $installer_dir . '/Fake_Sugar.zip',
                $installer_dir . '/config_si.php'
            )
        );
        $stub->deletePath();
        $stub->run();
        $this->assertFileExists($new_path . '/sugar_version.php');
        unlink($new_path . '/sugar_version.php');
        $this->assertFileNotExists($new_path . '/sugar_version.php');
        $stub->run(true);
        $this->assertFileExists($new_path . '/sugar_version.php');
        // Last run will fail with the exception
        $this->setExpectedExceptionRegExp('Inet\SugarCRM\Exception\InstallerException', '/Use --force/');
        $stub->run();
    }

    /**
     * @expectedException Inet\SugarCRM\Exception\InstallerException
     * @expectedExceptionMessage The web installer failed. Check your config_si.php file.
     */
    public function testSugarInstallerExit()
    {
        $stub = $this->getMock(
            'Inet\SugarCRM\Installer',
            array('getInstallScriptPath'),
            array(
                $this->newApp('')
            )
        );
        $stub->method('getInstallScriptPath')
            ->willReturn(__DIR__ . '/installer/install_exit.php');
        $stub->runSugarInstaller();
    }

    public function testSugarInstallerUnknownError()
    {
        $this->setExpectedException(
            'Inet\SugarCRM\Exception\InstallerException',
            'The web installer failed and return an unknown error. Check the install.log file on Sugar.'
        );
        $stub = $this->getMock(
            'Inet\SugarCRM\Installer',
            array('getInstallScriptPath'),
            array(
                $this->newApp('')
            )
        );
        $stub->method('getInstallScriptPath')
            ->willReturn(__DIR__ . '/installer/install_error.php');
        $stub->runSugarInstaller();
    }

    /**
     * @expectedException Inet\SugarCRM\Exception\InstallerException
     * @expectedExceptionMessageRegExp /Missing or unreadable config_si/
     */
    public function testFailedRunInvalidConfigSi()
    {
        $installer = new Installer($this->newApp(''));
        $installer->run();
    }

    /**
     * @group sugar
     */
    public function testInstall()
    {
        $this->assertFileExists(
            getenv('SUGARCRM_PATH'),
            'Please specify the SUGARCRM_PATH from the environment or phpunit.xml file.'
        );
        $install_path = getenv('SUGARCRM_PATH') . '/inetprocess_installer';
        $fs = new Filesystem;
        if ($fs->exists($install_path)) {
            $fs->remove($install_path);
        }
        $fs->mkdir($install_path);
        $installer = new Installer(
            $this->newApp($install_path),
            __DIR__ . '/installer/Fake_Sugar.zip',
            __DIR__ . '/installer/config_si.php'
        );
        $installer->run();
        $this->assertTrue(
            $installer->getApplication()->isValid(),
            'The install did not extract the zip archive correctly'
        );
        $this->assertTrue(
            $installer->getApplication()->isInstalled(),
            'The installer did not perform the sugar installation correctly.'
        );
        $sugar_config = $installer->getApplication()->getSugarConfig();
        $this->assertEquals('UTF-8', $sugar_config['default_export_charset']);
        $fs->remove($install_path);
    }
}
