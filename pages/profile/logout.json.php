<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

$user = new NERDZ\Core\User();

if(!$user->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('REGISTER')));
    
if(!$user->refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));

if(!$user->csrfControl(isset($_POST['tok']) ? $_POST['tok'] : 0))
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': token'));

$user->logout();
die(NERDZ\Core\Utils::jsonResponse('ok',$user->lang('LOGOUT_OK')));
?>
