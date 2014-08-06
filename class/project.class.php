<?php
namespace NERDZ\Core;
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use PDO;

class project extends Core
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getMembersAndOwnerFromHpid($hpid)
    {
        if(!($info = Db::query(array('SELECT "to" FROM "groups_posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),Db::FETCH_OBJ)))
            return false;

        $members = $this->getMembers($info->to);
        $members[] = $this->getOwner($info->to);

        return $members;
    }

    public function getObject($gid)
    {
        return Db::query(
            [
                'SELECT * FROM "groups" WHERE "counter" = :gid',
                [
                    ':gid' => $gid
                ]
            ],Db::FETCH_OBJ);
    }

    public function getId($name)
    {
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

    public function getOwner($gid)
    {
        if(!($o = Db::query(
            [
                'SELECT "owner" FROM "groups" WHERE "counter" = :gid',
                [
                    ':gid' => $gid
                ]
            ],Db::FETCH_OBJ)))
            return 0;
        return $o->owner;
    }

    public function isOpen($gid)
    {
        if(!($o = Db::query(
            [
                'SELECT "open" FROM "groups" WHERE "counter" = :gid',
                [
                    ':gid' => $gid
                ]
            ],Db::FETCH_OBJ)))
            return false;

        return $o->open;
    }
   
    public function getMembers($gid)
    {
        if(!($stmt = Db::query(
            [
                'SELECT "user" FROM "groups_members" WHERE "group" = :gid',
                [
                    ':gid' => $gid
                ]
            ],Db::FETCH_STMT)))
            return [];

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getFollowers($gid)
    {
        if(!($stmt = Db::query(array('SELECT "user" FROM "groups_followers" WHERE "group" = :gid',array(':gid' => $gid)),Db::FETCH_STMT)))
            return [];

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

if(isset($_GET['gid']) && !is_numeric($_GET['gid']) && is_string($_GET['gid']))
    $_GET['gid'] = (new Project())->getId(trim($_GET['gid']));

?>
