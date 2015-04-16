<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;
use NERDZ\Core\User;
use NERDZ\Core\Captcha;

$user = new User();
$cptcka = new Captcha();
$captcha = isset($_POST['captcha']) ? $_POST['captcha'] : false;

if(!$captcha)
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('MISSING').': '.$user->lang('CAPTCHA')));

if(!$cptcka->check($captcha))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('WRONG_CAPTCHA')));

die(NERDZ\Core\Utils::jsonResponse('ok', 'OK'));
