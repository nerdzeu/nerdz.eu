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
ob_start(array('NERDZ\\Core\\Utils', 'minifyHTML'));

use NERDZ\Core\User;

$user = new User();

if (!$user->isLogged()) {
    die($user->lang('REGISTER'));
}

if (!NERDZ\Core\Security::refererControl()) {
    die($user->lang('ERROR'));
}

switch (isset($_GET['action']) ? strtolower($_GET['action']) : '') {
case 'get':
    //fa tutto lei compresa la gestione di $_POST[hpid]
    $hpid = isset($_POST['hpid']) ? $_POST['hpid'] : -1;
    $draw = true;
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/profile/singlepost.html.php';
    break;

default:
    die($user->lang('ERROR'));
    break;
}
