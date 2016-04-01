<?php
namespace Inet\SugarCRM\Tests;

use Inet\SugarCRM\EntryPoint;
use Inet\SugarCRM\DB;
use Inet\SugarCRM\System;

class SystemTest extends SugarTestCase
{
    public function testRightInstanciation()
    {
        $system = new System($this->getEntryPointInstance());

        // Activity
        $this->assertTrue($system->isActivityEnabled());
        $system->disableActivity();
        $this->assertFalse($system->isActivityEnabled());

        // Logger
        $logger = $system->getLogger();
        $this->assertInstanceOf('Psr\Log\NullLogger', $logger);

        // EntryPoint
        $ep = $system->getEntryPoint();
        $this->assertInstanceOf('\Inet\SugarCRM\EntryPoint', $ep);
    }

    /**
     * This test is really slow ( > 1 minute)
     * @group sugarcrm-slow
     * @group repair
     */
    public function testRepairAll()
    {
        $checkFile = realpath(getenv('SUGARCRM_PATH') . '/cache/class_map.php');
        $this->assertFileExists($checkFile, 'That file is used to test my repair');
        unlink($checkFile);
        $this->assertFileNotExists($checkFile, 'That file is used to test my repair');
        $system = new System($this->getEntryPointInstance());
        $ret = $system->repair();
        $this->assertFileExists($checkFile, 'That file is used to test my repair');
        $this->assertNotEmpty($ret);
        $this->assertNotEmpty($ret[0]);
    }

    /**
     * @group repair
     */
    public function testRebuildExtensions()
    {
        $sugar_path = realpath(getenv('SUGARCRM_PATH'));
        $ext_path = $sugar_path . '/custom/Extension/modules/Accounts/Ext/LogicHooks/test_rebuild_ext.php';
        $compiled_ext_path = $sugar_path . '/custom/modules/Accounts/Ext/LogicHooks/logichooks.ext.php';
        $token = md5(mt_rand());
        if (!is_dir(dirname($ext_path))) {
            mkdir(dirname($ext_path), 0750, true);
        }
        file_put_contents($ext_path, "<?php\n// token: $token");
        $sys = new System($this->getEntryPointInstance());
        $ret = $sys->rebuildExtensions(array('Accounts'));
        $this->assertCount(2, $ret);
        $this->assertFileExists($compiled_ext_path);
        $compiled_ext = file_get_contents($compiled_ext_path);
        $this->assertContains("// token: $token", $compiled_ext);
    }

    /**
     * @group repair
     */
    public function testRebuildApplication()
    {
        $sugar_path = realpath(getenv('SUGARCRM_PATH'));
        $ext_path = $sugar_path . '/custom/Extension/application/Ext/Utils/test_rebuild_application.php';
        $compiled_ext_path = $sugar_path . '/custom/application/Ext/Utils/custom_utils.ext.php';
        $token = md5(mt_rand());
        if (!is_dir(dirname($ext_path))) {
            mkdir(dirname($ext_path), 0750, true);
        }
        file_put_contents($ext_path, "<?php\n// token: $token");
        $sys = new System($this->getEntryPointInstance());
        $sys->rebuildApplication();
        $this->assertFileExists($compiled_ext_path);
        $compiled_ext = file_get_contents($compiled_ext_path);
        $this->assertContains("// token: $token", $compiled_ext);
    }
}
