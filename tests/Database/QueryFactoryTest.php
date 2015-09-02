<?php

namespace Inet\SugarCRM\Database;

use Inet\SugarCRM\Tests\TestsUtil\DatabaseTestCase;
use Inet\SugarCRM\Database\QueryFactory;

/**
 * @group db
 */
class QueryFactoryTest extends DatabaseTestCase
{
    public function testGetters()
    {
        $qf = new QueryFactory(static::getPdo());
        $this->assertInstanceOf('PDO', $qf->getPdo());
    }

    public function testDrivers()
    {
        //MySQL
        $pdo = $this->getMockBuilder('Inet\SugarCRM\Tests\Database\MockPDO')
            ->getMock();
        $pdo->method('getAttribute')
            ->willReturn('mysql');
        $qf = new QueryFactory($pdo);
        $this->assertEquals('`', $qf->getIdentifierDelimiter());
        //SQLite
        $pdo = $this->getMockBuilder('Inet\SugarCRM\Tests\Database\MockPDO')
            ->getMock();
        $pdo->method('getAttribute')
            ->willReturn('sqlite');
        $qf = new QueryFactory($pdo);
        $this->assertEquals('"', $qf->getIdentifierDelimiter());
    }

    public function identifierProvider()
    {
        return array(
            array('`test`', 'test'),
            array('```foo`', '`foo'),
            array('`ba``r`', 'ba`r'),
            array('`baz```', 'baz`'),
            array('`fo````o`', 'fo``o'),
        );
    }

    /**
     * @dataProvider identifierProvider
     */
    public function testQuoteIdentifier($expected, $identifier)
    {
        $qf = new QueryFactory(static::getPdo());
        $this->assertEquals($expected, $qf->quoteIdentifier($identifier));
    }

    public function testSelectAll()
    {
        $expected_sql = "SELECT * FROM `test`";
        $qf = new QueryFactory(static::getPdo());
        $query = $qf->createSelectAllQuery('test');
        $this->assertEquals($expected_sql, $query->getRawSql());
    }

    public function testInsert()
    {
        $expected_sql = "INSERT INTO `test` (foo, bar) VALUES (1, 'baz')";
        $qf = new QueryFactory(static::getPdo());
        $query = $qf->createInsertQuery('test', array('foo' => 1, 'bar' => 'baz'));
        $this->assertEquals($expected_sql, $query->getRawSql());
    }

    public function testDelete()
    {
        $expected_sql = "DELETE FROM `test` WHERE id = '1'";
        $qf = new QueryFactory(static::getPdo());
        $query = $qf->createDeleteQuery('test', '1');
        $this->assertEquals($expected_sql, $query->getRawSql());
    }

    public function testUpdate()
    {
        $expected_sql = "UPDATE `test` SET foo = 1, bar = 'baz' WHERE id = '1'";
        $qf = new QueryFactory(static::getPdo());
        $query = $qf->createUpdateQuery('test', '1', array('foo' => 1, 'bar' => 'baz'));
        $this->assertEquals($expected_sql, $query->getRawSql());
    }
}
