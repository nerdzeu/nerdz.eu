<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\Captcha;
use NERDZ\Core\Db;
use NERDZ\Core\User;

$core = new User();

if(!$core->refererControl())
    die($core->jsonResponse('error',$core->lang('ERROR').': referer'));
        
if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));

$capt = new Captcha();

if(!$capt->check(isset($_POST['captcha']) ? $_POST['captcha'] : ''))
    die($core->jsonResponse('error',$core->lang('WRONG_CAPTCHA')));

if(Db::NO_ERRNO != Db::query(array('DELETE FROM "users" WHERE "counter" = ?',array($_SESSION['id'])),Db::FETCH_ERRNO))
    die($core->jsonResponse('error',$core->lang('ERROR')));

$core->logout();

die($core->jsonResponse('ok','Bye :('));
?>
