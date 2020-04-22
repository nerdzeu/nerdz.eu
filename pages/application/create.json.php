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
use NERDZ\Core\OAuth2;
use NERDZ\Core\Utils;

$user = new User();

if (!$user->isLogged()) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('REGISTER')));
}

$cptcka = new Captcha();

$captcha = isset($_POST['captcha']) ? $_POST['captcha'] : false;

if (!$captcha) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('MISSING')."\n".$user->lang('CAPTCHA')));
}
if (!$cptcka->check($captcha)) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('WRONG_CAPTCHA')));
}

foreach ($_POST as &$val) {
    if (!is_array($val)) {
        $val = trim($val);
    }
}


$appData = [];

if (empty($_POST['description']) || !is_string($_POST['description'])) { //always required
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('MUST_COMPLETE_FORM')."\n\n".$user->lang('MISSING').":\n".$user->lang('DESCRIPTION')));
}

if (empty($_POST['name']) || !is_string($_POST['name'])) { //always required
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('MUST_COMPLETE_FORM')."\n\n".$user->lang('MISSING').":\n".$user->lang('NAME')));
}


if (empty($_POST['redirect_uri']) || !is_string($_POST['redirect_uri'])) { //always required
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('MUST_COMPLETE_FORM')."\n\n".$user->lang('MISSING').":\nRedirect URI"));
}

if (!empty($_POST['redirect_uri']) && !Utils::isValidURL($_POST['redirect_uri'])) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': invalid redirect uri'));
}


$appData['description'] = $_POST['description'];
$appData['name'] = $_POST['name'];
$appData['redirect_uri'] = $_POST['redirect_uri'];
$appData['user'] = $_SESSION['id'];

foreach ($appData as &$value) {
    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

//htmlspecialchars empty return values FIX
if (count(array_filter($appData)) != count($appData)) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': INVALID UTF-8'));
}

if (empty($_POST["scopes"]) || !is_array($_POST["scopes"])) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('MUST_COMPLETE_FORM')."\n\n".$user->lang('MISSING').":\nScope"));
}

$scopes = [];
foreach ($_POST["scopes"] as &$scope) {
    if (!OAuth2::isValidScope($scope)) {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('MUST_COMPLETE_FORM')."\n\n".$user->lang('MISSING').":\nScope"));
    }
    $scopes[] = $scope;
}

$appData["scope"] = implode(' ', $scopes);

$ret = Db::query(
    [
        'INSERT INTO oauth2_clients ("name","secret", "description","scope","redirect_uri","user_id")
        VALUES (:name, crypt(:description, gen_salt(\'bf\', 7)),:description , :scope, :redirect_uri, :user_id)
        RETURNING oauth2_clients.*',
            [
                ':name' => $appData['name'],
                ':description' => $appData['description'],
                ':redirect_uri' => $appData['redirect_uri'],
                ':user_id' => $appData['user'],
                ':scope' => $appData["scope"]
            ],
      ],
    Db::FETCH_OBJ
);

if ($ret) {
    die(NERDZ\Core\Utils::JSONResponse('ok', "Going to settings to manage your application..."));
}

die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR'). ": maybe duplicated name"));
