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
use NERDZ\Core\Db;
use NERDZ\Core\User;
use NERDZ\Core\Captcha;
use NERDZ\Core\IpUtils;

$user = new User();
$cptcka = new Captcha();
$captcha = isset($_POST['captcha']) ? $_POST['captcha'] : false;

if (!$captcha) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('MISSING').': '.$user->lang('CAPTCHA')));
}

if (!$cptcka->check($captcha)) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('WRONG_CAPTCHA')));
}

if ($user->isLogged()) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ALREADY_LOGGED')));
}

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/validateuser.php';

$ret = Db::query(
    [
        'INSERT INTO users ("username","password","name","surname","email","birth_date","lang","board_lang","timezone","remote_addr", "http_user_agent")
        VALUES (:username, crypt(:password, gen_salt(\'bf\', 7)) , :name, :surname, :email, :date, :lang, :lang, :timezone, :remote_addr, :http_user_agent)',
            [
                ':username' => $userData['username'],
                ':password' => $userData['password'],
                ':name' => $userData['name'],
                ':surname' => $userData['surname'],
                ':email' => $userData['email'],
                ':timezone' => $userData['timezone'],
                ':date' => $birth['date'],
                ':lang' => $user->getLanguage(),
                ':remote_addr' => IpUtils::getIp(),
                ':http_user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? htmlspecialchars($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8') : '',
          ],
      ],
    Db::FETCH_ERRSTR
);

if ($ret != Db::NO_ERRSTR) {
    die(NERDZ\Core\Utils::jsonDbResponse($ret));
}

if (!$user->login($userData['username'], $userData['password'], $setCookie = true)) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': Login'));
}

die(NERDZ\Core\Utils::JSONResponse('ok', $user->lang('LOGIN_OK')));
