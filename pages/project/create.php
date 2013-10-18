<?php
//Template: Ok
$vals = array();
$vals['description_n'] = $core->lang('CREATE_PROJECT_DESCR');
$vals['projectname'] = $core->lang('PROJECT_NAME');
$vals['description'] = $core->lang('DESCRIPTION');
$vals['captcha'] = $core->lang('CAPTCHA');
$vals['reloadcaptcha'] = $core->lang('RELOAD_CAPTCHA');
$vals['create'] = $core->lang('CREATE');

$core->getTPL()->assign($vals);
$core->getTPL()->draw('project/create');
?>
