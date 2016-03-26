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
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/Autoload.class.php';
use NERDZ\Core\User;
use NERDZ\Core\Captcha;

$user = new User();
$cptcka = new Captcha();
$captcha = isset($_POST['captcha']) ? $_POST['captcha'] : false;

if (!$captcha) {
    die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('MISSING').': '.$user->lang('CAPTCHA')));
}

if (!$cptcka->check($captcha)) {
    die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('WRONG_CAPTCHA')));
}

die(NERDZ\Core\Utils::jsonResponse('ok', 'OK'));
