<?php

namespace Inet\SugarCRM\Database;

use Psr\Log\NullLogger;

use Inet\SugarCRM\Database\Metadata;
use Inet\SugarCRM\Tests\TestsUtil\DatabaseTestCase;

/**
 * @group db
 */
class MetadataDbTest extends DatabaseTestCase
{
    public function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            __DIR__ . '/metadata/db_base_dataset.yaml'
        );
    }

    public function testRealDb()
    {
        $meta = new Metadata(new NullLogger(), $this->getPdo());
        $db = $meta->loadFromDb();
        $meta->setMetadataFile(__DIR__ . '/metadata/metadata_base.yaml');
        $base = $meta->loadFromFile();
        $this->assertEquals($base, $db);
        $meta->setMetadataFile(__DIR__ . '/metadata/metadata_new.yaml');
        $new = $meta->loadFromFile();
        $diff = $meta->diff($base, $new);
        $meta->executeQueries($diff);
        $expected = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            __DIR__ . '/metadata/db_new_dataset.yaml'
        );
        $queryTable = $this->getConnection()
            ->createQueryTable('fields_meta_data', 'SELECT * FROM fields_meta_data ORDER BY BINARY id ASC');

        $this->assertTablesEqual($expected->getTable('fields_meta_data'), $queryTable);
    }
}
