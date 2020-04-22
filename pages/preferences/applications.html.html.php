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
use NERDZ\Core\OAuth2Client;
use NERDZ\Core\User;

$user = new User();
ob_start(array('NERDZ\\Core\\Utils', 'minifyHTML'));

$id = isset($_POST['id']) && is_numeric($_POST['id']) ? $_POST['id'] : false;

if (!$id || !$user->isLogged()) {
    die($user->lang('ERROR'));
}

$client = new OAuth2Client($id);
$info = $client->getObject();

if (!$info || $info->user_id != $_SESSION['id']) {
    die($user->lang('ERROR'));
}

$vals = [];
$vals['id_n'] = $info->id;
$vals['name_n'] = $info->name;
$vals['secret_n'] = $info->secret;
$vals['redirecturi_n'] = $info->redirect_uri;
$vals['description_n'] = $info->description;
$vals['scopes_a'] = explode(' ', $info->scope);
$user->getTPL()->assign($vals);
$user->getTPL()->draw('preferences/applications/manage');
