<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
$core  = new phpCore();

if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));

$viewonline = empty($_SESSION['nerdz_mark_offline']) ? '1' : '0';

if(db::NO_ERRNO != $core->query(array('UPDATE "users" SET "last" = NOW(), "viewonline" = :on WHERE "counter" = :id',array(':on' => $viewonline,':id' => $_SESSION['nerdz_id'])),db::FETCH_ERRNO))
    die($core->jsonResponse('error','Time'));

if(!($o = $core->query(array('SELECT "remote_addr","http_user_agent" FROM "profiles" WHERE "counter" = :id',array(':id' => $_SESSION['nerdz_id'])),db::FETCH_OBJ)))
    die($core->jsonResponse('error',$core->lang('ERROR')));

if(empty($o->remote_addr) || empty($_SESSION['nerdz_remote_addr']) || ($o->remote_addr != $_SERVER['REMOTE_ADDR']))
{
    if(db::NO_ERRNO != $core->query(array('UPDATE "profiles" SET "remote_addr" = :addr WHERE "counter" = :id',array(':addr' => $_SERVER['REMOTE_ADDR'], ':id' => $_SESSION['nerdz_id'])),db::FETCH_ERRNO))
        die($core->jsonResponse('error','IP'));
    $_SESSION['nerdz_remote_addr'] = $_SERVER['REMOTE_ADDR'];
}

if(empty($o->http_user_agent) || empty($_SESSION['nerdz_http_user_agent']) || ($o->http_user_agent != $_SERVER['HTTP_USER_AGENT']))
{
    if(db::NO_ERRNO != $core->query(array('UPDATE "profiles" SET "http_user_agent" = :uag WHERE "counter" = :id',array(':uag' => htmlentities($_SERVER['HTTP_USER_AGENT'],ENT_QUOTES,'UTF-8'), ':id' => $_SESSION['nerdz_id'])),db::FETCH_ERRNO))
        die($core->jsonResponse('error','UA'));
    
    $_SESSION['nerdz_http_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
}
die($core->jsonResponse('ok','OK'));
?>
