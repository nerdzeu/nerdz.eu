<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\Notification;
$core = new Notification();

if($core->isLogged())
    die($core->jsonResponse('ok',$core->count(false,true)));

die($core->jsonResponse('error',$core->lang('REGISTER')));

?>
