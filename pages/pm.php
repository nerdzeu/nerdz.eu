<?php
//Template: OK
$vals = array();
$vals['description'] = $core->lang('PM_DESCR');
$vals['compose'] = $core->lang('COMPOSE');
$vals['inbox'] = $core->lang('INBOX');

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/mobilemenu.php';

$core->getTPL()->assign($vals);
$core->getTPL()->draw('pm/main');
?>
