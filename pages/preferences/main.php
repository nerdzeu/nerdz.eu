<?php
//TEMPLATE: OK
if(!$core->isLogged())
    die(header('Location: /'));
$vals = array();
$vals['account'] = $core->lang('ACCOUNT');
$vals['profile'] = $core->lang('PROFILE');
$vals['guests'] = $core->lang('GUESTS');
$vals['projects'] = $core->lang('PROJECTS');
$vals['language'] = $core->lang('LANGUAGE');
$vals['delete'] = $core->lang('DELETE');
$vals['description_n'] = $core->lang('PREFERENCES_DESCR');
$core->getTPL()->assign($vals);
$core->getTPL()->draw('preferences/layout');
?>
