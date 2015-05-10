<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\User;
$user = new User();

if($user->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ALREADY_LOGGED')));

if(!NERDZ\Core\Security::refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': referer'));

$username = isset($_POST['username']) ? htmlspecialchars(trim($_POST['username']),ENT_QUOTES,'UTF-8') : false;
$pass     = isset($_POST['password']) ? $_POST['password'] : false;

if(!$username || !$pass)
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('INSERT_USER_PASS')));

if(is_numeric($username) || filter_var($username, FILTER_VALIDATE_EMAIL))
    $username = User::getUsername($username);

if(!$username)
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('WRONG_USER_OR_PASSWORD')));

if($user->login($username, $pass, isset($_POST['setcookie']), isset($_POST['offline'])))
    die(NERDZ\Core\Utils::jsonResponse('ok',$user->lang('LOGIN_OK')));

die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('WRONG_USER_OR_PASSWORD')));
