<?php

namespace Inet\SugarCRM\Database;

use Psr\Log\NullLogger;
use Inet\SugarCRM\Application;
use Inet\SugarCRM\Database\SugarPDO;

/**
 * @group db
 */
class SugarPDOTest extends \PHPUnit_Framework_TestCase
{

    public function getPath()
    {
        return __DIR__ . '/metadata/fake_sugar';
    }

    public function setUp()
    {
        $config = file_get_contents(__DIR__ . '/metadata/fake_sugar/config.tpl.php');
        $config = str_replace(
            array(
                '<DB_USER>',
                '<DB_PASSWORD>',
                '<DB_NAME>'
            ),
            array(
                getenv('SUGARCRM_DB_USER'),
                getenv('SUGARCRM_DB_PASSWORD'),
                getenv('SUGARCRM_DB_NAME'),
            ),
            $config
        );
        file_put_contents(__DIR__ . '/metadata/fake_sugar/config.php', $config);
    }

    public function tearDown()
    {
        unlink(__DIR__ . '/metadata/fake_sugar/config.php');
    }

    public function getSugarPDO()
    {
        $app = new Application(new NullLogger(), $this->getPath());
        return new SugarPDO($app);
    }

    /**
     * @expectedException \Inet\SugarCRM\Exception\SugarException
     * @expectedExceptionMessage Configuration parameter "db_config" is not an array
     */
    public function testMissingDbConfig()
    {
        $app = new Application(new NullLogger(), __DIR__ . '/metadata/invalid_sugar');
        $pdo = new SugarPDO($app);
    }

    public function testDbParamsNormalization()
    {
        $dbData = array(
            'db_name' => 'test_db',
            'db_user_name' => 'test_user',
        );
        $sugar = $this->getSugarPDO();
        $actual = $sugar->normalizeDbParams($dbData);
        $expected['db_name'] = 'test_db';
        $expected['db_user_name'] = 'test_user';
        $expected['db_password'] = '';
        $expected['db_host_name'] = 'localhost';
        $expected['db_port'] = 3306;
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException \Inet\SugarCRM\Exception\SugarException
     */
    public function testDbMissingDbName()
    {
        $db_data = array(
            'db_user_name' => 'test_user',
        );
        $sugar = $this->getSugarPDO();
        $sugar->normalizeDbParams($db_data);
    }

    /**
     * @expectedException \Inet\SugarCRM\Exception\SugarException
     */
    public function testDbMissingDbUserName()
    {
        $db_data = array(
            'db_name' => 'test_db',
        );
        $sugar = $this->getSugarPDO();
        $sugar->normalizeDbParams($db_data);
    }
}
