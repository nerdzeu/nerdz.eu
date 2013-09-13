<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
$tpl->configure('tpl_dir',$_SERVER['DOCUMENT_ROOT'].'/tpl/0/');
ob_start(array('phpCore','minifyHtml'));

$core = new phpCore();

if(!$core->isLogged())
    die($core->lang('REGISTER'));

$vals = array();
$vals['description'] = $core->lang('DELETE_DESCR');
$vals['delete'] = $core->lang('DELETE');
$vals['captcha'] = $core->lang('CAPTCHA');
$vals['reloadcaptcha'] = $core->lang('RELOAD_CAPTCHA');

$tpl->assign($vals);
$tpl->draw('preferences/delete');
?>
