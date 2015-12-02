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

    public function testGetModulesLogicHooksDef()
    {
        $sugar = $this->getEntryPointInstance();
        $lh = new LogicHook($sugar);
        $hooks = $lh->getModulesLogicHooksDef();
        $this->assertInternalType('array', $hooks);
        $this->arrayHasKey('before_save', $hooks);
    }

    /**
     * @expectedException Inet\SugarCRM\Exception\BeanNotFoundException
     */
    public function testInvalidModule()
    {
        $sugar = $this->getEntryPointInstance();
        $lh = new LogicHook($sugar);
        $lh->getModuleHooks('TOTO');
    }

    public function testValidModuleEmptyHooks()
    {
        $sugar = $this->getEntryPointInstance();
        $lh = new LogicHook($sugar);
        $hooks = $lh->getModuleHooks('Contacts');
        $this->assertInternalType('array', $hooks);
        $msg = 'If you find that error, that means that your Contacts module ';
        $msg.= 'has some defined hooks. Try an empty Sugar Instance !';
        $this->assertEmpty($hooks, $msg);
    }

    public function testValidModuleOneHook()
    {
        $sugar = $this->getEntryPointInstance();
        $lh = new LogicHook($sugar);
        $hooks = $lh->getModuleHooks('Meetings');
        $this->assertInternalType('array', $hooks);
        $msg = 'If you find that error, that means that your Meetings does not have the default ';
        $msg.= 'before_relationship_update Hook. Try an empty Sugar Instance !';
        $this->assertArrayHasKey('before_relationship_update', $hooks, $msg);
    }
}
