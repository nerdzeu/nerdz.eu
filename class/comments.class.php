<?php
/*
 * Classe per la gestione dei commenti
 */
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';

class comments extends project
{
    public function __construct()
    {
        parent::__construct();
    }

    private function addControl($author,$dest,$hpid,$prj = null)
    {
        $glue = $prj ? 'groups_' : '';

        if(
            !($r = parent::query(array('SELECT "from" AS a FROM "blacklist" WHERE "to" = ? UNION SELECT "to" AS a FROM "blacklist" WHERE "from" = ?',array($_SESSION['nerdz_id'],$_SESSION['nerdz_id'])),db::FETCH_STMT)) || //quelli da non notificare 
            !($nn = parent::query(array('SELECT "from","to" FROM "'.$glue.'comments_no_notify" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_STMT)) ||
            !($nnp = parent::query(array('SELECT "user" FROM "'.$glue.'posts_no_notify" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_STMT)) ||
            !($res = parent::query(array('SELECT DISTINCT "from" FROM "'.$glue.'comments" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_STMT)) ||
            !($lur = parent::query(array('SELECT "user" FROM "'.$glue.'lurkers" WHERE "post" = :hpid',array(':hpid' => $hpid)),db::FETCH_STMT))
          )
            return false;

        $lurkers = $blist = $nnu = $users = $nnpost = array();

        $blist = $r->fetchAll(PDO::FETCH_COLUMN);

        $lurkers = $lur->fetchAll(PDO::FETCH_COLUMN);


        if(in_array($_SESSION['nerdz_id'],$lurkers)) //se lurki non commenti (non avrebbe senso che tu venga notificato novamente)
        {
            if(db::NO_ERR != parent::query(array('DELETE FROM "'.$glue.'lurkers" WHERE "post" = :hpid AND "user" = :id',array(':hpid' => $hpid,':id' => $_SESSION['nerdz_id'])),db::FETCH_ERR))
                return false;

            unset($lurkers[array_search($_SESSION['nerdz_id'],$lurkers)]);
        }

        while(($o = $nn->fetch(PDO::FETCH_OBJ)))
            if(!isset($nnu[$o->to]))
                $nnu[$o->to] = $o->from;
            else
                $nnu[$o->to].= '-'.$o->from;

        $users = $res->fetchAll(PDO::FETCH_COLUMN);

        $nnpost = $nnp->fetchAll(PDO::FETCH_COLUMN);

        $jmp = false;
        if(!in_array($author,$users) && ($author != $_SESSION['nerdz_id']))
        {
            $users[] = $author;
            $jmp = true;
        }

        if(!$prj && (!in_array($dest,$users) && ($dest != $_SESSION['nerdz_id'])))
            $users[] = $dest;

        $users = array_values(array_diff(array_unique(array_merge($users,$lurkers)),array(USERS_NEWS,DELETED_USERS)));
        $i = count($users);
        $time = time(); //devo usare questa e non NOW perché nel while altrimenti perdo secondi e le cose si sfasano
        while($i-- > 0)
            if(isset($users[$i]) && ($jmp && ($author == $users[$i]))||($_SESSION['nerdz_id'] != $users[$i]))
            {
                if(in_array($users[$i],$blist) || in_array($users[$i],$nnpost))
                    continue;
                if(isset($nnu[$users[$i]]))
                {
                    $e = explode('-',$nnu[$users[$i]]);
                    $f = 1;
                    $u = 0;
                    while(isset($e[$u]) && $f)
                    {
                        if(in_array($e[$u],array($_SESSION['nerdz_id'],0)))
                            $f = 0;
                        ++$u;
                    }
                    if(!$f)
                        continue;
                }
                if(!in_array(parent::query(array('INSERT INTO "'.$glue.'comments_notify"("from","to","hpid","time") VALUES (:from,:to,:hpid,TO_TIMESTAMP(:time))',array(':from' => $_SESSION['nerdz_id'], ':to' => $users[$i],':hpid' => $hpid, ':time' => $time)),db::FETCH_ERR),array(db::NO_ERR,POSTGRESQL_DUP_KEY)))
                    break;
            }
        return !($i+1);
    }

    private function getProjectMembersAndOwner($hpid)
    {
        if(!($info = parent::query(array('SELECT "to" FROM "groups_posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_OBJ)))
            return false;

        $members = parent::getMembers($info->to);
        $members[] = parent::getOwnerByGid($info->to);

        return $members;
    }

    private function getCommentsArray($res,$hpid,$luck,$prj,$blist,$gravurl,$users,$cg,$times,$lkd,$glue)
    {
        $i = 0;
        $ret = array();
        while(($o = $res->fetch(PDO::FETCH_OBJ)))
        {
            if(in_array($o->from,$blist))
                continue;

            if($prj)
                $canremoveusers[] = $o->from;
            else
                $canremoveusers = array($o->from,$o->to);

            $ret[$i]['fromid_n'] = $o->from;
            $ret[$i]['gravatarurl_n'] = $gravurl[$o->from];
            $ret[$i]['toid_n'] = $o->to;
            $ret[$i]['from_n'] = $users[$o->from];
            $ret[$i]['uid_n'] = "c{$o->hcid}";
            $ret[$i]['from4link_n'] = phpCore::userLink($users[$o->from]);
            $ret[$i]['message_n'] = parent::bbcode($o->message,1,$cg,1,$o->hcid);
            $ret[$i]['datetime_n'] = parent::getDateTime($o->time);
            $ret[$i]['timestamp_n'] = $o->time;
            $ret[$i]['hcid_n'] = $o->hcid;
            $ret[$i]['hpid_n'] = $hpid;
            $ret[$i]['thumbs_n'] = $this->getThumbs($o->hcid, $prj);
            $ret[$i]['uthumb_n'] = $this->getUserThumb($o->hcid, $prj);
            
            if($luck)
            {            
                $ret[$i]['canshowlock_b'] = false;
                if(isset($lkd[$o->from]) && !in_array($o->from,$times) && ($_SESSION['nerdz_id'] != $o->from))
                {
                    $ret[$i]['lock_b'] = true;
                    $times[] = $o->from;
                    $ret[$i]['canshowlock_b'] = true;
                }
                elseif(!in_array($o->from,$times) && ($_SESSION['nerdz_id'] != $o->from))
                {
                    $ret[$i]['lock_b'] = false;
                    $times[] = $o->from;
                    $ret[$i]['canshowlock_b'] = true;
                }
            }
            else
                $ret[$i]['canshowlock_b'] = $ret[$i]['lock_b'] = false;

            $ret[$i]['canremove_b'] = in_array($_SESSION['nerdz_id'],$canremoveusers);

            if($prj)
                array_pop($canremoveusers);

            ++$i;
        }
        //non controllo il valore di ritorno, perché non è un errore grave per cui ritornare false, ci pensa poi la classe per le notifiche a gestire tutto
        if(parent::isLogged() && $i > 1)
            parent::query(array('DELETE FROM "'.$glue.'comments_notify" WHERE "to" = ? AND "hpid" = ?',array($_SESSION['nerdz_id'],$hpid)),db::NO_RETURN);
        
        return $ret;
    }

    private function showControl($from,$to,$hpid,$pid,$prj = null,$olderThanMe = null,$maxNum = null,$startFrom = 0)
    {
        if(!$prj && in_array($to,parent::getBlacklist())) // se ho messo l'utente in blacklist, non mostro i commenti fatti ai suoi post
            return array();

        $glue = $prj ? 'groups_' : '';
        // sorry for the bad indentation, but I'm not good at
        // making things pretty >:(
        $useLimitedQuery = is_numeric ($maxNum) && is_numeric ($startFrom);
        $queryArr = ( $olderThanMe ?
                       array('SELECT "from","to",EXTRACT(EPOCH FROM "time") AS time,"message","hcid" FROM "'.$glue.'comments" WHERE "hpid" = :hpid AND "hcid" > :hcid ORDER BY "hcid"',array(':hpid' => $hpid, ':hcid' => $olderThanMe))
                    : ($useLimitedQuery ?
                        // sort by hcid, descending, then reverse the order (ascending)
                       array('SELECT q.from, q.to, EXTRACT(EPOCH FROM q.time) AS time, q.message, q.hcid FROM (SELECT "from", "to", "time", "message", "hcid" FROM "'.$glue.'comments" WHERE "hpid" = ? AND "from" NOT IN (SELECT "from" AS a FROM "blacklist" WHERE "to" = ? UNION SELECT "to" AS a FROM "blacklist" WHERE "from" = ?) AND "to" NOT IN (SELECT "from" AS a FROM "blacklist" WHERE "to" = ? UNION SELECT "to" AS a FROM "blacklist" WHERE "from" = ?) ORDER BY "hcid" DESC LIMIT ? OFFSET ?) AS q ORDER BY q.hcid ASC', array ($hpid, $_SESSION['nerdz_id'], $_SESSION['nerdz_id'], $_SESSION['nerdz_id'], $_SESSION['nerdz_id'], $maxNum, $startFrom))
                     : array('SELECT "from","to",EXTRACT(EPOCH FROM "time") AS time,"message","hcid" FROM "'.$glue.'comments" WHERE "hpid" = :hpid ORDER BY "hcid"',array(':hpid' => $hpid)))
                    );
        //print $queryArr[]
        if(!($res = parent::query($queryArr, db::FETCH_STMT)))
            return false;

        if(
            !($f = parent::query(array('SELECT DISTINCT "from" FROM "'.$glue.'comments" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_STMT)) ||
            !($ll = parent::query(array('SELECT "from" FROM "'.$glue.'comments_no_notify" WHERE "hpid" = :hpid AND "to" = :id',array(':hpid' => $hpid,':id' => $_SESSION['nerdz_id'])),db::FETCH_STMT)) || //quelli da non notificare
            !($r = ($useLimitedQuery ? true : parent::query(array('SELECT "from" AS a FROM "blacklist" WHERE "to" = ? UNION SELECT "to" AS a FROM "blacklist" WHERE "from" = ?',array($_SESSION['nerdz_id'],$_SESSION['nerdz_id'])),db::FETCH_STMT)))
          )
            return false;
        
        $times = $gravurl = $users = $nonot = $lkd = $blist = $ret = array();
        
        if (!$useLimitedQuery)
            $blist = $r->fetchAll(PDO::FETCH_COLUMN);
        
        require_once $_SERVER['DOCUMENT_ROOT'].'/class/gravatar.class.php';
        $grav = new gravatar();

        while(($o = $f->fetch(PDO::FETCH_OBJ)))
        {
            $users[$o->from] = parent::getUserName($o->from);
            $gravurl[$o->from] = $grav->getURL($o->from);
            $nonot[] = $o->from;
        }

        $nonot[] = $from;
        $nonot[] = $to;

        $luck = in_array($_SESSION['nerdz_id'],$nonot);

        while(($o = $ll->fetch(PDO::FETCH_OBJ)))
            $lkd[$o->from] = parent::getUserName($o->from);

        $cg = $prj ? 'gc' : 'pc'; //per txt version code in commenti
        
        $canremoveusers = $prj ? $this->getProjectMembersAndOwner($hpid) : array();

        $ret = $this->getCommentsArray($res,$hpid,$luck,$prj,$blist,$gravurl,$users,$cg,$times,$lkd,$glue);
        
        /* Per il beforeHcid, nel caso in cui nella fase di posting si siano uniti gli ultimi messaggi
           allora l'hpid passato dev'essere quello dell'ultimo messaggio e glielo fetcho. Se non lo è ritorna empty e fuck off*/
        if($olderThanMe && empty($ret))
        {
            if(!($res = parent::query(array('SELECT "from","to",EXTRACT(EPOCH FROM "time") AS time,"message","hcid" FROM "'.$glue.'comments" WHERE "hpid" = :hpid AND "hcid" = :hcid ORDER BY "hcid"',array(':hpid' => $hpid, ':hcid' => $olderThanMe)),db::FETCH_STMT)))
                return false;
            $ret = $this->getCommentsArray($res,$hpid,$luck,$prj,$blist,$gravurl,$users,$cg,$times,$lkd,$glue);
        }
  
        return $ret;
    }

    public function parseCommentQuotes($message)
    {
        
        $i = 0;
        $pattern = '#\[quote=([0-9]+)\|p\]#i';
        while(preg_match($pattern,$message) && (++$i < 11))
            $message = preg_replace_callback($pattern,function($m) {
                    $username = comments::getUserNameFromProjectCid($m[1]);
                    return $username
                           ? '[commentquote=[user]'.$username.'[/user]]'.comments::getProjectComment($m[1]).'[/commentquote]'
                           : '';
                    },$message,1);

        if($i == 11)
            $message = preg_replace('#\[quote=([0-9]+)\|p\]#i','',$message);

        $i = 0;
        $pattern = '#\[quote=([0-9]+)\|u\]#i';
        while(preg_match($pattern,$message) && (++$i < 11))
            $message = preg_replace_callback($pattern,function($m) {
                    $username = comments::getUserNameFromCid($m[1]);
                    return $username
                            ? '[commentquote=[user]'.$username.'[/user]]'.comments::getComment($m[1]).'[/commentquote]'
                            : '';
                    },$message,1);

        if($i == 11)
            $message = preg_replace('#\[quote=([0-9]+)\|u\]#i','',$message);

        return $message;
    }

    private function explodeMessageInQuotes($str) //restituisce array("messaggio + quote o multiquote + altro testo fino al prossimo multiquote o fine messaggio")
    {
        $ret = array();

        if(strpos($str,'<div') === false)
            return $str;

        $len = strlen($str);
        $tmp = '';
        $divs = $lastlen = 0;
        $enter = false;

        for($i=0;$i<$len;++$i)
        {
            $tmp .= $str[$i];

            if(substr($tmp,-4) == '<div') // open
                ++$divs;

            if(substr($tmp,-6) == '</div>'){ // close
                --$divs;
                $enter = true;
                $lastlen = $i + 1;
            }
            
            if($enter && !$divs) { //termine blocco
                $ret[] = $tmp;
                $tmp = '';
                $enter = false;
            }
        }

        if($lastlen != $len)
            $ret[] = substr($str,$lastlen);

        return $ret;
    }

    private function removeNestedQuotes($str) // per ora funziona se c'è un solo multiquote all'interno del mesaggio
    {
        $quotes = substr_count($str,'<div class="qu_main">');
        $toremove = $quotes > 2 ? $quotes-2 : 0; //se ci sono più di due quote l'uno nell'altro mantengo gli ultimi due messaggi quotati
        
        if($toremove)
        {
            //devo mantenere i mittenti più esterni, eliminare  i più interni
            //preservi i mittenti più esterni quotes-toremove
            $newquote = '';
            $times = $quotes-$toremove;
            $str = preg_replace_callback('#<div class="qu_main"><div class="qu_user"><a href=(.+?)<\/a>:<\/div>#',function($m) use (&$newquote) {
                $newquote.= $m[0];
                return '>>><<<';
            },$str,$times);
            //ora new quote contiene i mittenti più esterni.
            //str contiene il testo prima dei quote, la posizione salvata da quotes-toremove >>><<<, da li partira newquote
            //eliminiamo tutto il testo finché non ho rimosso toremove divs
            $strpos = '';
            for($i = 0; $i <$times; ++$i)
                $strpos.= '>>><<<';

            $pos = strpos($str,$strpos) + 6*$times;

            //devo eliminare finché non ho rimosso toremove*2 (dato che c'è un </div> anche nel mittente del commento da eliminare') </div>s
            $toremove*=2;
            for($i = 0; $i < $toremove;++$i)
            {
                $divpos = strpos($str,'</div>') + 6;
                $todelete = '';
                for($k = $pos;$k<$divpos;++$k)
                    $todelete .= $str[$k];
                $str = str_replace($todelete,'',$str);
            }
            
            return str_replace($strpos,$newquote,$str); //metto i quote al posto giusto
        }
        return $str;
    }

    public function addComment($hpid,$message)
    {
        require_once $_SERVER['DOCUMENT_ROOT'].'/class/flood.class.php';
        if(!(new flood())->postComment())
            return null;

        $newMessage = $message; //required for appendComment

        $message = trim($this->parseCommentQuotes(htmlentities($message,ENT_QUOTES,'UTF-8')));

        if(
            empty($message) ||
            !($obj = parent::query(array('SELECT "to","from" FROM "posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_OBJ)) ||
            parent::isInBlacklist($_SESSION['nerdz_id'],$obj->to) || parent::isInBlacklist($obj->to,$_SESSION['nerdz_id'])
          )
            return false;

        //se l'utente è l'ultimo ad aver inviato un commento e ora ne aggiunge un altro allora append
        if(!($stmt = parent::query(array('SELECT "hpid","from","hcid","message" FROM "comments" WHERE "hpid" = ? AND "hcid" = (SELECT MAX("hcid") FROM "comments" WHERE "hpid" = ?)',array($hpid,$hpid)),db::FETCH_STMT)))
            return false;

        //for possible multiple append bug fix+
        if(($user = $stmt->fetch(PDO::FETCH_OBJ))) // if exists a previous message
        {
            $expl = explode('[hr]',$user->message);
            $lastAppendedMessage = $expl[count($expl) - 1]; //equals to $user->message if no append done before

            if($lastAppendedMessage == $message)
                return null; //null => flood error

            if($user->from == $_SESSION['nerdz_id']) //append and notify
                return $this->appendComment($user,$newMessage) && $this->addControl($obj->from,$obj->to,$hpid);
        }

//            $msg = $this->explodeMessageInQuotes($o->message);
//            $message = '';

//            var_dump($msg);

//            foreach((array)$msg as $quot)
                //$message .= $this->removeNestedQuotes($quot);
//                $message .=$quot;

        if(db::NO_ERR != parent::query(array('INSERT INTO "comments" ("from","to","hpid","message","time") VALUES (:from,:to,:hpid,:message,NOW())',array(':from' => $_SESSION['nerdz_id'],':to' => $obj->to,':hpid' => $hpid,':message' => $message)),db::FETCH_ERR))
            return false;

        return $this->addControl($obj->from,$obj->to,$hpid);
    }

    public function getComment($hcid)
    {
        if(!($o = parent::query(array('SELECT "message" FROM "comments" WHERE "hcid" = :hcid',array(':hcid' => $hcid)),db::FETCH_OBJ)))
            return '(null)';
        return $o->message;
    }

    private function getUserNameFromProjectCid($hcid)
    {
        if(!($o = parent::query(array('SELECT "from" FROM "groups_comments" WHERE "hcid" = :hcid',array(':hcid' => $hcid)),db::FETCH_OBJ)))
            return false;
        return parent::getUserName($o->from);
    }

    private function getUserNameFromCid($hcid)
    {
        if(!($o = parent::query(array('SELECT "from" FROM "comments" WHERE "hcid" = :hcid',array(':hcid' => $hcid)),db::FETCH_OBJ)))
            return false;
        return parent::getUserName($o->from);
    }

    /*
     * @deprecated Use getLastComments().
     */
    public function getComments($hpid)
    {
        if(!($o = parent::query(array('SELECT "to","pid","from" FROM "posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_OBJ)))
            return false;
        
        return $this->showControl($o->from,$o->to,$hpid,$o->pid);
    }

    public function getCommentsAfterHcid($hpid,$hcid)
    {
        if(!($o = parent::query(array('SELECT "to","pid","from" FROM "posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_OBJ)))
            return false;
        
        return $this->showControl($o->from,$o->to,$hpid,$o->pid,false,$hcid);
    }

    public function getLastComments ($hpid, $num, $cycle = 0)
    {
        if($num > 10 || $cycle > 200 || $num <= 0 || $cycle < 0 || !($o = parent::query(array('SELECT "to","pid","from" FROM "posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_OBJ)))
            return false;
        return $this->showControl ($o->from, $o->to, $hpid, $o->pid, false, false, $num, $cycle * $num);
    }

    public function delComment($hcid)
    {
        $ok =  (
            ($o = parent::query(array('SELECT "hpid","from","to",EXTRACT(EPOCH FROM "time") AS time FROM "comments" WHERE "hcid" = :hcid',array(':hcid' => $hcid)),db::FETCH_OBJ)) //cid, from, to, time servono
            &&
            ($owner = parent::query(array('SELECT "to" FROM "posts" WHERE "hpid" = :hpid',array(':hpid' => $o->hpid)),db::FETCH_OBJ))
            &&
            in_array($_SESSION['nerdz_id'],array($o->from,$owner->to)) // == canDelete
            &&
            parent::query(array('DELETE FROM "comments" WHERE "hcid" = :hcid',array(':hcid' => $hcid)),db::FETCH_ERR) == db::NO_ERR
            &&
            parent::query(array('DELETE FROM "comments_notify" WHERE "from" = :from AND "hpid" = :hpid AND "time" = TO_TIMESTAMP(:time)',array(':from' => $o->from,':hpid' => $o->hpid,':time' => $o->time)),db::FETCH_ERR)  == db::NO_ERR
        );
        if($ok)
        {
            if(!($c = parent::query(array('SELECT COUNT("hcid") AS cc FROM "comments" WHERE "hpid" = :hpid AND "from" = :id',array(':hpid' => $o->hpid,':id' => $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
                return false;

            if($c->cc == 0)
                if(db::NO_ERR != parent::query(array('DELETE FROM "comments_no_notify" WHERE "to" = :id AND "hpid" = :hpid',array(':id' => $_SESSION['nerdz_id'],':hpid' => $o->hpid)),db::FETCH_ERR))
                    return false;
            return true;
        }
        return false;
    }

    public function countComments($hpid)
    {
        if(parent::isLogged())
        {
            if(!($o = parent::query(array('SELECT COUNT("hcid") AS cc FROM "comments" WHERE "hpid" = ? AND "from" NOT IN (SELECT "from" AS a FROM "blacklist" WHERE "to" = ? UNION SELECT "to" AS a FROM "blacklist" WHERE "from" = ?) AND "to" NOT IN (SELECT "from" AS a FROM "blacklist" WHERE "to" = ? UNION SELECT "to" AS a FROM "blacklist" WHERE "from" = ?)',array($hpid, $_SESSION['nerdz_id'], $_SESSION['nerdz_id'], $_SESSION['nerdz_id'], $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
                return false;
        }
        else
            if(!($o = parent::query(array('SELECT COUNT("hcid") AS cc FROM "comments" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_OBJ)))
                return false;

        return $o->cc;
    }
    
    public function getProjectComment($hcid)
    {
        if(!($o = parent::query(array('SELECT "message" FROM "groups_comments" WHERE "hcid" = :hcid',array(':hcid' => $hcid)),db::FETCH_OBJ)))
            return '(null)';
        return $o->message;
    }

    private function appendProjectComment($oldMsgObj,$newMessage)
    {
        $message = $oldMsgObj->message.'[hr]'.trim( $this->parseCommentQuotes( htmlentities($newMessage,ENT_QUOTES,'UTF-8') ) );

        return db::NO_ERR == parent::query(array('UPDATE "groups_comments" SET message = :message WHERE "hcid" = :hcid',array(':message' => $message, ':hcid' => $oldMsgObj->hcid)),db::FETCH_ERR);
    }
    
    private function appendComment($oldMsgObj,$newMessage)
    {
        $message = $oldMsgObj->message.'[hr]'.trim( $this->parseCommentQuotes( htmlentities($newMessage,ENT_QUOTES,'UTF-8') ) );

        return db::NO_ERR == parent::query(array('UPDATE "comments" SET message = :message, time = NOW() WHERE "hcid" = :hcid',array(':message' => $message, ':hcid' => $oldMsgObj->hcid)),db::FETCH_ERR);
    }

    public function addProjectComment($hpid,$message)
    {
        require_once $_SERVER['DOCUMENT_ROOT'].'/class/flood.class.php';
        if(!(new flood())->projectComment())
            return null;

        $newMessage = $message; //required for appendComment

        $message = trim($this->parseCommentQuotes(htmlentities($message,ENT_QUOTES,'UTF-8')));
            
        if(
            empty($message) ||
            !($obj = parent::query(array('SELECT "to","from" FROM "groups_posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_OBJ))
          )
            return false;

        //se l'utente è l'ultimo ad aver inviato un commento e ora ne aggiunge un altro allora append
        if(!($stmt = parent::query(array('SELECT "hpid","from","hcid","message" FROM "groups_comments" WHERE "hpid" = ? AND "hcid" = (SELECT MAX("hcid") FROM "groups_comments" WHERE "hpid" = ?)',array($hpid,$hpid)),db::FETCH_STMT)))
            return false;

        if(($user = $stmt->fetch(PDO::FETCH_OBJ))) // if exists a previous message
        {
            $expl = explode('[hr]',$user->message);
            $lastAppendedMessage = $expl[count($expl) - 1]; //equals to $user->message if no append done before

            if($lastAppendedMessage == $message)
                return null; //null => flood error

            if($user->from == $_SESSION['nerdz_id']) //append and notify
                return $this->appendProjectComment($user,$newMessage) && $this->addControl($obj->from,$obj->to,$hpid,true);
        }

        if(db::NO_ERR != parent::query(array('INSERT INTO "groups_comments" ("from","to","hpid","message","time") VALUES (:id,:to,:hpid,:message,NOW())',array(':id' => $_SESSION['nerdz_id'], ':to' => $obj->to, ':hpid' => $hpid,':message' => $message)),db::FETCH_ERR))
            return false;
        return $this->addControl($obj->from,$obj->to,$hpid,true);
    }

    /*
     * @deprecated use getProjectLastComments()
     */
    public function getProjectComments($hpid)
    {
        if(!($o = parent::query(array('SELECT "to","from","pid" FROM "groups_posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_OBJ)))
            return false;
        return $this->showControl($o->from,$o->to,$hpid,$o->pid,true);
    }

    public function getProjectCommentsAfterHcid($hpid,$hcid)
    {
        if(!($o = parent::query(array('SELECT "to","from","pid" FROM "groups_posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_OBJ)))
            return false;
        return $this->showControl($o->from,$o->to,$hpid,$o->pid,true,$hcid);
    }

    public function getProjectLastComments ($hpid, $num, $cycle = 0)
    {
        if ($num > 10 || $cycle > 200 || $num <= 0 || $cycle < 0 || !($o = parent::query(array('SELECT "to","from","pid" FROM "groups_posts" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_OBJ)))
            return false;
        return $this->showControl ($o->from, $o->to, $hpid, $o->pid, true, false, $num, $cycle * $num);
    }

    public function delProjectComment($hcid)
    {
        if(
            !($o = parent::query(array('SELECT "hpid","from","to",EXTRACT(EPOCH FROM "time") AS time FROM "groups_comments" WHERE "hcid" = :hcid',array(':hcid' => $hcid)),db::FETCH_OBJ)) ||
            !($owner = parent::getOwnerByGid($o->to))
          )
            return false;

        $canremovecomment = $this->getProjectMembersAndOwner($o->hpid);
        $canremovecomment[] = $o->from;

        if(in_array($_SESSION['nerdz_id'],$canremovecomment))
        {
            if(
                db::NO_ERR != parent::query(array('DELETE FROM "groups_comments" WHERE "from" = :from AND "to" = :to AND "time" = TO_TIMESTAMP(:time)',array(':from' => $o->from,':to' => $o->to, ':time' => $o->time)),db::FETCH_ERR) ||
                db::NO_ERR != parent::query(array('DELETE FROM "groups_comments_notify" WHERE "from" = :from AND "hpid" = :hpid AND "time" = TO_TIMESTAMP(:time)',array(':from' => $o->from,':hpid' => $o->hpid,':time' => $o->time)),db::FETCH_ERR)
              )
                return false;
        }
        else
            return false;

        if(!($c = parent::query(array('SELECT COUNT("hcid") AS cc FROM "groups_comments" WHERE "hpid" = :hpid AND "from" = :id',array(':hpid' => $o->hpid,':id' => $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
            return false;
    
        if($c->cc == 0)
            if(db::NO_ERR != parent::query(array('DELETE FROM "groups_comments_no_notify" WHERE "to" = :id AND "hpid" = :hpid',array(':id' => $_SESSION['nerdz_id'],':hpid' => $o->hpid)),db::FETCH_ERR))
                return false;

        return true;
    }

    public function countProjectComments($hpid)
    {
        if(parent::isLogged())
        {
            if(!($o = parent::query(array('SELECT COUNT("hcid") AS cc FROM "groups_comments" WHERE "hpid" = ? AND "from" NOT IN (SELECT "from" AS a FROM "blacklist" WHERE "to" = ? UNION SELECT "to" AS a FROM "blacklist" WHERE "from" = ?)',array($hpid,$_SESSION['nerdz_id'],$_SESSION['nerdz_id'])),db::FETCH_OBJ)))
                return false;
        }
        else
            if(!($o = parent::query(array('SELECT COUNT("hpid") AS cc FROM "groups_comments" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),db::FETCH_OBJ)))
                return false;

        return $o->cc;
    }
    
    public function getThumbs($hcid, $prj = false) {
        $table = ($prj ? 'groups_' : ''). 'comment_thumbs';

        $ret = parent::query(
            [
                'SELECT SUM("vote") AS "sum" FROM "'.$table.'" WHERE "hcid" = :hcid GROUP BY hcid',
                [
                  ':hcid' => $hcid
                ]

            ],
            db::FETCH_OBJ
        );

        if (isset($ret->sum)) {
           return $ret->sum;
        }

        return 0;
    }

    public function getUserThumb($hcid, $prj = false) {
        if (!parent::isLogged()) {
          return 0;
        }

        $table = ($prj ? 'groups_' : ''). 'comment_thumbs';

        $ret = parent::query(
            [
                'SELECT "vote" FROM "'.$table.'" WHERE "hcid" = :hcid AND "user" = :user',
                [
                  ':hcid' => $hcid,
                  ':user' => $_SESSION['nerdz_id']
                ]

            ],
            db::FETCH_OBJ
        );

        if (isset($ret->vote)) {
           return $ret->vote;
        }

        return 0;
    }

    public function setThumbs($hcid, $vote, $prj = false) {
        if (!parent::isLogged()) {
          return false;
        }

        $table = ($prj ? 'groups_' : ''). 'comment_thumbs';
        
        $ret = parent::query(
            [
              'WITH new_values (hcid, "user", vote) AS ( VALUES(CAST(:hcid AS int8), CAST(:user AS int8), CAST(:vote AS int8))),
              upsert AS ( 
                  UPDATE '.$table.' AS m 
                  SET vote = nv.vote
                  FROM new_values AS nv
                  WHERE m.hcid = nv.hcid
                    AND m.user = nv.user
                  RETURNING m.*
              )
              INSERT INTO '.$table.' (hcid, "user", vote)
              SELECT hcid, "user", vote
              FROM new_values
              WHERE NOT EXISTS (SELECT 1 
                                FROM upsert AS up 
                                WHERE up.hcid = new_values.hcid 
                                  AND up.user = new_values.user)',
              [
                ':hcid' => (int) $hcid,
                ':user' => (int) $_SESSION['nerdz_id'],
                ':vote' => (int) $vote
              ]
            ],
            db::FETCH_ERR
        );

        return $ret == db::NO_ERR;
    }
}
?>
