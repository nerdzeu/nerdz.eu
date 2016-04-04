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
use NERDZ\Core\Db;
use NERDZ\Core\IpUtils;
use NERDZ\Core\User;
use NERDZ\Core\Utils;

$user = new User();

if (!$user->isLogged()) {
    die(Utils::jsonResponse('error', $user->lang('REGISTER')));
}

$viewonline = empty($_SESSION['mark_offline']) ? '1' : '0';

if (Db::NO_ERRNO != Db::query(array('UPDATE "users" SET "last" = NOW(), "viewonline" = :on WHERE "counter" = :id', array(':on' => $viewonline, ':id' => $_SESSION['id'])), Db::FETCH_ERRNO)) {
    die(Utils::jsonResponse('error', 'Time'));
}

if (!($o = Db::query(array('SELECT "remote_addr","http_user_agent" FROM "users" WHERE "counter" = :id', array(':id' => $_SESSION['id'])), Db::FETCH_OBJ))) {
    die(Utils::jsonResponse('error', $user->lang('ERROR')));
}

if (empty($o->remote_addr) || empty($_SESSION['remote_addr']) || ($o->remote_addr != IpUtils::getIp())) {
    if (Db::NO_ERRNO != Db::query(array('UPDATE "users" SET "remote_addr" = :addr WHERE "counter" = :id', array(':addr' => IpUtils::getIp(), ':id' => $_SESSION['id'])), Db::FETCH_ERRNO)) {
        die(Utils::jsonResponse('error', 'IP'));
    }
    $_SESSION['remote_addr'] = IpUtils::getIp();
}

if (empty($o->http_user_agent) || empty($_SESSION['http_user_agent']) || ($o->http_user_agent != $_SERVER['HTTP_USER_AGENT'])) {
    if (Db::NO_ERRNO != Db::query(array('UPDATE "users" SET "http_user_agent" = :uag WHERE "counter" = :id', array(':uag' => htmlspecialchars($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8'), ':id' => $_SESSION['id'])), Db::FETCH_ERRNO)) {
        die(Utils::jsonResponse('error', 'UA'));
    }

    $_SESSION['http_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
}
die(Utils::jsonResponse('ok', 'OK'));
