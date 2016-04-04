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
use NERDZ\Core\Pms;
use NERDZ\Core\User;

$pms = new Pms();
$user = new User();

if (!$user->isLogged()) {
    die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('REGISTER')));
}

if (!NERDZ\Core\Security::refererControl()) {
    die(NERDZ\Core\Utils::jsonResponse('error', 'No SPAM/BOT'));
}

if (empty($_POST['to'])) {
    die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('SOMETHING_MISS')));
}

if (!($toid = $user->getId(trim($_POST['to'])))) { //getId DON'T what htmlspecialchars in parameter
    die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('USER_NOT_FOUND')));
}

foreach ($_POST as &$val) {
    $val = htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

die(NERDZ\Core\Utils::jsonDbResponse($pms->send($toid, $_POST['message'])));
