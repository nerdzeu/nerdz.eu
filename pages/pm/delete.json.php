<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Pms;
ob_start('ob_gzhandler');
$user = new Pms();

if(!$user->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('REGISTER')));
    
if(empty($_POST['from']) || !is_numeric($_POST['from']) || empty($_POST['to']) || !is_numeric($_POST['to']))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('SOMETHING_MISS')));
    
if(!$user->refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error','No spam or spam-bot here'));

if($user->deleteConversation($_POST['from'],$_POST['to']))
    die(NERDZ\Core\Utils::jsonResponse('ok','OK'));
    
die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
?>
