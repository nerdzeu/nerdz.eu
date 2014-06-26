<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/config/constants.inc.php';

class db
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
        $this->dbh = new PDO('pgsql:host='.POSTGRESQL_HOST.';dbname='.POSTGRESQL_DATA_NAME.';port='.POSTGRESQL_PORT, POSTGRESQL_USER, POSTGRESQL_PASS);
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

        define('USERS_NEWS', $specialIds['GLOBAL_NEWS']);
        define('DELETED_USERS',$specialIds['DELETED']);

        try
        {
            $stmt = $this->dbh->query('SELECT * FROM special_groups');
            $specialIds = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        }
        catch(PDOException $e) {
            die($e->getTraceAsString());
        }

        define('ISSUE_BOARD',$specialIds['ISSUE']);
        define('PROJECTS_NEWS',$specialIds['GLOBAL_NEWS']);
    }

    private static function getInstance()
    {
        if(empty(self::$instance))
            self::$instance = new db();
        
        return self::$instance;
    }

    public static function getDB()
    {
        return self::getInstance()->dbh;
    }
}

?>
