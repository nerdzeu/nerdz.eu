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
require_once $_SERVER['DOCUMENT_ROOT'].'/class/Autoload.class.php';
use NERDZ\Core\Pms;
use NERDZ\Core\User;

ob_start('ob_gzhandler');

$user = new User();

if (!$user->isLogged()) {
    die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('REGISTER')));
}

if (!NERDZ\Core\Security::refererControl()) {
    die(NERDZ\Core\Utils::jsonResponse('error', 'No spam or spam-bot here'));
}

$pms = new Pms();

if (empty($_POST['from']) || !is_numeric($_POST['from']) || empty($_POST['to']) || !is_numeric($_POST['to'])) {
    die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('SOMETHING_MISS')));
}

if ($pms->deleteConversation($_POST['from'], $_POST['to'])) {
    die(NERDZ\Core\Utils::jsonResponse('ok', 'OK'));
}

die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('ERROR')));
