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
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\User;
use NERDZ\Core\Db;

$user = new User();
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

if(!NERDZ\Core\Security::refererControl())
    die($user->lang('ERROR'));

if(!$user->isLogged())
    die($user->lang('REGISTER'));

$vals = [];

if(!($r = Db::query(
    [
        'SELECT g."name", g.counter FROM "groups" g INNER JOIN "groups_owners" go
        ON go."to" = g.counter
        WHERE go."from" = :id',
        [
            ':id' => $_SESSION['id']
        ]
    ],Db::FETCH_STMT)))
    $vals['myprojects_a'] = [];
else
{
    $i = 0;
    while(($o = $r->fetch(PDO::FETCH_OBJ)))
    {
        $vals['myprojects_a'][$i]['name_n'] = $o->name;
        $vals['myprojects_a'][$i]['name4link_n'] = \NERDZ\Core\Utils::projectLink($o->name);
        $vals['myprojects_a'][$i]['id_n'] = $o->counter;
        ++$i;
    }
}
$user->getTPL()->assign($vals);
$user->getTPL()->draw('preferences/projects');
