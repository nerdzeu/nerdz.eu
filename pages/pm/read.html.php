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
use NERDZ\Core\Pms;
use NERDZ\Core\User;

ob_start(array('NERDZ\\Core\\Utils', 'minifyHTML'));

$pms = new Pms();
$user = new User();

if (!$user->isLogged()) {
    die($user->lang('REGISTER'));
}

switch (isset($_GET['action']) ? trim(strtolower($_GET['action'])) : '') {
case 'conversation':
    $from = isset($_POST['from']) && is_numeric($_POST['from']) ? $_POST['from'] : false;
    $to = isset($_POST['to']) && is_numeric($_POST['to']) ? $_POST['to'] : false;

    if (!$from || !$to || !in_array($_SESSION['id'], array($from, $to))) {
        die($user->lang('ERROR'));
    }

    $conv = null;
    if (isset($_POST['start']) && isset($_POST['num']) && is_numeric($_POST['start']) && is_numeric($_POST['num'])) {
        $conv = $pms->readConversation($from, $to, false, $_POST['num'], $_POST['start']);
    } elseif (isset($_POST['pmid']) && is_numeric($_POST['pmid'])) {
        $conv = $pms->readConversation($from, $to, $_POST['pmid']);
    } else {
        $conv = $pms->readConversation($from, $to);
    }
    $doShowForm = !isset($_POST['pmid']) && (!isset($_POST['start']) || $_POST['start'] == 0) && !isset($_POST['forceNoForm']);
    if (!$doShowForm && empty($conv)) {
        die();
    }
    $vals['toid_n'] = ($_SESSION['id'] != $to ? $to : $from);
    $vals['to_n'] = User::getUsername($vals['toid_n']);
    if (!$vals['to_n']) {
        die($user->lang('ERROR'));
    }
    $vals['list_a'] = $conv;
    $vals['pmcount_n'] = $pms->count($from, $to);
    $vals['needmorebtn_b'] = $doShowForm && $vals['pmcount_n'] > 10;
    $vals['needeverymsgbtn_b'] = $doShowForm && $vals['pmcount_n'] > 20;
    $vals['showform_b'] = $doShowForm;
    $user->getTPL()->assign($vals);
    $user->getTPL()->draw('pm/conversation');
    break;
default:
    die($user->lang('ERROR'));
    break;
}
