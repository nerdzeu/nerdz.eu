<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Pms;

$user = new Pms();

if(!$user->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('REGISTER')));
    
if(empty($_POST['to']) || empty($_POST['message']))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('SOMETHING_MISS')));
    
if(!($toid = $user->getId($_POST['to']))) //getId DON'T what htmlspecialchars in parameter
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('USER_NOT_FOUND')));

foreach($_POST as &$val)
    $val = htmlspecialchars(trim($val),ENT_QUOTES,'UTF-8');

if(!$user->refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error','No SPAM/BOT'));
    
die(NERDZ\Core\Utils::jsonDbResponse($user->send($toid,$_POST['message'])));
?>
