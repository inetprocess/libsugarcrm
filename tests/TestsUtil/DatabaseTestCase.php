<?php

namespace Inet\SugarCRM\Tests\TestsUtil;

use PDO;

abstract class DatabaseTestCase extends \PHPUnit_Extensions_Database_TestCase
{
    static private $pdo = null;

    private $conn = null;

    final public static function getPdo()
    {
        if (self::$pdo === null) {
            $dsn = 'mysql:';
            $params[] = 'host=' . getenv('SUGARCRM_DB_HOST');
            $params[] = 'port=' . getenv('SUGARCRM_DB_PORT');
            $params[] = 'dbname=' . getenv('SUGARCRM_DB_NAME');

            $dsn .= implode(';', $params);

            self::$pdo = new PDO($dsn, getenv('SUGARCRM_DB_USER'), getenv('SUGARCRM_DB_PASSWORD'));
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$pdo;
    }

    final public function getConnection()
    {
        if ($this->conn === null) {
            $this->conn = $this->createDefaultDBConnection(self::getPdo(), getenv('SUGARCRM_DB_NAME'));
        }
        return $this->conn;
    }

    /**
     * Return an empty data set for test that require a db connexion but no data
     */
    public function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_CsvDataSet();
    }
}
