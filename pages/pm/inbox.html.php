<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Pms;

$user = new Pms();
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

if(!$user->isLogged())
    die($user->lang('REGISTER'));

$vals = [];
$vals['list_a'] = $user->getList();

$user->getTPL()->assign($vals);
$user->getTPL()->draw('pm/inbox');
?>
