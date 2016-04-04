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
require_once $_SERVER['DOCUMENT_ROOT'].'/class/Autoload.class.php';
use NERDZ\Core\Db;
use NERDZ\Core\Project;
use NERDZ\Core\Utils;
use NERDZ\Core\Messages;

$messages = new Messages();

$limit = isset($_GET['lim']) ? NERDZ\Core\Security::limitControl($_GET['lim'], 20) : 20;
$order = isset($_GET['asc']) && $_GET['asc'] == 1 ? 'ASC' : 'DESC';
$q = empty($_GET['q']) ? '' : htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8');
$orderby = 'time';

$prj = isset($_GET['project']);

$vals = [];
$vals['project_b'] = $prj;

if ($prj) {
    $orderby = $orderby == 'time' ? 'groups_bookmarks.time' : $orderby;
    $query = empty($q)
        ?
        array(
            'SELECT p.*, EXTRACT(EPOCH FROM groups_bookmarks.time) AS time FROM "groups_bookmarks" INNER JOIN "groups_posts" p ON p.hpid = groups_bookmarks.hpid WHERE groups_bookmarks.from = ? ORDER BY '.$orderby.' '.$order.' LIMIT '.$limit,
            array($_SESSION['id']),
        )
        :
        array(
            "SELECT p.*, EXTRACT(EPOCH FROM groups_bookmarks.time) AS time FROM groups_bookmarks INNER JOIN groups_posts p ON p.hpid = groups_bookmarks.hpid WHERE groups_bookmarks.from = ? AND CAST({$orderby} AS TEXT) LIKE ? ORDER BY {$orderby} {$order} LIMIT {$limit}",
            array($_SESSION['id'], "%{$q}%"),
        );

    $linkMethod = 'projectLink';
    $nameMethod = 'getName';
    $object = new Project();
} else {
    $orderby = $orderby == 'time' ? 'bookmarks.time' : $orderby;
    $query = empty($q)
        ?
        array(
            "SELECT p.*, EXTRACT(EPOCH FROM bookmarks.time) AS time FROM bookmarks INNER JOIN posts p ON p.hpid = bookmarks.hpid WHERE bookmarks.from = ? ORDER BY {$orderby} {$order} LIMIT {$limit}",
            array($_SESSION['id']),
        )
        :
        array(
            "SELECT p.*, EXTRACT(EPOCH FROM bookmarks.time) AS time FROM bookmarks INNER JOIN posts p ON p.hpid = bookmarks.hpid WHERE bookmarks.from = ? AND CAST({$orderby} AS TEXT) LIKE ? ORDER BY {$orderby} {$order} LIMIT {$limit}",
            array($_SESSION['id'], "%{$q}%"),
        );

    $linkMethod = 'userLink';
    $nameMethod = 'getUsername';
    $object = $user;
}

$vals['list_a'] = [];

if (($r = Db::query($query, Db::FETCH_STMT))) {
    $i = 0;
    while (($o = $r->fetch(PDO::FETCH_OBJ))) {
        $vals['list_a'][$i] = $messages->getPost($o,
            [
                'project' => $prj,
                'truncate' => true,
            ]);

        $vals['list_a'][$i]['name_n'] = $object->$nameMethod($o->to);
        $vals['list_a'][$i]['preview_n'] = $messages->bbcode(htmlspecialchars(substr(html_entity_decode($o->message, ENT_QUOTES, 'UTF-8'), 0, 256), ENT_QUOTES, 'UTF-8').'...', true);
        $vals['list_a'][$i]['link_n'] = '/'.Utils::$linkMethod($vals['list_a'][$i]['name_n']).$o->pid;
        ++$i;
    }
}

\NERDZ\Core\Security::setNextAndPrevURLs($vals, $limit,
    [
        'order' => $order,
        'query' => $q,
        'field' => empty($_GET['orderby']) ? '' : $_GET['orderby'],
        'validFields' => ['time'],
    ]);

$user->getTPL()->assign($vals);
$user->getTPL()->draw('profile/bookmarks');
