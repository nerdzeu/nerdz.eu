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
use NERDZ\Core\User;
use NERDZ\Core\Config;
use NERDZ\Core\System;

$user = new User();
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

if(!$user->isLogged())
    die($user->lang('REGISTER'));

$vals = [];

$vals['themes_a'] = [];
$i = 0;
$templates = System::getAvailableTemplates();

foreach($templates as $val)
{
    $vals['themes_a'][$i]['tplno_n'] = $val['number'];
    $vals['themes_a'][$i]['tplname_n'] = $val['name'];
    ++$i;
}
$vals['mytplno_n'] = $user->getTemplate($_SESSION['id']);
$vals['mobile_b'] = User::isOnMobileHost();

$user->getTPL()->assign($vals);
$user->getTPL()->draw('preferences/themes');
