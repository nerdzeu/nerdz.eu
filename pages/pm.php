<?php
$vals = [];

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';

$core->getTPL()->assign($vals);
$core->getTPL()->draw('pm/main');
?>
