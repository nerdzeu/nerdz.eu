<?php
/*
Copyright (C) 2016 Paolo Galeone <nessuno@nerdz.eu>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace NERDZ\Core;

use PDO;
use PDOException;

require_once __DIR__.'/Autoload.class.php';

class Db
{
    private static $instance;
    public $dbh;

    const NO_ERRSTR = '';
    const NO_ERRNO = -1;

    const FETCH_OBJ = 1;
    const FETCH_STMT = 2;
    const FETCH_ERRNO = 3;
    const ROW_COUNT = 4;
    const NO_RETURN = 5;
    const FETCH_ERRSTR = 6;

    private function __construct()
    {
        $this->dbh = new PDO(
            'pgsql:host='.Config\POSTGRESQL_HOST.';dbname='.Config\POSTGRESQL_DATA_NAME.';port='.Config\POSTGRESQL_PORT,
            Config\POSTGRESQL_USER,
            Config\POSTGRESQL_PASS
        );

        $this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch the IDs for special profiles/projects
        $cache = Config\SITE_HOST.'special-ids';
        if (!($specialIds = Utils::apcu_get($cache))) {
            $me = $this;
            $specialIds = Utils::apcu_set($cache, function () use ($me) {
                try {
                    $stmt = $this->dbh->query('SELECT * FROM special_users');
                    $userIds = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

                    $stmt = $this->dbh->query('SELECT * FROM special_groups');
                    $projectsIds = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

                    return [
                        'USER' => $userIds,
                        'PROJECT' => $projectsIds,
                    ];
                } catch (PDOException $e) {
                    static::dumpException($e);
                    die($e->getTraceAsString());
                }
            }, 86400);
        }

        Config::add('USERS_NEWS', $specialIds['USER']['GLOBAL_NEWS']);
        Config::add('DELETED_USERS', $specialIds['USER']['DELETED']);
        Config::add('ISSUE_BOARD', $specialIds['PROJECT']['ISSUE']);
        Config::add('PROJECTS_NEWS', $specialIds['PROJECT']['GLOBAL_NEWS']);
    }

    private static function getInstance()
    {
        if (empty(static::$instance)) {
            static::$instance = new self();
        }

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
     * @param int    $action
     *
     * @return null|bool|object
     */
    public static function query($query, $action = self::NO_RETURN, $all = false)
    {
        $stmt = null; //PDO statement

        try {
            if (is_string($query)) {
                $stmt = static::getDb()->query($query);
            } else {
                $stmt = static::getDb()->prepare($query[0]);
                $stmt->execute($query[1]);
            }
        } catch (PDOException $e) {

            static::dumpException($e, $_SERVER['REQUEST_URI'].', '.$e->getTraceAsString());

            if ($action == static::FETCH_ERRNO && $stmt !== null) {
                return $stmt->errorInfo()[1];
            }
            if ($action == static::FETCH_ERRSTR && $stmt !== null) {
                return $stmt->errorInfo()[2];
            }

            return;
        }

        switch ($action) {
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
