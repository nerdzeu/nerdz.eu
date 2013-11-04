<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
$core = new phpCore();
$core->getTPL();
ob_start(array('phpCore','minifyHtml'));

if(!$core->isLogged())
    die($core->lang('REGISTER'));

$vals = array();
$vals['description'] = $core->lang('DELETE_DESCR');
$vals['delete'] = $core->lang('DELETE');
$vals['captcha'] = $core->lang('CAPTCHA');
$vals['reloadcaptcha'] = $core->lang('RELOAD_CAPTCHA');

$core->getTPL()->assign($vals);
$core->getTPL()->draw('preferences/delete');
?>
