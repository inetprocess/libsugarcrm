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

/**
 * SugarCRM Application Informations
 */
class Application
{
    /**
     * SugarCRM Directory
     * @var    string
     */
    protected $path;

    /**
     * SugarCRM configuration array.
     */
    protected $config;

    /**
     * @param    string    $path    Path of SugarCrm installation directory.
     */
    public function __construct($path)
    {
        $this->path = realpath($path) ?: $path;
    }

    /**
     * Return SugarCRM Path
     * @return    string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Check if the path is a valid sugar installation.
     * @return true if a sugar_version.php file is present in the path.
     */
    public function isValid()
    {
        return is_file($this->getPath() . '/sugar_version.php');
    }

    /**
     * Check if the Sugar application is installed
     * return true if $sugar_config['installer_locked'] = true; in config.php
     */
    public function isInstalled()
    {
        try {
            $sugarConfig = $this->getSugarConfig();
            if (array_key_exists('installer_locked', $sugarConfig)) {
                return $sugarConfig['installer_locked'];
            }
        } catch (SugarException $e) {
        }
        return false;
    }


    /**
     * Reset configuration cache
     */
    public function clearConfigCache()
    {
        $this->config = null;
    }

    /**
     *  Load SugarCrm configuration into internal cache.
     *  @param boolean $clearCache If true clear cache to fetch latest version of configuration.
     *  @return array Cached configuration from SugarCRM
     */
    public function getSugarConfig($clearCache = false)
    {
        if ($clearCache) {
            $this->clearConfigCache();
        }
        if ($this->config == null) {
            $path = $this->getPath();
            if ($this->isValid() and is_file($path . '/config.php')) {
                require($path . '/config.php');
                if (!isset($sugar_config) or !is_array($sugar_config)) {
                    throw new SugarException("Invalid sugarcrm configuration file at '$path/config.php'");
                }
                if (is_file($path . '/config_override.php')) {
                    require($path . '/config_override.php');
                }
                $this->config = $sugar_config;
            } else {
                throw new SugarException("'$path' is not a valid sugar installation.");
            }
        }
        return $this->config;
    }

    /**
     * Validate and merge with defaults for database configuration.
     * Required options: db_name, db_user_name
     * Optional: db_password, db_host_name, db_port.
     *
     * @param array $params Database configuration options
     * @return array Normalized array with all options
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

    /**
     * Get the version information from SugarCRM.
     * @return array Version of sugar instance.
     * @throws SugarException if the path is not valid.
     */
    public function getVersion()
    {
        if (!$this->isValid()) {
            throw new SugarException("{$this->path} is not a valid sugar installation.");
        }
        if (!defined('sugarEntry')) {
            // @codingStandardsIgnoreStart
            define('sugarEntry', true);
            // @codingStandardsIgnoreEnd
        }
        require($this->path . '/sugar_version.php');

        $version = array(
            'version' => $sugar_version,
            'db_version' => $sugar_db_version,
            'flavor' => $sugar_flavor,
            'build' => $sugar_build,
            'build_timestamp' => $sugar_timestamp,
        );
        return $version;
    }
}
