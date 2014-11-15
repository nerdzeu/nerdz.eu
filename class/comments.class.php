<?php
namespace NERDZ\Core;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';
use PDO;

class Comments extends Messages
{
    public function __construct()
    {
        parent::__construct();
    }

    public function canEdit($comment, $project = false)
    {
        return $comment['editable'] && $this->user->isLogged() && $comment['from'] == $_SESSION['id'];
    }

    public function canRemove($comment, $project = false)
    {
        if(!$this->user->isLogged())
            return false;

        if($project)
            $canremoveusers = array_merge(
                    $this->project->getMembersAndOwnerFromHpid($comment['hpid']),
                    (array)$comment['from']
                );
        else
        {
            if(!($owner = Db::query(
                    [
                        'SELECT "to" FROM "posts" WHERE "hpid" = :hpid',
                        [
                            ':hpid' => $comment['hpid']
                        ]
                    ],Db::FETCH_OBJ)))
                return false;

            $canremoveusers = [ $owner->to, $comment['from'], $comment['to'] ];
        }

        return in_array($_SESSION['id'],$canremoveusers);
    }

    public function edit($hcid, $message, $project = false)
    {
        $message = static::parseQuote(htmlspecialchars($message,ENT_QUOTES,'UTF-8'));
        $table = ($project ? 'groups_' : '').'comments';

        if(!($obj = Db::query(
            [
                'SELECT "editable", "from", "hpid" FROM "'.$table.'" WHERE "hcid" = :hcid',
                [
                    ':hcid' => $hcid
                ]
            ],Db::FETCH_OBJ)) ||
            !$this->canEdit((array)$obj, $project)
        )
        return 'ERROR';

        return Db::query(
            [
                'UPDATE "'.$table.'" SET "message" = :message WHERE "hcid" = :hcid',
                [
                    ':message' => $message,
                    ':hcid'    => $hcid
                ]
            ],Db::FETCH_ERRSTR);
    }

    private function getArray($res,$hpid,$luck,$project,$blist,$gravurl,$users,$cg,$times,$lkd,$glue)
    {
        $i = 0;
        $ret = [];

        while(($o = $res->fetch(PDO::FETCH_OBJ)))
        {
            if(in_array($o->from,$blist))
                continue;

            $ret[$i]['fromid_n']         = $o->from;
            $ret[$i]['gravatarurl_n']    = $gravurl[$o->from];
            $ret[$i]['toid_n']           = $o->to;
            $ret[$i]['from_n']           = $users[$o->from];
            $ret[$i]['uid_n']            = "c{$o->hcid}";
            $ret[$i]['from4link_n']      = Utils::userLink($users[$o->from]);
            $ret[$i]['message_n']        = parent::bbcode($o->message,1,$cg,1,$o->hcid);
            $ret[$i]['datetime_n']       = $this->user->getDateTime($o->time);
            $ret[$i]['timestamp_n']      = $o->time;
            $ret[$i]['hcid_n']           = $o->hcid;
            $ret[$i]['hpid_n']           = $hpid;
            $ret[$i]['thumbs_n']         = $this->getThumbs($o->hcid, $project);
            $ret[$i]['uthumb_n']         = $this->getUserThumb($o->hcid, $project);
            $ret[$i]['revisions_n']      = $this->getRevisionsNumber($o->hcid, $project);
            $ret[$i]['caneditcomment_b'] = $this->canEdit((array)$o);

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

            $ret[$i]['canremove_b'] = $this->canRemove((array)$o, $project);

            ++$i;
        }

        if($this->user->isLogged() && $i > 1)
            Db::query(array('DELETE FROM "'.$glue.'comments_notify" WHERE "to" = ? AND "hpid" = ?',array($_SESSION['id'],$hpid)),Db::NO_RETURN);

        return $ret;
    }

