<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';
$vals['mobile_b'] = $core->isMobile();
$vals['gotomobile_n'] = $_SERVER['REQUEST_URI']."&goto=mobile";
$vals['gotodesktop_n'] = $_SERVER['REQUEST_URI']."&goto=desktop";
$core->getTPL()->assign($vals);
$core->getTPL()->draw('base/splash');
?>
