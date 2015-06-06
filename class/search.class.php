<?php
namespace NERDZ\Core;

use PDO;
require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

class Search {

    private $user, $project;
    
    public function __construct() {
        $this->user = new User();
        $this->project = new Project();
        $this->messages = new Messages();
    }    

    private function byName($contains, $limit, $project = false) {
       if($limit)
           $limit = Security::limitControl($limit, 20);

       if(!$project) {
           $table = 'users';
           $field = 'username';
           $fetcher = $this->user;
       } else {
           $table = 'groups';
           $field = 'name';
           $fetcher = $this->project;
       }

        if(!($stmt = Db::query(
            [
                'SELECT "'.$field.'" FROM "'.$table.'" u WHERE u.'.$field.' ILIKE :contains ORDER BY u.'.$field.' LIMIT '.$limit,
                [
                    ':contains' => "%{$contains}%"
                ]
            ],Db::FETCH_STMT)))
            return [];

        $elements = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $ret = [];
        foreach($elements as $u) {
            $ret[] = $fetcher->getBasicInfo($u);
        }
        return $ret;
    }

    public function username($contains, $limit) {
        return $this->byName($contains, $limit);
    }

    public function projectName($contains, $limit) {
        return $this->byName($contains, $limit, true);
    }

    public function topic($tag, $limit, $hpid = 0) {
        $imp_blist = implode(',',$this->user->getBlacklist());

        $query = 'with tagged_posts(u_hpid, g_hpid) as (
            select u_hpid, g_hpid from posts_classification
            where lower(tag) = lower(:tag)) ';

        $query.= ' SELECT * FROM ( (SELECT p.hpid, p.from, p.to, p.closed, p.lang, p.news, EXTRACT(EPOCH FROM p."time") AS time, p.message, p.pid, false as "group"
                    FROM posts p INNER JOIN (select u_hpid FROM tagged_posts) AS pc
                    ON pc.u_hpid = p.hpid ';

        if(!empty($imp_blist))
            $query .= ' WHERE p."from" NOT IN ('.$imp_blist.') AND p."to" NOT IN ('.$imp_blist.') ';

        $query.= ') union (
                   SELECT gp.hpid, gp.from, gp.to, gp.closed, gp.lang, gp.news, EXTRACT(EPOCH FROM gp."time") AS time, gp.message, gp.pid, true as "group"
                    FROM "groups_posts" gp INNER JOIN (select g_hpid FROM tagged_posts) AS pc
                    ON pc.g_hpid = gp.hpid ';

        if(!empty($imp_blist))
            $query .= ' WHERE gp."from" NOT IN ('.$imp_blist.')';
        else {
            $query .= ' WHERE TRUE';
        }

        $query.= ' AND gp.to NOT IN (SELECT counter FROM groups WHERE private IS TRUE) )) AS t ';

        if($hpid) {
            $query .= ' WHERE t.hpid < :hpid ';
        }
        $query.= ' ORDER BY t.time DESC LIMIT '.$limit;

        if(!($result = Db::query(
            [
                $query,
                array_merge(
                    [ ':tag' => $tag ],
                    $hpid ? [ ':hpid' => $hpid ] : []
                )
            ],Db::FETCH_STMT))
        )
        return [];

        $c = 0;
        $ret = [];
        while(($row = $result->fetch(PDO::FETCH_OBJ)))
        {
            $ret[$c] = $this->messages->getPost($row,
                [
                    'project'  => $row->group,
                    'truncate' => true
                ]);
            ++$c;
        }
        $this->log($tag);
        return $ret;
    }

    private function log($tag) {
        if($this->user->isLogged()) {
            Db::query([
                'INSERT INTO searches("from", "value") VALUES(:id, :search)',
                [
                    ':id'     => $this->user->getId(),
                    ':search' => $tag
                ]
            ],Db::NO_RETURN);
        }
    }

}
