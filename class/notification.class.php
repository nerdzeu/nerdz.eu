<?php
namespace NERDZ\Core;
use PDO;

require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

class Notification
{
    private $rag, $cachekey, $user;

    const USER_COMMENT = 'profile_comments';
    const USER_POST    = 'new_post_on_profile';
    const USER_FOLLOW  = 'new_follower';

    const PROJECT_COMMENT = 'project_comments';
    const PROJECT_POST    = 'news_project';
    const PROJECT_FOLLOW  = 'new_project_follower';
    const PROJECT_MEMBER  = 'new_project_member';

    public function __construct($group = true)
    {
        $this->user     = new User();
        $this->cachekey = $this->user->isLogged() ? "{$_SESSION['id']}notifystory".Config\SITE_HOST : '';
        $this->rag      = $group;
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
        case 'projects_posts':
            $c = $this->countProjectPosts();
            break;
        case 'follow':
            $c = $this->countFollow();
            break;
        case 'projects_follow':
            $c = $this->countProjectFollow();
            break;
        case 'projects_members':
            $c = $this->countProjectMember();
            break;
        case 'all':
            $c = $this->countUserComments()   +
                $this->countUserPosts()       +
                $this->countProjectComments() +
                $this->countProjectPosts()    +
                $this->countFollow()          +
                $this->countProjectFollow()   +
                $this->countProjectMember();
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
            return 0;

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
                return 0;

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
            return 0;

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
                return 0;
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
                return 0;

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
            return 0;

        return $o->cc;
    }

