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

require_once $_SERVER['DOCUMENT_ROOT'].'/class/Autoload.class.php';
use NERDZ\Core\User;
use NERDZ\Core\Db;

$user = new User();
ob_start(array('NERDZ\\Core\\Utils', 'minifyHTML'));

if (!NERDZ\Core\Security::refererControl()) {
    die($user->lang('ERROR'));
}

if (!$user->isLogged()) {
    die($user->lang('REGISTER'));
}

$vals = [];

if (!($r = Db::query(
    [
        'SELECT c.id, c.name, c.secret, c.redirect_uri, c.user_id
        FROM oauth2_clients c
        WHERE c.user_id = :id',
        [
            ':id' => $_SESSION['id'],
        ],
    ],
    Db::FETCH_STMT
))) {
    $vals['myapplications_a'] = [];
} else {
    $i = 0;
    while (($o = $r->fetch(PDO::FETCH_OBJ))) {
        $vals['myapplications_a'][$i]['name_n'] = $o->name;
        $vals['myapplications_a'][$i]['secret_n'] = $o->secret;
        $vals['myapplications_a'][$i]['redirecturi_n'] = $o->redirect_uri;
        $vals['myapplications_a'][$i]['id_n'] = $o->id;
        ++$i;
    }
}
$user->getTPL()->assign($vals);
$user->getTPL()->draw('preferences/applications');
