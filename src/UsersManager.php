<?php
/**
 * SugarCRM Tools
 *
 * PHP Version 5.3 -> 5.6
 * SugarCRM Versions 6.5 - 7.6
 *
 * @author Emmanuel Dyan
 * @copyright 2005-2015 iNet Process
 *
 * @package inetprocess/sugarcrm
 *
 * @license GNU General Public License v2.0
 *
 * @link http://www.inetprocess.com
 */

namespace Inet\SugarCRM;

use Inet\SugarCRM\Exception\BeanNotFoundException;
use Inet\SugarCRM\Exception\UpdateBeanException;
use Inet\SugarCRM\Bean as BeanManager;

/**
 * SugarCRM User Management
 *
 * @todo Unit Tests
 */
class UsersManager
{
    const MODULE_NAME = 'Users';
    const STATUS_ACTIVE = 'Active';
    const STATUS_INACTIVE = 'Inactive';
    /**
     * Prefix that should be set by each class to identify it in logs
     *
     * @var string
     */
    protected $logPrefix;

    /**
     * BeanManager class
     */
    protected $beansManager;

    /**
     * SugarCRM EntryPoint
     */
    protected $entryPoint;

    /**
     * Set the LogPrefix to be unique and ask for an Entry Point to SugarCRM
     *
     * @param EntryPoint $entryPoint Enters the SugarCRM Folder
     */
    public function __construct(EntryPoint $entryPoint)
    {
        $this->logPrefix = __CLASS__ . ': ';
        $this->entryPoint = $entryPoint;
        $this->beansManager = new BeanManager($entryPoint);
    }

    public function getEntryPoint()
    {
        return $this->entryPoint;
    }

    public function getLogger()
    {
        return $this->getEntryPoint()->getLogger();
    }

    public function getBeansManager()
    {
        return $this->beansManager;
    }


    public function createUser($userName, array $fields = array())
    {
        $bm = $this->getBeansManager();
        $user = $bm->newBean(self::MODULE_NAME);
        $fields['user_name'] = $userName;
        $bm->updateBean($user, $fields, BeanManager::MODE_CREATE);
        return $user->id;
    }

    public function updateUser($userName, array $fields)
    {
        $user = $this->getUserBeanByName($userName);
        $this->getBeansManager()->updateBean($user, $fields, BeanManager::MODE_UPDATE);
    }

    public function getUserBeanByName($userName)
    {
        $users_list = $this->getBeansManager()->searchBeans(self::MODULE_NAME, array('user_name' => $userName));
        if (empty($users_list)) {
            $this->getLogger()->info("Unable to find a user with user_name: {$userName}.");
            throw new BeanNotFoundException("User with user_name '$userName' not found.");
        }
        return $users_list[0];
    }

    public function getUserIdByName($userName)
    {
        return $this->getUserBeanByName($userName)->id;
    }

    public function activate($userName)
    {
        $this->setActive($userName, true);
    }

    public function deactivate($userName)
    {
        $this->setActive($userName, false);
    }

    public function setActive($userName, $active)
    {
        try {
            $status = $active ? self::STATUS_ACTIVE : self::STATUS_INACTIVE;
            $this->updateUser($userName, array(
                'status' => $status,
            ));
        } catch (UpdateBeanException $e) {
            if ($e->getCode() !== BeanManager::SUGAR_FIELDS_NOT_MODIFIED) {
                throw $e;
            }
        }
    }

    public function setAdmin($userName, $admin)
    {
        try {
            $this->updateUser($userName, array(
                'is_admin' => intval($admin),
            ));
        } catch (UpdateBeanException $e) {
            if ($e->getCode() !== BeanManager::SUGAR_FIELDS_NOT_MODIFIED) {
                throw $e;
            }
        }
    }

    public function setPassword($userName, $password)
    {
        $user = $this->getUserBeanByName($userName);
        if (method_exists($user, 'setNewPassword')) {
            $user->setNewPassword($password);
        } else {
            $hash = strtolower(md5($password));
            $sql = "UPDATE {$user->table_name} SET user_hash='{$hash}' WHERE id='{$user->id}'";
            $db = new DB($this->getEntryPoint());
            $db->query($sql);
        }
    }
}
