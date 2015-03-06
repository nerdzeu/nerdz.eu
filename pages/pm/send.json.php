<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Pms;
use NERDZ\Core\User;

$pms = new Pms();
$user = new User();

if(!$user->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('REGISTER')));

if(!NERDZ\Core\Security::refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error','No SPAM/BOT'));

if(empty($_POST['to']))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('SOMETHING_MISS')));

if(!($toid = $user->getId(trim($_POST['to'])))) //getId DON'T what htmlspecialchars in parameter
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('USER_NOT_FOUND')));

foreach($_POST as &$val)
    $val = htmlspecialchars(trim($val),ENT_QUOTES,'UTF-8');

die(NERDZ\Core\Utils::jsonDbResponse($pms->send($toid,$_POST['message'])));
