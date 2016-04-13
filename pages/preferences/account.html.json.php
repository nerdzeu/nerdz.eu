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
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': referer'));
}

if (!NERDZ\Core\Security::csrfControl(isset($_POST['tok']) ? $_POST['tok'] : 0)) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': token'));
}

if (!$user->isLogged()) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('REGISTER')));
}

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/validateuser.php'; //include $updatedPassword
$params = [
    ':timezone' => $userData['timezone'],
    ':name' => $userData['name'],
    ':surname' => $userData['surname'],
    ':email' => $userData['email'],
    ':gender' => $userData['gender'],
    ':date' => $birth['date'],
    ':id' => $_SESSION['id'],
];

if ($updatedPassword) {
    $params[':password'] = $userData['password'];
}

$ret = Db::query(
    [
        'UPDATE users SET "timezone" = :timezone, "name" = :name,
        "surname" = :surname,"email" = :email,"gender" = :gender, "birth_date" = :date
        '.($updatedPassword ? ', "password" = crypt(:password, gen_salt(\'bf\', 7))' : '').' WHERE counter = :id', $params,
    ], Db::FETCH_ERRSTR);

if ($ret != Db::NO_ERRSTR) {
    die(NERDZ\Core\Utils::jsonDbResponse($ret));
}

if ($updatedPassword && ($cookie = isset($_COOKIE['nerdz_u']))) {
    if (!$user->login(User::getUsername(), $userData['password'], $cookie, $_SESSION['mark_offline'])) {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': Login'));
    }
}

die(NERDZ\Core\Utils::JSONResponse('error', 'OK'));
