<?php
//da includere in ogni pagina visbile all'utente da mobile in /*
$vals['usermenu'] = $core->lang('USER_MENU');
$vals['profile'] = $core->lang('PROFILE');
$vals['logout'] = $core->lang('LOGOUT');
$vals['preferences'] = $core->lang('PREFERENCES');
$vals['tok_n'] = $core->getCsrfToken();
$vals['myusername_n'] = $core->getUserName();
$vals['myusername4link_n'] = phpCore::userLink($vals['myusername_n']);
?>
