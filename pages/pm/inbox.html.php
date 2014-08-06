<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Pms;

$core = new Pms();
ob_start(array('NERDZ\\Core\\Core','minifyHTML'));

if(!$core->isLogged())
    die($core->lang('REGISTER'));

$vals = [];
$vals['list_a'] = $core->getList();

$core->getTPL()->assign($vals);
$core->getTPL()->draw('pm/inbox');
?>
