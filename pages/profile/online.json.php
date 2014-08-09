<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;

$core  = new NERDZ\Core\User();

if(!$core->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('REGISTER')));

$viewonline = empty($_SESSION['mark_offline']) ? '1' : '0';

if(Db::NO_ERRNO != Db::query(array('UPDATE "users" SET "last" = NOW(), "viewonline" = :on WHERE "counter" = :id',array(':on' => $viewonline,':id' => $_SESSION['id'])),Db::FETCH_ERRNO))
    die(NERDZ\Core\Utils::jsonResponse('error','Time'));

if(!($o = Db::query(array('SELECT "remote_addr","http_user_agent" FROM "users" WHERE "counter" = :id',array(':id' => $_SESSION['id'])),Db::FETCH_OBJ)))
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));

if(empty($o->remote_addr) || empty($_SESSION['remote_addr']) || ($o->remote_addr != $_SERVER['REMOTE_ADDR']))
{
    if(Db::NO_ERRNO != Db::query(array('UPDATE "users" SET "remote_addr" = :addr WHERE "counter" = :id',array(':addr' => $_SERVER['REMOTE_ADDR'], ':id' => $_SESSION['id'])),Db::FETCH_ERRNO))
        die(NERDZ\Core\Utils::jsonResponse('error','IP'));
    $_SESSION['remote_addr'] = $_SERVER['REMOTE_ADDR'];
}

if(empty($o->http_user_agent) || empty($_SESSION['http_user_agent']) || ($o->http_user_agent != $_SERVER['HTTP_USER_AGENT']))
{
    if(Db::NO_ERRNO != Db::query(array('UPDATE "users" SET "http_user_agent" = :uag WHERE "counter" = :id',array(':uag' => htmlspecialchars($_SERVER['HTTP_USER_AGENT'],ENT_QUOTES,'UTF-8'), ':id' => $_SESSION['id'])),Db::FETCH_ERRNO))
        die(NERDZ\Core\Utils::jsonResponse('error','UA'));
    
    $_SESSION['http_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
}
die(NERDZ\Core\Utils::jsonResponse('ok','OK'));
?>
