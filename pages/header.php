<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';
$vars = [];
$positionArray = explode('.',basename($_SERVER['PHP_SELF']));
$vars['position_n'] = reset($positionArray);
$user->getTPL()->assign($vars);
$user->getTPL()->draw('base/header');
