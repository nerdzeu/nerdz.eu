<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Pms;
use NERDZ\Core\User;

ob_start('ob_gzhandler');

$user = new User();

if(!$user->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('REGISTER')));

if(!$user->refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error','No spam or spam-bot here'));

$pms = new Pms();

if(empty($_POST['from']) || !is_numeric($_POST['from']) || empty($_POST['to']) || !is_numeric($_POST['to']))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('SOMETHING_MISS')));

if($pms->deleteConversation($_POST['from'],$_POST['to']))
    die(NERDZ\Core\Utils::jsonResponse('ok','OK'));

die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
?>
