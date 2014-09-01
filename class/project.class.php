<?php
namespace NERDZ\Core;
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use PDO;

class Project
{
    private $id;

    public function __construct($id = null)
    {
        if($id !== null) {
            if(!is_numeric($id)) {
                $this->id = $this->getId($id);
            } else {
                $this->id = $id;
            }
        }
    }

    private function checkId(&$id)
    {
        if(empty($id)) {
            if(empty($this->id)) {
                die(__CLASS__.' invalid project ID');
            }
            else $id = $this->id;
        }
    }

    public function getObject($id = null)
    {
        $this->checkId($id);
        return Db::query(
            [
                'SELECT * FROM "groups" WHERE "counter" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ);
    }

    public function getMembersAndOwnerFromHpid($hpid)
    {
        if(!($info = Db::query(array('SELECT "to" FROM "groups_posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),Db::FETCH_OBJ)))
            return false;

        $members   = $this->getMembers($info->to);
        $members[] = $this->getOwner($info->to);

        return $members;
    }

    public function getId($name = null)
    {
        if($name === null)
            return $this->id;

        if(!($o = Db::query(
            [
                'SELECT "counter" FROM "groups" WHERE LOWER("name") = LOWER(:name)',
                    [
                        ':name' => htmlspecialchars($name,ENT_QUOTES,'UTF-8')
                    ]
                ],Db::FETCH_OBJ)))
                return 0;
        return $o->counter;
    }

    public function getOwner($id = null)
    {
        $this->checkId($id);
        if(!($o = Db::query(
            [
                'SELECT "owner" FROM "groups" WHERE "counter" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)))
            return 0;
        return $o->owner;
    }

    public function isOpen($id = null)
    {
        $this->checkId($id);
        if(!($o = Db::query(
            [
                'SELECT "open" FROM "groups" WHERE "counter" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)))
            return false;

        return $o->open;
    }

    public function getMembers($id = null, $limit = 0)
    {
        $this->checkId($id);
        if($limit)
            $limit = Security::limitControl($limit, 20);

        if(!($stmt = Db::query(
            [
                'SELECT "from" FROM "groups_members" WHERE "to" = :id'.($limit !== 0 ? " LIMIT {$limit}" : ''),
                [
                    ':id' => $id
                ]
            ],Db::FETCH_STMT)))
            return [];

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getFollowers($id = null, $limit = 0)
    {
        $this->checkId($id);
        if($limit)
            $limit = Security::limitControl($limit, 20);

        if(!($stmt = Db::query(
            [
                'SELECT "from" FROM "groups_followers" WHERE "to" = :id'.($limit !== 0 ? " LIMIT {$limit}" : ''),
                [
                    ':id' => $id
                ]
            ],Db::FETCH_STMT)))
            return [];

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getFollowersCount($id = null)
    {
        $this->checkId($id);
        if(!($o = Db::query(
            [
                'SELECT COUNT("from") AS cc FROM "groups_followers" WHERE "to" = :id',
                [
                    ':id' => $id
                ]
            ], Db::FETCH_OBJ)))
            return 0;
        return $o->cc;
    }

    public function getMembersCount($id = null)
    {
        $this->checkId($id);
        if(!($o = Db::query(
            [
                'SELECT COUNT("from") AS cc FROM "groups_members" WHERE "to" = :id',
                [
                    ':id' => $id
                ]
            ], Db::FETCH_OBJ)))
            return 0;
        return $o->cc;
    }

    public function getInteractions($id, $limit = 0)
    {
        if(!$this->isLogged())
            return [];

        if($limit)
            $limit = Security::limitControl($limit, 20);

        $objs = [];
        if(!($objs = Db::query(
            [
                'SELECT "type", extract(epoch from time) as time, pid, post_to
                FROM group_interactions(:me, :id) AS
                f("type" text, "time" timestamp with time zone, pid int8, post_to int8)
                ORDER BY f.time DESC'.($limit !== 0 ? " LIMIT {$limit}" : ''),
                [
                    ':me' => $_SESSION['id'],
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ, true)))
            return [];

        //TODO: fix this
        $ret = [];
        for($i=0, $count = count($objs); $i<$count; ++$i) {
            $ret[$i]['type_n']      = $objs[$i]->type;
            $ret[$i]['to_n']        = static::getUsername($objs[$i]->to);
            $ret[$i]['datetime_n']  = $this->getDateTime($objs[$i]->time);
            $ret[$i]['pid_n']       = $objs[$i]->pid;
            $ret[$i]['postto_n']    = static::getUsername($objs[$i]->post_to);
            $ret[$i]['link_n']      = Utils::userLink($ret[$i]['postto_n']).$objs[$i]->pid;
        }

        return $ret;
    }


    public static function getName($id = null)
    {
        if(!($o = Db::query(
            [
                'SELECT "name" FROM "groups" WHERE "counter" = :id',
                [
                    ':id' => $id
                ]
            ],Db::FETCH_OBJ)))
            return '';

        return $o->name;
    }
}

if(isset($_GET['gid']) && !is_numeric($_GET['gid']) && is_string($_GET['gid']))
    $_GET['gid'] = (new Project(trim($_GET['gid'])))->getId();

?>
