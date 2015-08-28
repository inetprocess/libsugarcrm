<?php
namespace Inet\SugarCRM\Tests;

use Inet\SugarCRM\EntryPoint;
use Inet\SugarCRM\DB;
use Inet\SugarCRM\Bean;
use Inet\SugarCRM\BeanFactoryCache;

class BeanFactoryCacheTest extends SugarTestCase
{
    public function testRightInstanciation()
    {
        // first load a bean
        $entryPoint = $this->getEntryPointInstance();

        // My beans are empty: I have never loaded anything
        BeanFactoryCache::clearCache();
        $loadedBeans = BeanFactoryCache::getLoadedBeans();
        $this->assertEmpty($loadedBeans);

        $DBTest = new DBTest();
        $sugarDB = $DBTest->rightInstanciation();
        $sugarBean = new Bean($entryPoint, $sugarDB);
        $sugarBean->getBean('Users', 1, array(), true, true);

        // Now it contains something
        $loadedBeans = BeanFactoryCache::getLoadedBeans();
        $this->assertNotEmpty($loadedBeans);

        // Now it's empty again
        BeanFactoryCache::clearCache();
        $loadedBeans = BeanFactoryCache::getLoadedBeans();
        $this->assertEmpty($loadedBeans);
    }
}
