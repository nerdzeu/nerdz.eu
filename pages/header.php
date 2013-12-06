<?php
//TEMPLATE: OK
$vals = array();

$vals['logged_b'] = $core->isLogged();
if(!$vals['logged_b'])
{
    $vals['remember'] = $core->lang('REMEMBER_ME');
    $vals['forgot'] = $core->lang('FORGOT_PASSWORD');
    $vals['hidestatus'] = $core->lang('HIDE_STATUS');
    $vals['username'] = $core->lang('USERNAME');
    $vals['login'] = $core->lang('LOGIN');
}
else
{
    $vals['myusername_n'] = $core->getUserName();
    $vals['myusername4link_n'] = phpCore::userLink($vals['myusername_n']);
    $vals['logout'] = $core->lang('LOGOUT');
    $vals['welcome'] = $core->lang('WELCOME');
    $vals['preferences'] = $core->lang('PREFERENCES');
    $vals['profile'] = $core->lang('PROFILE');
    $vals['pm'] = $core->lang('PM');
}
$vals['tok_n'] = $core->getCsrfToken();
$vals['loading'] = $core->lang('LOADING');

$core->getTPL()->assign($vals);
$core->getTPL()->draw('base/header');
?>
