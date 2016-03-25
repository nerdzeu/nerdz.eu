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
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\Captcha;
use NERDZ\Core\Db;
use NERDZ\Core\User;

$user = new User();

if(!NERDZ\Core\Security::refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': referer'));

if(!$user->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('REGISTER')));

$capt = new Captcha();

if(!$capt->check(isset($_POST['captcha']) ? $_POST['captcha'] : ''))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('WRONG_CAPTCHA')));

if(Db::NO_ERRNO != Db::query(array('DELETE FROM "users" WHERE "counter" = ?',array($_SESSION['id'])),Db::FETCH_ERRNO))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));

$motivation = !empty($_POST['motivation']) ? htmlentities($_POST['motivation'], ENT_QUOTES,'UTF-8') : false;
if($motivation)
    Db::query(
        [
            'UPDATE "deleted_users" SET "motivation" = :motivation WHERE "counter" = :counter',
            [
                ':motivation' => $motivation,
                ':counter'    => $_SESSION['id']
            ]
        ], Db::NO_RETURN);

$user->logout();

die(NERDZ\Core\Utils::jsonResponse('ok','Bye :('));
