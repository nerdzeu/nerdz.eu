<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';
ob_start(array('phpCore','minifyHtml'));

$core = new project();

$vals = array();
if(!($vals['logged_b'] = $logged = $core->isLogged()))
    die($core->lang('REGISTER'));

$_POST['limit'] = $core->limitControl(isset($_POST['limit']) ? $_POST['limit'] : 10 ,10) ? $_POST['limit'] : 10;

switch(isset($_GET['action']) ? trim(strtolower($_GET['action'])) : '')
{
    case 'profile':
        $group = false;
    break;
    case 'project':
        $group = true;
    break;
    default:
        die($core->lang('ERROR'));
    break;
}

$vals['projects_b'] = $group;

if(isset($_POST['onlyfollowed'])) //chi se ne frega della lingua se lo seguo
{
    $lang = false;
    $onlyfollowed = true;
}
else
{
    if(($lang = isset($_POST['lang']) ? $_POST['lang'] : false))
    {
        $languages = $core->availableLanguages();
        $languages[] = '*'; //any language
        if(!in_array($lang,$languages))
            $lang = false;
    }
    $onlyfollowed = false;
}

$afterHpid = isset($_POST['hpid']) && is_numeric($_POST['hpid']) ? $_POST['hpid'] : false;

if(!(
        $mess = $afterHpid !== false ? 
        $core->getNLatestBeforeHpid($_POST['limit'],$afterHpid, $vals['projects_b'],$onlyfollowed,$lang)
        :
        $core->getLatests($_POST['limit'], $vals['projects_b'],$onlyfollowed,$lang)
    )
  )
    die();

//includo file per variabili di lingua comuni
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/postlist.lang.php';
//per le miniature!
$miniature = true;
if($group)
    //come sotto
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/project/postlist.html.php';
else
    //fa tutto il loop e assegna tutte le variabili corrette in $vals, comprese quelle di lingua comuni a entrabbi i loops
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/profile/postlist.html.php';

$core->getTPL()->assign($vals);
$core->getTPL()->draw('home/postlist');
?>