    private function showControl($from,$to,$hpid,$pid,$project = null,$olderThanMe = null,$maxNum = null,$startFrom = 0, $singleComment = false)
    {
        if(!$project && in_array($to,$this->user->getBlacklist())) // $to is in my blacklist -> don't show comments
            return [];

        $glue = $project ? 'groups_' : '';
        $useLimitedQuery = is_numeric ($maxNum) && is_numeric ($startFrom);

        $queryArr = $olderThanMe
            ? [
                'SELECT "from","to",EXTRACT(EPOCH FROM "time") AS time,"message","hcid", "editable", "hpid"
                FROM "'.$glue.'comments"
                WHERE "hpid" = :hpid AND "hcid" > :hcid
                      AND "from" NOT IN (SELECT "to" FROM "blacklist" WHERE "from" = :id)
                ORDER BY "hcid"',
                    [
                        ':hpid' => $hpid,
                        ':hcid' => $olderThanMe,
                        ':id'   => $_SESSION['id']
                    ]
                ]
                : (
                    $useLimitedQuery // sort by hcid, descending, then reverse the order (ascending)
                    ? [
                        'SELECT q.from, q.to, EXTRACT(EPOCH FROM q.time) AS time, q.message, q.hcid, q.editable, q.hpid FROM (
                            SELECT "from", "to", "time", "message", "hcid", "editable", "hpid"
                            FROM "'.$glue.'comments"
                            WHERE "hpid" = :hpid '.($singleComment ? 'AND "hcid" = :hcid' : '').'
                                AND "from" NOT IN (SELECT "to" FROM "blacklist" WHERE "from" = :id)
                            ORDER BY "hcid" DESC LIMIT :limit OFFSET :offset
                        ) AS q ORDER BY q.hcid ASC',
                        array_merge(
                            [
                                ':hpid'   => $hpid,
                                ':id'     => $_SESSION['id'],
                                ':limit'  => $maxNum,
                                ':offset' => $startFrom
                            ],
                            $singleComment ? [ ':hcid' => $singleComment ] : []
                        )
                    ]
                    : [
                        'SELECT "from","to",EXTRACT(EPOCH FROM "time") AS time,"message","hcid", "editable", "hpid"
                        FROM "'.$glue.'comments"
                        WHERE "hpid" = :hpid
                            AND "from" NOT IN (SELECT "to" FROM "blacklist" WHERE "from" = :id)
                         ORDER BY "hcid"',
                            [
                                ':hpid' => $hpid,
                                ':id'   => $_SESSION['id']
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

        while(($o = $f->fetch(PDO::FETCH_OBJ)))
        {
            $users[$o->from] = User::getUsername($o->from);
            $gravurl[$o->from] = $this->user->getGravatar($o->from);
            $nonot[] = $o->from;
        }

        $nonot[] = $from;
        $nonot[] = $to;

        $luck = in_array($_SESSION['id'],$nonot);

        while(($o = $ll->fetch(PDO::FETCH_OBJ)))
            $lkd[$o->from] = User::getUsername($o->from);

        $cg = $project ? 'gc' : 'pc'; //per txt version code in commenti

        $ret = $this->getArray($res,$hpid,$luck,$project,$blist,$gravurl,$users,$cg,$times,$lkd,$glue);

        /* Per il beforeHcid, nel caso in cui nella fase di posting si siano uniti gli ultimi messaggi
        allora l'hpid passato dev'essere quello dell'ultimo messaggio e glielo fetcho. Se non lo è ritorna empty */
        if($olderThanMe && empty($ret))
        {
            if(!($res = Db::query(array('SELECT "from","to",EXTRACT(EPOCH FROM "time") AS time,"message","hcid", "editable", "hpid" FROM "'.$glue.'comments" WHERE "hpid" = :hpid AND "hcid" = :hcid ORDER BY "hcid"',array(':hpid' => $hpid, ':hcid' => $olderThanMe)),Db::FETCH_STMT)))
                return false;
            $ret = $this->getArray($res,$hpid,$luck,$project,$blist,$gravurl,$users,$cg,$times,$lkd,$glue);
        }

        return $ret;
    }

    public function add($hpid,$message, $project = false)
    {
        $posts    = ($project ? 'groups_' : '').'posts';
        $comments = ($project ? 'groups_' : '').'comments';

        if(!($stmt = Db::query(
            [
                'SELECT "hpid","from","hcid","message", "editable" FROM "'.$comments.'" WHERE "hpid" = :hpid AND "hcid" = (SELECT MAX("hcid") FROM "'.$comments.'" WHERE "hpid" = :hpid)',
                    [
                        ':hpid' => $hpid
                    ],
            ],Db::FETCH_STMT)))
            return 'ERROR';

        $message = trim(static::parseQuote(htmlspecialchars($message,ENT_QUOTES,'UTF-8')));

        if(($user = $stmt->fetch(PDO::FETCH_OBJ)))
        {
            $expl = explode('[hr]',$user->message);
            $lastAppendedMessage = $expl[count($expl) - 1];

            if(trim($lastAppendedMessage) == $message)
                return 'error: FLOOD'; //simulate Db response

            if($user->from == $_SESSION['id'] && $user->editable)
                return $this->append($user,$message, $project);
        }

        return Db::query(
            [
                'INSERT INTO "'.$comments.'" ("from","hpid","message") VALUES (:from,:hpid,:message)',
                    [
                        ':from'    => $_SESSION['id'],
                        ':hpid'    => $hpid,
                        ':message' => $message
                    ]
                ],Db::FETCH_ERRSTR);
    }

    public static function getMessage($hcid, $project = false)
    {
        $table = ($project ? 'groups_' : '').'comments';

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

    private static function getUsernameFromCid($hcid, $project = false)
    {
        $table = ($project ? 'groups_' : '').'comments';
        if(!($o = Db::query(array('SELECT "from" FROM "'.$table.'" WHERE "hcid" = :hcid',array(':hcid' => $hcid)),Db::FETCH_OBJ)))
            return '';
        return User::getUsername($o->from);
    }

    public function getCommentsAfterHcid($hpid,$hcid, $project = false)
    {
        $table = ($project ? 'groups_' : '').'posts';
        if(!($o = Db::query(
            [
                'SELECT "to","pid","from" FROM "'.$table.'" WHERE "hpid" = :hpid',
                [
                    ':hpid' => $hpid
                ]
            ],Db::FETCH_OBJ))
        )
        return false;

        return $this->showControl($o->from,$o->to,$hpid,$o->pid,$project,$hcid);
    }

    public function getLastComments ($hpid, $num, $cycle = 0, $project = false)
    {
        $table = ($project ? 'groups_' : '').'posts';
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

        return $this->showControl ($o->from, $o->to, $hpid, $o->pid, $project, false, $num, $cycle * $num);
    }

    public function getAll($hpid, $project = false)
    {
        $table = ($project ? 'groups_' : '').'posts';

        if(!($o = Db::query(
                [
                    'SELECT "to","pid","from" FROM "'.$table.'" WHERE "hpid" = :hpid',
                    [
                        ':hpid' => $hpid
                    ]
                ],Db::FETCH_OBJ)))
            return false;

        return $this->showControl($o->from, $o->to, $hpid, $o->pid, $project);
    }


    public function get($hcid, $project = false)
    {
        $commentTable = ($project ? 'groups_' : '').'comments';
        $postTable    = ($project ? 'groups_' : '').'posts';

        if(!($o = Db::query(
            [
                'SELECT "hpid" FROM "'.$commentTable.'" WHERE "hcid" = :hcid',
                [
                    ':hcid' => $hcid
                ]
            ], Db::FETCH_OBJ))
        )
            return 'ERROR';

        $hpid = $o->hpid;

        if(!($o = Db::query(
            [
                'SELECT "to","pid","from" FROM "'.$postTable.'" WHERE "hpid" = :hpid',
                [
                    ':hpid' => $hpid
                ]
            ],Db::FETCH_OBJ)))
            return 'ERROR';

        return $this->showControl($o->from, $o->to, $hpid, $o->pid, $project, false, 1, 0, $hcid);
    }

    public function delete($hcid, $project = false)
    {
        if($project) {
            if(!($o = Db::query(array('SELECT "hpid","from","to",EXTRACT(EPOCH FROM "time") AS time FROM "groups_comments" WHERE "hcid" = :hcid',array(':hcid' => $hcid)),Db::FETCH_OBJ)))
                return false;

            $canremovecomment = array_merge($this->project->getMembersAndOwnerFromHpid($o->hpid), (array) $o->from);

            if($this->canRemove((array)$o,  $project))
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
        if(!($o = Db::query(array('SELECT "hpid","from","to",EXTRACT(EPOCH FROM "time") AS time FROM "comments" WHERE "hcid" = :hcid',array(':hcid' => $hcid)),Db::FETCH_OBJ)))
            return false;

        if($this->canRemove((array)$o, $project))
        {
            $ok = (
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
        }

        return false;
    }

    private function append($oldMsgObj,$parsedMessage, $project = false)
    {
        if(empty($parsedMessage))
            return 'error: NO_EMPTY_MESSAGE';

        return Db::query(
            [
                'UPDATE "'.($project ? 'groups_' : '').'comments" SET message = :message WHERE "hcid" = :hcid',
                [
                    ':message' => $oldMsgObj->message.'[hr]'.$parsedMessage,
                    ':hcid'    => $oldMsgObj->hcid
                ]
            ],Db::FETCH_ERRSTR);
    }

    public function getRevisionsNumber($hcid, $project = false) {
        $table = ($project ? 'groups_' : ''). 'comments_revisions';

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

    public function getRevision($hcid, $number,  $project = false) {
        $table = ($project ? 'groups_' : ''). 'comments_revisions';

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

    public function getThumbs($hcid, $project = false) {
        $table = ($project ? 'groups_' : ''). 'comment_thumbs';

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

    public function getUserThumb($hcid, $project = false) {
        if (!$this->user->isLogged()) {
            return 0;
        }

        $table = ($project ? 'groups_' : ''). 'comment_thumbs';

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

    public function setThumbs($hcid, $vote, $project = false) {
        if (!$this->user->isLogged())
            return Utils::$REGISTER_DB_MESSAGE;

        $table = ($project ? 'groups_' : ''). 'comment_thumbs';

        return Db::query(
            [
                'INSERT INTO '.$table.' (hcid, "from", vote) VALUES(:hcid, :from, :vote)',
                    [
                        ':hcid' => (int) $hcid,
                        ':from' => (int) $_SESSION['id'],
                        ':vote' => (int) $vote
                    ]
                ],
                Db::FETCH_ERRSTR
            );
    }

    public static function parseQuote($message)
    {
        $i = 0;
        $pattern = '#\[quote=([0-9]+)\|p\]#i';
        while(preg_match($pattern,$message) && (++$i < 11))
            $message = preg_replace_callback($pattern,function($m) {
                $username = comments::getUsernameFromCid($m[1], true);
                return $username
                    ? '[commentquote=[user]'.$username.'[/user]]'.comments::getMessage($m[1], true).'[/commentquote]'
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
                    ? '[commentquote=[user]'.$username.'[/user]]'.comments::getMessage($m[1]).'[/commentquote]'
                    : '';
                    },$message,1);

        if($i == 11)
            $message = preg_replace('#\[quote=([0-9]+)\|u\]#i','',$message);

		//Brought back quotes
		return $message;
    }
}
?>
