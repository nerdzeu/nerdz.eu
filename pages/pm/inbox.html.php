<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Pms;
use NERDZ\Core\User;

$user = new User();

ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

if(!$user->isLogged())
    die($pms->lang('REGISTER'));

$pms  = new Pms();

$vals = [];
$vals['list_a'] = $pms->getList();

$user->getTPL()->assign($vals);
$user->getTPL()->draw('pm/inbox');
?>
