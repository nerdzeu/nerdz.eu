<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
$core = new NERDZ\Core\Core();
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

if(!$core->isLogged())
    die($core->lang('REGISTER'));

$core->getTPL()->draw('preferences/delete');
?>
