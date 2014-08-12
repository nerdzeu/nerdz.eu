<?php
namespace NERDZ\Core;
use PDO;

require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

class Notification
{
    private $rag, $cachekey, $user;
    
    public function __construct()
    {
        $this->user = new User();
        $this->cachekey = $this->user->isLogged() ? "{$_SESSION['id']}notifystory".Config\SITE_HOST : '';
    }

    public function countPms()
    {
        if(!($o = Db::query(
            [
                'SELECT COUNT(DISTINCT "from") as cc FROM ( 
                    SELECT "from" FROM "pms" WHERE "to" = :id AND "to_read" = TRUE
                 ) AS tmp1',
                [
                    ':id' => $_SESSION['id']
                ]
            ],Db::FETCH_OBJ)))
            return 0;
        return $o->cc;
    }
    
    public function count($what = null,$rag = null)
    {
        $c = -1;
        $this->rag = $rag;
        
        if(empty($what))
            $what = 'all';
        
        switch(trim(strtolower($what)))
        {
            case 'users':
                $c = $this->countUserComments();
            break;
            case 'posts':
                $c = $this->countUserPosts();
            break;
            case 'projects':
                $c = $this->countProjectComments();
            break;
            case 'projects_news':
                $c = $this->countProjectPosts();
            break;
            case 'follow':
                $c = $this->countFollow();
            break;
            case 'projects_follow':
                $c = $this->countUserComments();
            break;
            case 'all':
                $c = (($a = $this->countUserComments())    <=0 ? 0 : $a) + 
                     (($a = $this->countUserPosts())       <=0 ? 0 : $a) + 
                     (($a = $this->countProjectComments()) <=0 ? 0 : $a) +
                     (($a = $this->countProjectPosts())    <=0 ? 0 : $a) +
                     (($a = $this->countFollow())          <=0 ? 0 : $a) +
                     (($a = $this->countProjectFollow())   <=0 ? 0 : $a);
            break;
        }
        return $c;
    }

    private function countUserComments()
    {
        $q = $this->rag
            ? 'SELECT COUNT("hpid") AS cc FROM (SELECT DISTINCT "hpid" FROM "comments_notify" WHERE "to" = :id GROUP BY "hpid") AS c'
            : 'SELECT COUNT("to") AS cc FROM "comments_notify" WHERE "to" = :id';
        
        if(!($o = Db::query(
            [
                $q,
                [
                    ':id' => $_SESSION['id']
                ]
            ],Db::FETCH_OBJ)))
            return -1;
        
        return $o->cc;
    }

    private function countUserPosts()
    {
        if(!($o = Db::query(
            [
                'SELECT COUNT("hpid") AS cc FROM "posts_notify" WHERE "to" = :id',
                [
                    ':id' => $_SESSION['id']
                ]
            ],Db::FETCH_OBJ)))
            return -1;
        
        return $o->cc;
    }

    private function countProjectComments()
    {
        $q = $this->rag
            ? 'SELECT COUNT("hpid") AS cc FROM (SELECT DISTINCT "hpid" FROM groups_comments_notify WHERE "to" = :id GROUP BY "hpid") AS c'
            : 'SELECT COUNT("to") AS cc FROM "groups_comments_notify" WHERE "to" = :id';
            
        if(!($o = Db::query(
            [
                $q,
                [
                    ':id' => $_SESSION['id']
                ]
            ],Db::FETCH_OBJ)))
            return -1;
        
        return $o->cc;
    }

    private function countProjectPosts()
    {
        if(!($o = Db::query(
            [
                'SELECT COUNT("from") AS cc FROM "groups_notify" WHERE "to" = :id',
                [
                    ':id' => $_SESSION['id']
                ]
            ],Db::FETCH_OBJ)))
            return -1;
        return $o->cc;
    }

    private function countFollow()
    {
        if(!($o = Db::query(
            [
                'SELECT COUNT("to") AS cc FROM "followers" WHERE "to" = :id AND "to_notify" = TRUE',
                [
                    ':id' => $_SESSION['id']
                ]
            ],Db::FETCH_OBJ)))
            return -1;
    
        return $o->cc;
    }

    private function countProjectFollow()
    {
        if(!($o = Db::query(
            [
                'SELECT COUNT("to") AS cc FROM "groups_followers" WHERE "to" IN (
                    SELECT "counter" FROM "groups" WHERE "owner" = :id
                   ) AND "to_notify" = TRUE',
                [
                    ':id' => $_SESSION['id']
                ]
            ],Db::FETCH_OBJ)))
            return -1;
    
        return $o->cc;
    }

    public function show($what = null,$del = true)
    {
        $ret = [];
        if(empty($what))
            $what = 'all';
            
        switch(trim(strtolower($what)))
        {
            case 'users':
                $ret = $this->getUserComments($del);
            break;
            case 'posts':
                $ret = $this->getUserPosts($del);
            break;
            case 'projects':
                $ret = $this->getProjectComments($del);
            break;
            case 'projects_posts':
                $ret = $this->getProjectPosts($del);
            break;
            case 'follow':
                $ret = $this->getUserFollowers($del);
            break;
            case 'projects_follow':
                $ret = $this->getProjectFollowers($del);
            break;
            case 'all':
                $ret = array_merge(
                    $this->getUserComments($del),
                    $this->getUserPosts($del),
                    $this->getProjectComments($del),
                    $this->getProjectPosts($del),
                    $this->getUserFollowers($del),
                    $this->getProjectFollowers($del));
            break;
        }
        return $ret;
    }

    private function getUserComments($del)
    {
        $ret = [];
        $i = 0;
        $result = Db::query(
            [
                'SELECT "from","hpid",EXTRACT(EPOCH FROM "time") AS time FROM "comments_notify" WHERE "to" = :id',
                [
                    ':id' => $_SESSION['id']
                ]
            ],Db::FETCH_STMT);
        
        while(($o = $result->fetch(PDO::FETCH_OBJ)) && ($p = Db::query(
            [
                'SELECT "from","to","pid" FROM "posts" WHERE "hpid" = :hpid',
                [
                    ':hpid' => $o->hpid
                ]
            ],Db::FETCH_OBJ))
        )
        {
            $ret[$i]['from'] = $o->from;
            $ret[$i]['from_user'] = User::getUsername($o->from);
            $ret[$i]['to'] = $p->to;
            $ret[$i]['to_user'] = User::getUsername($p->to);
            $ret[$i]['post_from_user'] = User::getUsername($p->from);
            $ret[$i]['post_from'] = $p->from;
            $ret[$i]['pid'] = $p->pid;
            $ret[$i]['datetime'] = $this->user->getDateTime($o->time);
            $ret[$i]['cmp'] = $o->time;
            $ret[$i]['board'] = false;
            $ret[$i]['project'] = false;
            ++$i;
        }

        if($del) {
            Db::query(
                [
                    'DELETE FROM "comments_notify" WHERE "to" = :id',
                    [
                        ':id' => $_SESSION['id']
                    ]
                ],Db::NO_RETURN);
        }
        return $ret;
    }

    private function getUserPosts($del)
    {
        $ret = [];
        $i = 0;
        $result = Db::query(
            [
                'SELECT p."pid",n."hpid", n."from", EXTRACT(EPOCH FROM n."time") AS time FROM "posts_notify" n JOIN "posts" p ON p.hpid = n.hpid WHERE n."to" = :id',
                [
                    ':id' => $_SESSION['id']
                ]
            ],Db::FETCH_STMT);

        $to = User::getUsername($_SESSION['id']);

        while(($o = $result->fetch(PDO::FETCH_OBJ)))
        {
            $ret[$i]['from'] = $o->from;
            $ret[$i]['from_user'] = User::getUsername($o->from);
            $ret[$i]['to'] = $_SESSION['id'];
            $ret[$i]['to_user'] = $to;
            $ret[$i]['pid'] = $o->pid;
            $ret[$i]['datetime'] = $this->user->getDateTime($o->time);
            $ret[$i]['cmp'] = $o->time;
            $ret[$i]['board'] = true;
            $ret[$i]['project'] = false;
            ++$i;
        }
        if($del) {
            Db::query(
                [
                    'DELETE FROM "posts_notify" WHERE "to" = :id',
                    [
                        ':id' => $_SESSION['id']
                    ]
                ],Db::NO_RETURN);
        }

        return $ret;
    }

    private function getProjectComments($del)
    {
        $ret = [];
        $i = 0;
        $result = Db::query(
            [
                'SELECT "from","hpid",EXTRACT(EPOCH FROM "time") AS time FROM "groups_comments_notify" WHERE "to" = :id',
                [
                    ':id' => $_SESSION['id']
                ]
            ],Db::FETCH_STMT);

        while(($o = $result->fetch(PDO::FETCH_OBJ)) && ($p = Db::query(
            [
                'SELECT "from","to","pid" FROM "groups_posts" WHERE "hpid" = :hpid',
                [
                    ':hpid' => $o->hpid
                ]
            ],Db::FETCH_OBJ))
        )
        {
            $ret[$i]['from'] = $o->from;
            $ret[$i]['from_user'] = User::getUsername($o->from);
            $ret[$i]['to'] = $p->to;
            $ret[$i]['to_project'] = Project::getName($p->to);
            $ret[$i]['post_from_user'] = User::getUsername($p->from);
            $ret[$i]['post_from'] = $p->from;
            $ret[$i]['pid'] = $p->pid;
            $ret[$i]['datetime'] = $this->user->getDateTime($o->time);
            $ret[$i]['cmp'] = $o->time;
            $ret[$i]['board'] = false;
            $ret[$i]['project'] = true;
            ++$i;
        }

        if($del) {
            Db::query(
                [
                    'DELETE FROM "groups_comments_notify" WHERE "to" = :id',
                    [
                        ':id' => $_SESSION['id']
                    ]
                ],Db::NO_RETURN);
        }
        return $ret;
    }

    private function getProjectPosts($del)
    {
        $ret = [];
        $i = 0;
        $result = Db::query(
            [
                'SELECT "from",EXTRACT(EPOCH FROM "time") AS time FROM "groups_notify" WHERE "to" = :id',
                [
                    ':id' => $_SESSION['id']
                ]
            ],Db::FETCH_STMT);

        while(($o = $result->fetch(PDO::FETCH_OBJ)))
        {
            $ret[$i]['project'] = true;
            $ret[$i]['to'] = $o->group;
            $ret[$i]['to_project'] = Project::getName($o->group);
            $ret[$i]['datetime'] = $this->user->getDateTime($o->time);
            $ret[$i]['cmp'] = $o->time;
            $ret[$i]['news'] = true;
            ++$i;
        }
        
        if($del) {
            Db::query(
                [
                    'DELETE FROM "groups_notify" WHERE "to" = :id',
                    [
                        ':id' => $_SESSION['id']
                    ]
                ],Db::NO_RETURN);
        }
        
        return $ret;
    }

    private function getUserFollowers($del)
    {
        $ret = [];
        $i = 0;
        $result = Db::query(
            [
                'SELECT "from",EXTRACT(EPOCH FROM "time") AS time FROM "followers" WHERE "to" = :id AND "to_notify" = TRUE',
                [
                    ':id' => $_SESSION['id']
                ]
            ],Db::FETCH_STMT);

        while(($o = $result->fetch(PDO::FETCH_OBJ)))
        {
            $ret[$i]['follow'] = true;
            $ret[$i]['from'] = $o->from;
            $ret[$i]['from_user'] = User::getUsername($o->from);
            $ret[$i]['datetime'] = $this->user->getDateTime($o->time);
            $ret[$i]['cmp'] = $o->time;
            ++$i;
        }
        
        if($del) {
            Db::query(
                [
                    'UPDATE "followers" SET "to_notify" = FALSE WHERE "to" = :id',
                    [
                        ':id' => $_SESSION['id']
                    ]
                ],Db::NO_RETURN);
        }
        return $ret;
    }

    private function getProjectFollowers($del)
    {
        $ret = [];
        $i = 0;
        $result = Db::query(
            [
                'SELECT "from", EXTRACT(EPOCH FROM "time") AS time FROM "groups_followers" WHERE "to" IN (
                    SELECT "counter" FROM "groups" WHERE "owner" = :id
                 ) AND "to_notify" = TRUE',
                [
                    ':id' => $_SESSION['id']
                ]
            ],Db::FETCH_STMT);

        while(($o = $result->fetch(PDO::FETCH_OBJ)))
        {
            $ret[$i]['follow'] = true;
            $ret[$i]['from'] = $o->from;
            $ret[$i]['from_user'] = User::getUsername($o->from);
            $ret[$i]['datetime'] = $this->user->getDateTime($o->time);
            $ret[$i]['cmp'] = $o->time;
            ++$i;
        }
        
        if($del) {
            Db::query(
                [
                    'UPDATE "groups_followers" SET "to_notify" = FALSE WHERE "to" IN (
                        SELECT "counter" FROM "groups" WHERE "owner" = :id
                     )',
                    [
                        ':id' => $_SESSION['id']
                    ]
                ],Db::NO_RETURN);
        }
        return $ret;
    }

    public function story()
    {
        if(apc_exists($this->cachekey))
            return unserialize(apc_fetch($this->cachekey));
        else
        {
            if(!($o = Db::query(
                [
                    'SELECT "notify_story" FROM "users" WHERE "counter" = :id',
                    [
                        ':id' => $_SESSION['id']
                    ]
                ],Db::FETCH_OBJ))
            )
                return [];
        
            $ret = json_decode($o->notify_story,true);
            @apc_store($this->cachekey,serialize($ret),300);
            return $ret;
        }
    }

    public function updateStory($new)
    {
        $old = $this->story();
        if(empty($old))
        {
            if(Db::NO_ERRNO != Db::query(
                [
                    'UPDATE "users" SET "notify_story" = :story WHERE "counter" = :id',
                    [
                        ':story' => json_encode($new,JSON_FORCE_OBJECT),
                        ':id'    => $_SESSION['id']
                     ]
                ],Db::FETCH_ERRNO)
            )
                return false;
        }
        else
        {
            if(($c = count($old))>15)
            {
                for($i=15;$i<$c;++$i)
                    unset($old[$i]);
                $c = count($new);
                for($i=0;$i<$c;++$i)
                    array_unshift($old,$new[$i]);
            }
            else                
                for($i=0, $c = count($new);$i<$c;++$i)
                    array_unshift($old,$new[$i]);

            if(Db::NO_ERRNO != Db::query(
                [
                    'UPDATE "users" SET "notify_story" = :story WHERE "counter" = :id',
                    [
                        ':story' => json_encode($old,JSON_FORCE_OBJECT),
                        ':id'    => $_SESSION['id']
                    ]
                ],Db::FETCH_ERRNO)
            )
                return false;
        }
        apc_delete($this->cachekey);
        return true;
    }

    public static function echoSort($a,$b) //callback
    {
        return $a[1] == $b[1] ? 0 : $a[1] > $b[1] ? -1 : 1;
    }
}
?>
