<?php
namespace Inet\SugarCRM\Tests;

use Inet\SugarCRM\EntryPoint;
use Inet\SugarCRM\DB;
use Inet\SugarCRM\Utils;

class UtilsTest extends SugarTestCase
{
    public function testRightInstanciation()
    {
        $util = new Utils($this->getEntryPointInstance());
        $this->assertInstanceOf('Inet\SugarCRM\Utils', $util);
    }

    public function testArrayToMultiselectAndOpposite()
    {
        $util = new Utils($this->getEntryPointInstance());
        $values = array('a', 'b', '');
        $multiselect = $util->arrayToMultiselect($values);
        $this->assertInternalType('string', $multiselect);
        $this->assertEquals('^a^,^b^', $multiselect);

        $multiselect = '^a^,^b^,^^';
        $array = $util->multiselectToArray($multiselect);
        $this->assertInternalType('array', $array);
        // we get the same array back
        $this->assertEmpty(array_diff($array, $values));
    }

    public function testAddAndRemoveLabels()
    {
        $langFile = getenv('SUGARCRM_PATH') . '/custom/Extension/modules/Accounts/Ext/Language/en_us.lang.php';
        if (file_exists($langFile)) {
            unlink($langFile);
        }
        $util = new Utils($this->getEntryPointInstance());
        $addLabel = $util->addLabel('Accounts', 'en_us', 'LBL_TEST_PHPUNIT', 'Test PHPUnit');
        $this->assertTrue($addLabel);
        $this->assertFileExists($langFile);
        $this->assertContains('LBL_TEST_PHPUNIT', file_get_contents($langFile));
        $this->assertContains('Test PHPUnit', file_get_contents($langFile));

        // Remove label
        $removeLabel = $util->removeLabel('Accounts', 'en_us', 'LBL_TEST_PHPUNIT', 'Test PHPUnit');
        $this->assertTrue($removeLabel);
        $this->assertFileExists($langFile);
        $this->assertNotContains('LBL_TEST_PHPUNIT', file_get_contents($langFile));
        $this->assertNotContains('Test PHPUnit', file_get_contents($langFile));

        // Remove label non existing file
        $removeLabel = $util->removeLabel('Accounts', 'en_us', 'LBL_TEST_PHPUNIT', 'Test PHPUnit');
        $this->assertTrue($removeLabel);
    }

    public function testAddAndRemoveDropdown()
    {
        $dir = getenv('SUGARCRM_PATH') . '/custom/include/language';
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        $langFile = $dir . '/en_us.lang.php';
        file_put_contents($langFile, '');

        $util = new Utils($this->getEntryPointInstance());

        $values = array('a', 'b', 'c');
        $util->addDropdown('test_phpunit_list', $values, 'en_us');

        $this->assertFileExists($langFile);
        require($langFile);
        $this->assertInternalType('array', $GLOBALS['app_list_strings']);
        $this->assertArrayHasKey('test_phpunit_list', $GLOBALS['app_list_strings']);
        $this->assertEmpty(array_diff($GLOBALS['app_list_strings']['test_phpunit_list'], $values));

        // Now get the dropdown and make sure it's correct
        $dp = $util->getDropdown('test_phpunit_list', 'en_us');
        $this->assertEmpty(array_diff($dp, $values));

        // Wrong DP
        $dp = $util->getDropdown('wrong_test_phpunit_list', 'en_us');
        $this->assertFalse($dp);
    }
}
