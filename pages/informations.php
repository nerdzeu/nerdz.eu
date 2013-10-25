<?php
//TEMPLATE: OK
$l = $core->isLogged() ? $core->getUserLanguage($_SESSION['nerdz_id']) : $core->getBrowserLanguage();
$f = false;
$lcon = $_SERVER['DOCUMENT_ROOT'].'/data/informations/'.$l.'.txt';
foreach(glob($_SERVER['DOCUMENT_ROOT'].'/data/informations/*.txt') as $lang)
    if($lcon == $lang)
    {
        $f = true;
        break;
    }
if(!$f)
    $lcon = $_SERVER['DOCUMENT_ROOT'].'/data/informations/en.txt';

$txt = file_get_contents($lcon);
$exp = explode("\n",$txt);
$vals = array();
$vals['informations_n'] = $txt;

$vals['user_menu_m']= ('<div class="title">'.$core->lang('USER_MENU').'</div><div class="box_menu"> <ul><a href="/"><li><img src="tpl/1/base/images/home-dark.png">Home</li></a><a href="/'.phpCore::userLink($core->getUserName()).'"><li><img src="tpl/1/base/images/prof.png">'.$core->lang('PROFILE').'</li></a><a href="/preferences.php"><li><img src="tpl/1/base/images/settings.png">'.$core->lang('PREFERENCES').'</li></a><a href="/" id="logout" data-tok="'.$core->getCsrfToken().'"><li><img src="tpl/1/base/images/exit.png">'.$core->lang('LOGOUT').'</li></a></ul></div>');

$core->getTPL()->assign($vals);
$core->getTPL()->draw('base/informations');
?>
