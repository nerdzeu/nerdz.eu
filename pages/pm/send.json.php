<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Pms;

$core = new Pms();

if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));
    
if(empty($_POST['to']) || empty($_POST['message']))
    die($core->jsonResponse('error',$core->lang('SOMETHING_MISS')));
    
if(!($toid = $core->getUserId($_POST['to']))) //getUserId DON'T what htmlspecialchars in parameter
    die($core->jsonResponse('error',$core->lang('USER_NOT_FOUND')));

foreach($_POST as &$val)
    $val = htmlspecialchars(trim($val),ENT_QUOTES,'UTF-8');

if(!$core->refererControl())
    die($core->jsonResponse('error','No SPAM/BOT'));
    
die($core->jsonDbResponse($core->send($toid,$_POST['message'])));
?>
