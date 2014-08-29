<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;
use NERDZ\Core\Messages;

$messages = new Messages();

if(!NERDZ\Core\Security::refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error',$messages->lang('ERROR').': referer'));

$hpid  = isset($_POST['hpid'])  && is_numeric($_POST['hpid'])  ? $_POST['hpid']  : false;

if(!$hpid)
    die(NERDZ\Core\Utils::jsonResponse('error',$messages->lang('ERROR')));

$prj = isset($prj);

switch(isset($_GET['action']) ? strtolower(trim($_GET['action'])) : '')
{
case 'open':
    die(NERDZ\Core\Utils::jsonDbResponse($messages->reOpen($hpid, $prj)));

case 'close':
    die(NERDZ\Core\Utils::jsonDbResponse($messages->close($hpid, $prj)));

default:
    die(NERDZ\Core\Utils::jsonResponse('error',$messages->lang('ERROR')));
}
?>
