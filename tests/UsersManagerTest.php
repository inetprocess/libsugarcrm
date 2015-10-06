<?php
namespace Inet\SugarCRM\Tests;

use Inet\SugarCRM\Application;
use Inet\SugarCRM\EntryPoint;
use Inet\SugarCRM\UsersManager;
use Inet\SugarCRM\Exception\UpdateBeanException;
use Psr\Log\NullLogger;

/**
 * @group sugarcrm
 */
class UsersManagerTest extends SugarTestCase
{

    public function cleanUsers($user_name)
    {
        $sugar = $this->getEntryPointInstance();
        $db = $sugar->getSugarDb();
        $sql = "DELETE FROM users WHERE user_name='{$user_name}'";
        $db->query($sql);
    }

    public function testGetUsers()
    {
        $user_name = 'test_user';
        $sugar = $this->getEntryPointInstance();
        $this->cleanUsers($user_name);
        $um = new UsersManager($sugar);
        $user_id = $um->createUser($user_name, array(
            'is_admin' => 1,
        ));
        $this->assertRegExp('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/', $user_id);

        $user_bean = $um->getUserBeanByName($user_name);
        $this->assertEquals($user_id, $user_bean->id);
        $this->assertEquals(1, $user_bean->is_admin);

        $this->assertEquals($user_id, $um->getUserIdByName($user_name));

        $um->deactivate($user_name);
        $user_bean = $um->getUserBeanByName($user_name);
        $this->assertEquals('Inactive', $user_bean->status);

        $um->activate($user_name);
        $user_bean = $um->getUserBeanByName($user_name);
        $this->assertEquals('Active', $user_bean->status);

        $um->setAdmin($user_name, false);
        $user_bean = $um->getUserBeanByName($user_name);
        $this->assertEquals(0, $user_bean->is_admin);

        $um->setPassword($user_name, 'test_password');
        $user_bean = $um->getUserBeanByName($user_name);
        $this->assertTrue($user_bean->authenticate_user(md5('test_password')));

        $mock = $this->getMock('Inet\SugarCRM\UsersManager', array('getUserBeanByName'), array($sugar));
        $mock->method('getUserBeanByName')
            ->willReturn((object) array(
                'id' => $user_id,
                'table_name' => 'users',
            ));
        $mock->setPassword($user_name, 'test_password2');
        $user_bean = $um->getUserBeanByName($user_name);
        $this->assertEquals(md5('test_password2'), $user_bean->user_hash);
        $this->assertTrue($user_bean->authenticate_user(md5('test_password2')));
    }

    /**
     * @expectedException Inet\SugarCRM\Exception\BeanNotFoundException
     */
    public function testInvalidUser()
    {
        $sugar = $this->getEntryPointInstance();
        $um = new UsersManager($sugar);
        $um->getUserBeanByName('invalid user');
    }

    /**
     * @expectedException Inet\SugarCRM\Exception\UpdateBeanException
     * @expectedExceptionMessage test
     */
    public function testSetActiveException()
    {
        $mock = $this->getMock(
            'Inet\SugarCRM\UsersManager',
            array('updateUser'),
            array($this->getEntryPointInstance())
        );
        $mock->method('updateUser')
            ->will($this->throwException(new UpdateBeanException('test', 99)));

        $mock->setActive('test', true);
    }

    /**
     * @expectedException Inet\SugarCRM\Exception\UpdateBeanException
     * @expectedExceptionMessage test
     */
    public function testSetAdminException()
    {
        $mock = $this->getMock(
            'Inet\SugarCRM\UsersManager',
            array('updateUser'),
            array($this->getEntryPointInstance())
        );
        $mock->method('updateUser')
            ->will($this->throwException(new UpdateBeanException('test', 99)));

        $mock->setAdmin('test', true);
    }
}
