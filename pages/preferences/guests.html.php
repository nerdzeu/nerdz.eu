<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
$core = new phpCore();
ob_start(array('phpCore','minifyHtml'));

if(!$core->isLogged())
    die($core->lang('REGISTER'));

if(!($o = $core->query(array('SELECT "private" FROM "users" WHERE "counter" = ?',array($_SESSION['nerdz_id'])),db::FETCH_OBJ)))
    die($core->lang('ERROR'));

$vals['private_b'] = $o->private;
$vals['tok_n'] = $core->getCsrfToken('edit');

$core->getTPL()->assign($vals);
$core->getTPL()->draw('preferences/guests');
?>
