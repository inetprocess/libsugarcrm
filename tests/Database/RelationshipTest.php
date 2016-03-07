<?php

namespace Inet\SugarCRM\Database;

use Inet\SugarCRM\Database\Relationship;

use Inet\SugarCRM\Tests\TestsUtil\DatabaseTestCase;
use Inet\SugarCRM\Tests\TestsUtil\TestLogger;

/**
 * @group db
 */
class RelationshipTest extends DatabaseTestCase
{
    protected $meta = null;
    protected $base = null;
    protected $new = null;

    const DB_BASE_DATASET = 'db_base_dataset';
    const DB_NEW_DATASET = 'db_new_dataset';
    const METADATA_BASE = 'base';
    const METADATA_NEW = 'new';

    public function getYamlFilename($name)
    {
        return __DIR__ . '/relationships/' . $name . '.yaml';
    }

    public function setUp()
    {
        parent::setUp();

        $logger = new TestLogger();
        $this->meta = new Relationship($logger, $this->getPdo());
        $this->meta->setDefFile($this->getYamlFilename(self::METADATA_BASE));
        $this->base = $this->meta->loadFromFile();
        $this->meta->setDefFile($this->getYamlFilename(self::METADATA_NEW));
        $this->new = $this->meta->loadFromFile();

    }

    public function testEmptyRelationship()
    {
        $logger = new TestLogger();
        $this->meta = new Relationship($logger, $this->getPdo());
        $this->meta->setDefFile($this->getYamlFilename('empty'));
        $this->assertEmpty($this->meta->loadFromFile());
        $this->assertEquals(
            "[warning] No definition found in relationships file.\n",
            $logger->getLines()
        );

    }

    public function testDiffFull()
    {
        $diff = $this->meta->diff($this->base, $this->new);
        $expected[Relationship::ADD]['test2']['id'] = 2;
        $expected[Relationship::ADD]['test2']['relationship_name'] = 'test2';

        $expected[Relationship::DEL]['test1']['id'] = 1;
        $expected[Relationship::DEL]['test1']['relationship_name'] = 'test1';

        $expected[Relationship::UPDATE]['same'][Relationship::BASE]['id'] = 3;
        $expected[Relationship::UPDATE]['same'][Relationship::BASE]['relationship_name'] = 'same';
        $expected[Relationship::UPDATE]['same'][Relationship::BASE]['lhs_module'] = 'old';
        $expected[Relationship::UPDATE]['same'][Relationship::MODIFIED]['lhs_module'] = 'new';

        $this->assertEquals($expected, $diff);
    }

    public function testDiffMerge()
    {
        $diff = $this->meta->diff($this->base, $this->new);
        $merged_data = $this->meta->getMergedData($this->base, $diff);
        $this->assertEquals($this->new, $merged_data);
    }

    public function testDiffEmpty()
    {
        $diff = $this->meta->diff($this->base, $this->new, Relationship::DIFF_NONE);
        $expected = array(
            Relationship::ADD => array(),
            Relationship::DEL => array(),
            Relationship::UPDATE => array()
        );
        $this->assertEquals($expected, $diff);
    }

    public function testSorted()
    {
        $this->meta->setDefFile($this->getYamlFilename('unsorted'));
        $unsorted = $this->meta->loadFromFile();

        $expected_array = array(
            'a' => array(
                'id' => 1,
                'relationship_name' => 'a',
            ),
            'b' => array(
                'id' => 2,
                'relationship_name' => 'b',
            )
        );

        $this->assertEquals(var_export($expected_array, true), var_export($unsorted, true));
    }

    public function testTestQueryBuilder()
    {
        $diff = $this->meta->diff($this->base, $this->new);
        $sql = $this->meta->generateSqlQueries($diff);

        $expected_sql = <<<SQL
INSERT INTO `relationships` (`id`, `relationship_name`) VALUES (2, 'test2');
DELETE FROM `relationships` WHERE id = 1;
UPDATE `relationships` SET `lhs_module` = 'new' WHERE id = 3;

SQL;
        $this->assertEquals($expected_sql, $sql);
    }

    public function testWriteFile()
    {
        $test_path = $this->getYamlFilename('test_write');
        $diff = $this->meta->diff($this->base, $this->new);
        $this->meta->setDefFile($test_path);
        $this->meta->writeFile($diff);
        $this->assertFileEquals($this->getYamlFilename('merged'), $test_path);
        unlink($test_path);
    }

    /**
     * @requires OS Linux
     * @expectedException Inet\SugarCRM\Exception\SugarException
     */
    public function testWriteToWrongFile()
    {
        $diff = $this->meta->diff($this->base, $this->new);
        $this->meta->setDefFile('/dev/full');
        $this->meta->writeFile($diff);
    }
}
