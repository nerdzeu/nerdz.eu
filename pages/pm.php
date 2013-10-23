<?php
//Template: OK
$vals = array();
$vals['description'] = $core->lang('PM_DESCR');
$vals['compose'] = $core->lang('COMPOSE');
$vals['inbox'] = $core->lang('INBOX');
$vals['user_menu_m']= ('<div class="title">'.$core->lang('USER_MENU').'</div><div class="box_menu"> <ul><a href="/"><li><img src="tpl/1/base/images/home-dark.png">Home</li></a><a href="/'.phpCore::userLink($core->getUserName()).'"><li><img src="tpl/1/base/images/prof.png">'.$core->lang('PROFILE').'</li></a><a href="/preferences.php"><li><img src="tpl/1/base/images/settings.png">'.$core->lang('PREFERENCES').'</li></a><a href="/" id="logout" data-tok="'.$core->getCsrfToken().'"><li><img src="tpl/1/base/images/exit.png">'.$core->lang('LOGOUT').'</li></a></ul></div>');
$core->getTPL()->assign($vals);
$core->getTPL()->draw('pm/main');
?>
