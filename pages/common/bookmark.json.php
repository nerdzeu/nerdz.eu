<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;
use NERDZ\Core\User;

$user = new User();

if(!NERDZ\Core\Security::refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': referer'));

$hpid  = isset($_POST['hpid'])  && is_numeric($_POST['hpid'])  ? $_POST['hpid']  : false;

if(!$hpid)
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));

$prj = isset($prj);

switch(isset($_GET['action']) ? strtolower(trim($_GET['action'])) : '')
{
case 'add':
    die(NERDZ\Core\Utils::jsonDbResponse($user->bookmark($hpid, $prj)));

case 'del':
    die(NERDZ\Core\Utils::jsonDbResponse($user->unbookmark($hpid, $prj)));

default:
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
}
?>
