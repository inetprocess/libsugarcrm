<?php
/**
 * SugarCRM Tools
 *
 * PHP Version 5.3 -> 5.6
 * SugarCRM Versions 6.5 - 7.6
 *
 * @author Emmanuel Dyan
 * @copyright 2005-2015 iNet Process
 * @package inetprocess/sugarcrm
 * @license GNU General Public License v2.0
 * @link http://www.inetprocess.com
 */

namespace Inet\SugarCRM;

use Psr\Log\LoggerInterface;

/**
 * SugarCRM EntryPoint: Enters SugarCRM and set the current_user + all needed variables
 */
class EntryPoint
{
    /**
     * Prefix that should be set by each class to identify it in logs
     * @var    string
     */
    protected $logPrefix;
    /**
     * Must Implement at least the LoggerInterface
     * @var    LoggerInterface
     */
    protected $log;

    /**
     * SugarCRM Application
     * @var    string
     */
    protected $sugarApp;
    /**
     * Last Current working directory before changing to getPath()
     * @var    string
     */
    protected $lastCwd;
    /**
     * SugarCRM User Id to connect with
     * @var    string
     */
    protected $sugarUserId;
    /**
     * SugarCRM User Bean
     * @var    \User
     */
    protected $currentUser;

    /**
     * SugarCRM DB Object
     * @var    \MySQLi
     */
    private $sugarDb;

    /**
     * List of Beans from SugarCRM as "$key [singular] => $value [plural]"
     * @var    array
     */
    private $beanList = array();

    /**
     * Globals variables defined (save it because it's lost sometimes)
     * @var    array
     */
    private $globals = array();

    /**
     * Singleton pattern instance
     * @var Inet\SugarCrm\EntryPoint
     */
    private static $instance = null;

    /**
     * Constructor, to get the Container, then the log and config
     * @param        LoggerInterface    $log             Allow any logger extended from PSR\Log
     * @param        Application        $sugarApp
     * @param        string             $sugarUserId
     */
    private function __construct(LoggerInterface $logger, Application $sugarApp, $sugarUserId)
    {
        $this->logPrefix = __CLASS__ . ': ';
        $this->log = $logger;

        $this->sugarApp = $sugarApp;
        $this->sugarUserId = $sugarUserId;
    }

    /**
     * @return true if the EntryPoint instances is already created.
     */
    public static function isCreated()
    {
        return !is_null(self::$instance);
    }

    /**
     * Create the singleton instance only if it doesn't exists already.
     * @param        LoggerInterface    $log             Allow any logger extended from PSR\Log
     * @param        Application        $sugarApp
     * @param        string             $sugarUserId
     * @throws      \RuntimeException
     */
    public static function createInstance(LoggerInterface $logger, Application $sugarApp, $sugarUserId)
    {
        if (!is_null(self::$instance)) {
            throw new \RuntimeException('Unable to create a SugarCRM\EntryPoint more than once.');
        }
        $instance = new EntryPoint($logger, $sugarApp, $sugarUserId);
        $instance->initSugar();
        self::$instance = $instance;
    }

    /**
     * Returns EntryPoint singleton instance.
     * @throws \RuntimeException if the instance is not initiated.
     * @return    LoggerInterface
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            throw new \RuntimeException('You must first create the singleton instance with createInstance().');
        }
        self::$instance->setGlobalsFromSugar();
        self::$instance->chdirToSugarDir();
        return self::$instance;
    }

    /**
     * Returns the logger set
     * @return    LoggerInterface
     */
    public function getLogger()
    {
        return $this->log;
    }

    /**
     * Returns the Sugar Application used by this EntryPoint
     * @return    string
     */
    public function getApplication()
    {
        return $this->sugarApp;
    }

    /**
     * Alias for $this->getApplication()->getPath()
     * @return string Sugar path.
     */
    public function getPath()
    {
        return $this->getApplication()->getPath();
    }

    /**
     * Returns the last working directory before moving to getPath()
     * @return    string
     */
    public function getLastCwd()
    {
        return $this->lastCwd;
    }

    /**
     * Returns the SugarDb of this instance
     * @return    mysqli
     */
    public function getSugarDb()
    {
        return $this->sugarDb;
    }

    /**
     * Returns the Logged In user
     * @return    User
     */
    public function getCurrentUser()
    {
        return $this->currentUser;
    }

