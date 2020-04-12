<?php
/*
Copyright (C) 2010-2020 Paolo Galeone <nessuno@nerdz.eu>

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
// require_once $prj, $path variables
if (!isset($prj, $path)) {
    die('$prj, $path required');
}



require_once $_SERVER['DOCUMENT_ROOT'].'/class/Autoload.class.php';

ob_start(array('NERDZ\\Core\\Utils', 'minifyHTML'));

$user = new NERDZ\Core\User();
$messages = new NERDZ\Core\Messages();

$logged = $user->isLogged();

// boards
$id = isset($_POST['id']) && is_numeric($_POST['id']) ? $_POST['id'] : false;
$limit = isset($_POST['limit']) ? NERDZ\Core\Security::limitControl($_POST['limit'], 10)     : 10;
$beforeHpid = isset($_POST['hpid']) && is_numeric($_POST['hpid']) ? $_POST['hpid'] : false;

// homepage
if (isset($_POST['onlyfollowed'])) {
    $lang = false;
    $onlyfollowed = true;
} else {
    $lang = isset($_POST['lang']) ? $_POST['lang'] : false;
    $onlyfollowed = false;
}

$vote = isset($_POST['vote']) && is_string($_POST['vote']) ? trim($_POST['vote']) : false;

//search
$specific = isset($_GET['specific']);
$action = isset($_GET['action']) && $_GET['action'] === 'profile' ? 'profile' : 'project';
$search = !empty($_POST['q']) ? trim(htmlspecialchars($_POST['q'], ENT_QUOTES, 'UTF-8')) : false;

//rewrite $path if searching not in home
if ($specific) {
    $path = $action;
    $prj = $action == 'project';
}

$vals = [];
$method = $id ? 'getPosts' : 'getHome';

$vals['list_a'] = $messages->$method(
    array_merge(
        ['id' => $id],
        ['project' => $prj],
        ['truncate' => true], // always truncate in postlist
        $limit          ? ['limit' => $limit]         : [],
        $beforeHpid     ? ['hpid' => $beforeHpid]    : [],
        $onlyfollowed   ? ['onlyfollowed' => $onlyfollowed]  : [],
        $lang           ? ['lang' => $lang]          : [],
        $search         ? ['search' => $search]        : []
    )
);

if (empty($vals['list_a']) || (!$logged && $beforeHpid)) {
    die('');
} //empty so javascript client code stop making requsts

$vals['count_n'] = count($vals['list_a']);

$user->getTPL()->assign($vals);
$user->getTPL()->draw($path.'/postlist');
