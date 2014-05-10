<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/captcha.class.php';

$core = new phpCore();

if(!$core->refererControl())
    die($core->jsonResponse('error',$core->lang('ERROR').': referer'));
        
if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));

$capt = new Captcha();

if(!$capt->check(isset($_POST['captcha']) ? $_POST['captcha'] : ''))
    die($core->jsonResponse('error',$core->lang('WRONG_CAPTCHA')));

if(db::NO_ERRNO != $core->query(array('DELETE FROM "users" WHERE "counter" = ?',array($_SESSION['nerdz_id'])),db::FETCH_ERRNO)) // il trigger fa il resto
    die($core->jsonResponse('error',$core->lang('ERROR')));

$core->logout();

die($core->jsonResponse('ok','Bye :('));
?>
