<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/messages.class.php';
ob_start(array('phpCore','minifyHtml'));

$core = new messages();
$messages = new messages();

$vals = array();
if(!($vals['logged_b'] = $logged = $core->isLogged()))
    die($core->lang('REGISTER'));

$limit = isset($_POST['limit']) ? $core->limitControl($_POST['limit'] ,10) : 10;

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

$beforeHpid = isset($_POST['hpid']) && is_numeric($_POST['hpid']) ? $_POST['hpid'] : false;

if(!(
        $mess = $beforeHpid !== false ? 
        $messsages->getNLatestBeforeHpid($limit, $beforeHpid, $vals['projects_b'],$onlyfollowed,$lang)
        :
        $messages->getLatests($limit, $vals['projects_b'],$onlyfollowed,$lang)
    )
  )
    die();

//Variable required to set image thumbnails in postlist
$miniature = true;
if($group)
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/project/postlist.html.php';
else
    //fa tutto il loop e assegna tutte le variabili corrette in $vals, comprese quelle di lingua comuni a entrabbi i loops
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/profile/postlist.html.php';

$core->getTPL()->assign($vals);
$core->getTPL()->draw('home/postlist');
?>
