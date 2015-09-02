<?php
/**
 * SugarCRM Tools
 *
 * PHP Version 5.3 -> 5.6
 * SugarCRM Versions 6.5 - 7.6
 *
 * @author Rémi Sauvat
 * @copyright 2005-2015 iNet Process
 *
 * @package inetprocess/sugarcrm
 *
 * @license GNU General Public License v2.0
 *
 * @link http://www.inetprocess.com
 */

namespace Inet\SugarCRM\Database;

use PDO;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;

use Inet\SugarCRM\Exception\SugarException;

/**
 * Manage fields_meta_data table.
 */
class Metadata
{
    const TABLE_NAME = 'fields_meta_data';

    const ADD = 0;
    const DEL = 1;
    const UPDATE = 2;

    const BASE = 0;
    const MODIFIED = 1;

    const DIFF_NONE = 0;
    const DIFF_ADD = 1;
    const DIFF_DEL = 2;
    const DIFF_UPDATE = 4;
    const DIFF_ALL = 7;

    /**
     * Logger
     * @var
     */
    protected $logger;

    /**
     * Database instance
     * @var
     */
    protected $pdo;

    /**
     * Query Factory
     * @var
     */
    protected $query_factory;

    /**
     * Path of metadata definition file.
     * @var
     */
    protected $metadata_file;

    public function __construct(LoggerInterface $logger, PDO $pdo, $metadata_file = null)
    {
        $this->logger = $logger;
        $this->pdo = $pdo;
        $this->metadata_file = $metadata_file;

        $this->query_factory = new QueryFactory($this->getPdo());
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    public function getQueryFactory()
    {
        return $this->query_factory;
    }

    public function setMetadataFile($metadata_file)
    {
        $this->metadata_file = $metadata_file;
        return $this;
    }

    /**
     * Fetch metadata array from the sugar database.
     */
    public function loadFromDb()
    {
        $this->getLogger()->debug('Reading fields_meta_data from DB.');
        $query = $this->getQueryFactory()->createSelectAllQuery(self::TABLE_NAME);
        $res = $query->execute();
        $fields = array();
        foreach ($res->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $fields[$row['id']] = $row;
        }
        ksort($fields);
        return $fields;
    }

    /**
     * Fetch metadata array from the definition file
     */
    public function loadFromFile()
    {
        $this->getLogger()->debug('Reading metadata from ' . $this->metadata_file);
        $fields = Yaml::parse($this->metadata_file);
        if (!is_array($fields)) {
            $fields = array();
            $this->getLogger()->warning('No definition found in metadata file.');
        }
        $res = array();
        foreach ($fields as $field_data) {
            $res[$field_data['id']] = $field_data;
        }
        ksort($res);
        return $res;
    }

    /**
     * Compute the difference between two metadata arrays.
     * @param $base Base or old array.
     * @param $new New array with new definitions.
     * @param $add If true find new fields. Default: true
     * @param $del If true find fields to delete. Default: true
     * @param $update if true find modified fields; Default: true
     * @param $field_ids Array for field name to filter the results.
     * @return array Return a 3-row array for add, del and update fields.
     */
    public function diff($base, $new, $mode = self::DIFF_ALL, array $field_ids = array())
    {
        if (!empty($field_ids)) {
            $field_ids = array_flip($field_ids);
            $base = array_intersect_key($base, $field_ids);
            $new = array_intersect_key($new, $field_ids);
        }
        $res = array(
            self::ADD => array(),
            self::DEL => array(),
            self::UPDATE => array()
        );
        if ($mode & self::DIFF_ADD) {
            $res[self::ADD] = array_diff_key($new, $base);
        }
        if ($mode & self::DIFF_DEL) {
            $res[self::DEL] = array_diff_key($base, $new);
        }
        if ($mode & self::DIFF_UPDATE) {
            // Update array will have common fields with different data.
            $common = array_intersect_key($new, $base);
            foreach ($common as $field_name => $new_field_data) {
                $new_data = array_diff_assoc($new_field_data, $base[$field_name]);
                if (!empty($new_data)) {
                    $res[self::UPDATE][$field_name][self::BASE] = $base[$field_name];
                    $res[self::UPDATE][$field_name][self::MODIFIED] = $new_data;
                }
            }
        }
        return $res;
    }

    /**
     * Build Query for add field
     */
    public function createAddQuery(array $field_data)
    {
        return $this->getQueryFactory()
            ->createInsertQuery(self::TABLE_NAME, $field_data);
    }

    /**
     * Build query for fields to delete
     */
    public function createDeleteQuery(array $field_data)
    {
        return $this->getQueryFactory()
            ->createDeleteQuery(self::TABLE_NAME, $field_data['id']);
    }

    /**
     *  Build query to update fields.
     */
    public function createUpdateQuery(array $field_data)
    {
        return $this->getQueryFactory()
            ->createUpdateQuery(self::TABLE_NAME, $field_data[self::BASE]['id'], $field_data[self::MODIFIED]);
    }

    /**
     * Get the sql query string for a query.
     */
    public function getSqlQuery(Query $query)
    {
        return $query->getRawSql();
    }

    /**
     * Get the queries for a diff result
     */
    public function generateQueries(array $diff_res)
    {
        $queries = array();
        foreach ($diff_res[self::ADD] as $new_field) {
            $queries[] = $this->createAddQuery($new_field);
        }
        foreach ($diff_res[self::DEL] as $del_field) {
            $queries[] = $this->createDeleteQuery($del_field);
        }
        foreach ($diff_res[self::UPDATE] as $mod_field) {
            $queries[] = $this->createUpdateQuery($mod_field);
        }
        return $queries;
    }

    /**
     * Get the all the sql queries for a diff result.
     */
    public function generateSqlQueries(array $diff_res)
    {
        $queries = $this->generateQueries($diff_res);
        $sql = '';
        foreach ($queries as $query) {
            $sql .= $this->getSqlQuery($query) . ";\n";
        }
        return $sql;
    }

    /**
     * Execute DB queries for a diff result
     */
    public function executeQueries(array $diff_res)
    {
        $this->getLogger()->debug('Running sql queries.');
        $queries = $this->generateQueries($diff_res);
        foreach ($queries as $query) {
            $query->execute();
        }
    }

    /**
     * Merge base metadata array with modifications from diff result
     */
    public function getMergedMetadata(array $base, array $diff_res)
    {
        $res = $base + $diff_res[self::ADD];
        $res = array_diff_key($res, $diff_res[self::DEL]);
        foreach ($diff_res[self::UPDATE] as $field_id => $values) {
            $new_values = array_merge($values[self::BASE], $values[self::MODIFIED]);
            $res[$field_id] = $new_values;
        }
        return $res;
    }

    /**
     * Write to file
     */
    public function writeFile(array $diff_res)
    {
        $base = array();
        if (is_readable($this->metadata_file)) {
            $base = $this->loadFromFile();
        }
        $merged_data = $this->getMergedMetadata($base, $diff_res);
        ksort($merged_data);
        $yaml = Yaml::dump(array_values($merged_data));
        if (@file_put_contents($this->metadata_file, $yaml) === false) {
            throw new SugarException("Unable to dump metadata file to {$this->metadata_file}.");
        }
    }
}