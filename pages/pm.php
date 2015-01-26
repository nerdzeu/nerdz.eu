<?php
$vals = [];

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';

$user->getTPL()->assign($vals);
$user->getTPL()->draw('pm/main');
