<?php
/*
 * Classe per la gestione dei commenti
 */

require_once $_SERVER['DOCUMENT_ROOT'].'/class/messages.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';

class comments extends messages
{
    private $project;
    public function __construct()
    {
        parent::__construct();
        $this->project = new project();
    }

    private function getCommentsArray($res,$hpid,$luck,$prj,$blist,$gravurl,$users,$cg,$times,$lkd,$glue)
    {
        $i = 0;
        $ret = array();
        if($prj)
            $canremoveusers = $this->project->getMembersAndOwnerFromHpid($hpid);

        while(($o = $res->fetch(PDO::FETCH_OBJ)))
        {
            if(in_array($o->from,$blist))
                continue;

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
            $ret[$i]['revisions_n'] = $this->getRevisionsNumber($o->hcid, $prj);
            $ret[$i]['caneditcomment_b'] = $o->editable && parent::isLogged() && $o->from == $_SESSION['nerdz_id'];
            
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


            $canremoveusers = $prj ? array_merge($canremoveusers, (array)$o->from) : array($o->from,$o->to);
            $ret[$i]['canremove_b'] = in_array($_SESSION['nerdz_id'],$canremoveusers);

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
                       array('SELECT "from","to",EXTRACT(EPOCH FROM "time") AS time,"message","hcid", "editable" FROM "'.$glue.'comments" WHERE "hpid" = :hpid AND "hcid" > :hcid ORDER BY "hcid"',array(':hpid' => $hpid, ':hcid' => $olderThanMe))
                    : ($useLimitedQuery ?
                        // sort by hcid, descending, then reverse the order (ascending)
                       array('SELECT q.from, q.to, EXTRACT(EPOCH FROM q.time) AS time, q.message, q.hcid, q.editable FROM (SELECT "from", "to", "time", "message", "hcid", "editable" FROM "'.$glue.'comments" WHERE "hpid" = ? AND "from" NOT IN (SELECT "from" AS a FROM "blacklist" WHERE "to" = ? UNION SELECT "to" AS a FROM "blacklist" WHERE "from" = ?) AND "to" NOT IN (SELECT "from" AS a FROM "blacklist" WHERE "to" = ? UNION SELECT "to" AS a FROM "blacklist" WHERE "from" = ?) ORDER BY "hcid" DESC LIMIT ? OFFSET ?) AS q ORDER BY q.hcid ASC', array ($hpid, $_SESSION['nerdz_id'], $_SESSION['nerdz_id'], $_SESSION['nerdz_id'], $_SESSION['nerdz_id'], $maxNum, $startFrom))
                     : array('SELECT "from","to",EXTRACT(EPOCH FROM "time") AS time,"message","hcid", "editable" FROM "'.$glue.'comments" WHERE "hpid" = :hpid ORDER BY "hcid"',array(':hpid' => $hpid)))
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
            $users[$o->from] = parent::getUsername($o->from);
            $gravurl[$o->from] = $grav->getURL($o->from);
            $nonot[] = $o->from;
        }

        $nonot[] = $from;
        $nonot[] = $to;

        $luck = in_array($_SESSION['nerdz_id'],$nonot);

        while(($o = $ll->fetch(PDO::FETCH_OBJ)))
            $lkd[$o->from] = parent::getUsername($o->from);

        $cg = $prj ? 'gc' : 'pc'; //per txt version code in commenti

        $ret = $this->getCommentsArray($res,$hpid,$luck,$prj,$blist,$gravurl,$users,$cg,$times,$lkd,$glue);
        
        /* Per il beforeHcid, nel caso in cui nella fase di posting si siano uniti gli ultimi messaggi
           allora l'hpid passato dev'essere quello dell'ultimo messaggio e glielo fetcho. Se non lo è ritorna empty */
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
                    $username = comments::getUsernameFromCid($m[1], true);
                    return $username
                           ? '[commentquote=[user]'.$username.'[/user]]'.comments::getComment($m[1], true).'[/commentquote]'
                           : '';
                    },$message,1);

        if($i == 11)
            $message = preg_replace('#\[quote=([0-9]+)\|p\]#i','',$message);

        $i = 0;
        $pattern = '#\[quote=([0-9]+)\|u\]#i';
        while(preg_match($pattern,$message) && (++$i < 11))
            $message = preg_replace_callback($pattern,function($m) {
                    $username = comments::getUsernameFromCid($m[1]);
                    return $username
                            ? '[commentquote=[user]'.$username.'[/user]]'.comments::getComment($m[1]).'[/commentquote]'
                            : '';
                    },$message,1);

        if($i == 11)
            $message = preg_replace('#\[quote=([0-9]+)\|u\]#i','',$message);

        return $message;
    }

    public function addComment($hpid,$message, $prj = false)
    {
        $posts = ($prj ? 'groups_' : '').'posts';
        $comments = ($prj ? 'groups_' : '').'comments';

        if(
                !($obj = parent::query(
                    [
                        'SELECT "to" FROM "'.$posts.'" WHERE "hpid" = :hpid',
                        [
                            ':hpid' => $hpid
                        ]
                    ],db::FETCH_OBJ))
                ||
                !($stmt = parent::query(
                    [
                        'SELECT "hpid","from","hcid","message" FROM "'.$comments.'" WHERE "hpid" = :hpid AND "hcid" = (SELECT MAX("hcid") FROM "'.$comments.'" WHERE "hpid" = :hpid)',
                        [
                            ':hpid' => $hpid
                        ],
                    ],db::FETCH_STMT))
          )
          return 'ERROR';

        $message = trim($this->parseCommentQuotes(htmlspecialchars($message,ENT_QUOTES,'UTF-8')));

        if(($user = $stmt->fetch(PDO::FETCH_OBJ)))
        {
            $expl = explode('[hr]',$user->message);
            $lastAppendedMessage = $expl[count($expl) - 1];

            if(trim($lastAppendedMessage) == $message)
                return 'error: FLOOD'; //simulate db response

            if($user->from == $_SESSION['nerdz_id'])
                return $this->appendComment($user,$message, $prj);
        }
        
        return parent::query(
            [
                'INSERT INTO "'.$comments.'" ("from","to","hpid","message") VALUES (:from,:to,:hpid,:message)',
                [
                    ':from' => $_SESSION['nerdz_id'],
                    ':to' => $obj->to,
                    ':hpid' => $hpid,
                    ':message' => $message
                ]
            ],db::FETCH_ERRSTR);
    }

    public function getComment($hcid, $prj = false)
    {
        $tbl = ($prj ? 'groups_' : '').'comments';

        if(!($o = parent::query(
                        [
                            'SELECT "message" FROM "'.$table.'" WHERE "hcid" = :hcid',
                            [
                                ':hcid' => $hcid
                            ]
                        ],db::FETCH_OBJ))
         )
            return '(null)';
        return $o->message;
    }

    private function getUsernameFromCid($hcid, $prj = false)
    {
        $table = ($prj ? 'groups_' : '').'comments';
        if(!($o = parent::query(array('SELECT "from" FROM "'.$table.'" WHERE "hcid" = :hcid',array(':hcid' => $hcid)),db::FETCH_OBJ)))
            return '';
        return parent::getUsername($o->from);
    }

    public function getCommentsAfterHcid($hpid,$hcid, $prj = false)
    {
        $table = ($prj ? 'groups_' : '').'posts';
        if(!($o = parent::query(
                        [
                            'SELECT "to","pid","from" FROM "'.$table.'" WHERE "hpid" = :hpid',
                            [
                                ':hpid' => $hpid
                            ]
                        ],db::FETCH_OBJ))
          )
            return false;
        
        return $this->showControl($o->from,$o->to,$hpid,$o->pid,$prj,$hcid);
    }

    public function getLastComments ($hpid, $num, $cycle = 0, $prj = false)
    {
        $table = ($prj ? 'groups_' : '').'posts';
        if($num > 10 || $cycle > 200 || $num <= 0 || $cycle < 0 ||
                !($o = parent::query(
                        [
                            'SELECT "to","pid","from" FROM "'.$table.'" WHERE "hpid" = :hpid',
                            [
                                ':hpid' => $hpid
                            ]
                        ],db::FETCH_OBJ)
                 )
          )
            return false;
        return $this->showControl ($o->from, $o->to, $hpid, $o->pid, $prj, false, $num, $cycle * $num);
    }

    public function delComment($hcid, $prj = false)
    {
        if($prj) {
            if(
                !($o = parent::query(array('SELECT "hpid","from","to",EXTRACT(EPOCH FROM "time") AS time FROM "groups_comments" WHERE "hcid" = :hcid',array(':hcid' => $hcid)),db::FETCH_OBJ)) ||
                !($owner = parent::getOwner($o->to))
              )
                return false;

            $canremovecomment = array_merge($this->project->getMembersAndOwnerFromHpid($o->hpid), (array) $o->from);

            if(in_array($_SESSION['nerdz_id'],$canremovecomment))
            {
                if(
                    db::NO_ERRNO != parent::query(array('DELETE FROM "groups_comments" WHERE "from" = :from AND "to" = :to AND "time" = TO_TIMESTAMP(:time)',array(':from' => $o->from,':to' => $o->to, ':time' => $o->time)),db::FETCH_ERRNO) ||
                    db::NO_ERRNO != parent::query(array('DELETE FROM "groups_comments_notify" WHERE "from" = :from AND "hpid" = :hpid AND "time" = TO_TIMESTAMP(:time)',array(':from' => $o->from,':hpid' => $o->hpid,':time' => $o->time)),db::FETCH_ERRNO)
                  )
                    return false;
            }
            else
                return false;

            if(!($c = parent::query(array('SELECT COUNT("hcid") AS cc FROM "groups_comments" WHERE "hpid" = :hpid AND "from" = :id',array(':hpid' => $o->hpid,':id' => $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
                return false;
        
            if($c->cc == 0)
                if(db::NO_ERRNO != parent::query(array('DELETE FROM "groups_comments_no_notify" WHERE "to" = :id AND "hpid" = :hpid',array(':id' => $_SESSION['nerdz_id'],':hpid' => $o->hpid)),db::FETCH_ERRNO))
                    return false;

            return true;
        }

        //profile
        $ok =  (
            ($o = parent::query(array('SELECT "hpid","from","to",EXTRACT(EPOCH FROM "time") AS time FROM "comments" WHERE "hcid" = :hcid',array(':hcid' => $hcid)),db::FETCH_OBJ)) //cid, from, to, time servono
            &&
            ($owner = parent::query(array('SELECT "to" FROM "posts" WHERE "hpid" = :hpid',array(':hpid' => $o->hpid)),db::FETCH_OBJ))
            &&
            in_array($_SESSION['nerdz_id'],array($o->from,$owner->to)) // == canDelete
            &&
            parent::query(array('DELETE FROM "comments" WHERE "hcid" = :hcid',array(':hcid' => $hcid)),db::FETCH_ERRNO) == db::NO_ERRNO
            &&
            parent::query(array('DELETE FROM "comments_notify" WHERE "from" = :from AND "hpid" = :hpid AND "time" = TO_TIMESTAMP(:time)',array(':from' => $o->from,':hpid' => $o->hpid,':time' => $o->time)),db::FETCH_ERRNO)  == db::NO_ERRNO
        );
        if($ok)
        {
            if(!($c = parent::query(array('SELECT COUNT("hcid") AS cc FROM "comments" WHERE "hpid" = :hpid AND "from" = :id',array(':hpid' => $o->hpid,':id' => $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
                return false;

            if($c->cc == 0)
                if(db::NO_ERRNO != parent::query(array('DELETE FROM "comments_no_notify" WHERE "to" = :id AND "hpid" = :hpid',array(':id' => $_SESSION['nerdz_id'],':hpid' => $o->hpid)),db::FETCH_ERRNO))
                    return false;
            return true;
        }
        return false;
    }

    public function countComments($hpid, $prj = false)
    {
        $table = ($prj ? 'groups_' : '').'comments';

        if(parent::isLogged())
        {
            if(!($o = parent::query(
                            [
                                'SELECT COUNT("hcid") AS cc FROM "'.$table.'" WHERE "hpid" = :hpid AND "from" NOT IN (
                                    SELECT "from" AS a FROM "blacklist" WHERE "to" = :id UNION SELECT "to" AS a FROM "blacklist" WHERE "from" = :id)'.
                                $prj ? '' 
                                : ' AND "to" NOT IN ( SELECT "from" AS a FROM "blacklist" WHERE "to" = :id UNION SELECT "to" AS a FROM "blacklist" WHERE "from" = :id)',
                                [
                                    ':hpid' => $hpid,
                                    ':id'   => $_SESSION['nerdz_id']
                                ]
                            ],db::FETCH_OBJ))
              )
                return 0;
        }
        else {
            if(!($o = parent::query(
                            [
                                'SELECT COUNT("hcid") AS cc FROM "'.$table.'" WHERE "hpid" = :hpid',
                                [
                                    ':hpid' => $hpid
                                ]
                            ],db::FETCH_OBJ))
              )
                return 0;
        }

        return $o->cc;
    }

    private function appendComment($oldMsgObj,$parsedMessage, $prj = false)
    {
        return parent::query(
                [
                    'UPDATE "'.($prj ? 'groups_' : '').'comments" SET message = :message WHERE "hcid" = :hcid',
                    [
                        ':message' => $oldMsgObj->message.'[hr]'.$parsedMessage,
                        ':hcid' => $oldMsgObj->hcid
                    ]
                ],db::FETCH_ERRSTR);
    }

    public function getRevisionsNumber($hcid, $prj = false) {
        $table = ($prj ? 'groups_' : ''). 'comments_revisions';

        $ret = parent::query(
            [
                'SELECT COALESCE( MAX("rev_no"), 0 )  AS "rev_no" FROM "'.$table.'" WHERE "hcid" = :hcid',
                [
                  ':hcid' => $hcid
                ]

            ],
            db::FETCH_OBJ
        );

        return isset($ret->rev_no) ? $ret->rev_no : 0;
    }

    public function getRevision($hcid, $number,  $prj = false) {
        $table = ($prj ? 'groups_' : ''). 'comments_revisions';

        return parent::query(
            [
                'SELECT message, EXTRACT(EPOCH FROM "time") AS time FROM "'.$table.'" WHERE "hcid" = :hcid AND "rev_no" = :number',
                [

                    ':hcid' => $hcid,
                    ':number' => $number
                ]

            ],
            db::FETCH_OBJ
        );
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
                'SELECT "vote" FROM "'.$table.'" WHERE "hcid" = :hcid AND "from" = :from',
                [
                  ':hcid' => $hcid,
                  ':from' => $_SESSION['nerdz_id']
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
              'INSERT INTO '.$table.' (hcid, "from", vote) VALUES(:hcid, :from, :vote)',
              [
                ':hcid' => (int) $hcid,
                ':from' => (int) $_SESSION['nerdz_id'],
                ':vote' => (int) $vote
              ]
            ],
            db::FETCH_ERRNO
        );

        return $ret == db::NO_ERRNO;
    }
}
?>
