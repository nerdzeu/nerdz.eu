<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
$core = new Core();
ob_start(array('Core','minifyHtml'));

if(!$core->isLogged())
    die($core->lang('REGISTER'));

if(!($o = $core->query(array('SELECT "private" FROM "users" WHERE "counter" = ?',array($_SESSION['id'])),Db::FETCH_OBJ)))
    die($core->lang('ERROR'));

$vals['private_b'] = $o->private;
$vals['tok_n'] = $core->getCsrfToken('edit');

$core->getTPL()->assign($vals);
$core->getTPL()->draw('preferences/guests');
?>
