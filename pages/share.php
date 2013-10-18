<?php
//TEMPLATE: OK
$vals = array();
$vals['nerdzit'] = $core->lang('NERDZ_IT');
$vals['comments'] = $core->lang('COMMENTS');
$core->getTPL()->assign($vals);
$core->getTPL()->draw('base/share');
?>
