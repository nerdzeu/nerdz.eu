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
use NERDZ\Core\Db;

if (!($stmt = Db::query('SELECT counter, username, motivation ,EXTRACT(EPOCH FROM "time") AS time FROM "deleted_users" ORDER BY "time" DESC', Db::FETCH_STMT))) {
    echo $user->lang('ERROR');
} else {
    $i = 0;
    $ret = [];
    while (($o = $stmt->fetch(PDO::FETCH_OBJ))) {
        $ret[$i]['id_n'] = $o->counter;
        $ret[$i]['username_n'] = $o->username;
        $ret[$i]['date_n'] = $user->getDate($o->time);
        $ret[$i]['time_n'] = $user->getTime($o->time);
        $ret[$i]['motivation_n'] = $o->motivation;
        ++$i;
    }

    $user->getTPL()->assign('list_a', $ret);
    $user->getTPL()->draw('base/deleted');
}
