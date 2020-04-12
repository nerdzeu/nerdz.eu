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
use NERDZ\Core\User;
use NERDZ\Core\Config;
use NERDZ\Core\Db;
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

if (isset($_GET['action']) && $_GET['action'] == 'vars') {
    if (isset($_POST['vars']) && is_array($_POST['vars'])) {
        $user->setTemplateVariables($_POST['vars']);
    } else {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': JSON'));
    }
} else {
    $theme = isset($_POST['theme']) && is_string($_POST['theme']) ? trim($_POST['theme']) : '';
    $shorts = [];
    $templates = System::getAvailableTemplates();
    foreach ($templates as $val) {
        $shorts[] = $val['number'];
    }

    if (!in_array($theme, $shorts)) {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
    }

    $column = (Config\MOBILE_HOST == $_SERVER['HTTP_HOST'] ? 'mobile_' : '').'template';

    if (Db::NO_ERRNO != Db::query(
        [
                'UPDATE "profiles" SET "'.$column.'" = :theme WHERE "counter" = :id',
                [
                    ':theme' => $theme,
                    ':id' => $_SESSION['id'],
                ],
            ],
        Db::FETCH_ERRNO
    )) {
        die(NERDZ\Core\Utils::JSONResponse('error', 'Update: '.$user->lang('ERROR')));
    }

    $_SESSION['template'] = $theme;
}

die(NERDZ\Core\Utils::JSONResponse('ok', 'OK'));
