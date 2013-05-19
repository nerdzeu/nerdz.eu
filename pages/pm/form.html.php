<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'class/core.class.php';
ob_start(array('phpCore','minifyHtml'));

$core = new phpCore();
$vals = array();

$vals['username'] = $core->lang('USERNAME');
$vals['message'] = $core->lang('MESSAGE');
$vals['send'] = $core->lang('SEND');
$vals['to'] = $core->lang('TO');
$vals['send'] = $core->lang('SEND');
$vals['preview'] = $core->lang('PREVIEW');
$vals['tok_n'] = $core->getCsrfToken('pm');

$tpl->assign($vals);
$tpl->draw('pm/form');

?>
