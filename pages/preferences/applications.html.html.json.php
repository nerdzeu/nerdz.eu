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
use NERDZ\Core\Captcha;
use NERDZ\Core\Db;
use NERDZ\Core\Utils;
use NERDZ\Core\OAuth2Client;

$user = new User();

if (!$user->isLogged()) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('REGISTER')));
}

$id = $_POST['id'] = isset($_POST['id']) && is_numeric($_POST['id']) ? trim($_POST['id']) : false;

if (!NERDZ\Core\Security::csrfControl(isset($_POST['tok']) ? $_POST['tok'] : 0)) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': token'));
}

$client = new OAuth2Client($id);
$info = $client->getObject();

if (!$info || $info->user_id != $_SESSION['id']) {
    die($user->lang('ERROR'));
}

$appData = [];
$appData["id"] = $id;

if (!empty($_POST['redirect_uri']) && !Utils::isValidURL($_POST['redirect_uri'])) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': invalid redirect uri'));
}

if (empty($_POST["description"])) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': description'));
}

$appData["redirect_uri"] = trim($_POST['redirect_uri']);
$appData["description"] = trim($_POST['description']);

foreach ($appData as &$value) {
    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

//htmlspecialchars empty return values FIX
if (count(array_filter($appData)) != count($appData)) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': INVALID UTF-8'));
}


switch (isset($_GET['action']) ? strtolower($_GET['action']) : '') {

case 'update':
        if (Db::NO_ERRNO != Db::query(
            [
                'UPDATE "oauth2_clients" SET "description" = :desc, "redirect_uri" = :redirect_uri WHERE "id" = :id',
                [

                    ':desc' => $appData['description'],
                    ':redirect_uri' => $appData['redirect_uri'],
                    ':id' => $id,
                ],
            ],
            Db::FETCH_ERRNO
        )
        ) {
            die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
        }
        break;
default:
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
    break;
}
die(NERDZ\Core\Utils::JSONResponse('ok', 'OK'));
