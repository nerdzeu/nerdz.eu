<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/notify.class.php';

$core = new notify();

if($core->isLogged())
    die($core->jsonResponse('ok',$core->count(false,true)));

die($core->jsonResponse('error',$core->lang('REGISTER')));

?>
