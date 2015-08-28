<?php
/**
 * Sort arrays inside sugarcrm lang files.
 */

namespace Inet\SugarCRM;

use Symfony\Component\Finder\Finder;

class LangFileCleaner
{

    public $sugarApp;

    public function __construct(Application $sugarApp)
    {
        $this->sugarApp = $sugarApp;
    }

    public function getLogger()
    {
        return $this->getApplication()->getLogger();
    }

    public function getApplication()
    {
        return $this->sugarApp;
    }

    /**
     * Clean all sugar language files.
     */
    public function clean($sort = true, $test = false)
    {
        $finder = new Finder();
        $finder->files()
            ->in($this->getApplication()->getPath())
            ->path('/^custom\/include\/language/')
            ->depth('== 3')
            ->name('*.lang.php');
        $found_one = false;
        foreach ($finder as $file) {
            $this->getLogger()->notice('Processing file ' . $file);
            $found_one = true;
            $content = file_get_contents($file);
            if ($content === false) {
                throw new \Exception('Unable to load the file contents of ' . $file . '.');
            }
            $lang = new LangFile($this->getLogger(), $content, $test);
            file_put_contents($file, $lang->getSortedFile($sort));
        }
        if (!$found_one) {
            $this->getLogger()->notice('No lang files found to process.');
            return false;
        }
        return true;
    }
}
