<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;
use NERDZ\Core\User;
$user = new User();

if(!NERDZ\Core\Security::refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': referer'));

$from  = isset($_POST['from']) && is_numeric($_POST['from']) ? $_POST['from'] : 0; // 0 = full post
$hpid  = isset($_POST['hpid']) && is_numeric($_POST['hpid']) ? $_POST['hpid'] : 0;

if(!$hpid)
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));

$prj = isset($prj);

switch(isset($_GET['action']) ? strtolower(trim($_GET['action'])) : '')
{
case 'add':
    die(NERDZ\Core\Utils::jsonDbResponse($user->dontNotify(['hpid' => $hpid, 'from' => $from], $prj)));

case 'del':
    die(NERDZ\Core\Utils::jsonDbResponse($user->reNotify(['hpid' => $hpid, 'from' => $from], $prj)));

default:
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
}
?>
