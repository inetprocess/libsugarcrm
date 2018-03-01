<?php
/**
 * SugarCRM Tools
 *
 * PHP Version 5.3 -> 5.6
 * SugarCRM Versions 6.5 - 7.6
 *
 * @author Emmanuel Dyan
 * @copyright 2005-2018 iNet Process
 *
 * @package inetprocess/sugarcrm
 *
 * @license Apache License 2.0
 *
 * @link http://www.inetprocess.com
 */

namespace Inet\SugarCRM;

/**
 * Iterator class to iterate in a memory safe way SugarQuery results.
 * Usage:
 * $query = new \SugarQuery();
 * // setup $query
 *
 * foreach (new SugarQueryIterator($query) as $id => $bean) {
 *    // Do something with $bean
 * }
 */
class SugarQueryIterator implements \Iterator
{
    protected $retrieve_params;
    protected $query;
    protected $use_fixed_offset;
    protected $start_offset = 0;
    protected $clean_memory_on_fetch = true;

    protected $query_offset = 0;

    protected $results_cache = array();
    protected $cache_current_index = 0;

    protected $iteration_counter = 0;

    /**
     * $query A sugarCRM query to fetch record ids.
     * $retrieve_params Parameters passed to \BeanFactory::retrieveBean on each iteration
     */
    public function __construct(\SugarQuery $query, $retrieve_params = array())
    {
        $this->query = $query;
        $this->retrieve_params = $retrieve_params;
        // Set query parameters
        $this->query->select(array('id'));
        $this->setPaginationSize(100);
    }

    /**
     * Start the iteration at $offset
     */
    public function setStartOffset($offset)
    {
        $this->start_offset = $offset;
    }

    public function getStartOffset()
    {
        return $this->start_offset;
    }

    /**
     * Fetch only $size records at a time from the database
     */
    public function setPaginationSize($size)
    {
        $this->query->limit($size);
    }

    /**
     * Set to true if the results are modified during the iteration
     * in a way that they are not return on the next query call.
     * This way the iterator keep quering the same first records hoping
     * eventually the query returns no results.
     * Be carefull when setting to true as infinite loop are really easy to create.
     */
    public function useFixedOffset($fixed_offset)
    {
        $this->use_fixed_offset = $fixed_offset;
    }

    /**
     * Should the iterator try to free some memory
     * before fetching new results.
     */
    public function setCleanMemoryOnFetch($value)
    {
        $this->clean_memory_on_fetch = $value;
    }

    /**
     * Return the number of iteration. Starts at 1.
     */
    public function getIterationCounter()
    {
        return $this->iteration_counter;
    }

    /**
     * Iterator interface.
     */
    public function current()
    {
        $module = $this->query->getFromBean()->module_name;
        return \BeanFactory::retrieveBean($module, $this->key(), $this->retrieve_params);
    }

    public function key()
    {
        return $this->results_cache[$this->cache_current_index]['id'];
    }

    public function next()
    {
        $this->cache_current_index++;
        $this->query_offset++;
        $this->iteration_counter++;
        if ($this->cache_current_index > (count($this->results_cache) - 1)) {
            $this->fetchNextRecords();
        }
    }

    public function rewind()
    {
        $this->cache_current_index = 0;
        $this->results_cache = array();
        $this->query_offset = $this->getStartOffset();
        $this->iteration_counter = 1;
        $this->fetchNextRecords();
    }

    public function valid()
    {
        return !empty($this->results_cache);
    }

    /**
     * Fetch the next page of ids from the database
     */
    protected function fetchNextRecords()
    {
        if (!$this->use_fixed_offset) {
            $this->query->offset($this->query_offset);
        }
        if ($this->clean_memory_on_fetch) {
            // Attempt to clean php memory
            $this->results_cache = null;
            BeanFactoryCache::clearCache();
            gc_collect_cycles();
        }
        $this->results_cache = $this->query->execute();
        $this->cache_current_index = 0;
    }
}
