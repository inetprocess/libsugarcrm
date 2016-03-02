<?php

namespace Inet\SugarCRM\Tests;

use Inet\SugarCRM\DB;
use Inet\SugarCRM\EntryPoint;
use Inet\SugarCRM\Bean as BeanManager;
use Psr\Log\NullLogger;

/**
 * @group sugarcrm
 */
class BeanTest extends SugarTestCase
{
    public function getBeanManager()
    {
        $sugar = $this->getEntryPointInstance();
        $sugar->setCurrentUser('1');
        return new BeanManager($sugar);
    }

    public function testNewBean()
    {
        $bm = $this->getBeanManager();
        $account = $bm->newBean('Accounts');
        $this->assertInstanceOf('SugarBean', $account);
        $this->assertInstanceOf('Account', $account);
    }

    /**
     * @todo Test encode parameter with htmlspecialchar
     * @todo Test disable_row_level_security.
     */
    public function testGetBean()
    {
        $bm =  $this->getBeanManager();
        $user = $bm->getBean('Users', '1');
        $this->assertInstanceOf('User', $user);
        $this->assertEquals('1', $user->id);

        $user1 = $bm->getBean('Users', '1', array(), true, true);
        $user2 = $bm->getBean('Users', '1', array(), true, true);
        $this->assertInstanceOf('User', $user1);
        $this->assertEquals(1, $user1->id);
        $this->assertSame($user1, $user2);

        $this->assertFalse($bm->getBean('Users', 'invalid id'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid module does not exist in SugarCRM, I cannot retrieve anything
     */
    public function testInvalidModule()
    {
        $this->getBeanManager()->getBean('Invalid module');
    }

    public function testUpdateBeanFields()
    {
        $bm = $this->getBeanManager();
        $account = $bm->newBean('Accounts');
        $account->description = 'bar';
        $fields = array(
            'name' => 'Test account',
            'invalid_field' => 'test',
            'description' => 'bar',
        );
        $changes = $bm->updateBeanFields($account, $fields);
        $this->assertEquals('Test account', $account->name);
        $this->assertObjectHasAttribute('invalid_field', $account);
        $this->assertEquals(2, $changes);
    }

    public function testUpdateBeanFieldsFromCurrentUser()
    {
        $bm = $this->getBeanManager();
        $account = $bm->newBean('Accounts');
        $account->created_by = '';
        $bm->updateBeanFieldsFromCurrentUser($account);
        $this->assertNotEmpty($account->assigned_user_id);
        $this->assertNotEmpty($account->team_id);
        $this->assertNotEmpty($account->team_set_id);
        $this->assertNotEmpty($account->created_by);
    }

    public function testUpdateBean()
    {
        $account_name = 'Test PHPUNIT account';
        $bm = $this->getBeanManager();
        $account = $bm->newBean('Accounts');
        // Test dry run
        $ret = $bm->updateBean($account, array('name' => 'dry run'), BeanManager::MODE_DRY_RUN);
        $this->assertEquals(BeanManager::SUGAR_NOTCHANGED, $ret);
        $this->assertNotEmpty($account->assigned_user_id);
        $this->assertNotEmpty($account->team_id);
        $this->assertNotEmpty($account->team_set_id);
        $this->assertNotEmpty($account->created_by);
        // Test create
        $fields = array('name' => $account_name);
        $ret = $bm->updateBean($account, $fields, BeanManager::MODE_CREATE);
        $this->assertEquals(BeanManager::SUGAR_CREATED, $ret);
        $account = $bm->getBean('Accounts', $account->id);
        $this->assertNotEmpty($account->assigned_user_id);
        $this->assertNotEmpty($account->team_id);
        $this->assertNotEmpty($account->team_set_id);
        $this->assertNotEmpty($account->created_by);
        $this->assertInstanceOf('Account', $account);
        $this->assertEquals($account_name, $account->name);

        // Test update
        $ret = $bm->updateBean($account, array('description' => 'foo'), BeanManager::MODE_UPDATE);
        $this->assertEquals(BeanManager::SUGAR_UPDATED, $ret);
        $account = $bm->getBean('Accounts', $account->id);
        $this->assertInstanceOf('Account', $account);
        $this->assertEquals('foo', $account->description);

        // Test same update
        $ret = $bm->updateBean($account, array('description' => 'foo'), BeanManager::MODE_UPDATE);
        $this->assertEquals(BeanManager::SUGAR_NOTCHANGED, $ret);
        $account = $bm->getBean('Accounts', $account->id);
        $this->assertInstanceOf('Account', $account);
        $this->assertEquals('foo', $account->description);

        // Test create with id
        $account = $bm->newBean('Accounts');
        $fields = array(
            'name' => $account_name,
            'id' => 'test_account_id',
        );
        $ret = $bm->updateBean($account, $fields, BeanManager::MODE_CREATE_WITH_ID);
        $this->assertEquals(BeanManager::SUGAR_CREATED, $ret);
        $account = $bm->getBean('Accounts', 'test_account_id');
        $this->assertInstanceOf('Account', $account);
        $this->assertEquals('test_account_id', $account->id);
        $this->assertEquals($account_name, $account->name);
    }

    /**
     * @expectedException \Inet\SugarCRM\Exception\UpdateBeanException
     * @expectedExceptionMessage Error: Won't create an empty bean.
     */
    public function testCreateBeanNoChanges()
    {
        $bm = $this->getBeanManager();
        $account = $bm->newBean('Accounts');
        $bm->updateBean($account, array(), BeanManager::MODE_CREATE);
    }

    /**
     * @expectedException \Inet\SugarCRM\Exception\UpdateBeanException
     * @expectedExceptionMessageRegexp /Because not in UPDATE or CREATE_WITH_ID mode/
     */
    public function testUpdateBeanInvalidCreate()
    {
        $bm = $this->getBeanManager();
        $account = $bm->newBean('Accounts');
        $bm->updateBean($account, array('name' => 'test', 'id' => 'test_with_id'), BeanManager::MODE_CREATE);
    }

    /**
     * @expectedException \Inet\SugarCRM\Exception\UpdateBeanException
     * @expectedExceptionMessageRegexp /when not in CREATE mode/
     */
    public function testUpdateBeanInvalidUpdate()
    {
        $bm = $this->getBeanManager();
        $account = $bm->newBean('Accounts');
        $bm->updateBean($account, array('name' => 'test'), BeanManager::MODE_UPDATE);
    }

    public function tearDown()
    {
        $db = new DB($this->getEntryPointInstance());
        $sql = "DELETE from accounts where name='Test PHPUNIT account';";
        $db->query($sql);
    }

    public function testApplyLabelsToField()
    {
        $bm = $this->getBeanManager();
        $field_name = 'test';
        $expected_field = 'foo';
        $md = array(
            $field_name => array(
                'vname' => $expected_field,
                'type' => 'test',
            )
        );

        list($actual_field, $actual_value) = $bm->applyLabelsToField($md, $field_name, 'bar');
        $this->assertEquals($expected_field, $actual_field);
        $this->assertEquals('bar', $actual_value);

        $md[$field_name]['type'] = 'bool';
        list($actual_field, $actual_value) = $bm->applyLabelsToField($md, $field_name, true);
        $this->assertEquals($expected_field, $actual_field);
        $this->assertEquals("\xE2\x9C\x94", $actual_value);

        list($actual_field, $actual_value) = $bm->applyLabelsToField($md, $field_name, false);
        $this->assertEquals($expected_field, $actual_field);
        $this->assertEquals("\xE2\x9C\x95", $actual_value);

        $md[$field_name]['type'] = 'enum';
        $md[$field_name]['options_list']['bar'] = 'Baz';
        list($actual_field, $actual_value) = $bm->applyLabelsToField($md, $field_name, 'bar');
        $this->assertEquals($expected_field, $actual_field);
        $this->assertEquals('Baz', $actual_value);

    }

    public function testApplyLabelsToFieldFromSugar()
    {
        $bm = $this->getBeanManager();
        $md = $bm->getModuleFields('Users', 'fr_FR');
        $field_name = 'user_name';
        $expected_field = 'Login';
        list($actual_field, $actual_value) = $bm->applyLabelsToField($md, $field_name, 'bar');
        $this->assertEquals($expected_field, $actual_field);
        $this->assertEquals('bar', $actual_value);

        $field_name = 'is_admin';
        $expected_field = 'Administrateur ?';
        list($actual_field, $actual_value) = $bm->applyLabelsToField($md, $field_name, true);
        $this->assertEquals($expected_field, $actual_field);
        $this->assertEquals("\xE2\x9C\x94", $actual_value);

        list($actual_field, $actual_value) = $bm->applyLabelsToField($md, $field_name, false);
        $this->assertEquals($expected_field, $actual_field);
        $this->assertEquals("\xE2\x9C\x95", $actual_value);

        $field_name = 'status';
        $expected_field = 'Statut';
        list($actual_field, $actual_value) = $bm->applyLabelsToField($md, $field_name, 'Active');
        $this->assertEquals($expected_field, $actual_field);
        $this->assertEquals('Actif', $actual_value);

    }

    public function testApplyLabelsToFieldFailures()
    {
        $bm = $this->getBeanManager();
        $field_name = 'toto';
        $expected_field = 'foo';
        $md = array(
            'test' => array(
                'name' => $expected_field,
                'type' => 'test',
            )
        );
        // Unkown field name
        list($actual_field, $actual_value) = $bm->applyLabelsToField($md, $field_name, 'bar');
        $this->assertEquals($field_name, $actual_field);
        $this->assertEquals('bar', $actual_value);
        // No vname for field
        $field_name = 'test';
        list($actual_field, $actual_value) = $bm->applyLabelsToField($md, $field_name, 'bar');
        $this->assertEquals($field_name, $actual_field);
        $this->assertEquals('bar', $actual_value);
        // No options lists
        $md[$field_name]['type'] = 'enum';
        list($actual_field, $actual_value) = $bm->applyLabelsToField($md, $field_name, 'bar');
        $this->assertEquals($field_name, $actual_field);
        $this->assertEquals('bar', $actual_value);
    }

    public function testBeanToArray()
    {
        $bm = $this->getBeanManager();
        $admin = $bm->getBean('Users', '1');
        $fields = array('id', 'user_name', 'status', 'is_admin');
        // Test pretty off
        $bean_array = $bm->beanToArray($fields, $admin);
        $expected_array = array(
            'id' => '1',
            'user_name' => 'admin',
            'status' => 'Active',
            'is_admin' => '1',
        );
        $this->assertEquals($expected_array, $bean_array);

        // Test pretty on
        $bean_array = $bm->beanToArray($fields, $admin, true, 'fr_FR');
        $expected_array = array(
            'ID' => '1',
            'Login' => 'admin',
            'Statut' => 'Actif',
            'Administrateur ?' => "\xE2\x9C\x94",
        );
        $this->assertEquals($expected_array, $bean_array);
    }

    public function testBeanListtoArray()
    {
        $bm = $this->getBeanManager();
        $beans_list = array(
            $bm->getBean('Users', '1'),
            $bm->getBean('Users', 'seed_jim_id'),
        );
        $fields = array('id', 'user_name', 'status', 'is_admin');
        // Test pretty off
        $bean_array = $bm->beanListToArray($fields, $beans_list);
        $expected_array = array(
            array(
                'id' => '1',
                'user_name' => 'admin',
                'status' => 'Active',
                'is_admin' => '1',
            ),
            array(
                'id' => 'seed_jim_id',
                'user_name' => 'jim',
                'status' => 'Active',
                'is_admin' => '0',
            ),
        );
        $this->assertEquals($expected_array, $bean_array);
        // Test pretty on
        $bean_array = $bm->beanListToArray($fields, $beans_list, true, 'fr_FR');
        $expected_array = array(
            array(
                'ID' => '1',
                'Login' => 'admin',
                'Statut' => 'Actif',
                'Administrateur ?' => "\xE2\x9C\x94",
            ),
            array(
                'ID' => 'seed_jim_id',
                'Login' => 'jim',
                'Statut' => 'Actif',
                'Administrateur ?' => "\xE2\x9C\x95",
            ),
        );
        $this->assertEquals($expected_array, $bean_array);
    }
}
