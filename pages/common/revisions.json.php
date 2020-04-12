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

$user = new User();

if (isset($_POST['comment'])) {
    $message = new NERDZ\Core\Comments();
    if (!isset($_POST['hcid']) || !is_numeric($_POST['hcid'])) {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': no hcid'));
    }
    $id = $_POST['hcid'];
} else {
    $message = new NERDZ\Core\Messages();
    if (!isset($_POST['hpid']) || !is_numeric($_POST['hpid'])) {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': no hpid'));
    }
    $id = $_POST['hpid'];
}

$revNo = isset($_POST['revNo']) && is_numeric($_POST['revNo']) && $_POST['revNo'] >= 1 ? $_POST['revNo'] : 0;

if (!$revNo) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': invalid revNo'));
}

if (!$user->isLogged()) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('REGISTER')));
}

$rev = $message->getRevision($id, $revNo, isset($prj));

die(is_object($rev) ?
    NERDZ\Core\Utils::JSONResponse(
        [
            'datetime' => $user->getDate($rev->time).' '.$user->getTime($rev->time),
            'message' => $message->bbcode($rev->message),
        ]
    ) :
        NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
