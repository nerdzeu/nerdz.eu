<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
ob_start(array('phpCore','minifyHtml'));

$core = new phpCore();
$vals = array();

$vals['tok_n'] = $core->getCsrfToken('pm');

$core->getTPL()->assign($vals);
$core->getTPL()->draw('pm/form');

?>
