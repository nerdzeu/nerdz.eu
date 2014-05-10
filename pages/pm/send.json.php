<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/pm.class.php';

$core = new pm();

if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));
    
if(empty($_POST['to']) || empty($_POST['message']))
    die($core->jsonResponse('error',$core->lang('SOMETHING_MISS')));
    
if(!($toid = $core->getUserId($_POST['to']))) //getUserId DON'T what htmlspecialchars in parameter
    die($core->jsonResponse('error',$core->lang('USER_NOT_FOUND')));

foreach($_POST as &$val)
    $val = htmlspecialchars(trim($val),ENT_QUOTES,'UTF-8');

if(!$core->refererControl())
    die($core->jsonResponse('error','No SPAM/BOT'));
    
$ret = $core->send($toid,$_POST['message']);

if($ret === null)
{
    include_once $_SERVER['DOCUMENT_ROOT'].'/class/flood.class.php';
    $flood = new flood();
    die($core->jsonResponse('error',$core->lang('WAIT').' '.($flood::PM_TIMEOUT - (time()-$_SESSION['nerdz_MPflood'])).'s'));
}
if($ret === false)
    die($core->jsonResponse('error',$core->lang('ERROR')));

if(is_string($ret))
   die($core->jsonResponse('error',$ret));

die($core->jsonResponse('ok','OK'));
?>
