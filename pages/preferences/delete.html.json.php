<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\Captcha;
use NERDZ\Core\Db;
use NERDZ\Core\User;

$user = new User();

if(!NERDZ\Core\Security::refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': referer'));

if(!$user->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('REGISTER')));

$capt = new Captcha();

if(!$capt->check(isset($_POST['captcha']) ? $_POST['captcha'] : ''))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('WRONG_CAPTCHA')));

if(Db::NO_ERRNO != Db::query(array('DELETE FROM "users" WHERE "counter" = ?',array($_SESSION['id'])),Db::FETCH_ERRNO))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));

$user->logout();

die(NERDZ\Core\Utils::jsonResponse('ok','Bye :('));
?>
