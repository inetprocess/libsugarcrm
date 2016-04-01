<?php
namespace Inet\SugarCRM\Tests;

use Inet\SugarCRM\Application;
use Inet\SugarCRM\EntryPoint;
use Inet\SugarCRM\LogicHook;
use Inet\SugarCRM\Exception\UpdateBeanException;
use Psr\Log\NullLogger;

/**
 * @group sugarcrm
 */
class LogicHookTest extends SugarTestCase
{
    protected $lh;
    protected $sugarPath;
    protected $mainDir;
    protected $extDir;
    protected $cacheDir;
    protected static $cacheFileContent;

    public function setUp()
    {
        $this->lh = new LogicHook($this->getEntryPointInstance());

        // Create dirs and clean
        $this->mainDir = $this->getEntryPointInstance()->getPath() . '/custom/modules/Meetings';
        if (!is_dir($this->mainDir)) {
            mkdir($this->mainDir, 0750, true);
        }
        $this->extDir = $this->getEntryPointInstance()->getPath() . '/custom/Extension/modules/Meetings/Ext/LogicHooks';
        if (!is_dir($this->extDir)) {
            mkdir($this->extDir, 0750, true);
        }
        $this->cacheDir = $this->getEntryPointInstance()->getPath() . '/custom/modules/Meetings/Ext/LogicHooks';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0750, true);
        }
        if (empty(self::$cacheFileContent) && file_exists($this->cacheDir . '/logichooks.ext.php')) {
            self::$cacheFileContent = file_get_contents($this->cacheDir . '/logichooks.ext.php');
        }

        $this->resetFiles();
    }

    public function tearDown()
    {
        $this->resetFiles();
    }

    public function resetFiles()
    {
        if (file_exists($this->mainDir . '/logic_hooks.php')) {
            file_put_contents($this->mainDir . '/logic_hooks.php', '');
        }
        if (file_exists($this->extDir . '/test.php')) {
            file_put_contents($this->extDir . '/test.php', '');
        }
        if (file_exists($this->cacheDir . '/logichooks.ext.php')) {
            file_put_contents($this->cacheDir . '/logichooks.ext.php', self::$cacheFileContent);
        }
    }


    public function testGeneralMethods()
    {
        // Check i get the logger
        $logger = $this->lh->getLogger();
        $this->assertInstanceOf('Psr\Log\NullLogger', $logger);
    }

    public function testGetModulesLogicHooksDef()
    {
        $hooks = $this->lh->getModulesLogicHooksDef();
        $this->assertInternalType('array', $hooks);
        $this->arrayHasKey('before_save', $hooks);
    }

    /**
     * @expectedException Inet\SugarCRM\Exception\BeanNotFoundException
     */
    public function testInvalidModule()
    {
        $this->lh->getModuleHooks('TOTO');
    }

    public function testValidModuleEmptyHooks()
    {
        $hooks = $this->lh->getModuleHooks('Contacts');
        $this->assertInternalType('array', $hooks);
        $msg = 'If you find that error, that means that your Contacts module ';
        $msg.= 'has some defined hooks. Try an empty Sugar Instance !';
        $this->assertEmpty($hooks, $msg);
    }

    public function testValidModuleOneHook()
    {
        $hooks = $this->lh->getModuleHooks('Meetings');
        $this->assertInternalType('array', $hooks);
        $msg = 'If you find that error, that means that your Meetings does not have the default ';
        $msg.= 'before_relationship_update Hook. Try an empty Sugar Instance !';
        $this->assertArrayHasKey('before_relationship_update', $hooks, $msg);
    }

    /**
     * @expectedException Inet\SugarCRM\Exception\BeanNotFoundException
     */
    public function testGetHooksDefinitionsFromFilesWrongModule()
    {
        // Check the definition
        $hooks = $this->lh->getHooksDefinitionsFromFiles('TOTO');
    }


    public function testDefineMissingHook()
    {
        $hooksBefore = $this->lh->getModuleHooks('Meetings');
        // Hook in main file
        file_put_contents($this->mainDir . '/logic_hooks.php', '<?php
$hook_version = 1;
$hook_array = array();
$hook_array["before_save"][] = array(
    10,
    "test",
    "test.php",
    "Test",
    "test"
);');


        // Hook in extension
        file_put_contents($this->extDir . '/test.php', '<?php
$hook_array["after_save"][] = array(
    10,
    "test",
    "test.php",
    "Test",
    "test"
);');

        // Hook in cache file
        file_put_contents($this->cacheDir . '/logichooks.ext.php', self::$cacheFileContent . PHP_EOL . '
$hook_array["after_save"][] = array(
    10,
    "test",
    "test.php",
    "Test",
    "test"
);
$hook_array["lost_hook"][] = array(
    10,
    "test",
    "test.php",
    "Test",
    "test"
);
$hook_array["empty_hook"][] = array();');

        // Check the definition
        $hooks = $this->lh->getHooksDefinitionsFromFiles('Meetings');
        $this->assertInternalType('array', $hooks);
        // Main file
        $this->assertArrayHasKey('custom/modules/Meetings/logic_hooks.php', $hooks);
        $this->assertArrayHasKey('before_save', $hooks['custom/modules/Meetings/logic_hooks.php']);
        // Extension
        $this->assertArrayHasKey('custom/Extension/modules/Meetings/Ext/LogicHooks/test.php', $hooks);
        $this->assertArrayHasKey('after_save', $hooks['custom/Extension/modules/Meetings/Ext/LogicHooks/test.php']);


        // Now get the hooks and one should be missing
        $hooksAfter = $this->lh->getModuleHooks('Meetings');
        $this->assertArrayNotHasKey('after_save', $hooksBefore);
        $this->assertArrayHasKey('after_save', $hooksAfter);
        $this->assertArrayHasKey('lost_hook', $hooksAfter);
        $this->assertEmpty($hooksAfter['lost_hook'][0]['Defined In']);
    }

    public function testFindHookInDefinition()
    {
        $def = array(
            array('before_save' => array())
        );
        $ret = $this->lh->findHookInDefinition($def, 'before_save', null, null, null);
        $this->assertNull($ret);
    }
}
