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
use NERDZ\Core\Comments;

$user = new User();
$comments = new Comments();

if (!$user->isLogged()) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('REGISTER')));
}

if (!NERDZ\Core\Security::refererControl()) {
    die(NERDZ\Core\Utils::JSONResponse('error', 'CSRF'));
}

$prj = isset($prj);

switch (isset($_GET['action']) ? strtolower($_GET['action']) : '') {
case 'add':
    $hpid = isset($_POST['hpid']) && is_numeric($_POST['hpid']) ? $_POST['hpid']  : false;

    if (!$hpid) {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
    }

    die(NERDZ\Core\Utils::jsonDbResponse($comments->add($hpid, $_POST['message'], $prj)));

case 'del':
    $hcid = isset($_POST['hcid']) && is_numeric($_POST['hcid']) ? $_POST['hcid'] : false;

    if (!$hcid || !$comments->delete($hcid, $prj)) {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
    }
    break;

case 'get':
    if (empty($_POST['hcid']) || !($message = Comments::getMessage($_POST['hcid'], $prj))) {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
    }

    die(NERDZ\Core\Utils::JSONResponse('ok', $message));

case 'edit':
    if (empty($_POST['hcid'])) {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
    }

    die(NERDZ\Core\Utils::jsonDbResponse($comments->edit($_POST['hcid'], $_POST['message'], $prj)));

default:
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
}
die(NERDZ\Core\Utils::JSONResponse('ok', 'OK'));
