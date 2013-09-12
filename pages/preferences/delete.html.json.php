<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/captcha.class.php';
$tpl->configure('tpl_dir',$_SERVER['DOCUMENT_ROOT'].'/tpl/0/');

$core = new phpCore();

if(!$core->refererControl())
    die($core->jsonResponse('error',$core->lang('ERROR').': referer'));
        
if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));

$capt = new Captcha();

if(!$capt->check(isset($_POST['captcha']) ? $_POST['captcha'] : ''))
    die($core->jsonResponse('error',$core->lang('WRONG_CAPTCHA')));

if(db::NO_ERR != $core->query(array('DELETE FROM "users" WHERE "counter" = ?',array($_SESSION['nerdz_id'])),db::FETCH_ERR)) // il trigger fa il resto
    die($core->jsonResponse('error',$core->lang('ERROR')));

if(isset($_COOKIE['nerdz_id']))
    setcookie('nerdz_id','',time()-3600,'/',SITE_HOST);
if(isset($_COOKIE['nerdz_u']))
    setcookie('nerdz_u','',time()-3600,'/',SITE_HOST);

$core->logout();

die($core->jsonResponse('ok','Bye :('));
?>
