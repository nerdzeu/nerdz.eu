<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
$core = new Core();
ob_start(array('NERDZ\\Core\\Core','minifyHtml'));

if(!$core->isLogged())
    die($core->lang('REGISTER'));

$core->getTPL()->draw('preferences/delete');
?>
