<?php

namespace Inet\SugarCRM\Tests;

use Inet\SugarCRM\SugarQueryIterator;

class SugarQueryIteratorTest extends SugarTestCase
{
    protected $query;

    public function setUp()
    {
        // Load sugar clases
        $this->getEntryPointInstance()->setCurrentUser('1');

        $this->query = new \SugarQuery();
        $this->query->from(\BeanFactory::newBean('Accounts'));
    }

    public function testRewindValid()
    {
        $iter = new SugarQueryIterator($this->query);
        $iter->rewind();
        $this->assertTrue($iter->valid());
    }

    public function testFetchAllAccounts()
    {
        $this->query->limit(10);
        $results = $this->query->execute();

        $iter = new SugarQueryIterator($this->query);
        $iter->setPaginationSize(5);
        $i = 0;
        foreach ($iter as $id => $bean) {
            $i++;
            $this->assertEquals($i, $iter->getIterationCounter());
            $this->assertInternalType('string', $id);
            $this->assertInstanceOf('Account', $bean);
            $this->assertEquals($id, $bean->id);
            if ($iter->getIterationCounter() >= 10) {
                break;
            }
        }
        $this->assertGreaterThan(0, $i);
        $this->assertEquals(count($results), $i);
    }

    public function testStartOffset()
    {
        $this->query->limit(10);
        $this->query->select(array('id'));
        $results = $this->query->execute();

        $iter = new SugarQueryIterator($this->query);
        $iter->setPaginationSize(5);
        $iter->setStartOffset(5);

        $iter_results = array();
        foreach ($iter as $key => $bean) {
            $iter_results[] = array('id' => $key);
            if ($iter->getIterationCounter() >= 5) {
                break;
            }
        }
        $this->assertEquals(array_slice($results, 5), $iter_results);
    }

    public function testFixedOffset()
    {
        $iter = new SugarQueryIterator($this->query);
        $iter->useFixedOffset(true);
        $iter->setCleanMemoryOnFetch(false);
        $iter->setPaginationSize(1);

        $iter->rewind();
        $this->assertTrue($iter->valid());
        $bean_id = $iter->key();
        $iter->next();
        $this->assertTrue($iter->valid());
        $bean2_id = $iter->key();

        $this->assertEquals($bean_id, $bean2_id);
    }
}
