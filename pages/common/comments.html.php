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
ob_start(array('NERDZ\\Core\\Utils', 'minifyHTML'));

use NERDZ\Core\Comments;
use NERDZ\Core\Messages;
use NERDZ\Core\System;
use NERDZ\Core\User;

$prj = isset($prj);
$user = new User();
$comments = new Comments();

if (!$user->isLogged()) {
    die($user->lang('REGISTER'));
}

switch (isset($_GET['action']) ? strtolower($_GET['action']) : '') {
case 'get':
    $hcid = isset($_POST['hcid']) && is_numeric($_POST['hcid']) ? $_POST['hcid']  : false;
    if (!$hcid) {
        die($user->lang('ERROR').': no hcid');
    }

    $vals = [];
    $vals['list_a'] = $comments->get($hcid, $prj);
    $vals['showform_b'] = false;
    $vals['needmorebtn_b'] = false;
    $vals['commentcount_n'] = 0;
    $vals['hpid_n'] = 0;
    $vals['onerrorimgurl_n'] = System::getResourceDomain().'/static/images/red_x.png';

    $user->getTPL()->assign($vals);
    $user->getTPL()->draw(($prj ? 'project' : 'profile').'/comments');
    break;

case 'show':
    $hpid = isset($_POST['hpid']) && is_numeric($_POST['hpid']) ? $_POST['hpid']  : false;
    if (!$hpid) {
        die($user->lang('ERROR').': no hpid');
    }
    $_list = null;
    if (isset($_POST['start']) && isset($_POST['num']) &&
        is_numeric($_POST['start']) && is_numeric($_POST['num'])) {
        $_list = $comments->getLastComments($hpid, $_POST['num'], $_POST['start'], $prj);
    } elseif (isset($_POST['hcid']) && is_numeric($_POST['hcid'])) {
        $_list = $comments->getCommentsAfterHcid($hpid, $_POST['hcid'], $prj);
    } else {
        $_list = $comments->getAll($hpid, $prj);
    }

    $doShowForm = !isset($_POST['hcid']) && (!isset($_POST['start']) || $_POST['start'] == 0) && !isset($_POST['forceNoForm']);

    if (empty($_list) && !$doShowForm) {
        die();
    }

    $vals = [];
    $vals['currentuserprofile_n'] = \NERDZ\Core\Utils::userLink($_SESSION['id']);
    $vals['currentusergravatar_n'] = $user->getGravatar($_SESSION['id']);
    $vals['currentusername_n'] = User::getUsername();
    $vals['onerrorimgurl_n'] = System::getResourceDomain().'/static/images/red_x.png';
    $vals['list_a'] = $_list;
    $vals['showform_b'] = $doShowForm;
    $vals['hpid_n'] = $hpid;
    $vals['commentcount_n'] = (new Messages())->countComments($hpid, $prj);
    $vals['needmorebtn_b'] = $doShowForm && $vals['commentcount_n'] > 10;
    $vals['needeverycommentbtn_b'] = $doShowForm && $vals['commentcount_n'] > 20;

    $user->getTPL()->assign($vals);
    $user->getTPL()->draw(($prj ? 'project' : 'profile').'/comments');
    break;
default:
    die($user->lang('ERROR'));
    break;
}
