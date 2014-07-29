<?php
namespace NERDZ\Core;
use PDO;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

class Db
{
    public $dbh;
    private static $instance;

    const NO_ERRSTR      = '';
    const NO_ERRNO       = -1;

    const FETCH_OBJ      = 1;
    const FETCH_STMT     = 2;
    const FETCH_ERRNO    = 3;
    const ROW_COUNT      = 4;
    const NO_RETURN      = 5;
    const FETCH_ERRSTR   = 6;

    private function __construct()
    {
        $this->dbh = new PDO(
            'pgsql:host='.Config\POSTGRESQL_HOST.
            ';dbname='.Config\POSTGRESQL_DATA_NAME.
            ';port='.Config\POSTGRESQL_PORT,
            Config\POSTGRESQL_USER,
            Config\POSTGRESQL_PASS
        );

        $this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

        // Fetch the IDs for special profiles/projects
        $specialIds = null;
        try
        {
            $stmt = $this->dbh->query('SELECT * FROM special_users');
            $specialIds = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        }
        catch(PDOException $e) {
            die($e->getTraceAsString());
        }

        Config::add('USERS_NEWS', $specialIds['GLOBAL_NEWS']);
        Config::add('DELETED_USERS',$specialIds['DELETED']);

        try
        {
            $stmt = $this->dbh->query('SELECT * FROM special_groups');
            $specialIds = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        }
        catch(PDOException $e) {
            die($e->getTraceAsString());
        }

        Config::add('ISSUE_BOARD',$specialIds['ISSUE']);
        Config::add('PROJECTS_NEWS',$specialIds['GLOBAL_NEWS']);
    }

    private static function getInstance()
    {
        if(empty(self::$instance))
            self::$instance = new Db();
        
        return self::$instance;
    }

    public static function getDB()
    {
        return self::getInstance()->dbh;
    }
}

?>
