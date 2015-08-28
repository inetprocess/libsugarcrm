<?php
namespace Inet\SugarCRM\Tests;

use Inet\SugarCRM\Application;

class ApplicationTest extends SugarTestCase
{
    public function testSugarPath()
    {
        $sugarDir = __DIR__ . '/fake_sugar';
        $app = new Application($sugarDir);
        $this->assertInstanceOf('Inet\SugarCRM\Application', $app);
        $this->assertEquals(realpath($sugarDir), $app->getPath());
        $this->assertTrue($app->isValid());
        $this->assertTrue($app->isInstalled());
    }

    /**
     * @expectedException \Inet\SugarCrm\SugarException
     */
    public function testFailSugarPath()
    {
        $sugar = new Application(__DIR__);
        $sugar->getSugarConfig();
    }

    public function testSugarConfig()
    {
        $sugar = new Application(__DIR__ . '/fake_sugar');
        $actual_config = $sugar->getSugarConfig(true);
        require(__DIR__ . '/fake_sugar/config.php');
        require(__DIR__ . '/fake_sugar/config_override.php');
        $sugar_config;
        $this->assertEquals($sugar_config, $actual_config);
        $conf = $sugar->getSugarConfig();
        $this->assertEquals('localhost', $conf['dbconfig']['db_host_name']);
        $this->assertEquals('debug', $conf['logger']['level']);
    }

    /**
     * @expectedException \Inet\SugarCrm\SugarException
     */
    public function testInvalidSugarConfig()
    {
        $sugar = new Application(__DIR__ . '/invalid_sugar');
        $sugar->getSugarConfig();
    }

    public function testDbParamsNormalization()
    {
        $dbData = array(
            'db_name' => 'test_db',
            'db_user_name' => 'test_user',
        );
        $sugar = new Application(__DIR__ . '/fake_sugar');
        $actual = $sugar->normalizeDbParams($dbData);
        $expected['db_name'] = 'test_db';
        $expected['db_user_name'] = 'test_user';
        $expected['db_password'] = '';
        $expected['db_host_name'] = 'localhost';
        $expected['db_port'] = 3306;
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException \Inet\SugarCrm\SugarException
     */
    public function testDbMissingDbName()
    {
        $db_data = array(
            'db_user_name' => 'test_user',
        );
        $sugar = new Application(__DIR__ . '/fake_sugar');
        $sugar->normalizeDbParams($db_data);
    }

    /**
     * @expectedException \Inet\SugarCrm\SugarException
     */
    public function testDbMissingDbUserName()
    {
        $db_data = array(
            'db_name' => 'test_db',
        );
        $sugar = new Application(__DIR__ . '/fake_sugar');
        $sugar->normalizeDbParams($db_data);
    }

    public function testGetVersion()
    {
        $sugar = new Application(__DIR__ . '/fake_sugar');
        $expected = array(
            'version' => '7.5.0.1',
            'db_version' => '7.5.0.1',
            'flavor' => 'PRO',
            'build' => '1006',
            'build_timestamp' => '2014-12-12 09:59am',
        );
        $this->assertEquals($expected, $sugar->getVersion());
    }
}
