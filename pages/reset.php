<?php
//TEMPLATE: OK
if($core->isLogged())
    die(header('Location: /home.php'));
    
$vals = array();
$vals['resetdescr'] = $core->lang('RESET_DESCR');
$vals['reloadcaptcha'] = $core->lang('RELOAD_CAPTCHA');
$vals['send'] = $core->lang('SEND');
$vals['captcha'] = $core->lang('CAPTCHA');
$vals['email'] = $core->lang('EMAIL');
$tpl->assign($vals);
$tpl->draw('base/reset');
?>
