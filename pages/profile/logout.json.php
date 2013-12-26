<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';

$core = new phpCore();

if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));
    
if(!$core->refererControl())
    die($core->jsonResponse('error',$core->lang('ERROR')));

if(!$core->csrfControl(isset($_POST['tok']) ? $_POST['tok'] : 0))
    die($core->jsonResponse('error',$core->lang('ERROR').': token'));

$core->logout();
die($core->jsonResponse('ok',$core->lang('LOGOUT_OK')));
?>
