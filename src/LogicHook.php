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

use Inet\SugarCRM\Bean;
use Inet\SugarCRM\Exception\BeanNotFoundException;

/**
 * SugarCRM Logic Hooks Management
 *
 * @todo Unit Tests
 */
class LogicHook
{
    /**
     * Prefix that should be set by each class to identify it in logs
     *
     * @var string
     */
    protected $logPrefix;

    /**
     * SugarCRM EntryPoint
     */
    protected $entryPoint;

    /**
     * Logic Hooks Name defined in Sugar 7.6
     */
    protected $modulesLogicHooksDef = array(
        'after_retrieve' => 'Executes after a record has been retrieved from the database.',
        'before_save' => 'Executes before a record is saved.',
        'after_save' => 'Executes after a record is saved.',
        'before_delete' => 'Executes before a record is deleted.',
        'after_delete' => 'Executes after a record is deleted.',
        'before_restore' => 'Executes before a record is undeleted.',
        'after_restore' => 'Executes after a record is undeleted.',

        'before_relationship_add' => 'Executes before a relationship has been added between two records.',
        'after_relationship_add' => 'Executes after a relationship has been added between two records.',
        'before_relationship_delete' => 'Executes before a relationship has been added between two records.',
        'after_relationship_delete' => 'Executes after a relationship has been deleted between two records.',

        'handle_exception' => 'Executes when an exception is thrown.',
        'process_record' => 'Executes when the record is being processed as a part of the ListView or subpanel list.',
    );

    /**
     * Set the LogPrefix to be unique and ask for an Entry Point to SugarCRM
     *
     * @param EntryPoint $entryPoint Enters the SugarCRM Folder
     */
    public function __construct(EntryPoint $entryPoint)
    {
        $this->logPrefix = __CLASS__ . ': ';
        $this->entryPoint = $entryPoint;
    }

    public function getEntryPoint()
    {
        return $this->entryPoint;
    }

    public function getLogger()
    {
        return $this->getEntryPoint()->getLogger();
    }

    public function getModulesLogicHooksDef()
    {
        return $this->modulesLogicHooksDef;
    }

    /**
     * Get a list of hooks, by type and sorted by weight for a specific module
     * @param     string    $module    SugarCRM module name
     * @return    array                List of hooks
     */
    public function getModuleHooks($module)
    {
        $modulesList = array_keys($this->getEntryPoint()->getBeansList());

        if (!in_array($module, $modulesList)) {
            throw new BeanNotFoundException("$module is not a valid module name");
        }

        $beanManager = new Bean($this->getEntryPoint());
        $bean = $beanManager->newBean($module);

        $logicHook = new \LogicHook();
        $logicHook->setBean($bean);
        $moduleHooks = $logicHook->loadHooks($beanManager->getModuleDirectory($module));
        if (empty($moduleHooks)) {
            return array();
        }

        $sortedHooks = array();
        // Process hooks in a specific order
        foreach ($this->modulesLogicHooksDef as $hookType => $hookDesc) {
            if (!array_key_exists($hookType, $moduleHooks) || empty($moduleHooks[$hookType])) {
                continue;
            }

            foreach ($moduleHooks[$hookType] as $moduleHook) {
                $sortedHooks[$hookType][$moduleHook[0]] = array(
                    'Weight' => $moduleHook[0],
                    'Description' => $moduleHook[1],
                    'File' => $moduleHook[2],
                    'Class' => $moduleHook[3],
                    'Method' => $moduleHook[4],
                );
            }

            ksort($sortedHooks[$hookType]);
            unset($moduleHooks[$hookType]);
        }

        // Have I lost some hooks ?
        if (!empty($moduleHooks)) {
            foreach ($moduleHooks as $hookType => $hooks) {
                if (empty($hooks)) {
                    continue;
                }
                $sortedHooks[$hookType] = $hooks;
            }
        }

        return $sortedHooks;
    }
}
