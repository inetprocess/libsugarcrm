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

use Inet\SugarCRM\Bean;
use Inet\SugarCRM\Exception\BeanNotFoundException;

/**
 * SugarCRM Logic Hooks Management
 *
 */
class System
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
     * Messages sent by Sugar as an output
     * @var    array
     */
    protected $messages = array();

    /**
     * Need to flush ob before getting messages ?
     * @var    boolean
     */
    protected $isFlushed = true;

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

    /**
     * @deprecated Use repairAll instead
     */
    public function repair($executeSql = false, $userId = '1')
    {
        return $this->repairAll($executeSql, $userId);
    }

    /**
     * Taken from fayebsg/sugarcrm-cli
     * Repair and rebuild sugarcrm
     * @param     boolean    $executeSql    Launch the SQL queries
     * @param     string     $user_id       User id of the admin user
     * @return    array                     Messages
     */
    public function repairAll($executeSql = false, $userId = '1')
    {
        $this->setUpQuickRepair($userId);
        $repair = new \RepairAndClear();
        $repair->repairAndClearAll(array('clearAll'), array(translate('LBL_ALL_MODULES')), $executeSql, true, '');
        //remove the js language files
        if (!method_exists('LanguageManager', 'removeJSLanguageFiles')) {
            $this->getLogger()->warning('No removeJSLanguageFiles method (sugar too old?). Check that it\'s clean.');
        } else {
            \LanguageManager::removeJSLanguageFiles();
        }
        return $this->getMessages();
    }

    /**
     * Rebuild only Extensions.
     * @param     array      $modules       Rebuild only the specified modules
     * @param     string     $user_id       User id of the admin user
     * @return    array                     Messages
     */
    public function rebuildExtensions(array $modules = array(), $user_id = '1')
    {
        $this->setUpQuickRepair($user_id);
        $repair = new \RepairAndClear();
        $repair->repairAndClearAll(
            array('rebuildExtensions'),
            $modules
        );
        return $this->getMessages();
    }

    /**
     * Specific for a fast rebuild of Extentions files for application
     */
    public function rebuildApplication()
    {
        require_once('ModuleInstall/ModuleInstaller.php');
        $moduleInstaller = new \ModuleInstaller();
        $moduleInstaller->silent = true;
        $moduleInstaller->rebuild_all(true, array('application'));
    }

    /**
     * Prepare all the necessary thing to run a repair and rebuild
     * ob is also started to catch any output
     */
    private function setUpQuickRepair($user_id = '1')
    {
        // Config ang language
        $sugarConfig = $this->getEntryPoint()->getApplication()->getSugarConfig();
        // Force setting the admin user.
        $this->getEntryPoint()->setCurrentUser($user_id);
        $currentLanguage = $sugarConfig['default_language'];
        require_once('modules/Administration/QuickRepairAndRebuild.php');
        require_once('include/utils/layout_utils.php');
        $GLOBALS['mod_strings'] = return_module_language($currentLanguage, 'Administration');
        ob_start(array($this, 'parseOutput'));
        $this->isFlushed = false;
    }

    /**
     * Parse ouput of ob to remove html
     * and store messages.
     */
    private function parseOutput($message)
    {
        $message = preg_replace('#<script.*</script>#i', '', $message);
        $message = preg_replace('#<(br\s*/?|/h3)>#i', PHP_EOL, $message);
        $message = trim(strip_tags($message));
        $message = preg_replace('#'.PHP_EOL.'{2,}#', PHP_EOL, $message);
        $this->addMessage(trim($message));
        return '';
    }

    /**
     * Add a message to the array
     * @param    string    $message
     */
    private function addMessage($message)
    {
        $this->messages[] = $message;
    }

    /**
     * Flush ob if necessary and return stored messages.
     */
    public function getMessages()
    {
        if ($this->isFlushed !== true) {
            ob_end_flush();
        }
        return $this->messages;
    }

    /**
     * Disable trackers in SugarCRM
     */
    public function disableActivity()
    {
        \Activity::disable();
    }

    /**
     * Is activity Enabled ?
     * @return    boolean
     */
    public function isActivityEnabled()
    {
        return \Activity::isEnabled();
    }
}
