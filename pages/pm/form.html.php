<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
ob_start(array('Core','minifyHtml'));

$core = new Core();
$vals = [];

$vals['tok_n'] = $core->getCsrfToken('pm');

$core->getTPL()->assign($vals);
$core->getTPL()->draw('pm/form');

?>
