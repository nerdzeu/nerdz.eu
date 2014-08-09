<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Notification;
use NERDZ\Core\User;

$core = new Notification();
$user = new User();

if($user->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('ok',$core->count(false,true)));

die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('REGISTER')));
?>
