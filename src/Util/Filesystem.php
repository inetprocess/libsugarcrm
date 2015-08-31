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

namespace Inet\SugarCRM\Util;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem as BaseFS;
use Symfony\Component\Finder\Finder;

class Filesystem extends BaseFS
{
    /**
     * Test is files or directories passed as arguments are empty.
     * Only valid for regular files and directories.
     *
     * @param mixed $files String or array of strings of path to files and directories.
     *
     * @return boolean True if all arguments are empty. (Size 0 for files and no children for directories).
     */
    public function isEmpty($files)
    {
        if (!$files instanceof \Traversable) {
            $files = new \ArrayObject(is_array($files) ? $files : array($files));
        }
        foreach ($files as $file) {
            if (!$this->exists($file)) {
                throw new FileNotFoundException(null, 0, null, $file);
            }
            if (is_file($file)) {
                $file_info = new \SplFileInfo($file);
                if ($file_info->getSize() !== 0) {
                    return false;
                }
            } elseif (is_dir($file)) {
                $finder = new Finder();
                $finder->in($file);
                $it = $finder->getIterator();
                $it->rewind();
                if ($it->valid()) {
                    return false;
                }
            } else {
                throw new IOException(
                    sprintf('File "%s" is not a directory or a regular file.', $file),
                    0,
                    null,
                    $file
                );
            }
        }

        return true;
    }
}
