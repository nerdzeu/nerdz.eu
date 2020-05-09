<?php
/*
Copyright (C) 2010-2020 Paolo Galeone <nessuno@nerdz.eu>

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

require_once __DIR__.'/Autoload.class.php';
use PDO;

class Project
{
    private $id;
    private $user;

    public function __construct($id = null)
    {
        $this->user = new User();
        if ($id !== null) {
            if (!is_numeric($id)) {
                $this->id = $this->getId($id);
            } else {
                $this->id = intval($id);
            }
        }
    }

    private function checkId(&$id)
    {
        if (empty($id)) {
            if (empty($this->id)) {
                die(__CLASS__.' invalid project ID');
            } else {
                $id = $this->id;
            }
        } elseif (!is_numeric($id)) {
            $id = $this->getId($id);
        }
        $id = intval($id);
    }

    public function getObject(&$id = null)
    {
        $this->checkId($id);

        return Db::query(
            [
                'SELECT * FROM "groups" WHERE "counter" = :id',
                [
                    ':id' => $id,
                ],
            ],
            Db::FETCH_OBJ
        );
    }

    public function getBasicInfo($id = null)
    {
        $o = $this->getObject($id);
        $ret = [];
        $ret['name_n'] = $o->name;
        $ret['name4link_n'] = Utils::projectLink($o->name);
        $ret['id_n'] = $id;
        $ret['canifollow_b'] = !$this->user->isFollowing($id, true);
        $ret['since_n'] = $this->user->getDate(strtotime($o->creation_time));

        return $ret;
    }

    public function getMembersAndOwnerFromHpid($hpid)
    {
        if (!($info = Db::query(array('SELECT "to" FROM "groups_posts" WHERE "hpid" = :hpid', array(':hpid' => $hpid)), Db::FETCH_OBJ))) {
            return false;
        }

        $members = $this->getMembers($info->to);
        $members[] = $this->getOwner($info->to);

        return $members;
    }

    public function getId($name = null)
    {
        if ($name === null) {
            return $this->id;
        }

        if (!($o = Db::query(
            [
                'SELECT "counter" FROM "groups" WHERE LOWER("name") = LOWER(:name)',
                    [
                        ':name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
                    ],
                ],
            Db::FETCH_OBJ
        ))) {
            return 0;
        }

        return $o->counter;
    }

    public function getOwner($id = null)
    {
        $this->checkId($id);
        if (!($o = Db::query(
            [
                'SELECT "from" as owner FROM "groups_owners" WHERE "to" = :id',
                [
                    ':id' => $id,
                ],
            ],
            Db::FETCH_OBJ
        ))) {
            return 0;
        }

        return $o->owner;
    }

    public function isOpen($id = null)
    {
        $this->checkId($id);
        if (!($o = Db::query(
            [
                'SELECT "open" FROM "groups" WHERE "counter" = :id',
                [
                    ':id' => $id,
                ],
            ],
            Db::FETCH_OBJ
        ))) {
            return false;
        }

        return $o->open;
    }

    public function getMembers($id = null, $limit = 0)
    {
        $this->checkId($id);
        if ($limit) {
            $limit = Security::limitControl($limit, 20);
        }

        if (!($stmt = Db::query(
            [
                'SELECT "from" FROM "groups_members" WHERE "to" = :id'.($limit !== 0 ? " LIMIT {$limit}" : ''),
                [
                    ':id' => $id,
                ],
            ],
            Db::FETCH_STMT
        ))) {
            return [];
        }

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getFollowers($id = null, $limit = 0)
    {
        $this->checkId($id);
        if ($limit) {
            $limit = Security::limitControl($limit, 20);
        }

        if (!($stmt = Db::query(
            [
                'SELECT "from" FROM "groups_followers" WHERE "to" = :id'.($limit !== 0 ? " LIMIT {$limit}" : ''),
                [
                    ':id' => $id,
                ],
            ],
            Db::FETCH_STMT
        ))) {
            return [];
        }

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getFollowersCount($id = null)
    {
        $this->checkId($id);
        if (!($o = Db::query(
            [
                'SELECT COUNT("from") AS cc FROM "groups_followers" WHERE "to" = :id',
                [
                    ':id' => $id,
                ],
            ],
            Db::FETCH_OBJ
        ))) {
            return 0;
        }

        return $o->cc;
    }

    public function getMembersCount($id = null)
    {
        $this->checkId($id);
        if (!($o = Db::query(
            [
                'SELECT COUNT("from") AS cc FROM "groups_members" WHERE "to" = :id',
                [
                    ':id' => $id,
                ],
            ],
            Db::FETCH_OBJ
        ))) {
            return 0;
        }

        return $o->cc;
    }

    public function getInteractions($id, $limit = 0)
    {
        if (!$this->user->isLogged()) {
            return [];
        }

        if ($limit) {
            $limit = Security::limitControl($limit, 20);
        }

        $objs = [];
        if (!($objs = Db::query(
            [
                'SELECT "type", extract(epoch from time) as time, pid
                FROM group_interactions(:me, :id) AS
                f("type" text, "time" timestamp without time zone, pid int8, post_to int8)
                ORDER BY f.time DESC'.($limit !== 0 ? " LIMIT {$limit}" : ''),
                [
                    ':me' => $_SESSION['id'],
                    ':id' => $id,
                ],
            ],
            Db::FETCH_OBJ,
            true
        ))) {
            return [];
        }
        $name = static::getName($id);
        $link =  Utils::projectLink($name);
        $ret = [];
        for ($i = 0, $count = count($objs); $i < $count; ++$i) {
            $ret[$i]['type_n'] = $objs[$i]->type;
            $ret[$i]['date_n'] = $this->user->getDate($objs[$i]->time);
            $ret[$i]['time_n'] = $this->user->getTime($objs[$i]->time);
            $ret[$i]['pid_n'] = $objs[$i]->pid;
            $ret[$i]['postto_n'] = $name;
            $ret[$i]['link_n'] = $link.($objs[$i]->pid ?$objs[$i]->pid : '');
        }

        return $ret;
    }

    public static function getName($id)
    {
        if (!($o = Db::query(
            [
                'SELECT "name" FROM "groups" WHERE "counter" = :id',
                [
                    ':id' => $id,
                ],
            ],
            Db::FETCH_OBJ
        ))) {
            return false;
        }

        return $o->name;
    }
}

if (isset($_GET['gid']) && !is_numeric($_GET['gid']) && is_string($_GET['gid'])) {
    $_GET['gid'] = (new Project(trim($_GET['gid'])))->getId();
}
