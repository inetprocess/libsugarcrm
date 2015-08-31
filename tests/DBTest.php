<?php
namespace Inet\SugarCRM\Tests;

use Inet\SugarCRM\EntryPoint;
use Inet\SugarCRM\DB;

class DBTest extends SugarTestCase
{
    private $sugarDB = null;

    public function rightInstanciation()
    {
        if (is_null($this->sugarDB)) {
            // first load a bean
            $entryPoint = $this->getEntryPointInstance();

            $this->sugarDB = new DB($entryPoint);
            $this->assertInstanceOf('Inet\SugarCRM\DB', $this->sugarDB);
        }

        return $this->sugarDB;
    }

    public function testTableExistsAndNotExist()
    {
        $sugarDB = $this->rightInstanciation();
        $tableUser = $sugarDB->tableExists('users');
        $this->assertTrue($tableUser);

        $tableUser = $sugarDB->tableExists('foousersfoo');
        $this->assertFalse($tableUser);
    }

    public function testExcapeString()
    {
        $sugarDB = $this->rightInstanciation();
        $escapedString = $sugarDB->escape("foo'foo");
        $this->assertInternalType('string', $escapedString);
        $this->assertEquals("foo\'foo", $escapedString);
    }


    public function testGetNumericFields()
    {
        $sugarDB = $this->rightInstanciation();
        $numericFields = $sugarDB->getNumericFields();
        $this->assertInternalType('array', $numericFields);
        $this->assertContains('int', $numericFields);
    }

    public function testDoRightQueryGetResult()
    {
        $sugarDB = $this->rightInstanciation();
        $sql = 'SELECT * FROM users WHERE id = 1';
        $result = $sugarDB->query($sql);
        $this->assertInternalType('array', $result);
        $this->assertCount(1, $result);
    }

    public function testDoRightQueryGetEmptyResult()
    {
        $sugarDB = $this->rightInstanciation();
        $sql = "SELECT * FROM users WHERE id = 'foo'";
        $result = $sugarDB->query($sql);
        $this->assertInternalType('array', $result);
        $this->assertEmpty($result);
    }

    public function testDoRightQueryGetNoResult()
    {
        $sugarDB = $this->rightInstanciation();
        $sql = "UPDATE users SET id = 1 WHERE id = 1";
        $result = $sugarDB->query($sql);
        $this->assertInternalType('bool', $result);
        $this->assertTrue($result);
    }

    /** Define a wrong folder: exception thrown
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessageRegExp #SQL Error in query#
     */
    public function testDoWrongQuery()
    {
        $sugarDB = $this->rightInstanciation();
        $sql = 'SELECT * FROM foousersfoo WHERE id = 1';
        $sugarDB->query($sql);
    }

    /** Define a wrong folder: exception thrown
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessageRegExp #Sorry I don't understand your SQL#
     */
    public function testDoEmptyQuery()
    {
        $sugarDB = $this->rightInstanciation();
        $sugarDB->query('    ');
    }
}
