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
/* out.php is used for avoid tabnabbing attacks*/
if(empty($_GET['url'])) {
    die(header('Location: /'));
}


require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\User;
use NERDZ\Core\Config;
use NERDZ\Core\Utils;

$user = new User();
$tplcfg = $user->getTemplateCfg();

if($user->isLogged()) {
    // TODO: collect stats
}

$url  = trim(html_entity_decode($_GET['url'],ENT_QUOTES, 'UTF-8'));
$hmac = !empty($_GET['hmac']) ? Utils::getHMAC($url, Config\CAMO_KEY) === $_GET['hmac'] : false;
if($hmac) {
    die(header("Location: {$url}"));
}
