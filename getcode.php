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
use NERDZ\Core\Messages;
use NERDZ\Core\Db;

$user = new Messages();

$ncode = isset($_GET['ncode']) && is_numeric($_GET['ncode']) && intval($_GET['ncode']) > 0 ? $_GET['ncode'] : 1;
--$ncode;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
} elseif (isset($_GET['gid']) && is_numeric($_GET['gid'])) {
    $gid = intval($_GET['gid']);
}

if (!isset($id) || !isset($gid)) {
    if (isset($_GET['pcid']) && is_numeric($_GET['pcid'])) {
        $pcid = intval($_GET['pcid']);
    }

    if (isset($_GET['gcid']) && is_numeric($_GET['gcid'])) {
        $gcid = intval($_GET['gcid']);
    }
}

if ((isset($id) || isset($gid)) && isset($_GET['pid']) && is_numeric($_GET['pid'])) {
    $pid = intval($_GET['pid']);
}

if ((isset($id) || isset($gid)) && isset($pid)) {
    $new = isset($id) ? $id : $gid;
    if (!($o = Db::query(
        [
            'SELECT "message" FROM "'.(isset($id) ? '' : 'groups_').'posts" WHERE "pid" = :pid AND "to" = :new',
                [
                    ':pid' => $pid,
                    ':new' => $new,
                ],
            ],
        Db::FETCH_OBJ
    ))) {
        die('Error');
    }
} elseif (isset($pcid) || isset($gcid)) {
    $new = isset($pcid) ? $pcid : $gcid;
    if (!($o = Db::query(
        [
            'SELECT "message" FROM "'.(isset($pcid) ? '' : 'groups_').'comments" WHERE "hcid" = :hcid',
                [
                    ':hcid' => $new,
                ],
            ],
        Db::FETCH_OBJ
    ))) {
        die('error');
    }
} else {
    die();
}
$codes = $user->getCodes($o->message);
if (isset($codes[$ncode]['code']) && isset($codes[$ncode]['lang'])) {
    switch (strtolower(trim($codes[$ncode]['lang']))) {
    case 'js':
    case 'javascript':
    case 'jquery':
        header('Content-type: application/javascript; charset=utf-8');
        break;

    case 'css':
        header('Content-Type: text/css; charset=utf-8');
        break;

    default:
        header('Content-Type: text/plain; charset=utf-8');
        break;
    }
    die(html_entity_decode($codes[$ncode]['code'], ENT_QUOTES, 'UTF-8'));
}
header('Content-Type: text/plain; charset=utf-8');
die('Wrong GET parameters');
