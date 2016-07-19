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
 * @license Apache License 2.0
 *
 * @link http://www.inetprocess.com
 */

namespace Inet\SugarCRM;

use Symfony\Component\Finder\Finder;
use Inet\SugarCRM\Bean;
use Inet\SugarCRM\Exception\BeanNotFoundException;

/**
 * SugarCRM Logic Hooks Management
 *
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
     * Logic Hooks defined in Sugar 7.6 (from the documentation, except *_relationship_update)
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
        'before_relationship_update' => 'Executes before a relationship has been updated between two records.',
        'after_relationship_update' => 'Executes after a relationship has been updated between two records.',
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
        $hooks = $this->getHooksDefinitionsFromSugar($module);
        if (empty($hooks)) {
            return array();
        }

        $sortedHooks = array();
        $hooksDef = $this->getHooksDefinitionsFromFiles($module);

        // Process hooks in a specific order
        foreach ($this->modulesLogicHooksDef as $type => $hookDesc) {
            if (!array_key_exists($type, $hooks) || empty($hooks[$type])) {
                continue;
            }

            $i = 0;
            foreach ($hooks[$type] as $hook) {
                $num = str_pad($hook[0], 20, 0, \STR_PAD_LEFT) . '_' . $i;
                $sortedHooks[$type][$num] = array(
                    'Weight' => $hook[0],
                    'Description' => $hook[1],
                    'File' => $hook[2],
                    'Class' => $hook[3],
                    'Method' => $hook[4],
                    'Defined In' => $this->findHookInDefinition($hooksDef, $type, $hook[2], $hook[3], $hook[4]),
                );
                $i++;
            }
            ksort($sortedHooks[$type]);
            unset($hooks[$type]);
        }

        // Have I lost some hooks ?
        if (!empty($hooks)) {
            foreach ($hooks as $type => $hooks) {
                foreach ($hooks as $hook) {
                    if (empty($hook)) {
                        continue;
                    }
                    $sortedHooks[$type][] = array(
                        'Weight' => $hook[0],
                        'Description' => $hook[1],
                        'File' => $hook[2],
                        'Class' => $hook[3],
                        'Method' => $hook[4],
                        'Defined In' => $this->findHookInDefinition($hooksDef, $type, $hook[2], $hook[3], $hook[4]),
                    );
                }
            }
        }

        return $sortedHooks;
    }

    /**
     * Get Hooks Definitions From SugarCRM
     * @param     string    $module
     * @return    array
     */
    public function getHooksDefinitionsFromSugar($module)
    {
        $modulesList = array_keys($this->getEntryPoint()->getBeansList());
        if (!in_array($module, $modulesList)) {
            throw new BeanNotFoundException("$module is not a valid module name");
        }

        $beanManager = new Bean($this->getEntryPoint());
        $bean = $beanManager->newBean($module);

        // even if we have our own method, rely on sugar to identify hooks
        $logicHook = new \LogicHook();
        // @codeCoverageIgnoreStart
        if (!method_exists($logicHook, 'loadHooks')) {
            // Will fail on old sugar version.
            throw new \BadMethodCallException('The loadHooks method does not exist. Is your SugarCRM too old ?');
        }
        // @codeCoverageIgnoreEnd
        $logicHook->setBean($bean);

        return $logicHook->loadHooks($beanManager->getModuleDirectory($module));
    }


    /**
     * Get, from the known files, the list of hooks defined in the system
     * @param     string    $module
     * @return    array                List of Hooks
     */
    public function getHooksDefinitionsFromFiles($module, $byFiles = true)
    {
        $modulesList = array_keys($this->getEntryPoint()->getBeansList());
        if (!in_array($module, $modulesList)) {
            throw new BeanNotFoundException("$module is not a valid module name");
        }

        $hooks = array();

        // Create a new find to locate all the files where hooks could be defined
        $beanManager = new Bean($this->getEntryPoint());
        $bean = $beanManager->newBean($module);

        $files = array();
        // process the main file
        $mainFile = 'custom/modules/' . $beanManager->getModuleDirectory($module) . '/logic_hooks.php';
        if (file_exists($mainFile)) {
            $files[] = $mainFile;
        }

        // find files in ExtDir
        $customExtDir = 'custom/Extension/modules/' . $beanManager->getModuleDirectory($module) . '/Ext/LogicHooks/';
        if (is_dir($customExtDir)) {
            $finder = new Finder();
            $finder->files()->in($customExtDir)->name('*.php');
            foreach ($finder as $file) {
                $files[] = $customExtDir . $file->getRelativePathname();
            }
        }

        // read file and exit as soon as we find one
        $hooksDefs = array();
        foreach ($files as $file) {
            if ($byFiles === true) {
                $hook_array = array();
            }
            require($file);
            $hooksDefs[$file] = $hook_array;
        }

        return ($byFiles === true ? $hooksDefs : $hook_array);
    }


    /**
     * Try to identify the file where a hook is defined
     * @param     array     $hooks
     * @param     string    $type
     * @param     string    $file
     * @param     string    $class
     * @param     string    $method
     * @return    string                 File Name
     */
    public function findHookInDefinition(array $hooksDef, $type, $file, $class, $method)
    {
        foreach ($hooksDef as $defFile => $hooks) {
            if (!array_key_exists($type, $hooks)) {
                continue;
            }

            // Find a hook
            foreach ($hooks[$type] as $hook) {
                if ($hook[2] == $file && $hook[3] == $class && $hook[4] == $method) {
                    return $defFile;
                }
            }
        }
    }
}
