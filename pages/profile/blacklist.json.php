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
require_once $_SERVER['DOCUMENT_ROOT'].'/class/Autoload.class.php';

use NERDZ\Core\User;
use NERDZ\Core\Db;

$user = new User();

if (!$user->isLogged() || empty($_POST['id']) || !is_numeric($_POST['id'])) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('LOGIN')));
}

switch (isset($_GET['action']) ? strtolower($_GET['action']) : '') {
case 'del':
    if (Db::NO_ERRNO != Db::query(
        [
            'DELETE FROM "blacklist" WHERE "from" = :me AND "to" = :to',
            [
                ':me' => $_SESSION['id'],
                ':to' => $_POST['id'],
            ],
        ], Db::FETCH_ERRNO)) {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
    }
    break;

case 'add':
    $motivation = empty($_POST['motivation']) ? '' : htmlspecialchars(trim($_POST['motivation']), ENT_QUOTES, 'UTF-8');
    if (!($user->hasInBlacklist($_POST['id']))) {
        if (Db::NO_ERRNO != Db::query(
            [
                'INSERT INTO "blacklist"("from","to","motivation") VALUES (:me,:to,:motivation)',
                    [
                        ':me' => $_SESSION['id'],
                        ':to' => $_POST['id'],
                        ':motivation' => $motivation,
                    ],
                ], Db::FETCH_ERRNO)) {
            die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
        }
    } else {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').'1'));
    }
    break;

default:
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').'2'));
    break;
}
die(NERDZ\Core\Utils::JSONResponse('ok', 'OK'));