    /**
     * Set the SugarCRM current user. This user will be used for all remaining operation.
     * @param string $sugarUserId Database id of the sugar crm user.
     */
    public function setCurrentUser($sugarUserId)
    {
        // Retrieve my User
        $current_user = new \User;
        $current_user = $current_user->retrieve($sugarUserId);
        if (empty($current_user)) {
            throw new \InvalidArgumentException('Wrong User ID: ' . $sugarUserId);
        }
        $this->currentUser = $current_user;
        $this->sugarUserId = $sugarUserId;
        $this->log->info($this->logPrefix . "Changed current user to {$current_user->full_name}.");
    }

    /**
     * Returns the List Of Beans
     * @return    array
     */
    public function getBeansList()
    {
        return $this->beanList;
    }

    private function initSugar()
    {
        $callers = debug_backtrace();
        // If called by a class / method
        if (isset($callers[1]['class'])) {
            $msg = " - I have been called by {$callers[1]['class']}::{$callers[1]['function']}";
            $this->log->info($this->logPrefix . __FUNCTION__ . $msg);
        }
        $this->chdirToSugarDir();
        $this->loadSugarEntryPoint();
        $this->setCurrentUser($this->sugarUserId);
        $this->getSugarGlobals();
    }

    /**
     * Move to Sugar directory.
     * @throws \InvalidArgumentException if the folder is not a valid sugarcrm installation folder.
     */
    private function chdirToSugarDir()
    {
        if (!$this->getApplication()->isInstalled()) {
            throw new SugarException('Unable to find an installed instance of SugarCRM in :' . $this->getPath(), 1);
        }
        $this->lastCwd = realpath(getcwd());
        @chdir($this->getPath());
    }

    private function loadSugarEntryPoint()
    {
        // 1. Check that SugarEntry is not set (it could be if we have multiple instances)
        if (!defined('sugarEntry')) {
            // @codingStandardsIgnoreStart
            define('sugarEntry', true);
            // @codingStandardsIgnoreEnd
        }
        if (!defined('ENTRY_POINT_TYPE')) {
            define('ENTRY_POINT_TYPE', 'api');
        }
        // Save the variables as it is to make a diff later
        $beforeVars = get_defined_vars();

        // Define sugar variables as global (so new)
        global $sugar_config, $current_user, $system_config, $beanList, $app_list_strings;
        global $timedate, $current_entity, $locale, $current_language;

        // Sugar will not reload config.php so we need to make sure the $sugar_config global is set properly.
        $sugar_config = $this->getApplication()->getSugarConfig(true);

        // 2. Get the "autoloader"
        require_once('include/entryPoint.php');

        // Set all variables as Global to be able to access $sugar_config for example
        // Even the GLOBALS one ! Because I save it locally and it could disappear later
        $this->defineVariablesAsGlobal(
            array_merge($GLOBALS, get_defined_vars()),
            array_keys($beforeVars)
        );
    }

    /**
     * Get some GLOBALS variables from the instance
     * (such as log, directly saved as $GLOBALS['log'] are not kept correctly)
     */
    private function getSugarGlobals()
    {
        $this->sugarDb = $GLOBALS['db'];
        $this->beanList = $GLOBALS['beanList'];
        asort($this->beanList);
    }

    /**
     * Set a group of variables as GLOBALS. It's needed for SugarCRM
     * I also have to keep everything in a static variable as the GLOBALS could be reset by
     * another script (I am thinking of PHPUnit)
     * @param     array     $variables          [description]
     * @param     array     $ignoreVariables    [description]
     * @return    [type]                        [description]
     */
    private function defineVariablesAsGlobal(array $variables, array $ignoreVariables)
    {
        $ignoreVariables = array_merge(
            $ignoreVariables,
            array('_GET', '_POST', '_COOKIE', '_FILES', 'argv', 'argc', '_SERVER', 'GLOBALS', '_ENV', '_REQUEST')
        );

        if (!array_key_exists($this->getPath(), $this->globals)) {
            $this->globals = array();
        }

        foreach ($variables as $key => $value) {
            if (empty($value)) {
                // empty variable = useless
                continue;
            }
            // Ignore superglobals
            if (!in_array($key, $ignoreVariables)
              || (array_key_exists($key, $this->globals)
                  && $value != $this->globals[$key])) {
                $this->globals[$key] = $value;
            }
        }

        // Inject only new variables
        foreach ($this->globals as $key => $val) {
            if (!array_key_exists($key, $GLOBALS) || $GLOBALS[$key] != $val) {
                $GLOBALS[$key] = $val;
            }
        }
    }

    /**
     * Load stored global variables state into global state
     */
    public function setGlobalsFromSugar()
    {
        $this->defineVariablesAsGlobal(
            $this->globals,
            array()
        );
    }
}
