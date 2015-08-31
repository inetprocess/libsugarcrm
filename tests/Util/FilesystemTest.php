<?php

namespace Inet\SugarCRM\Tests\Util;

use Inet\SugarCRM\Util\Filesystem;

class FilesystemTest extends \PHPUnit_Framework_TestCase
{
    public function testIsEmptyFile()
    {
        $empty_file = __DIR__ . '/empty';
        $fs = new Filesystem();
        if ($fs->exists($empty_file)) {
            $fs->remove($empty_file);
        }
        $fs->touch($empty_file);
        $this->assertFalse($fs->isEmpty(__FILE__));
        $this->assertTrue($fs->isEmpty($empty_file));
        $fs->remove($empty_file);
    }

    public function testIsEmptyDir()
    {
        $empty_dir = __DIR__ . '/empty';
        $fs = new Filesystem();
        if ($fs->exists($empty_dir)) {
            $fs->remove($empty_dir);
        }
        $fs->mkdir($empty_dir);
        $this->assertFalse($fs->isEmpty(__DIR__));
        $this->assertTrue($fs->isEmpty($empty_dir));
        $fs->remove($empty_dir);
    }

    public function testArray()
    {
        $empty_file = __DIR__ . '/empty';
        $fs = new Filesystem();
        if ($fs->exists($empty_file)) {
            $fs->remove($empty_file);
        }
        $fs->touch($empty_file);
        $empty_dir = __DIR__ . '/empty';
        $fs = new Filesystem();
        if ($fs->exists($empty_dir)) {
            $fs->remove($empty_dir);
        }
        $fs->mkdir($empty_dir);
        $this->assertTrue($fs->isEmpty(array($empty_dir, $empty_file)));
        $fs->remove($empty_file);
        $fs->remove($empty_dir);
    }

    /**
     * @expectedException Symfony\Component\Filesystem\Exception\FileNotFoundException
     */
    public function testUnknownFile()
    {
        $fs = new Filesystem();
        $fs->isEmpty('test_unknown_file');
    }

    /**
     * @expectedException Symfony\Component\Filesystem\Exception\IOException
     * @requires OS Linux
     */
    public function testInvalidFileType()
    {
        $fs = new Filesystem();
        $fs->isEmpty('/dev/null');
    }
}
