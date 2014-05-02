<?php
/*
 * Classe per la gestione dei progetti
 * I nomi dei metodi sono esplicativi
 */
require_once $_SERVER['DOCUMENT_ROOT'].'/class/messages.class.php';

class project extends messages
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getProjectObject($gid)
    {
        return parent::query(array('SELECT * FROM "groups" WHERE "counter" = :gid',array(':gid' => $gid)),db::FETCH_OBJ);
    }

    public function getGid($name)
    {
        if(!($o = parent::query(array('SELECT "counter" FROM "groups" WHERE LOWER("name") = LOWER(:name)',array(':name' => htmlentities($name,ENT_QUOTES,'UTF-8'))),db::FETCH_OBJ)))
            return false;
        return $o->counter;
    }

    public function getOwnerByGid($gid)
    {
        if(!($o = parent::query(array('SELECT "owner" FROM "groups" WHERE "counter" = :gid',array(':gid' => $gid)),db::FETCH_OBJ)))
            return false;
        return $o->owner;
    }

    public function isProjectOpen($gid)
    {
        if(!($o = parent::query(array('SELECT "open" FROM "groups" WHERE "counter" = :gid',array(':gid' => $gid)),db::FETCH_OBJ)))
            return false;
        return $o->open;
    }

    public function countProjectMessages($gid)
    {
        if(!($o = parent::query(array('SELECT MAX("pid") AS cc FROM "groups_posts" WHERE "to" = :gid',array(':gid' => $gid)),db::FETCH_OBJ)))
            return false;
        return $o->cc;
    }

    public function getProjectMessage($hpid,$edit = false)
    {
        if(!($o = parent::query(array('SELECT groups_posts.hpid, groups_posts.from, groups_posts.to, groups_posts.pid, groups_posts.message, groups_posts.news, EXTRACT(EPOCH FROM groups_posts.time) AS time FROM "groups_posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_OBJ)))
            return false;
        if($edit)
            $_SESSION['nerdz_editpid'] = $o->pid;
        return $o;
    }

    public function getProjectMessages($gid,$limit)
    {
        $blist = parent::getBlacklist();

        if(empty($blist))
            $glue = '';
        else
        {
            $imp_blist = implode(',',$blist);
            $glue = 'AND "groups_posts"."from" NOT IN ('.$imp_blist.')';
        }
        if(!($result = parent::query(array('SELECT groups_posts.hpid, groups_posts.from, groups_posts.to, groups_posts.pid, groups_posts.message, groups_posts.news, EXTRACT(EPOCH FROM groups_posts.time) AS time FROM "groups_posts" WHERE "to" = :gid '.$glue.' ORDER BY "hpid" DESC LIMIT '.$limit,array(':gid' => $gid)),db::FETCH_STMT)))
            return false;

        return parent::getPostsArray($result,true,$inList = true);
    }
    
    public function getNMessagesBeforeHpid($N,$hpid,$id)
    {
        $blist = parent::getBlacklist();

        if($N > 20 || $N <= 0) //massimo 20 posts, defaults
            $N = 20;

        if(empty($blist))
            $glue = '';
        else
        {
            $imp_blist = implode(',',$blist);
            $glue = 'AND "groups_posts"."from" NOT IN ('.$imp_blist.')';
        }

        if(!($result = parent::query(array('SELECT groups_posts.hpid, groups_posts.from, groups_posts.to, groups_posts.pid, groups_posts.message, groups_posts.news, EXTRACT(EPOCH FROM groups_posts.time) AS time FROM "groups_posts" WHERE "hpid" < :hpid AND "to" = :gid '.$glue.' ORDER BY "hpid" DESC LIMIT '.$N,array(':gid' => $id,':hpid' => $hpid)),db::FETCH_STMT)))
            return false;

        return parent::getPostsArray($result,true, $inList = true);
    }

    public function getMembers($gid)
    {
        if(!($stmt = parent::query(array('SELECT "user" FROM "groups_members" WHERE "group" = :gid',array(':gid' => $gid)),db::FETCH_STMT)))
            return array();

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getFollowers($gid)
    {
        if(!($stmt = parent::query(array('SELECT "user" FROM "groups_followers" WHERE "group" = :gid',array(':gid' => $gid)),db::FETCH_STMT)))
            return array();

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function addProjectMessage($to,$message,$news = null)
    {
        require_once $_SERVER['DOCUMENT_ROOT'].'/class/flood.class.php';
        if(!(new flood())->projectPost())
            return 0;

        if(!($own = $this->getOwnerByGid($to)))
            return false;
        
        $members = $this->getMembers($to);
        $followers = $this->getFollowers($to);

        $blacklist = parent::getBlacklist();//non devono essere notificati

        $news = $news ? 'TRUE' : 'FALSE';

        $time = time(); //nel loop di insertimento si perdono secondi

        $oldMessage = $message; //required for github implementation
        
        $message = htmlentities($message,ENT_QUOTES,'UTF-8'); //fixed empty entities

        if(empty($message) || db::NO_ERRNO != parent::query(array('INSERT INTO "groups_posts" ("from","to","message","news") VALUES (:id,:to,:message,:news)',array(':id' => $_SESSION['nerdz_id'], ':to' => $to, ':message' => $message, ':news' => $news)),db::FETCH_ERRNO))
            return false;

        if($_SESSION['nerdz_id'] != $own)
            $members[] = $own;

        $tonotify = array_diff(array_unique(array_merge($members,$followers)),$blacklist,array($_SESSION['nerdz_id']));

        foreach($tonotify as $v)
            if(db::NO_ERRNO != parent::query(array('INSERT INTO "groups_notify" ("group","to","time") VALUES (:to,:v,TO_TIMESTAMP(:time))',array(':to' => $to, ':v' => $v, ':time' => $time)),db::FETCH_ERRNO))
                    return false;

        
        if($to == ISSUE_BOARD) {
            require_once $_SERVER['DOCUMENT_ROOT'].'/class/vendor/autoload.php';
            $client = new \Github\Client();
            $client->authenticate(ISSUE_GIT_KEY, null, Github\client::AUTH_URL_TOKEN);
            $client->api('issue')->create('nerdzeu','nerdz.eu',
                    [
                        'title' => 'NERDZIlla issue',
                        'body'  => parent::getUserName().': '.$oldMessage
                    ]
            );
        }
        

        return true;
    }

    public function deleteProjectMessage($hpid)
    {
        return (
            ($obj = parent::query(array('SELECT "from","to","pid" FROM "groups_posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_OBJ)) &&
            in_array($_SESSION['nerdz_id'],array($this->getOwnerByGid($obj->to),$obj->from)) &&
            db::NO_ERRNO == parent::query(array('DELETE FROM "groups_comments" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_ERRNO) &&
            db::NO_ERRNO == parent::query(array('DELETE FROM "groups_posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_ERRNO) // triggers do the rest
          );
    }

    public function editProjectMessage($hpid,$message)
    {
        $message = htmlentities($message,ENT_QUOTES,'UTF-8'); //fixed empty entities
        return ! (
            empty($message) ||
            !($obj = parent::query(array('SELECT "from","to","pid" FROM "groups_posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_OBJ)) ||
            !$this->canEditProjectPost((array)$obj,$this->getOwnerByGid($obj->to),$this->getMembers($obj->to)) ||
            empty($_SESSION['nerdz_editpid']) || $_SESSION['nerdz_editpid'] != $obj->pid ||
            db::NO_ERRNO != parent::query(array('UPDATE "groups_posts" SET "from" = :from, "to" = :to, "pid" = :pid, "message" = :message WHERE "hpid" = :hpid',array(':from' => $obj->from,':to' => $obj->to, ':pid' => $obj->pid, ':message' => $message, ':hpid' => $hpid)),db::FETCH_ERRNO)
          );
    }

    public function canEditProjectPost($post,$own,$members)
    {
        return parent::isLogged() && in_array($_SESSION['nerdz_id'],array_merge((array)$members,(array)$own,(array)$post['from']));
    }

    public function canRemoveProjectPost($post,$own)
    {
        return (parent::isLogged() && ($_SESSION['nerdz_id'] == $post['from'] || $_SESSION['nerdz_id'] == $own));
    }

    public function canShowLockForProjectPost($post)
    {
        return
        (
            parent::isLogged() &&
            (
                $_SESSION['nerdz_id'] == $post['from'] ||
                parent::query(array('SELECT DISTINCT "from" FROM "groups_comments" WHERE "hpid" = :hpid AND "from" = :id ',array(':hpid' => $post['hpid'],':id' => $_SESSION['nerdz_id'])),db::ROW_COUNT) > 0
            )
          );
    }

    public function hasLockedProjectPost($post)
    {
        return (
                parent::isLogged() &&
                parent::query(array('SELECT "hpid" FROM "groups_posts_no_notify" WHERE "hpid" = :hpid AND "user" = :id',array(':hpid' => $post['hpid'],':id' => $_SESSION['nerdz_id'])),db::ROW_COUNT) > 0
               );
    }

    public function hasLurkedProjectPost($post)
    {
        return (
                parent::isLogged() &&
                parent::query(array('SELECT "post" FROM "groups_lurkers" WHERE "post" = :hpid AND "user" = :id',array(':hpid' => $post['hpid'],':id' => $_SESSION['nerdz_id'])),db::ROW_COUNT) > 0
               );
    }

    public function hasBookmarkedProjectPost($post)
    {
        return (
                parent::isLogged() &&
                parent::query(array('SELECT "hpid" FROM "groups_bookmarks" WHERE "hpid" = :hpid AND "from" = :id',array(':hpid' => $post['hpid'],':id' => $_SESSION['nerdz_id'])),db::ROW_COUNT) > 0
               );
    }
}

if(isset($_GET['gid']) && !is_numeric($_GET['gid']) && is_string($_GET['gid']))
    $_GET['gid'] = (new project())->getGid(trim($_GET['gid']));

?>
