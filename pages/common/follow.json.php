<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\User;

$user = new User();

if(empty($_POST['id'])||!is_numeric($_POST['id']))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));

$prj = isset($prj);

switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
case 'del':
    die(NERDZ\Core\Utils::jsonDbResponse($user->defollow($_POST['id'], $prj)));
    break;
case 'add':
    die(NERDZ\Core\Utils::jsonDbResponse($user->follow($_POST['id'], $prj)));
    break;
default:
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
    break;
}
?>
