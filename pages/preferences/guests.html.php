<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
$tpl->configure('tpl_dir',$_SERVER['DOCUMENT_ROOT'].'/tpl/0/');
ob_start(array('phpCore','minifyHtml'));

$core = new phpCore();
if(!$core->isLogged())
    die($core->lang('REGISTER'));

if(!($o = $core->query(array('SELECT `private` FROM `users` WHERE `counter` = ?',array($_SESSION['nerdz_id'])),db::FETCH_OBJ)))
    die($core->lang('ERROR'));

$vals['description'] = $core->lang('GUESTS_DESCR');
$vals['private_b'] = $o->private;
$vals['publicprofile'] = $core->lang('PUBLIC_PROFILE');
$vals['privateprofile'] = $core->lang('PRIVATE_PROFILE');
$vals['edit'] = $core->lang('EDIT');
$vals['tok_n'] = $core->getCsrfToken('edit');

$tpl->assign($vals);
$tpl->draw('preferences/guests');
?>
