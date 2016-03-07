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
 * @license Apache License 2.0
 *
 * @link http://www.inetprocess.com
 */

namespace Inet\SugarCRM\Database;

use Symfony\Component\Yaml\Yaml;

/**
 * Manage fields_meta_data table.
 */
class Metadata extends AbstractTablesDiff
{
    protected $tableName = 'fields_meta_data';

    /**
     * Fetch metadata array from the sugar database.
     */
    public function loadFromDb()
    {
        $this->getLogger()->debug("Reading {$this->tableName} from DB.");
        $query = $this->getQueryFactory()->createSelectAllQuery($this->tableName);
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
        $this->getLogger()->debug('Reading metadata from ' . $this->defFile);
        $fields = Yaml::parse($this->defFile);
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
}
