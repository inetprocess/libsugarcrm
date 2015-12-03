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
     * Taken from fayebsg/sugarcrm-cli
     * Repair and rebuild sugarcrm
     */
    public function repair()
    {
        $qrrFile = 'modules/Administration/QuickRepairAndRebuild.php';
        if (!file_exists($qrrFile)) {
            throw new \RuntimeException("Can't load the QuickRepairAndRebuild class from SugarCRM.");
        }

        require_once($qrrFile);
        $repair = new \RepairAndClear();
        $repair->repairAndClearAll(array('clearAll'), array(translate('LBL_ALL_MODULES')), true, false);

        //remove the js language files
        if (!method_exists('LanguageManager','removeJSLanguageFiles')) {
            $this->getLogger->warning('Could not call the removeJSLanguageFiles method. Check that everything is clean.');
        }
        \LanguageManager::removeJSLanguageFiles();
        //remove language cache files
        if (!method_exists('LanguageManager','clearLanguageCache')) {
            $this->getLogger->warning('Could not call the clearLanguageCache method. Check that everything is clean.');
        }
        \LanguageManager::clearLanguageCache();

        $this->tearDown();
    }

    /**
     * Taken from fayebsg/sugarcrm-cli
     * Useful to clean Sugar before leaving it
     */
    protected function tearDown()
    {
        sugar_cleanup(false);
        if (class_exists('DBManagerFactory')) {
            $db = \DBManagerFactory::getInstance();
            $db->disconnect();
        }
    }
}
