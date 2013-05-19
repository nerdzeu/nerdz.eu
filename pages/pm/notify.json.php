<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/pm.class.php';
$core = new pm();
if($core->isLogged())
	die($core->jsonResponse('ok',$core->countNew()));

die($core->jsonResponse('error',$core->lang('ERROR')));
?>
