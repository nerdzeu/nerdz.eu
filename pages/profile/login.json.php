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

$user = new User();

if ($user->isLogged()) {
    die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('ALREADY_LOGGED')));
}

if (!NERDZ\Core\Security::refererControl()) {
    die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('ERROR').': referer'));
}

if (!NERDZ\Core\Security::csrfControl(isset($_POST['tok']) ? $_POST['tok'] : 0)) {
    die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('ERROR').': token'));
}

$username = isset($_POST['username']) ? htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8') : false;
$pass = isset($_POST['password']) ? $_POST['password'] : false;

if (!$username || !$pass) {
    die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('INSERT_USER_PASS')));
}

if (is_numeric($username) || filter_var($username, FILTER_VALIDATE_EMAIL)) {
    $username = User::getUsername($username);
}

if (!$username) {
    die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('WRONG_USER_OR_PASSWORD')));
}

if ($user->login($username, $pass, isset($_POST['setcookie']), isset($_POST['offline']))) {
    die(NERDZ\Core\Utils::jsonResponse('ok', $user->lang('LOGIN_OK')));
}

die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('WRONG_USER_OR_PASSWORD')));
