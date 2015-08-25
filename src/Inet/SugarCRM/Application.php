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
 * SugarCRM Application Informations
 */
class Application
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
     * SugarCRM Directory
     * @var    string
     */
    protected $sugarDir;

    /**
     * Set the LogPrefix to be unique and ask for an Entry Point to SugarCRM
     * @param    EntryPoint    $entryPoint    Enters the SugarCRM Folder
     */
    public function __construct(EntryPoint $entryPoint)
    {
        $this->logPrefix = __CLASS__ . ': ';
        $this->log = $entryPoint->getLogger();
        $this->sugarDir = $entryPoint->getSugarDir();
    }

    /**
     * Return SugarCRM Path, from the EntryPoint
     * @return    string
     */
    public function getSugarPath()
    {
        return $this->sugarDir;
    }
}
