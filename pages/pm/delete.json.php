<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/pm.class.php';
ob_start('ob_gzhandler');
$core = new pm();

if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));
    
if(empty($_POST['from']) || !is_numeric($_POST['from']) || empty($_POST['to']) || !is_numeric($_POST['to']))
    die($core->jsonResponse('error',$core->lang('SOMETHING_MISS')));
    
if(!$core->refererControl())
    die($core->jsonResponse('error','No spam or spam-bot here'));

if($core->deleteConversation($_POST['from'],$_POST['to']))
    die($core->jsonResponse('ok','OK'));
    
die($core->jsonResponse('error',$core->lang('ERROR')));
?>
