<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
$core = new Core();
    
if($core->isLogged())
    die($core->jsonResponse('error',$core->lang('ALREADY_LOGGED')));

$user = isset($_POST['username']) ? htmlspecialchars(trim($_POST['username']),ENT_QUOTES,'UTF-8') : false;
$pass = isset($_POST['password']) ? $_POST['password'] : false;

if(!$user || !$pass)
    die($core->jsonResponse('error',$core->lang('INSERT_USER_PASS')));

$user = is_numeric($user) ? $core->getUsername($user) : $user;
if(!$user)
    die($core->jsonResponse('error',$core->lang('WRONG_USER_OR_PASSWORD')));

if($core->login($user, $pass, isset($_POST['setcookie']), isset($_POST['offline'])))
     die($core->jsonResponse('ok',$core->lang('LOGIN_OK')));

die($core->jsonResponse('error',$core->lang('WRONG_USER_OR_PASSWORD')));
?>
