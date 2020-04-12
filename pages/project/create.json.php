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

$create = true; //required by validateproject.php
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/validateproject.php';
try {
    Db::getDb()->beginTransaction();
    Db::query(
        [
            'INSERT INTO groups ("description","name") VALUES (:description,:name)',
            [
                ':description' => $projectData['description'],
                ':name' => $projectData['name'],
            ],
        ],
        Db::NO_RETURN
    );

    $o = Db::query(
        [
            'SELECT counter FROM groups WHERE name = :name',
            [
                ':name' => $projectData['name'],
            ],
         ],
        Db::FETCH_OBJ
    );

    Db::query(
        [
            'INSERT INTO groups_owners("from", "to")  VALUES(:owner, :group)',
            [
                ':owner' => $projectData['owner'],
                ':group' => $o->counter,
            ],
        ],
        Db::NO_RETURN
    );

    Db::getDb()->commit();
} catch (\PDOException $e) {
    Db::getDb()->rollBack();
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
}

die(NERDZ\Core\Utils::JSONResponse('ok', 'OK'));
