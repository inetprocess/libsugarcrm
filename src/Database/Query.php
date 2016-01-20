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

/**
 * Store a sql query with it's parameters to be executed later with a pdo connection.
 */
class Query
{
    protected $sql;
    protected $params;
    protected $pdo;

    public function __construct(\PDO $pdo, $sql, array $params = array())
    {
        $this->pdo = $pdo;
        $this->sql = $sql;
        $this->params = $params;
    }

    public function getSql()
    {
        return $this->sql;
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    public function getParams()
    {
        return $this->params;
    }

    /**
     * Interpolate the sql query with the parameters.
     * Do not try to execute this query directly as it can lead to SQL Injection.
     *
     * @todo Quote with pdo.
     */
    public function getRawSql()
    {
        $search = array();
        $replace = array();
        $params = $this->getParams();
        ksort($params, SORT_STRING);
        foreach ($params as $key => $value) {
            if (is_int($key)) {
                $key = '?';
            }
            $search[] = $key;
            if (is_string($value)) {
                $value = "'$value'";
            }
            $replace[] = $value;
        }
        return str_replace($search, $replace, $this->getSql());
    }

    public function execute()
    {
        $stmt = $this->getPdo()->prepare($this->getSql());
        foreach ($this->getParams() as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt;
    }
}