    private function countProjectMember()
    {
        if(!($o = Db::query(
            [
                'SELECT COUNT("to") AS cc FROM "groups_members" WHERE "from" = :id AND "to_notify" = TRUE',
                [
                    ':id' => $_SESSION['id']
                ]
            ],Db::FETCH_OBJ)))
            return 0;

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
        case 'projects_member':
            $ret = $this->getProjectMember($del);
            break;
        case 'all':
            $ret = array_merge(
                $this->getUserComments($del),
                    $this->getUserPosts($del),
                    $this->getProjectComments($del),
                    $this->getProjectPosts($del),
                    $this->getUserFollowers($del),
                    $this->getProjectFollowers($del),
                    $this->getProjectMember($del)
                );
            break;
        }
        usort($ret,array(__CLASS__,'echoSort'));
        return $ret;
    }

    private function isProject($type)
    {
        return strpos($type, 'project') !== false;
    }

    private function get($params, $type)
    {
        extract($params);
        $post = !empty($post) ? $post : false;
        $row  = !empty($row)  ? $row  : false;

        $ret = [];
        if(!$row)
            return $ret;

        $ret['fromid_n']    = $row->from;
        $ret['from_n']      = User::getUsername($row->from);
        $ret['from4link_n'] = Utils::userLink($ret['from_n']);
        $ret['type_n']      = $type;

        if($post)
        {
            $ret['hpid_n']    = $row->hpid;
            $ret['pid_n']     = $post->pid;
            if($this->isProject($type))
            {
                $ret['to_n']      = Project::getName($post->to);
                $ret['to4link_n'] = Utils::projectLink($ret['to_n']).$ret['pid_n'];
            } else {
                $ret['to_n'] = User::getUsername($post->to);
                $ret['to4link_n']  = Utils::userLink($ret['to_n']).$ret['pid_n'];
            }
        } else { // followers - members
            $ret['toid_n'] = $row->to;
            if($this->isProject($type)) {
                $ret['to_n']   = Project::getName($row->to);
                $ret['to4link_n'] = Utils::projectLink($ret['to_n']);
            } else {
                $ret['to_n']      = User::getUsername($row->to);
                $ret['to4link_n'] = Utils::userLink($ret['to_n']);
            }
        }

        $ret['datetime_n']  = $this->user->getDateTime($row->time);
        $ret['timestamp_n'] = $row->time;

        return $ret;
    }

    private function getUserComments($del)
    {
        $ret = [];
        $i = 0;
        $result = Db::query(
            [
                'SELECT "from","to", "hpid", EXTRACT(EPOCH FROM "time") AS time
                FROM "comments_notify" n WHERE n."to" = :id'. (
                    $this->rag
                    ? ' AND n.time = (SELECT MAX("time") FROM "comments_notify" WHERE hpid = n.hpid AND "to" = n."to")'
                    : ''
                ),
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
            $ret[$i++] = $this->get(
                [
                    'row'  => $o,
                    'post' => $p
                ], static::USER_COMMENT);
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
                'SELECT p."pid",n."hpid", n."from", n."to", EXTRACT(EPOCH FROM n."time") AS time
                FROM "posts_notify" n JOIN "posts" p
                ON p.hpid = n.hpid WHERE n."to" = :id',
                [
                    ':id' => $_SESSION['id']
                ]
            ],Db::FETCH_STMT);

        $to = User::getUsername($_SESSION['id']);

        while(($o = $result->fetch(PDO::FETCH_OBJ)) && ($p = Db::query(
            [
                'SELECT "from","to","pid" FROM "posts" WHERE "hpid" = :hpid',
                [
                    ':hpid' => $o->hpid
                ]
            ],Db::FETCH_OBJ)
        ))
        {
            $ret[$i++] = $this->get(
                [
                    'row'  => $o,
                    'post' => $p
                ], static::USER_POST);
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
                'SELECT "from", "to", "hpid",EXTRACT(EPOCH FROM "time") AS time
                FROM "groups_comments_notify" n WHERE n."to" = :id'.(
                    $this->rag
                    ? ' AND n.time = (SELECT MAX("time") FROM "groups_comments_notify" WHERE hpid = n.hpid AND "to" = n."to")'
                    : ''
                ),
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
            $ret[$i++] = $this->get(
                [
                    'row'  => $o,
                    'post' => $p
                ], static::PROJECT_COMMENT);
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
                'SELECT p."pid",n."hpid", n."from", n."to", EXTRACT(EPOCH FROM n."time") AS time
                FROM "groups_notify" n JOIN "groups_posts" p
                ON p.hpid = n.hpid WHERE n."to" = :id',
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
            ],Db::FETCH_OBJ)
        ))
        {
            $ret[$i++] = $this->get(
                [
                    'row'  => $o,
                    'post' => $p
                ], static::PROJECT_POST);
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
                'SELECT "from", "to", EXTRACT(EPOCH FROM "time") AS time FROM "followers" WHERE "to" = :id AND "to_notify" = TRUE',
                    [
                        ':id' => $_SESSION['id']
                    ]
                ],Db::FETCH_STMT);

        while(($o = $result->fetch(PDO::FETCH_OBJ)))
            $ret[$i++] = $this->get(
                [
                    'row' => $o
                ], static::USER_FOLLOW);

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
                'SELECT "from", "to", EXTRACT(EPOCH FROM "time") AS time FROM "groups_followers" WHERE "to" IN (
                    SELECT "counter" FROM "groups" WHERE "owner" = :id
                ) AND "to_notify" = TRUE',
                [
                    ':id' => $_SESSION['id']
                ]
            ],Db::FETCH_STMT);

        while(($o = $result->fetch(PDO::FETCH_OBJ)))
            $ret[$i++] = $this->get(
                [
                    'row' => $o
                ], static::PROJECT_FOLLOW);

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

    private function getProjectMember($del)
    {
        $ret = [];
        $i = 0;
        $result = Db::query(
            [
                'SELECT "from", "to", EXTRACT(EPOCH FROM "time") AS time FROM "groups_members" WHERE "from" = :id AND "to_notify" = TRUE',
                [
                    ':id' => $_SESSION['id']
                ]
            ],Db::FETCH_STMT);

        while(($o = $result->fetch(PDO::FETCH_OBJ)))
            $ret[$i++] = $this->get(
                [
                    'row' => $o
                ], static::PROJECT_MEMBER);

        if($del) {
            Db::query(
                [
                    'UPDATE "groups_members" SET "to_notify" = FALSE WHERE "from" = :id',
                    [
                        ':id' => $_SESSION['id']
                    ]
                ],Db::NO_RETURN);
        }
        return $ret;
    }

    public function story()
    {
        if(!($ret = Utils::apc_get($this->cachekey)))
            return Utils::apc_set($this->cachekey, function() {
                if(!($o = Db::query(
                    [
                        'SELECT "notify_story" FROM "users" WHERE "counter" = :id',
                        [
                            ':id' => $_SESSION['id']
                        ]
                    ],Db::FETCH_OBJ))
                )
                return [];

                return json_decode($o->notify_story,true);
            }, 300);
        return $ret;
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

    private static function echoSort($a,$b) //callback
    {
        return $a['timestamp_n'] == $b['timestamp_n'] ? 0 : $a['timestamp_n'] > $b['timestamp_n'] ? -1 : 1;
    }
}
?>
