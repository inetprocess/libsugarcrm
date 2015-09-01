<?php

namespace Inet\SugarCRM\Database;

use Inet\SugarCRM\Database\Metadata;

use Inet\SugarCRM\Tests\TestsUtil\DatabaseTestCase;
use Inet\SugarCRM\Tests\TestsUtil\TestLogger;

/**
 * @group db
 */
class MetadataTest extends DatabaseTestCase
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
        return __DIR__ . '/metadata/' . $name . '.yaml';
    }

    public function setUp()
    {
        parent::setUp();

        $logger = new TestLogger();
        $this->meta = new Metadata($logger, $this->getPdo());
        $this->meta->setMetadataFile($this->getYamlFilename(self::METADATA_BASE));
        $this->base = $this->meta->loadFromFile();
        $this->meta->setMetadataFile($this->getYamlFilename(self::METADATA_NEW));
        $this->new = $this->meta->loadFromFile();

    }

    public function testEmptyMetadata()
    {
        $logger = new TestLogger();
        $this->meta = new Metadata($logger, $this->getPdo());
        $this->meta->setMetadataFile($this->getYamlFilename('empty'));
        $this->assertEmpty($this->meta->loadFromFile());
        $this->assertEquals(
            "[warning] No definition found in metadata file.\n",
            $logger->getLines()
        );

    }

    public function testDiffFull()
    {
        $diff = $this->meta->diff($this->base, $this->new);

        $expected[Metadata::ADD]['field4']['id'] = 'field4';
        $expected[Metadata::ADD]['field4']['name'] = 'foobar';

        $expected[Metadata::DEL]['field1']['id'] = 'field1';
        $expected[Metadata::DEL]['field1']['name'] = 'foo';

        $expected[Metadata::UPDATE]['field2'][Metadata::BASE]['id'] = 'field2';
        $expected[Metadata::UPDATE]['field2'][Metadata::BASE]['name'] = 'bar';
        $expected[Metadata::UPDATE]['field2'][Metadata::MODIFIED]['name'] = 'baz';

        $this->assertEquals($expected, $diff);
    }

    public function testDiffMerge()
    {
        $diff = $this->meta->diff($this->base, $this->new);
        $merged_data = $this->meta->getMergedMetadata($this->base, $diff);
        $this->assertEquals($this->new, $merged_data);
    }

    public function testDiffEmpty()
    {
        $diff = $this->meta->diff($this->base, $this->new, Metadata::DIFF_NONE);
        $expected = array(
            Metadata::ADD => array(),
            Metadata::DEL => array(),
            Metadata::UPDATE => array()
        );
        $this->assertEquals($expected, $diff);
    }

    public function testDiffFilter()
    {
        $diff = $this->meta->diff(
            $this->base,
            $this->new,
            Metadata::DIFF_ADD | Metadata::DIFF_UPDATE,
            array('field4', 'field1')
        );
        $expected = array(
            Metadata::ADD => array(),
            Metadata::DEL => array(),
            Metadata::UPDATE => array()
        );
        $expected[Metadata::ADD]['field4']['id'] = 'field4';
        $expected[Metadata::ADD]['field4']['name'] = 'foobar';
        $this->assertEquals($expected, $diff);
    }

    public function testSorted()
    {
        $this->meta->setMetadataFile($this->getYamlFilename('unsorted'));
        $unsorted = $this->meta->loadFromFile();
        $expected_array = <<<EOA
array (
  'field1' => 
  array (
    'id' => 'field1',
    'name' => 'foo',
  ),
  'field2_test' => 
  array (
    'id' => 'field2_test',
    'name' => 'bar',
  ),
  'field_test' => 
  array (
    'id' => 'field_test',
    'name' => 'bar',
  ),
)
EOA;

        $this->assertEquals($expected_array, var_export($unsorted, true));
    }

    public function testTestQueryBuilder()
    {
        $diff = $this->meta->diff($this->base, $this->new);
        $sql = $this->meta->generateSqlQueries($diff);

        $expected_sql = <<<SQL
INSERT INTO fields_meta_data (id, name) VALUES ('field4', 'foobar');
DELETE FROM fields_meta_data WHERE id = 'field1';
UPDATE fields_meta_data SET name = 'baz' WHERE id = 'field2';

SQL;
        $this->assertEquals($expected_sql, $sql);
    }

    public function testWriteFile()
    {
        $test_path = $this->getYamlFilename('test_write');
        $diff = $this->meta->diff($this->base, $this->new);
        $this->meta->setMetadataFile($test_path);
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
        $this->meta->setMetadataFile('/dev/full');
        $this->meta->writeFile($diff);
    }
}
