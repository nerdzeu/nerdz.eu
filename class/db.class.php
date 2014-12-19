<?php
namespace NERDZ\Core;
use PDO, PDOException;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

class Db
{
    private static $instance;
    public $dbh;

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
            'pgsql:host='.Config\POSTGRESQL_HOST.';dbname='.Config\POSTGRESQL_DATA_NAME.';port='.Config\POSTGRESQL_PORT,
            Config\POSTGRESQL_USER,
            Config\POSTGRESQL_PASS);

        $this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

        // Fetch the IDs for special profiles/projects
        $cache = Config\SITE_HOST.'special-ids';
        if(!($specialIds = Utils::apc_get($cache))) {
            $me = $this;
            $specialIds = Utils::apc_set($cache, function() use ($me) {
                try
                {
                    $stmt = $this->dbh->query('SELECT * FROM special_users');
                    $userIds = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

                    $stmt = $this->dbh->query('SELECT * FROM special_groups');
                    $projectsIds = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

                    return [
                        'USER'    => $userIds,
                        'PROJECT' =>$projectsIds
                    ];
                }
                catch(PDOException $e) {
                    static::dumpException($e);
                    die($e->getTraceAsString());
                }
            }, 86400);
        }

        Config::add('USERS_NEWS',    $specialIds['USER']['GLOBAL_NEWS']);
        Config::add('DELETED_USERS', $specialIds['USER']['DELETED']);
        Config::add('ISSUE_BOARD',   $specialIds['PROJECT']['ISSUE']);
        Config::add('PROJECTS_NEWS', $specialIds['PROJECT']['GLOBAL_NEWS']);
    }

    private static function getInstance()
    {
        if(empty(static::$instance))
            static::$instance = new Db();

        return static::$instance;
    }

    public static function getDb()
    {
        return static::getInstance()->dbh;
    }

    public static function dumpException($e, $moredata = false)
    {
        System::dumpError((($moredata != false) ? "{$moredata}: " : '').$e->getMessage());
    }

    /**
     * Executes a query.
     * Its return value varies according to the $action parameter, which should
     * be a constant member of Db.
     *
     * @param string $query
     * @param int $action
     * @return null|boolean|object
     *
     */
    public static function query($query,$action = Db::NO_RETURN, $all = false)
    {
        $stmt = null; //PDO statement

        try
        {
            if(is_string($query))
                $stmt = static::getDb()->query($query);
            else
            {
                $stmt = static::getDb()->prepare($query[0]);
                $stmt->execute($query[1]);
            }
        }
        catch(PDOException $e)
        {
            if(defined('DEBUG'))
                static::dumpException($e,$_SERVER['REQUEST_URI'].', '.$e->getTraceAsString());

            if($action == static::FETCH_ERRNO) {
                return $stmt->errorInfo()[1];
            }
            if($action == static::FETCH_ERRSTR) {
                return $stmt->errorInfo()[2];
            }

            static::dumpException($e,$_SERVER['REQUEST_URI'].', '.$e->getTraceAsString());

            return null;
        }

        switch($action)
        {
        case static::FETCH_ERRNO:
            return static::NO_ERRNO;

        case static::FETCH_STMT:
            return $stmt;

        case static::FETCH_OBJ:
            return ($all === false) ? $stmt->fetch(PDO::FETCH_OBJ) : $stmt->fetchAll(PDO::FETCH_OBJ);

        case static::ROW_COUNT:
            return $stmt->rowCount();

        case static::NO_RETURN:
            return true;
        }

        return false;
    }
}

?>
