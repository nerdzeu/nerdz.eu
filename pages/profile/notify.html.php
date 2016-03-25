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
use NERDZ\Core\Notification;
use NERDZ\Core\User;
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

$user         = new User();
$notification = new Notification(); // group notification by default

if($user->isLogged())
{
    $vals = [];
    $vals['list_a'] = $notification->show('all', !isset ($_POST['doNotDelete']));

    if(!count($vals['list_a']))
        $vals['list_a'] = $notification->story();
    else
        $notification->updateStory($vals['list_a']);

    $user->getTPL()->assign($vals);
    $user->getTPL()->draw('profile/notify');
}
else
    echo $user->lang('REGISTER');
