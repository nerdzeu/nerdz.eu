<?php
//TEMPLATE: OK
$vals = array();

$vals['logged_b'] = $core->isLogged();
if(!$vals['logged_b'])
{
	if(false !== strpos(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : getenv('HTTP_HOST') ? getenv('HTTP_HOST') : '','gotdns'))
		die(header('Location: http://'.SITE_HOST.$_SERVER['REQUEST_URI']));

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

$tpl->assign($vals);
$tpl->draw('base/header');
?>
