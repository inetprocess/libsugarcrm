<?php

namespace Inet\SugarCRM\Database;

use Psr\Log\NullLogger;

use Inet\SugarCRM\Database\Relationship;
use Inet\SugarCRM\Tests\TestsUtil\DatabaseTestCase;

/**
 * @group db
 */
class RelationshipDbTest extends DatabaseTestCase
{
    public function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            __DIR__ . '/relationships/db_base_dataset.yaml'
        );
    }

    public function testRealDb()
    {
        $meta = new Relationship(new NullLogger(), $this->getPdo());
        $db = $meta->loadFromDb();
        $meta->setDefFile(__DIR__ . '/relationships/metadata_base.yaml');
        $base = $meta->loadFromFile();
        $this->assertEquals($base, $db);
        $meta->setDefFile(__DIR__ . '/relationships/metadata_new.yaml');
        $new = $meta->loadFromFile();
        $diff = $meta->diff($base, $new);
        $meta->executeQueries($diff);
        $expected = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            __DIR__ . '/relationships/db_new_dataset.yaml'
        );
        $queryTable = $this->getConnection()
            ->createQueryTable('relationships', 'SELECT * FROM relationships ORDER BY BINARY id ASC');

        $this->assertTablesEqual($expected->getTable('relationships'), $queryTable);
    }
}
