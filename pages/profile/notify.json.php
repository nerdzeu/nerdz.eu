<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Notification;
use NERDZ\Core\User;

$notification = new Notification();
$user = new User();

if($user->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('ok',$notification->count(false,true)));

die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('REGISTER')));
?>
