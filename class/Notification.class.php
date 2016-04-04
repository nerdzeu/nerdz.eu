<?php
/*
Copyright (C) 2016 Paolo Galeone <nessuno@nerdz.eu>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace NERDZ\Core;

use PDO;

require_once $_SERVER['DOCUMENT_ROOT'].'/class/Autoload.class.php';

class Notification
{
    private $rag, $cachekey, $user;

    const USER_COMMENT = 'profile_comments';
    const USER_POST = 'new_post_on_profile';
    const USER_FOLLOW = 'new_follower';
    const USER_MENTION = 'new_mention_on_profile_post';

    const PROJECT_COMMENT = 'project_comments';
    const PROJECT_POST = 'news_project';
    const PROJECT_FOLLOW = 'new_project_follower';
    const PROJECT_MEMBER = 'new_project_member';
    const PROJECT_OWNER = 'you_are_the_new_project_owner';
    const PROJECT_MENTION = 'new_mention_on_project_post';

    public function __construct($group = true)
    {
        $this->user = new User();
        $this->cachekey = $this->user->isLogged() ? "{$_SESSION['id']}notifystory".Config\SITE_HOST : '';
        $this->rag = $group;
    }

    public function countPms()
    {
        if (!($o = Db::query(
            [
                'SELECT COUNT(DISTINCT "from") as cc FROM ( 
                    SELECT "from" FROM "pms" WHERE "to" = :id AND "to_read" = TRUE
                ) AS tmp1',
                [
                    ':id' => $_SESSION['id'],
                ],
            ], Db::FETCH_OBJ))) {
            return 0;
        }

        return $o->cc;
    }

    public function count($what = null, $rag = null)
    {
        $c = -1;
        $this->rag = $rag;

        if (empty($what)) {
            $what = 'all';
        }

        switch (trim(strtolower($what))) {
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
        case 'projects_owner':
            $c = $this->countProjectOwner();
            break;
        case 'mentions':
            $c = $this->countMentions();
            break;
        case 'all':
            $c = $this->countUserComments()   +
                $this->countUserPosts()       +
                $this->countProjectComments() +
                $this->countProjectPosts()    +
                $this->countFollow()          +
                $this->countProjectFollow()   +
                $this->countProjectMember()   +
                $this->countProjectOwner()    +
                $this->countMentions();
            break;
        }

        return $c;
    }

    private function countUserComments()
    {
        $q = $this->rag
            ? 'SELECT COUNT("hpid") AS cc FROM (SELECT DISTINCT "hpid" FROM "comments_notify" WHERE "to" = :id AND to_notify = TRUE GROUP BY "hpid") AS c'
            : 'SELECT COUNT("to") AS cc FROM "comments_notify" WHERE "to" = :id AND to_notify = TRUE';

        if (!($o = Db::query(
            [
                $q,
                [
                    ':id' => $_SESSION['id'],
                ],
            ], Db::FETCH_OBJ))) {
            return 0;
        }

        return $o->cc;
    }

    private function countUserPosts()
    {
        if (!($o = Db::query(
            [
                'SELECT COUNT("hpid") AS cc FROM "posts_notify" WHERE "to" = :id AND to_notify = TRUE',
                    [
                        ':id' => $_SESSION['id'],
                    ],
                ], Db::FETCH_OBJ))) {
            return 0;
        }

        return $o->cc;
    }

    private function countProjectComments()
    {
        $q = $this->rag
            ? 'SELECT COUNT("hpid") AS cc FROM (SELECT DISTINCT "hpid" FROM groups_comments_notify WHERE "to" = :id AND to_notify = TRUE GROUP BY "hpid") AS c'
            : 'SELECT COUNT("to") AS cc FROM "groups_comments_notify" WHERE "to" = :id AND to_notify = TRUE';

        if (!($o = Db::query(
            [
                $q,
                [
                    ':id' => $_SESSION['id'],
                ],
            ], Db::FETCH_OBJ))) {
            return 0;
        }

        return $o->cc;
    }

    private function countProjectPosts()
    {
        if (!($o = Db::query(
            [
                'SELECT COUNT("from") AS cc FROM "groups_notify" WHERE "to" = :id AND to_notify = TRUE',
                    [
                        ':id' => $_SESSION['id'],
                    ],
                ], Db::FETCH_OBJ))) {
            return 0;
        }

        return $o->cc;
    }

    private function countFollow()
    {
        if (!($o = Db::query(
            [
                'SELECT COUNT("to") AS cc FROM "followers" WHERE "to" = :id AND "to_notify" = TRUE',
                    [
                        ':id' => $_SESSION['id'],
                    ],
                ], Db::FETCH_OBJ))) {
            return 0;
        }

        return $o->cc;
    }

    private function countProjectFollow()
    {
        if (!($o = Db::query(
            [
                'SELECT COUNT("to") AS cc FROM "groups_followers" WHERE "to" IN (
                    SELECT "to" FROM "groups_owners" WHERE "from" = :id
                ) AND "to_notify" = TRUE',
                [
                    ':id' => $_SESSION['id'],
                ],
            ], Db::FETCH_OBJ))) {
            return 0;
        }

        return $o->cc;
    }

    private function countProjectMember()
    {
        if (!($o = Db::query(
            [
                'SELECT COUNT("to") AS cc FROM "groups_members" WHERE "from" = :id AND "to_notify" = TRUE',
                [
                    ':id' => $_SESSION['id'],
                ],
            ], Db::FETCH_OBJ))) {
            return 0;
        }

        return $o->cc;
    }

    private function countProjectOwner()
    {
        if (!($o = Db::query(
            [
                'SELECT COUNT("to") AS cc FROM "groups_owners" WHERE "from" = :id AND "to_notify" = TRUE',
                [
                    ':id' => $_SESSION['id'],
                ],
            ], Db::FETCH_OBJ))) {
            return 0;
        }

        return $o->cc;
    }

    private function countMentions()
    {
        if (!($o = Db::query(
            [
                'SELECT COUNT("to") AS cc FROM "mentions" WHERE "to" = :id AND "to_notify" = TRUE',
                [
                    ':id' => $_SESSION['id'],
                ],
            ], Db::FETCH_OBJ))) {
            return 0;
        }

        return $o->cc;
    }

    public function show($what = null, $del = true)
    {
        $ret = [];
        if (empty($what)) {
            $what = 'all';
        }

        switch (trim(strtolower($what))) {
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
        case 'projects_owner':
            $ret = $this->getProjectOwner($del);
            break;
        case 'mentions':
            $ret = $this->getMentions();
            break;
        case 'all':
            $ret = array_merge(
                $this->getUserComments($del),
                    $this->getUserPosts($del),
                    $this->getProjectComments($del),
                    $this->getProjectPosts($del),
                    $this->getUserFollowers($del),
                    $this->getProjectFollowers($del),
                    $this->getProjectMember($del),
                    $this->getProjectOwner($del),
                    $this->getMentions($del)
                );
            break;
        }
        usort($ret, array(__CLASS__, 'echoSort'));

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
        $row = !empty($row)  ? $row  : false;

        $ret = [];
        if (!$row) {
            return $ret;
        }

        $ret['fromid_n'] = $row->from;
        $ret['from_n'] = User::getUsername($row->from);
        $ret['from4link_n'] = Utils::userLink($ret['from_n']);
        $ret['type_n'] = $type;

        if ($post) {
            $ret['hpid_n'] = $row->hpid;
            $ret['pid_n'] = $post->pid;
            if ($this->isProject($type)) {
                $ret['to_n'] = Project::getName($post->to);
                $ret['to4link_n'] = Utils::projectLink($ret['to_n']).$ret['pid_n'];
            } else {
                $ret['to_n'] = User::getUsername($post->to);
                $ret['to4link_n'] = Utils::userLink($ret['to_n']).$ret['pid_n'];
            }
        } else { // followers - members
            $ret['toid_n'] = $row->to;
            if ($this->isProject($type)) {
                $ret['to_n'] = Project::getName($row->to);
                $ret['to4link_n'] = Utils::projectLink($ret['to_n']);
            } else {
                $ret['to_n'] = User::getUsername($row->to);
                $ret['to4link_n'] = Utils::userLink($ret['to_n']);
            }
        }

        $ret['date_n'] = $this->user->getDate($row->time);
        $ret['time_n'] = $this->user->getTime($row->time);
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
                FROM "comments_notify" n WHERE n."to" = :id AND to_notify = TRUE'.(
                    $this->rag
                    ? ' AND n.time = (SELECT MAX("time") FROM "comments_notify" WHERE hpid = n.hpid AND "to" = n."to" AND to_notify = TRUE)'
                    : ''
                ),
                [
                    ':id' => $_SESSION['id'],
                ],
            ], Db::FETCH_STMT);

        while (($o = $result->fetch(PDO::FETCH_OBJ)) && ($p = Db::query(
            [
                'SELECT "from","to","pid" FROM "posts" WHERE "hpid" = :hpid',
                [
                    ':hpid' => $o->hpid,
                ],
            ], Db::FETCH_OBJ))
        ) {
            $ret[$i++] = $this->get(
                [
                    'row' => $o,
                    'post' => $p,
                ], static::USER_COMMENT);
        }

        if ($del) {
            Db::query(
                [
                    'UPDATE "comments_notify" SET to_notify = FALSE WHERE "to" = :id',
                    [
                        ':id' => $_SESSION['id'],
                    ],
                ], Db::NO_RETURN);
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
                ON p.hpid = n.hpid WHERE n."to" = :id AND to_notify = TRUE',
                [
                    ':id' => $_SESSION['id'],
                ],
            ], Db::FETCH_STMT);

        $to = User::getUsername($_SESSION['id']);

        while (($o = $result->fetch(PDO::FETCH_OBJ)) && ($p = Db::query(
            [
                'SELECT "from","to","pid" FROM "posts" WHERE "hpid" = :hpid',
                [
                    ':hpid' => $o->hpid,
                ],
            ], Db::FETCH_OBJ)
        )) {
            $ret[$i++] = $this->get(
                [
                    'row' => $o,
                    'post' => $p,
                ], static::USER_POST);
        }

        if ($del) {
            Db::query(
                [
                    'UPDATE "posts_notify" SET to_notify = FALSE WHERE "to" = :id',
                    [
                        ':id' => $_SESSION['id'],
                    ],
                ], Db::NO_RETURN);
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
                FROM "groups_comments_notify" n WHERE n."to" = :id AND to_notify = TRUE'.(
                    $this->rag
                    ? ' AND n.time = (SELECT MAX("time") FROM "groups_comments_notify" WHERE hpid = n.hpid AND "to" = n."to"  AND to_notify = TRUE)'
                    : ''
                ),
                [
                    ':id' => $_SESSION['id'],
                ],
            ], Db::FETCH_STMT);

        while (($o = $result->fetch(PDO::FETCH_OBJ)) && ($p = Db::query(
            [
                'SELECT "from","to","pid" FROM "groups_posts" WHERE "hpid" = :hpid',
                [
                    ':hpid' => $o->hpid,
                ],
            ], Db::FETCH_OBJ))
        ) {
            $ret[$i++] = $this->get(
                [
                    'row' => $o,
                    'post' => $p,
                ], static::PROJECT_COMMENT);
        }

        if ($del) {
            Db::query(
                [
                    'UPDATE "groups_comments_notify" SET to_notify = FALSE WHERE "to" = :id',
                    [
                        ':id' => $_SESSION['id'],
                    ],
                ], Db::NO_RETURN);
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
                ON p.hpid = n.hpid WHERE n."to" = :id AND to_notify = TRUE',
                [
                    ':id' => $_SESSION['id'],
                ],
            ], Db::FETCH_STMT);

        while (($o = $result->fetch(PDO::FETCH_OBJ)) && ($p = Db::query(
            [
                'SELECT "from","to","pid" FROM "groups_posts" WHERE "hpid" = :hpid',
                [
                    ':hpid' => $o->hpid,
                ],
            ], Db::FETCH_OBJ)
        )) {
            $ret[$i++] = $this->get(
                [
                    'row' => $o,
                    'post' => $p,
                ], static::PROJECT_POST);
        }

        if ($del) {
            Db::query(
                [
                    'UPDATE "groups_notify" SET to_notify = FALSE WHERE "to" = :id',
                    [
                        ':id' => $_SESSION['id'],
                    ],
                ], Db::NO_RETURN);
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
                        ':id' => $_SESSION['id'],
                    ],
                ], Db::FETCH_STMT);

        while (($o = $result->fetch(PDO::FETCH_OBJ))) {
            $ret[$i++] = $this->get(
                [
                    'row' => $o,
                ], static::USER_FOLLOW);
        }

        if ($del) {
            Db::query(
                [
                    'UPDATE "followers" SET "to_notify" = FALSE WHERE "to" = :id',
                    [
                        ':id' => $_SESSION['id'],
                    ],
                ], Db::NO_RETURN);
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
                    SELECT "to" FROM "groups_owners" WHERE "from" = :id
                ) AND "to_notify" = TRUE',
                [
                    ':id' => $_SESSION['id'],
                ],
            ], Db::FETCH_STMT);

        while (($o = $result->fetch(PDO::FETCH_OBJ))) {
            $ret[$i++] = $this->get(
                [
                    'row' => $o,
                ], static::PROJECT_FOLLOW);
        }

        if ($del) {
            Db::query(
                [
                    'UPDATE "groups_followers" SET "to_notify" = FALSE WHERE "to" IN (
                        SELECT "to" FROM "groups_owners" WHERE "from" = :id
                    )',
                    [
                        ':id' => $_SESSION['id'],
                    ],
                ], Db::NO_RETURN);
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
                    ':id' => $_SESSION['id'],
                ],
            ], Db::FETCH_STMT);

        while (($o = $result->fetch(PDO::FETCH_OBJ))) {
            $ret[$i++] = $this->get(
                [
                    'row' => $o,
                ], static::PROJECT_MEMBER);
        }

        if ($del) {
            Db::query(
                [
                    'UPDATE "groups_members" SET "to_notify" = FALSE WHERE "from" = :id',
                    [
                        ':id' => $_SESSION['id'],
                    ],
                ], Db::NO_RETURN);
        }

        return $ret;
    }

    private function getProjectOwner($del)
    {
        $ret = [];
        $i = 0;
        $result = Db::query(
            [
                'SELECT "from", "to", EXTRACT(EPOCH FROM "time") AS time FROM "groups_owners" WHERE "from" = :id AND "to_notify" = TRUE',
                [
                    ':id' => $_SESSION['id'],
                ],
            ], Db::FETCH_STMT);

        while (($o = $result->fetch(PDO::FETCH_OBJ))) {
            $ret[$i++] = $this->get(
                [
                    'row' => $o,
                ], static::PROJECT_OWNER);
        }

        if ($del) {
            Db::query(
                [
                    'UPDATE "groups_owners" SET "to_notify" = FALSE WHERE "from" = :id',
                    [
                        ':id' => $_SESSION['id'],
                    ],
                ], Db::NO_RETURN);
        }

        return $ret;
    }

    private function getMentions($del)
    {
        $ret = [];
        $i = 0;
        $result = Db::query(
            [
                'SELECT "from", "to", "g_hpid", "u_hpid", EXTRACT(EPOCH FROM "time") AS time FROM "mentions" WHERE "to" = :id AND "to_notify" = TRUE',
                [
                    ':id' => $_SESSION['id'],
                ],
            ], Db::FETCH_STMT);

        while ($o = $result->fetch(PDO::FETCH_OBJ)) {
            if (empty($o->g_hpid)) {
                $table = 'posts';
                $field = 'u_hpid';
                $type = static::USER_MENTION;
            } else {
                $table = 'groups_posts';
                $field = 'g_hpid';
                $type = static::PROJECT_MENTION;
            }

            if (($p = Db::query(
                [
                    'SELECT "from","to","pid" FROM "'.$table.'" WHERE "hpid" = :hpid',
                    [
                        ':hpid' => $o->$field,
                    ],
                ], Db::FETCH_OBJ))) {
                $o->hpid = $o->$field;

                $ret[$i++] = $this->get(
                    [
                        'row' => $o,
                        'post' => $p,
                    ], $type);
            }
        }

        if ($del) {
            Db::query(
                [
                    'UPDATE "mentions" SET "to_notify" = FALSE WHERE "to" = :id',
                    [
                        ':id' => $_SESSION['id'],
                    ],
                ], Db::NO_RETURN);
        }

        return $ret;
    }

    public function story()
    {
        if (!($ret = Utils::apc_get($this->cachekey))) {
            return Utils::apc_set($this->cachekey, function () {
                if (!($o = Db::query(
                    [
                        'SELECT "notify_story" FROM "users" WHERE "counter" = :id',
                        [
                            ':id' => $_SESSION['id'],
                        ],
                    ], Db::FETCH_OBJ))
                ) {
                    return [];
                }

                return json_decode($o->notify_story, true);
            }, 300);
        }

        return $ret;
    }

    public function updateStory($new)
    {
        $old = $this->story();
        if (empty($old)) {
            if (Db::NO_ERRNO != Db::query(
                [
                    'UPDATE "users" SET "notify_story" = :story WHERE "counter" = :id',
                    [
                        ':story' => json_encode($new),
                        ':id' => $_SESSION['id'],
                    ],
                ], Db::FETCH_ERRNO)) {
                return false;
            }
        } else {
            $new = array_reverse($new);
            if (($c = count($old)) > 15) {
                for ($i = 15;$i < $c;++$i) {
                    unset($old[$i]);
                }
                $c = count($new);
                for ($i = 0;$i < $c;++$i) {
                    array_unshift($old, $new[$i]);
                }
            } else {
                for ($i = 0, $c = count($new);$i < $c;++$i) {
                    array_unshift($old, $new[$i]);
                }
            }

            if (Db::NO_ERRNO != Db::query(
                [
                    'UPDATE "users" SET "notify_story" = :story WHERE "counter" = :id',
                    [
                        ':story' => json_encode($old),
                        ':id' => $_SESSION['id'],
                    ],
                ], Db::FETCH_ERRNO)) {
                return false;
            }
        }
        apc_delete($this->cachekey);

        return true;
    }

    private static function echoSort($a, $b) //callback
    {
        return $a['timestamp_n'] == $b['timestamp_n'] ? 0 : $a['timestamp_n'] > $b['timestamp_n'] ? -1 : 1;
    }
}
