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
 * @license Apache License 2.0
 *
 * @link http://www.inetprocess.com
 */

namespace Inet\SugarCRM;

use Inet\SugarCRM\Util\Filesystem;
use Inet\SugarCRM\Exception\InstallerException;

class Installer
{
    public $source;
    public $config;

    private $sugarApp;

    private $fs;

    public function __construct(Application $sugarApp, $source = null, $config = null)
    {
        $this->sugarApp = $sugarApp;
        $this->source = $source;
        $this->config = $config;

        $this->fs = new Filesystem();
    }

    public function getApplication()
    {
        return $this->sugarApp;
    }

    public function getLogger()
    {
        return $this->getApplication()->getLogger();
    }

    public function getPath()
    {
        return $this->getApplication()->getPath();
    }

    public function getConfigTarget()
    {
        return $this->getPath() . '/config_si.php';
    }

    public function getInstallScriptPath()
    {
        return $this->getPath() . '/install.php';
    }

    /**
     * Remove sugar installation path
     */
    public function deletePath()
    {
        $this->getLogger()->info("Removing installation path {$this->getPath()}...");
        $this->fs->remove($this->getPath());
        $this->getLogger()->info("Path {$this->getPath()} was successfully removed.");
    }

    /**
     * Create Sugar installation path
     */
    public function createPath()
    {
        $this->getLogger()->info("Creating installation path {$this->getPath()}.");
        $this->fs->mkdir($this->getPath(), 0750);
    }

    /**
     * Remove the first directory level from the path.
     * Used to remove the top directory in the zip file.
     */
    public static function junkParent($path)
    {
        return preg_replace('/^\/?[^\/]+\/(.*)$/', '$1', $path);
    }

    /**
     * Extract the source archive into $this->getPath().
     * While extracting, we remove the top folder from the filename inside the archive.
     * For example, a file 'SugarPro-Full-7.2.1/soap.php' will get extracted to
     * <install_path>/soap.php .
     */
    public function extract()
    {
        $this->getLogger()->info("Extracting {$this->source} into " . $this->getPath() . '...');
        if (!is_dir($this->getPath()) || !$this->fs->isEmpty($this->getPath())) {
            throw new InstallerException(
                "The target path {$this->getPath()} is not a directory or is not empty when extracting the archive."
            );
        }
        if (!is_file($this->source)) {
            throw new InstallerException("{$this->source} doesn't exists or is not a file.");
        }

        $zip = new \ZipArchive();
        if ($zip->open($this->source) !== true) {
            throw new InstallerException("Unable to open zip {$this->source}.");
        }
        $zip_paths = array();
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $zip_paths[$i] = $zip->getNameIndex($i);
        }
        $target_paths = Installer::junkParent($zip_paths);
        foreach ($target_paths as $i => $name) {
            if (empty($name)) {
                continue;
            }

            $target_path = $this->getPath() . '/' . $name;
            // Check is name ends with '/' (directory name)
            if (strpos($name, '/', strlen($name) - 1) === false) {
                // We have a file name
                // We load each zipped file in memory.
                // It is much faster than getting the Stream handle.
                // For Sugar 7 archive we peak at 24MB so it shouldn't be an issue.
                $content = $zip->getFromIndex($i);
                if ($content === false) {
                    throw new InstallerException("Error while extracting {$name} from the archive.");
                }
                if (file_put_contents($target_path, $content) === false) {
                    throw new InstallerException("Error while writting to file {$target_path}.");
                }
            } else {
                // We have a dir name
                $this->fs->mkdir($target_path);
            }
        }

        if (!$zip->close()) {
            throw new InstallerException("Unable to close zip {$this->source}.");
        }
        $this->getLogger()->info('Extraction OK.');
    }

    public function copyConfigSi()
    {
        $this->getLogger()->info("Copying configuration file {$this->config} to ".$this->getConfigTarget().'.');
        $this->fs->copy($this->config, $this->getConfigTarget(), true);
    }

    public function deleteConfigSi()
    {
        $this->getLogger()->info('Deleting configuration file '.$this->getConfigTarget().'.');
        $this->fs->remove($this->getConfigTarget());
    }

    /**
     * Run the sugar install script. This doesn't require a web server.
     */
    public function runSugarInstaller()
    {
        $_REQUEST['goto'] = 'SilentInstall';
        $_REQUEST['cli'] = 'true';
        ob_start();
        require($this->getInstallScriptPath());
        $installer_res = ob_get_clean();
        // find the bottle message
        if (preg_match('/<bottle>(.*)<\/bottle>/s', $installer_res, $msg) === 1) {
            $this->getLogger()->info('The web installer was successfully completed.');
            $this->getLogger()->info("Web installer: {$msg[1]}");
        } elseif (preg_match('/Exit (.*)/s', $installer_res, $msg)) {
            $this->getLogger()->info("Web installer: {$msg[1]}");
            throw new InstallerException(
                'The web installer failed. Check your config_si.php file.'
            );
        } else {
            $this->getLogger()->debug("Web installer: {$installer_res}");
            throw new InstallerException(
                'The web installer failed and return an unknown error. Check the install.log file on Sugar.'
            );
        }
    }

    /**
     * Run the complete installation process.
     *
     * @param force If true then remove install directory first.
     */
    public function run($force = false)
    {
        if (!is_readable($this->config)) {
            throw new InstallerException("Missing or unreadable config_si file {$this->config}.");
        }
        $this->getLogger()->notice("Installing SugarCRM into {$this->getPath()}...");
        if ($this->fs->exists($this->getPath())) {
            if (!$this->fs->isEmpty($this->getPath())) {
                if ($force === true) {
                    $this->deletePath();
                    $this->createPath();
                } else {
                    throw new InstallerException(
                        "The target path {$this->getPath()} is not empty. "
                        ."Use --force to remove {$this->getPath()} and its contents before installing."
                    );
                }
            }
        } else {
            $this->createPath();
        }
        // At this point we should have an empty dir for path.
        $this->extract();
        $this->copyConfigSi();
        $this->runSugarInstaller();
        $this->deleteConfigSi();
        $this->getLogger()->notice('Installation complete.');
    }
}
