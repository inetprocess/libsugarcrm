<?php
/**
 * SugarCRM Tools
 *
 * PHP Version 5.3 -> 5.6
 * SugarCRM Versions 6.5 - 7.6
 *
 * @author Rémi Sauvat
 * @copyright 2005-2015 iNet Process
 *
 * @package inetprocess/sugarcrm
 *
 * @license GNU General Public License v2.0
 *
 * @link http://www.inetprocess.com
 */

namespace Inet\SugarCRM\Database;

use PDO;

use Inet\SugarCRM\Application;
use Inet\SugarCRM\Exception\SugarException;

class SugarPDO extends PDO
{
    protected $sugarApp;

    public function __construct(Application $sugarApp, $options = array())
    {
        $this->sugarApp = $sugarApp;
        $params = $this->getPdoParams();
        parent::__construct($params['dsn'], $params['username'], $params['password'], $options);
    }

    public function getApplication()
    {
        return $this->sugarApp;
    }

    protected function getPdoParams()
    {
        $sugar_config = $this->getApplication()->getSugarConfig();
        if (!array_key_exists('dbconfig', $sugar_config)
            || !is_array($sugar_config['dbconfig'])
        ) {
            throw new SugarException('Configuration parameter "db_config" is not an array');
        }
        $dbconfig = $this->normalizeDbParams($sugar_config['dbconfig']);

        $params = array(
            'host' => $dbconfig['db_host_name'],
            'port' => $dbconfig['db_port'],
            'dbname' => $dbconfig['db_name'],
            'charset' => 'utf8',
        );
        $dsn = 'mysql:' . http_build_query($params, null, ';');
        return array(
            'dsn' => $dsn,
            'username' => $dbconfig['db_user_name'],
            'password' => $dbconfig['db_password'],
        );
    }

    /**
     * Validate and merge with defaults for database configuration.
     * Required options: db_name, db_user_name
     * Optional: db_password, db_host_name, db_port.
     *
     * @param array $params Database configuration options
     *
     * @return array Normalized array with all options
     *
     * @throws SugarException if some required options are not present.
     */
    public function normalizeDbParams($params)
    {
        $defaults = array(
            'db_password' => '',
            'db_host_name' => 'localhost',
            'db_port' => 3306,
        );

        if (empty($params['db_name'])) {
            throw new SugarException('Missing configuration parameter "db_name".');
        }
        if (empty($params['db_user_name'])) {
            throw new SugarException('Missing configuration parameter "db_user_name".');
        }

        return array_merge($defaults, $params);
    }
}
