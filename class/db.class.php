<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/config/constants.inc.php';

class db
{
    public $dbh;
    private static $instance;

    const NO_ERR        = -1;

    const FETCH_OBJ      = 1;
    const FETCH_STMT     = 2;
    const FETCH_ERR      = 3;
    const ROW_COUNT      = 4;
    const NO_RETURN      = 5;

    private function __construct()
    {
        //no host specified; will connect through UNIX sockets or fallback on localhost
        $this->dbh = new PDO('pgsql:dbname='.POSTGRESQL_DATA_NAME,POSTGRESQL_USER,POSTGRESQL_PASS);
        $this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
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
