<?php
namespace NERDZ\Core;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';
use PDO;

class comments extends Messages
{
    private $project;
    public function __construct()
    {
        parent::__construct();
        $this->project = new Project();
    }

    private function getCommentsArray($res,$hpid,$luck,$prj,$blist,$gravurl,$users,$cg,$times,$lkd,$glue)
    {
        $i = 0;
        $ret = [];
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
            $ret[$i]['from4link_n'] = \NERDZ\Core\Utils::userLink($users[$o->from]);
            $ret[$i]['message_n'] = parent::bbcode($o->message,1,$cg,1,$o->hcid);
            $ret[$i]['datetime_n'] = parent::getDateTime($o->time);
            $ret[$i]['timestamp_n'] = $o->time;
            $ret[$i]['hcid_n'] = $o->hcid;
            $ret[$i]['hpid_n'] = $hpid;
            $ret[$i]['thumbs_n'] = $this->getThumbs($o->hcid, $prj);
            $ret[$i]['uthumb_n'] = $this->getUserThumb($o->hcid, $prj);
            $ret[$i]['revisions_n'] = $this->getRevisionsNumber($o->hcid, $prj);
            $ret[$i]['caneditcomment_b'] = $o->editable && parent::isLogged() && $o->from == $_SESSION['id'];
            
            if($luck)
            {
                $ret[$i]['canshowlock_b'] = false;
                if(isset($lkd[$o->from]) && !in_array($o->from,$times) && ($_SESSION['id'] != $o->from))
                {
                    $ret[$i]['lock_b'] = true;
                    $times[] = $o->from;
                    $ret[$i]['canshowlock_b'] = true;
                }
                elseif(!in_array($o->from,$times) && ($_SESSION['id'] != $o->from))
                {
                    $ret[$i]['lock_b'] = false;
                    $times[] = $o->from;
                    $ret[$i]['canshowlock_b'] = true;
                }
            }
            else
                $ret[$i]['canshowlock_b'] = $ret[$i]['lock_b'] = false;


            $canremoveusers = $prj ? array_merge($canremoveusers, (array)$o->from) : array($o->from,$o->to);
            $ret[$i]['canremove_b'] = in_array($_SESSION['id'],$canremoveusers);

            ++$i;
        }

        if(parent::isLogged() && $i > 1)
            Db::query(array('DELETE FROM "'.$glue.'comments_notify" WHERE "to" = ? AND "hpid" = ?',array($_SESSION['id'],$hpid)),Db::NO_RETURN);

