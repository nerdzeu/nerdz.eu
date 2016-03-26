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
use NERDZ\Core\Db;

$user = new NERDZ\Core\User();
ob_start(array('NERDZ\\Core\\Utils', 'minifyHTML'));

if (!$user->isLogged()) {
    die($user->lang('REGISTER'));
}

if (!($obj = Db::query(array('SELECT * FROM "users" WHERE "counter" = ?', array($_SESSION['id'])), Db::FETCH_OBJ))) {
    die($user->lang('ERROR'));
}

$vals = [];
$vals['username_n'] = $obj->username;
$vals['name_n'] = $obj->name;
$vals['surname_n'] = $obj->surname;
$vals['timezone_n'] = $obj->timezone;
$vals['ismale_b'] = $obj->gender == 1;
$vals['email_n'] = $obj->email;
$now = date('o');
$vals['years_a'] = array_reverse(range($now - 100, $now - 1));
$vals['months_a'] = range(1, 12);
$vals['days_a'] = range(1, 31);
$date = explode('-', $obj->birth_date);
$vals['year_n'] = $date[0];
$vals['month_n'] = $date[1];
$vals['day_n'] = $date[2];
$vals['timezones_a'] = DateTimeZone::listIdentifiers();

$user->getTPL()->assign($vals);
$user->getTPL()->draw('preferences/account');
