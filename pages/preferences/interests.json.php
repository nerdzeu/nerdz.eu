<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;
use NERDZ\Core\Utils;
use NERDZ\Core\User;
$user = new User();

if(!NERDZ\Core\Security::refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': referer'));

if(!NERDZ\Core\Security::csrfControl(isset($_POST['tok']) ? $_POST['tok'] : 0))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': token'));

if(!$user->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('REGISTER')));


$interest  = isset($_POST['interest'])  ? trim($_POST['interest']) : '';

switch(isset($_GET['action']) ? strtolower(trim($_GET['action'])) : '')
{
case 'add':
    die(NERDZ\Core\Utils::jsonDbResponse($user->addInterest($interest)));

case 'del':
    die(NERDZ\Core\Utils::jsonDbResponse($user->deleteInterest($interest)));

default:
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
}
