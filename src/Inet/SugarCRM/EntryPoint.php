<?php
/**
 * InetETL
 *
 * PHP Version 5.3 -> 5.6
 * SugarCRM Versions 6.5 - 7.6
 *
 * @author Emmanuel Dyan
 * @copyright 2005-2015 iNet Process
 * @package iNetETL
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
     * SugarCRM Directory
     * @var    string
     */
    protected $sugarDir;
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
    private static $sugarDb = array();

    /**
     * List of Beans from SugarCRM as "$key [singular] => $value [plural]"
     * @var    array
     */
    private static $beanList = array();

    /**
     * Globals variables defined (save it because it's lost sometimes)
     * @var    array
     */
    private static $globals = array();

    /**
     * Constructor, to get the Container, then the log and config
     * @param        LoggerInterface    $log             Allow any logger extended from PSR\Log
     * @param        string             $sugarDir
     * @param        string             $sugarUserId
     */
    public function __construct(LoggerInterface $logger, $sugarDir, $sugarUserId)
    {
        $this->logPrefix = __CLASS__ . ': ';
        $this->log = $logger;

        $this->sugarDir = $sugarDir;
        $this->sugarUserId = $sugarUserId;

        $callers = debug_backtrace();
        $msg = " - I have been called by {$callers[1]['class']}::{$callers[1]['function']}";
        $this->log->info($this->logPrefix . __FUNCTION__ . $msg);
        // Go Into Sugar
        // 1. Enter the folder
        if (!file_exists($this->sugarDir . '/include/entryPoint.php')) {
            throw new \InvalidArgumentException('Wrong SugarCRM folder: ' . $this->sugarDir, 1);
        }
        @chdir($this->sugarDir);

        // 2. Check that SugarEntry is not set (it could be if we have multiple instances)
        if (!defined('sugarEntry')) {
            define('sugarEntry', true);
        }
        if (!defined('ENTRY_POINT_TYPE')) {
            define('ENTRY_POINT_TYPE', 'api');
        }

        // Save the variables as it is to make a diff later
        $beforeVars = get_defined_vars();

        // Define sugar variables as global (so new)
        global $sugar_config, $current_user, $system_config, $beanList, $app_list_strings;
        global $timedate, $current_entity, $locale, $current_language;

        // 3. Get the "autoloader"
        require_once('include/entryPoint.php');

        // Set all variables as Global to be able to access $sugar_config for example
        // Even the GLOBALS one ! Because I save it locally and it could disappear later
        $this->defineVariablesAsGlobal(
            array_merge($GLOBALS, get_defined_vars()),
            array_keys($beforeVars)
        );
        // 4. Retrieve my User
        $current_user = new \User;
        $current_user = $current_user->retrieve($this->sugarUserId);
        if (empty($current_user)) {
            throw new \InvalidArgumentException('Wrong User ID: ' . $sugarUserId);
        }
        $this->currentUser = $current_user;
        $this->log->info($this->logPrefix . "Retrieving {$current_user->full_name} to do everything with it");

        // Finally some GLOBALS variables (such as log, directly saved as $GLOBALS['log']) are not kept correctly
        if (!array_key_exists($this->sugarDir, self::$sugarDb)) {
            self::$sugarDb[$this->sugarDir] = $GLOBALS['db'];
        }
        if (!array_key_exists($this->sugarDir, self::$beanList)) {
            self::$beanList[$this->sugarDir] = $GLOBALS['beanList'];
            asort(self::$beanList[$this->sugarDir]);
        }
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
     * Returns the Sugar Dir where the entryPoint entered
     * @return    string
     */
    public function getSugarDir()
    {
        return $this->sugarDir;
    }

    /**
     * Returns the Sugar Dir where the entryPoint entered
     * @return    mysqli
     */
    public function getSugarDb()
    {
        return self::$sugarDb[$this->sugarDir];
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
     * Returns the List Of Beans
     * @return    array
     */
    public function getBeansList()
    {
        return self::$beanList[$this->sugarDir];
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

        if (!array_key_exists($this->sugarDir, self::$globals)) {
            self::$globals[$this->sugarDir] = array();
        }

        foreach ($variables as $key => $value) {
            if (empty($value)) {
                // empty variable = useless
                continue;
            }
            // Ignore superglobals
            if (!in_array($key, $ignoreVariables)
              || (array_key_exists($key, self::$globals[$this->sugarDir])
                  && $value != self::$globals[$this->sugarDir][$key])) {
                self::$globals[$this->sugarDir][$key] = $value;
            }
        }

        // Inject only new variables
        foreach (self::$globals[$this->sugarDir] as $key => $val) {
            if (!array_key_exists($key, $GLOBALS) || $GLOBALS[$key] != $val) {
                $GLOBALS[$key] = $val;
            }
        }
    }
}
