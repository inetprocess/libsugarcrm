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
 * Manage relationships table.
 */
class Relationship extends AbstractTablesDiff
{
    protected $tableName = 'relationships';

    /**
     * Fetch relationships array from the sugar database.
     */
    public function loadFromDb()
    {
        $this->getLogger()->debug('Reading relationships from DB.');
        $query = $this->getQueryFactory()->createSelectAllQuery($this->tableName);
        $res = $query->execute();
        $rels = array();
        foreach ($res->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $rels[$row['relationship_name']] = $row;
        }
        ksort($rels);

        return $rels;
    }

    /**
     * Fetch relationships array from the definition file
     */
    public function loadFromFile()
    {
        $this->getLogger()->debug('Reading relationships from ' . $this->defFile);
        $relsFromFile = Yaml::parse($this->defFile);
        if (!is_array($relsFromFile)) {
            $relsFromFile = array();
            $this->getLogger()->warning('No definition found in relationships file.');
        }
        $rels = array();
        foreach ($relsFromFile as $relsData) {
            $rels[$relsData['relationship_name']] = $relsData;
        }
        ksort($rels);

        return $rels;
    }

    /**
     * Compute the difference between two metadata arrays.
     * That method is overriden because else the id is taken into account
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
        $res = parent::diff($base, $new, $mode, $field_ids);

        // For update, be careful to ignore the ID if it's the only difference
        foreach ($res[self::UPDATE] as $key => $data) {
            $fields = $data[self::MODIFIED];
            if (count($fields) === 1 && array_key_exists('id', $fields)) {
                unset($res[self::UPDATE][$key]);
            }
        }

        return $res;
    }
}
