<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

$core = new NERDZ\Core\Core();
$vals = [];

$vals['tok_n'] = $core->getCsrfToken('pm');

$core->getTPL()->assign($vals);
$core->getTPL()->draw('pm/form');

?>
