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
use NERDZ\Core\User;

$messages = new Messages();
$user = new User();
$prj = isset($prj);

if (!$user->isLogged()) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('REGISTER')));
}

if (!NERDZ\Core\Security::refererControl()) {
    die(NERDZ\Core\Utils::JSONResponse('error', 'CSRF'));
}

switch (isset($_GET['action']) ? strtolower($_GET['action']) : '') {
case 'add':

    if (empty($_POST['to'])) {
        if ($prj) {
            die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').'a'));
        } else {
            $_POST['to'] = $_SESSION['id'];
        }
    }

    die(NERDZ\Core\Utils::jsonDbResponse(
        $messages->add(
            $_POST['to'],
            isset($_POST['message']) ? $_POST['message'] : '',
            [
                'news' => !empty($_POST['news']),
                'issue' => !empty($_POST['issue']),
                'project' => $prj,
                'language' => !empty($_POST['language']) ? $_POST['language'] : false,
            ]
        )
    )
        );
    break;

case 'del':

    if (!isset($_SESSION['delpost']) || empty($_POST['hpid']) || !is_numeric($_POST['hpid']) || ($_SESSION['delpost'] != $_POST['hpid']) || !$messages->delete($_POST['hpid'], $prj)) {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
    }
    unset($_SESSION['delpost']);
    break;

case 'delconfirm':

    $_SESSION['delpost'] = isset($_POST['hpid']) && is_numeric($_POST['hpid']) ? $_POST['hpid'] : -1;
    die(NERDZ\Core\Utils::JSONResponse('ok', $user->lang('ARE_YOU_SURE')));
    break;

case 'get':

    if (empty($_POST['hpid']) || !is_numeric($_POST['hpid']) || !($message = Messages::getMessage($_POST['hpid'], $prj))) {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').'2'));
    }

    die(NERDZ\Core\Utils::JSONResponse('ok', $message));
    break;

case 'edit':

    if (empty($_POST['hpid']) || !is_numeric($_POST['hpid'])) {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
    }

    die(NERDZ\Core\Utils::jsonDbResponse(
        $messages->edit($_POST['hpid'], $_POST['message'], $prj)
    )
);
    break;

default:

    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').' Wrong request'));
    break;
}

die(NERDZ\Core\Utils::JSONResponse('ok', 'OK'));