        return $ret;
    }

    private function showControl($from,$to,$hpid,$pid,$prj = null,$olderThanMe = null,$maxNum = null,$startFrom = 0)
    {
        if(!$prj && in_array($to,parent::getRealBlacklist())) // $to is in my blacklist -> don't show comments
            return [];

        $glue = $prj ? 'groups_' : '';
        $useLimitedQuery = is_numeric ($maxNum) && is_numeric ($startFrom);

        $queryArr = $olderThanMe
            ? [
                'SELECT "from","to",EXTRACT(EPOCH FROM "time") AS time,"message","hcid", "editable" FROM "'.$glue.'comments" WHERE "hpid" = :hpid AND "hcid" > :hcid ORDER BY "hcid"',
                    [
                        ':hpid' => $hpid,
                        ':hcid' => $olderThanMe
                    ]
            ]
            : (
                $useLimitedQuery // sort by hcid, descending, then reverse the order (ascending)
                ? [
                        'SELECT q.from, q.to, EXTRACT(EPOCH FROM q.time) AS time, q.message, q.hcid, q.editable FROM (SELECT "from", "to", "time", "message", "hcid", "editable" FROM "'.$glue.'comments" WHERE "hpid" = :hpid AND "from" NOT IN (SELECT "to" FROM "blacklist" WHERE "from" = :id) AND "to" NOT IN (SELECT "to" FROM "blacklist" WHERE "from" = :id) ORDER BY "hcid" DESC LIMIT :limit OFFSET :offset) AS q ORDER BY q.hcid ASC', 
                        [
                            ':hpid' => $hpid,
                            ':id'   => $_SESSION['id'],
                            ':limit' => $maxNum,
                            ':offset' => $startFrom
                        ]
                  ]
               : [
                     'SELECT "from","to",EXTRACT(EPOCH FROM "time") AS time,"message","hcid", "editable" FROM "'.$glue.'comments" WHERE "hpid" = :hpid ORDER BY "hcid"',
                        [
                           ':hpid' => $hpid
                        ]
                 ]
              );

        if(!($res = Db::query($queryArr, Db::FETCH_STMT)))
            return false;

        if(
            !($f = Db::query(array('SELECT DISTINCT "from" FROM "'.$glue.'comments" WHERE "hpid" = :hpid',array(':hpid' => $hpid)),Db::FETCH_STMT)) ||
            !($ll = Db::query(array('SELECT "from" FROM "'.$glue.'comments_no_notify" WHERE "hpid" = :hpid AND "to" = :id',array(':hpid' => $hpid,':id' => $_SESSION['id'])),Db::FETCH_STMT)) || //quelli da non notificare
            !($r = ($useLimitedQuery ? true : Db::query(array('SELECT "to" AS a FROM "blacklist" WHERE "from" = ?',array($_SESSION['id'])),Db::FETCH_STMT)))
          )
            return false;
        
        $times = $gravurl = $users = $nonot = $lkd = $blist = $ret = [];
        
        if (!$useLimitedQuery)
            $blist = $r->fetchAll(PDO::FETCH_COLUMN);

        $grav = new Gravatar();

        while(($o = $f->fetch(PDO::FETCH_OBJ)))
        {
            $users[$o->from] = parent::getUsername($o->from);
            $gravurl[$o->from] = $grav->getURL($o->from);
            $nonot[] = $o->from;
        }

        $nonot[] = $from;
        $nonot[] = $to;

        $luck = in_array($_SESSION['id'],$nonot);

        while(($o = $ll->fetch(PDO::FETCH_OBJ)))
            $lkd[$o->from] = parent::getUsername($o->from);

        $cg = $prj ? 'gc' : 'pc'; //per txt version code in commenti

        $ret = $this->getCommentsArray($res,$hpid,$luck,$prj,$blist,$gravurl,$users,$cg,$times,$lkd,$glue);

        /* Per il beforeHcid, nel caso in cui nella fase di posting si siano uniti gli ultimi messaggi
           allora l'hpid passato dev'essere quello dell'ultimo messaggio e glielo fetcho. Se non lo è ritorna empty */
        if($olderThanMe && empty($ret))
        {
            if(!($res = Db::query(array('SELECT "from","to",EXTRACT(EPOCH FROM "time") AS time,"message","hcid" FROM "'.$glue.'comments" WHERE "hpid" = :hpid AND "hcid" = :hcid ORDER BY "hcid"',array(':hpid' => $hpid, ':hcid' => $olderThanMe)),Db::FETCH_STMT)))
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
                !($obj = Db::query(
                    [
                        'SELECT "to" FROM "'.$posts.'" WHERE "hpid" = :hpid',
                        [
                            ':hpid' => $hpid
                        ]
                    ],Db::FETCH_OBJ))
                ||
                !($stmt = Db::query(
                    [
                        'SELECT "hpid","from","hcid","message" FROM "'.$comments.'" WHERE "hpid" = :hpid AND "hcid" = (SELECT MAX("hcid") FROM "'.$comments.'" WHERE "hpid" = :hpid)',
                        [
                            ':hpid' => $hpid
                        ],
                    ],Db::FETCH_STMT))
          )
          return 'ERROR';

        $message = trim($this->parseCommentQuotes(htmlspecialchars($message,ENT_QUOTES,'UTF-8')));

        if(($user = $stmt->fetch(PDO::FETCH_OBJ)))
        {
            $expl = explode('[hr]',$user->message);
            $lastAppendedMessage = $expl[count($expl) - 1];

            if(trim($lastAppendedMessage) == $message)
                return 'error: FLOOD'; //simulate Db response

            if($user->from == $_SESSION['id'])
                return $this->appendComment($user,$message, $prj);
        }
        
        return Db::query(
            [
                'INSERT INTO "'.$comments.'" ("from","to","hpid","message") VALUES (:from,:to,:hpid,:message)',
                [
                    ':from' => $_SESSION['id'],
                    ':to' => $obj->to,
                    ':hpid' => $hpid,
                    ':message' => $message
                ]
            ],Db::FETCH_ERRSTR);
    }

    public function getComment($hcid, $prj = false)
    {
        $table = ($prj ? 'groups_' : '').'comments';

        if(!($o = Db::query(
                        [
                            'SELECT "message" FROM "'.$table.'" WHERE "hcid" = :hcid',
                            [
                                ':hcid' => $hcid
                            ]
                        ],Db::FETCH_OBJ))
         )
            return '(null)';
        return $o->message;
    }

    private function getUsernameFromCid($hcid, $prj = false)
    {
        $table = ($prj ? 'groups_' : '').'comments';
        if(!($o = Db::query(array('SELECT "from" FROM "'.$table.'" WHERE "hcid" = :hcid',array(':hcid' => $hcid)),Db::FETCH_OBJ)))
            return '';
        return parent::getUsername($o->from);
    }

    public function getCommentsAfterHcid($hpid,$hcid, $prj = false)
    {
        $table = ($prj ? 'groups_' : '').'posts';
        if(!($o = Db::query(
                        [
                            'SELECT "to","pid","from" FROM "'.$table.'" WHERE "hpid" = :hpid',
                            [
                                ':hpid' => $hpid
                            ]
                        ],Db::FETCH_OBJ))
          )
            return false;
        
        return $this->showControl($o->from,$o->to,$hpid,$o->pid,$prj,$hcid);
    }

    public function getLastComments ($hpid, $num, $cycle = 0, $prj = false)
    {
        $table = ($prj ? 'groups_' : '').'posts';
        if($num > 10 || $cycle > 200 || $num <= 0 || $cycle < 0 ||
                !($o = Db::query(
                        [
                            'SELECT "to","pid","from" FROM "'.$table.'" WHERE "hpid" = :hpid',
                            [
                                ':hpid' => $hpid
                            ]
                        ],Db::FETCH_OBJ)
                 )
          )
          return false;

        return $this->showControl ($o->from, $o->to, $hpid, $o->pid, $prj, false, $num, $cycle * $num);
    }

    public function delComment($hcid, $prj = false)
    {
        if($prj) {
            if(
                !($o = Db::query(array('SELECT "hpid","from","to",EXTRACT(EPOCH FROM "time") AS time FROM "groups_comments" WHERE "hcid" = :hcid',array(':hcid' => $hcid)),Db::FETCH_OBJ)) ||
                !($owner = parent::getOwner($o->to))
              )
                return false;

            $canremovecomment = array_merge($this->project->getMembersAndOwnerFromHpid($o->hpid), (array) $o->from);

            if(in_array($_SESSION['id'],$canremovecomment))
            {
                if(
                    Db::NO_ERRNO != Db::query(array('DELETE FROM "groups_comments" WHERE "from" = :from AND "to" = :to AND "time" = TO_TIMESTAMP(:time)',array(':from' => $o->from,':to' => $o->to, ':time' => $o->time)),Db::FETCH_ERRNO) ||
                    Db::NO_ERRNO != Db::query(array('DELETE FROM "groups_comments_notify" WHERE "from" = :from AND "hpid" = :hpid AND "time" = TO_TIMESTAMP(:time)',array(':from' => $o->from,':hpid' => $o->hpid,':time' => $o->time)),Db::FETCH_ERRNO)
                  )
                    return false;
            }
            else
                return false;

            if(!($c = Db::query(array('SELECT COUNT("hcid") AS cc FROM "groups_comments" WHERE "hpid" = :hpid AND "from" = :id',array(':hpid' => $o->hpid,':id' => $_SESSION['id'])),Db::FETCH_OBJ)))
                return false;
        
            if($c->cc == 0)
                if(Db::NO_ERRNO != Db::query(array('DELETE FROM "groups_comments_no_notify" WHERE "to" = :id AND "hpid" = :hpid',array(':id' => $_SESSION['id'],':hpid' => $o->hpid)),Db::FETCH_ERRNO))
                    return false;

            return true;
        }

        //profile
        $ok =  (
            ($o = Db::query(array('SELECT "hpid","from","to",EXTRACT(EPOCH FROM "time") AS time FROM "comments" WHERE "hcid" = :hcid',array(':hcid' => $hcid)),Db::FETCH_OBJ)) //cid, from, to, time servono
            &&
            ($owner = Db::query(array('SELECT "to" FROM "posts" WHERE "hpid" = :hpid',array(':hpid' => $o->hpid)),Db::FETCH_OBJ))
            &&
            in_array($_SESSION['id'],array($o->from,$owner->to)) // == canDelete
            &&
            Db::query(array('DELETE FROM "comments" WHERE "hcid" = :hcid',array(':hcid' => $hcid)),Db::FETCH_ERRNO) == Db::NO_ERRNO
            &&
            Db::query(array('DELETE FROM "comments_notify" WHERE "from" = :from AND "hpid" = :hpid AND "time" = TO_TIMESTAMP(:time)',array(':from' => $o->from,':hpid' => $o->hpid,':time' => $o->time)),Db::FETCH_ERRNO)  == Db::NO_ERRNO
        );
        if($ok)
        {
            if(!($c = Db::query(array('SELECT COUNT("hcid") AS cc FROM "comments" WHERE "hpid" = :hpid AND "from" = :id',array(':hpid' => $o->hpid,':id' => $_SESSION['id'])),Db::FETCH_OBJ)))
                return false;

            if($c->cc == 0)
                if(Db::NO_ERRNO != Db::query(array('DELETE FROM "comments_no_notify" WHERE "to" = :id AND "hpid" = :hpid',array(':id' => $_SESSION['id'],':hpid' => $o->hpid)),Db::FETCH_ERRNO))
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
            if(!($o = Db::query(
                            [
                                'SELECT COUNT("hcid") AS cc FROM "'.$table.'" WHERE "hpid" = :hpid AND "from" NOT IN (
                                    SELECT "from" AS a FROM "blacklist" WHERE "to" = :id UNION SELECT "to" AS a FROM "blacklist" WHERE "from" = :id)'.
                                    (
                                        $prj ? ''
                                        : ' AND "to" NOT IN ( SELECT "from" AS a FROM "blacklist" WHERE "to" = :id UNION SELECT "to" AS a FROM "blacklist" WHERE "from" = :id)'
                                    ),
                                [
                                    ':hpid' => $hpid,
                                    ':id'   => $_SESSION['id']
                                ]
                            ],Db::FETCH_OBJ))
              )
                return 0;
        }
        else {
            if(!($o = Db::query(
                            [
                                'SELECT COUNT("hcid") AS cc FROM "'.$table.'" WHERE "hpid" = :hpid',
                                [
                                    ':hpid' => $hpid
                                ]
                            ],Db::FETCH_OBJ))
              )
                return 0;
        }

        return $o->cc;
    }

    private function appendComment($oldMsgObj,$parsedMessage, $prj = false)
    {
        return Db::query(
                [
                    'UPDATE "'.($prj ? 'groups_' : '').'comments" SET message = :message WHERE "hcid" = :hcid',
                    [
                        ':message' => $oldMsgObj->message.'[hr]'.$parsedMessage,
                        ':hcid' => $oldMsgObj->hcid
                    ]
                ],Db::FETCH_ERRSTR);
    }

    public function getRevisionsNumber($hcid, $prj = false) {
        $table = ($prj ? 'groups_' : ''). 'comments_revisions';

        $ret = Db::query(
            [
                'SELECT COALESCE( MAX("rev_no"), 0 )  AS "rev_no" FROM "'.$table.'" WHERE "hcid" = :hcid',
                [
                  ':hcid' => $hcid
                ]
            ],
            Db::FETCH_OBJ
        );

        return isset($ret->rev_no) ? $ret->rev_no : 0;
    }

    public function getRevision($hcid, $number,  $prj = false) {
        $table = ($prj ? 'groups_' : ''). 'comments_revisions';

        return Db::query(
            [
                'SELECT message, EXTRACT(EPOCH FROM "time") AS time FROM "'.$table.'" WHERE "hcid" = :hcid AND "rev_no" = :number',
                [

                    ':hcid' => $hcid,
                    ':number' => $number
                ]
            ],
            Db::FETCH_OBJ
        );
    }

    public function getThumbs($hcid, $prj = false) {
        $table = ($prj ? 'groups_' : ''). 'comment_thumbs';

        $ret = Db::query(
            [
                'SELECT SUM("vote") AS "sum" FROM "'.$table.'" WHERE "hcid" = :hcid GROUP BY hcid',
                [
                  ':hcid' => $hcid
                ]

            ],
            Db::FETCH_OBJ
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

        $ret = Db::query(
            [
                'SELECT "vote" FROM "'.$table.'" WHERE "hcid" = :hcid AND "from" = :from',
                [
                  ':hcid' => $hcid,
                  ':from' => $_SESSION['id']
                ]

            ],
            Db::FETCH_OBJ
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

        $ret = Db::query(
            [
              'INSERT INTO '.$table.' (hcid, "from", vote) VALUES(:hcid, :from, :vote)',
              [
                ':hcid' => (int) $hcid,
                ':from' => (int) $_SESSION['id'],
                ':vote' => (int) $vote
              ]
            ],
            Db::FETCH_ERRNO
        );

        return $ret == Db::NO_ERRNO;
    }
}
?>
