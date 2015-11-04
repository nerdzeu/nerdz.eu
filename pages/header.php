<?php
$vars = [];
$positionArray = explode('.',basename($_SERVER['PHP_SELF']));
$vars['position_n'] = reset($positionArray);
$user->getTPL()->assign($vars);
$user->getTPL()->draw('base/header');
