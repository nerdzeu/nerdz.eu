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
use NERDZ\Core\Db;
use NERDZ\Core\User;

$user = new User();
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

if(!$user->isLogged())
    die($user->lang('REGISTER'));

if(!($obj = Db::query(array('SELECT * FROM "profiles" WHERE "counter" = ?',array($_SESSION['id'])),Db::FETCH_OBJ)))
    die($user->lang('ERROR'));

$vals = [];
$vals['interests_a'] = $user->getInterests($_SESSION['id']);
$vals['biography_n'] = $obj->biography;

$vals['quotes_a'] = explode("\n",$obj->quotes);
foreach($vals['quotes_a'] as &$val)
    $val = trim($val);

$vals['website_n'] = $obj->website;
$vals['jabber_n'] = $obj->jabber;
$vals['yahoo_n'] = $obj->yahoo;
$vals['facebook_n'] = $obj->facebook;
$vals['twitter_n'] = $obj->twitter;
$vals['steam_n'] = $obj->steam;
$vals['skype_n'] = $obj->skype;
$vals['github_n'] = $obj->github;
$vals['userscript_n'] = $obj->userscript;
$vals['closedprofile_b'] = $user->hasClosedProfile($_SESSION['id']);
$vals['canshowwhitelist_b'] = $vals['closedprofile_b'];
$wl = $user->getWhitelist($_SESSION['id']);
$i = 0;
foreach($wl as &$val)
    $vals['whitelist_a'][$i++] = User::getUsername($val);

$vals['dateformat_n'] = $obj->dateformat;

$user->getTPL()->assign($vals);
$user->getTPL()->draw('preferences/profile');
