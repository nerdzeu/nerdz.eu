<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/pm.class.php';
$core = new pm();
ob_start(array('phpCore','minifyHtml'));

if(!$core->isLogged())
    die($core->lang('REGISTER'));

$vals = [];
$vals['list_a'] = $core->getList();

$core->getTPL()->assign($vals);
$core->getTPL()->draw('pm/inbox');
?>
