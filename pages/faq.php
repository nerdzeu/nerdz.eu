<?php
//Template: OK
$l = $core->isLogged() ? $core->getUserLanguage($_SESSION['nerdz_id']) : $core->getBrowserLanguage();
$f = false;
$lcon = $l.'.txt';
foreach(glob($_SERVER['DOCUMENT_ROOT'].'/data/faq/*.txt') as $lang)
    if(is_numeric(strpos($lang,$lcon)))
    {
        $f = true;
        break;
    }
$exp = explode("\n",file_get_contents($_SERVER['DOCUMENT_ROOT'].'/data/faq/'.($f ? $lcon : 'it.txt')));

$vals = array();
$c = 0;
$questions = 0;
foreach($exp as $v)
{
    $vals['list_a'][$c]['title_b'] = isset($v[0]) && ($v[0] == 'Q');
    $vals['list_a'][$c]['questionid_n'] = $vals['list_a'][$c]['title_b'] ? ++$questions : 0;
    $vals['list_a'][$c]['line_n'] = htmlentities($v,ENT_QUOTES,'UTF-8');
    ++$c;
}

$vals['user_menu_m']= ('<div class="title">'.$core->lang('USER_MENU').'</div><div class="box_menu"> <ul><a href="/"><li><img src="tpl/1/base/images/home-dark.png">Home</li></a><a href="/'.phpCore::userLink($core->getUserName()).'"><li><img src="tpl/1/base/images/prof.png">'.$core->lang('PROFILE').'</li></a><a href="/preferences.php"><li><img src="tpl/1/base/images/settings.png">'.$core->lang('PREFERENCES').'</li></a><a href="/" id="logout" data-tok="'.$core->getCsrfToken().'"><li><img src="tpl/1/base/images/exit.png">'.$core->lang('LOGOUT').'</li></a></ul></div>');

$core->getTPL()->assign($vals);
$core->getTPL()->draw('base/faq');
?>
