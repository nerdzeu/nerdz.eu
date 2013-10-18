<?php
//Template: OK
$vals = array();
$vals['description'] = $core->lang('PM_DESCR');
$vals['compose'] = $core->lang('COMPOSE');
$vals['inbox'] = $core->lang('INBOX');
$core->getTPL()->assign($vals);
$core->getTPL()->draw('pm/main');
?>
