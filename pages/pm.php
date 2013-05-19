<?php
//Template: OK
$vals = array();
$vals['description'] = $core->lang('PM_DESCR');
$vals['compose'] = $core->lang('COMPOSE');
$vals['inbox'] = $core->lang('INBOX');
$tpl->assign($vals);
$tpl->draw('pm/main');
?>
