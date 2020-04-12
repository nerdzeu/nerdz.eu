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
require_once $_SERVER['DOCUMENT_ROOT'].'/class/vendor/autoload.php';
use MCilloni\Pushed\Pushed;
use MCilloni\Pushed\PushedException;
use NERDZ\Core\User;
use NERDZ\Core\Config;
use NERDZ\Core\Utils;

$user = new User();
try {
    if (!$user->isLogged()) {
        die(Utils::JSONResponse(['ERROR' => 'Not logged']));
    }

    if (!isset($_GET['action'])) {
        die(Utils::JSONResponse(['ERROR' => 'Action not set']));
    }

    $thisUser = $user->getId();

    if (!NERDZ\Core\Security::floodPushRegControl()) {
        die(Utils::JSONResponse(['ERROR' => 'NO SPAM']));
    }

    $pushed = Pushed::connectIp(Config\PUSHED_PORT, Config\PUSHED_IP6);

    $resp = [];

    switch ($_GET['action']) {

    case 'subscribe':
        if (!isset($_POST['service']) || !isset($_POST['deviceId'])) {
            die(Utils::JSONResponse(['ERROR' => 'Field not set']));
        }

        $user->setPush($thisUser, true);

        if (!$pushed->exists($thisUser)) {
            if ($pushed->addUser($thisUser)[0] !== Pushed::$ACCEPTED) {
                die(Utils::JSONResponse(['ERROR' => 'Request rejected']));
            }
        }

        if ($pushed->subscribe($thisUser, $_POST['service'], $_POST['deviceId'])[0] !== Pushed::$ACCEPTED) {
            die(Utils::JSONResponse(['ERROR' => 'Request rejected']));
        }

        $resp = ['ACCEPTED' => 'Ok'];

        break;

    case 'unsubscribe': {

        if (!isset($_POST['service']) || !isset($_POST['deviceId'])) {
            die(Utils::JSONResponse(['ERROR' => 'Field not set']));
        }

        $user->setPush($thisUser, true);

        if (!$pushed->exists($thisUser)) {
            die(Utils::JSONResponse(['ERROR' => 'No push for this user']));
        }

        if ($pushed->unsubscribe($thisUser, $_POST['service'], $_POST['deviceId'])[0] !== Pushed::$ACCEPTED) {
            die(Utils::JSONResponse(['ERROR' => 'Request rejected']));
        }

        $resp = ['ACCEPTED' => 'Ok'];

        break;
    }

    default:
        die(Utils::JSONResponse(['ERROR' => "Unknown request: '".addslashes($_GET['action'])."'"]));

    }
} catch (PushedException $e) {
    $resp = ['ERROR' => 'Internal Server Error'];
}

die(Utils::JSONResponse($resp));
