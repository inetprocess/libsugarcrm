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

/**
 * SugarCRM DB Wrapper
 *
 * @todo Unit Tests
 * @todo Replace SugarCRM's $db to \PDO ?
 */
class DB
{
    /**
     * Prefix that should be set by each class to identify it in logs
     * @var    string
     */
    protected $logPrefix;
    /**
     * Logger, inherits PSR\Log and uses Monolog
     * @var    Inet\Util\Logger
     */
    protected $log;

    /**
     * SugarCRM DB Utils
     * @var    Inet\SugarCRM\DB
     */
    protected $sugarDb;

    /**
     * Contains the list of numeric fields in SugaRCRM
     * @var    array
     */
    private static $numericFields = array('double', 'int', 'currency');


    /**
     * Set the LogPrefix to be unique and ask for an Entry Point to SugarCRM
     * @param    EntryPoint    $entryPoint    Enters the SugarCRM Folder
     */
    public function __construct(EntryPoint $entryPoint)
    {
        $this->logPrefix = __CLASS__ . ': ';
        $this->log = $entryPoint->getLogger();

        $this->sugarDb = $entryPoint->getSugarDb();
    }

    /**
     * Check if a Table exists
     * @param     [type]    $sql       [description]
     * @return    [type]               [description]
     */
    public function tableExists($table)
    {
        $res = $this->doQuery("SHOW TABLES LIKE '{$table}'");

        return (count($res) === 1 ? true : false);
    }

    /**
     * Escape a value with MySQLi tools
     * @param     string    $string    Value
     * @return    string               Escaped Value
     */
    public function escape($string)
    {
        return $this->sugarDb->database->escape_string($string);
    }


    /**
     * Do a Query (any)
     * @param     [type]    $sql       [description]
     * @return    [type]               [description]
     */
    public function doQuery($sql)
    {
        $sql = trim($sql);
        if (empty($sql)) {
            $callers = debug_backtrace();
            $msg = "Sorry I don't understand your SQL: " . PHP_EOL . $sql . PHP_EOL . PHP_EOL;
            $msg.= "I have been called by {$callers[1]['class']}::{$callers[1]['function']}";
            throw new \InvalidArgumentException($msg);
        }

        $data = array();
        $sql = preg_replace('/\s+/', ' ', $sql);
        $this->log->debug($this->logPrefix . 'Query: ' . $sql);

        $res = $this->sugarDb->query($sql, false);
        // Error in Query
        if (!empty($this->sugarDb->database->error)) {
            throw new \InvalidArgumentException("SQL Error in doQuery: {$this->sugarDb->database->error}");
        }

        // No data to send to the caller
        if (!isset($res->num_rows)) {
            return true;
        }

        // Empty result
        if ($res->num_rows === 0) {
            return array();
        }

        // data to send
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
        $res->free();

        return $data;
    }

    /**
     * Return the numeric fields defined in SugarCRM
     * @return    Array    Array of fields types
     */
    public static function getNumericFields()
    {
        return self::$numericFields;
    }
}
