<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

$core = new NERDZ\Core\User();

if(!$core->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('REGISTER')));
    
if(!$core->refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));

if(!$core->csrfControl(isset($_POST['tok']) ? $_POST['tok'] : 0))
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR').': token'));

$core->logout();
die(NERDZ\Core\Utils::jsonResponse('ok',$core->lang('LOGOUT_OK')));
?>
