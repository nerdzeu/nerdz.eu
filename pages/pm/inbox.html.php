<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/pm.class.php';
$core = new pm();
ob_start(array('phpCore','minifyHtml'));

if(!$core->isLogged())
    die($core->lang('REGISTER'));

$vals = array();
$vals['from'] = $core->lang('USERNAME');
$vals['read'] = $core->lang('READ');
$vals['when'] = $core->lang('WHEN');
$vals['delete'] = $core->lang('DELETE');
$vals['send'] = $core->lang('SEND');
$vals['nopmdescr'] = $core->lang('NO_PM');
$vals['list_a'] = $core->getList();

$core->getTPL()->assign($vals);
$core->getTPL()->draw('pm/inbox');
?>
