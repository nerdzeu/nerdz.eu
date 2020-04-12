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
use NERDZ\Core\User;
use NERDZ\Core\System;

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

$lang = isset($_POST['lang']) && is_string($_POST['lang']) ? trim($_POST['lang']) : '';

if (!in_array($lang, System::getAvailableLanguages())) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
}

switch (isset($_GET['action']) ? strtolower($_GET['action']) : '') {
case 'userlang':
    if (!$user->setLanguage($lang)) {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
    }

    $_SESSION['lang'] = $lang;
    break;

case 'boardlang':
    if (!$user->setBoardLanguage($lang)) {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
    }

    $_SESSION['board_lang'] = $lang;
    break;

default:
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
    break;
}
die(NERDZ\Core\Utils::JSONResponse('ok', 'OK'));
