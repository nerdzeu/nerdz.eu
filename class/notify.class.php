<?php
/*
 * Classe per la gestione delle notifiche
 */
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';

class notify extends phpCore
{
    private $rag, $cachekey;
    
    public function __construct()
    {
        parent::__construct();
        $this->cachekey = parent::isLogged() ? "{$_SESSION['nerdz_id']}notifystory".SITE_HOST : '';
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
                $c = $this->countUsers();
            break;
            case 'posts':
                $c = $this->countPosts();
            break;
            case 'projects':
                $c = $this->countProjects();
            break;
            case 'projects_news':
                $c = $this->countProjectsNews();
            break;
            case 'follow':
                $c = $this->countFollow();
            break;
            case 'all':
                $c = (($a = $this->countUsers())<=0 ? 0 : $a) + (($a = $this->countPosts())<=0 ? 0 : $a) + (($a = $this->countProjects())<=0 ? 0 : $a) + (($a = $this->countProjectsNews())<=0 ? 0 : $a) + (($a = $this->countFollow())<=0 ? 0 : $a);
            break;
        }
        return $c;
    }

    private function countUsers()
    {
        $q = $this->rag ? 'SELECT COUNT("hpid") AS cc FROM (SELECT DISTINCT "hpid" FROM "comments_notify" WHERE "to" = :id GROUP BY "hpid") AS c' :
                          'SELECT COUNT("to") AS cc FROM "comments_notify" WHERE "to" = :id';
        
        if(!($o = parent::query(array($q,array(':id' => $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
            return -1;
        
        return $o->cc;
    }

    private function countPosts()
    {
        if(!($o = parent::query(array('SELECT COUNT("hpid") AS cc FROM "posts_notify" WHERE "to" = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
            return -1;
        
        return $o->cc;
    }

    private function countProjects()
    {
        $q = $this->rag ? 'SELECT COUNT("hpid") AS cc FROM (SELECT DISTINCT "hpid" FROM groups_comments_notify WHERE "to" = :id GROUP BY "hpid") AS c' :
                          'SELECT COUNT("to") AS cc FROM "groups_comments_notify" WHERE "to" = :id';
            
        if(!($o = parent::query(array($q,array(':id' => $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
            return -1;
        
        return $o->cc;
    }

    private function countProjectsNews()
    {
        if(!($o = parent::query(array('SELECT COUNT(DISTINCT "group") AS cc FROM "groups_notify" WHERE "to" = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
            return -1;
        return $o->cc;
    }

    private function countFollow()
    {
        if(!($o = parent::query(array('SELECT COUNT("to") AS cc FROM "follow" WHERE "to" = :id AND "notified" = TRUE',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
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
                $ret = $this->getUsers($del);
            break;
            case 'posts':
                $ret = $this->getPosts($del);
            break;
            case 'projects':
                $ret = $this->getProjects($del);
            break;
            case 'projects_news':
                $ret = $this->getProjectsNews($del);
            break;
            case 'follows':
                $ret = $this->getFollows($del);
            break;
            case 'all':
                $ret = array_merge($this->getUsers($del),$this->getPosts($del),$this->getProjects($del),$this->getProjectsNews($del),$this->getFollows($del));
            break;
        }
        return $ret;
    }

    private function getUsers($del)
    {
        $ret = [];
        $i = 0;
        $result = parent::query(array('SELECT "from","hpid",EXTRACT(EPOCH FROM "time") AS time FROM "comments_notify" WHERE "to" = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_STMT);
        
        while(($o = $result->fetch(PDO::FETCH_OBJ)) && ($p = parent::query(array('SELECT "from","to","pid" FROM "posts" WHERE "hpid" = :hpid',array(':hpid' => $o->hpid)),db::FETCH_OBJ)))
        {
            $ret[$i]['from'] = $o->from;
            $ret[$i]['from_user'] = parent::getUsername($o->from);
            $ret[$i]['to'] = $p->to;
            $ret[$i]['to_user'] = parent::getUsername($p->to);
            $ret[$i]['post_from_user'] = parent::getUsername($p->from);
            $ret[$i]['post_from'] = $p->from;
            $ret[$i]['pid'] = $p->pid;
            $ret[$i]['datetime'] = parent::getDateTime($o->time);
            $ret[$i]['cmp'] = $o->time;
            $ret[$i]['board'] = false;
            $ret[$i]['project'] = false;
            ++$i;
        }

        if($del) {
            parent::query(
                [
                    'DELETE FROM "comments_notify" WHERE "to" = :id',
                    [
                        ':id' => $_SESSION['nerdz_id']
                    ]
                ],db::NO_RETURN);
        }
        return $ret;
    }

    private function getPosts($del)
    {
        $ret = [];
        $i = 0;
        $result = parent::query(array('SELECT p."pid",n."hpid", n."from", EXTRACT(EPOCH FROM n."time") AS time FROM "posts_notify" n JOIN "posts" p ON p.hpid = n.hpid WHERE n."to" = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_STMT);
        $to = parent::getUsername($_SESSION['nerdz_id']);

        while(($o = $result->fetch(PDO::FETCH_OBJ)))
        {
            $ret[$i]['from'] = $o->from;
            $ret[$i]['from_user'] = parent::getUsername($o->from);
            $ret[$i]['to'] = $_SESSION['nerdz_id'];
            $ret[$i]['to_user'] = $to;
            $ret[$i]['pid'] = $o->pid;
            $ret[$i]['datetime'] = parent::getDateTime($o->time);
            $ret[$i]['cmp'] = $o->time;
            $ret[$i]['board'] = true;
            $ret[$i]['project'] = false;
            ++$i;
        }
        if($del) {
            parent::query(
                [
                    'DELETE FROM "posts_notify" WHERE "to" = :id',
                    [
                        ':id' => $_SESSION['nerdz_id']
                    ]
                ],db::NO_RETURN);
        }

        return $ret;
    }

    private function getProjects($del)
    {
        $ret = [];
        $i = 0;
        $result = parent::query(array('SELECT "from","hpid",EXTRACT(EPOCH FROM "time") AS time FROM "groups_comments_notify" WHERE "to" = :id', array(':id' => $_SESSION['nerdz_id'])),db::FETCH_STMT);

        while(($o = $result->fetch(PDO::FETCH_OBJ)) && ($p = parent::query(array('SELECT "from","to","pid" FROM "groups_posts" WHERE "hpid" = :hpid',array(':hpid' => $o->hpid)),db::FETCH_OBJ)))
        {
            $ret[$i]['from'] = $o->from;
            $ret[$i]['from_user'] = parent::getUsername($o->from);
            $ret[$i]['to'] = $p->to;
            $ret[$i]['to_project'] = parent::getProjectName($p->to);
            $ret[$i]['post_from_user'] = parent::getUsername($p->from);
            $ret[$i]['post_from'] = $p->from;
            $ret[$i]['pid'] = $p->pid;
            $ret[$i]['datetime'] = parent::getDateTime($o->time);
            $ret[$i]['cmp'] = $o->time;
            $ret[$i]['board'] = false;
            $ret[$i]['project'] = true;
            ++$i;
        }

        if($del) {
            parent::query(
                [
                    'DELETE FROM "groups_comments_notify" WHERE "to" = :id',
                    [
                        ':id' => $_SESSION['nerdz_id']
                    ]
                ],db::NO_RETURN);
        }
        return $ret;
    }

    private function getProjectsNews($del)
    {
        $ret = [];
        $i = 0;
        $result = parent::query(array('SELECT DISTINCT "group",EXTRACT(EPOCH FROM "time") AS time FROM "groups_notify" WHERE "to" = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_STMT);
        while(($o = $result->fetch(PDO::FETCH_OBJ)))
        {
            $ret[$i]['project'] = true;
            $ret[$i]['to'] = $o->group;
            $ret[$i]['to_project'] = parent::getProjectName($o->group);
            $ret[$i]['datetime'] = parent::getDateTime($o->time);
            $ret[$i]['cmp'] = $o->time;
            $ret[$i]['news'] = true;
            ++$i;
        }
        if($del) {
            parent::query(
                [
                    'DELETE FROM "groups_notify" WHERE "to" = :id',
                    [
                        ':id' => $_SESSION['nerdz_id']
                    ]
                ],db::NO_RETURN);
        }
        return $ret;
    }

    private function getFollows($del)
    {
        $ret = [];
        $i = 0;
        $result = parent::query(array('SELECT "from",EXTRACT(EPOCH FROM "time") AS time FROM "follow" WHERE "to" = :id AND "notified" = TRUE',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_STMT);
        while(($o = $result->fetch(PDO::FETCH_OBJ)))
        {
            $ret[$i]['follow'] = true;
            $ret[$i]['from'] = $o->from;
            $ret[$i]['from_user'] = parent::getUsername($o->from);
            $ret[$i]['datetime'] = parent::getDateTime($o->time);
            $ret[$i]['cmp'] = $o->time;
            ++$i;
        }
        
        if($del) {
            parent::query(
                [
                    'UPDATE "follow" SET "notified" = FALSE WHERE "to" = :id',
                    [
                        ':id' => $_SESSION['nerdz_id']
                    ]
                ],db::NO_RETURN);
        }
        return $ret;
    }

    public function story()
    {
        if(apc_exists($this->cachekey))
            return unserialize(apc_fetch($this->cachekey));
        else
        {
            if(!($o = parent::query(
                [
                    'SELECT "notify_story" FROM "users" WHERE "counter" = :id',
                    [
                        ':id' => $_SESSION['nerdz_id']
                    ]
                ],db::FETCH_OBJ))
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
            if(db::NO_ERRNO != parent::query(
                [
                    'UPDATE "users" SET "notify_story" = :story WHERE "counter" = :id',
                    [
                        ':story' => json_encode($new,JSON_FORCE_OBJECT),
                        ':id'    => $_SESSION['nerdz_id']
                     ]
                ],db::FETCH_ERRNO)
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

            if(db::NO_ERRNO != parent::query(
                [
                    'UPDATE "users" SET "notify_story" = :story WHERE "counter" = :id',
                    [
                        ':story' => json_encode($old,JSON_FORCE_OBJECT),
                        ':id'    => $_SESSION['nerdz_id']
                    ]
                ],db::FETCH_ERRNO)
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
