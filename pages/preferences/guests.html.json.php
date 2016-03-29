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

if (!NERDZ\Core\Security::refererControl()) {
    die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('ERROR').': referer'));
}

if (!NERDZ\Core\Security::csrfControl(isset($_POST['tok']) ? $_POST['tok'] : 0)) {
    die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('ERROR').': token'));
}

if (!$user->isLogged()) {
    die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('REGISTER')));
}

$id = $_SESSION['id'];

if (!($obj = Db::query(array('SELECT "private" FROM "users" WHERE "counter" = ?', array($id)), Db::FETCH_OBJ))) {
    die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('ERROR')));
}

switch (isset($_GET['action']) ? strtolower($_GET['action']) : '') {
case 'public':
    if ($obj->private == 1) {
        if (Db::NO_ERRNO != Db::query(array('UPDATE "users" SET "private" = FALSE WHERE "counter" = ?', array($id)), Db::FETCH_ERRNO)) {
            die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('ERROR')));
        }
    }
    break;
case 'private':
    if (!$obj->private) {
        if (Db::NO_ERRNO != Db::query(array('UPDATE "users" SET "private" = TRUE WHERE "counter" = ?', array($id)), Db::FETCH_ERRNO)) {
            die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('ERROR')));
        }
    }
    break;
default:
    die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('ERROR')));
    break;
}
die(NERDZ\Core\Utils::jsonResponse('ok', 'OK'));
