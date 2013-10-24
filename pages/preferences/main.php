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

$vals['user_menu_m']= ('<div class="title">'.$core->lang('USER_MENU').'</div><div class="box_menu"> <ul><a href="/"><li><img src="tpl/1/base/images/home-dark.png">Home</li></a><a href="/'.phpCore::userLink($core->getUserName()).'"><li><img src="tpl/1/base/images/prof.png">'.$core->lang('PROFILE').'</li></a><a href="/preferences.php"><li><img src="tpl/1/base/images/settings.png">'.$core->lang('PREFERENCES').'</li></a><a href="/" id="logout" data-tok="'.$core->getCsrfToken().'"><li><img src="tpl/1/base/images/exit.png">'.$core->lang('LOGOUT').'</li></a></ul></div>');

$core->getTPL()->assign($vals);
$core->getTPL()->draw('preferences/layout');
?>
