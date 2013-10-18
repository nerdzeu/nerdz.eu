<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/comments.class.php';

ob_start(array('phpCore','minifyHtml'));

$core = new project();
$comments = new comments();

$gid = (isset($_POST['id']) && is_numeric($_POST['id'])) ? $_POST['id'] : false; //ID DEL PROPOSTTO CHE STO VISITANDO

if(isset($_POST['limit']))
    $_POST['limit'] = $core->limitControl($_POST['limit'],10) ? $_POST['limit'] : 10;
else
    $_POST['limit'] = 10;

if(!$gid)
    die($core->lang('ERROR'));

$logged = $core->isLogged();
$afterHpid = isset($_POST['hpid']) && is_numeric($_POST['hpid']) ? $_POST['hpid'] : false;

if(!(
        $mess = $afterHpid ? 
        $core->getNMessagesBeforeHpid($_POST['limit'],$afterHpid,$gid)
        :
        $core->getProjectMessages($gid,$_POST['limit'])
    ) || 
    (!$logged && !is_numeric($_POST['limit']))
  )
    die(); //vuoto così automaticamente il javascript non fa altre chiamate

$vals = array();
//includo file per variabili di lingua comuni
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/postlist.lang.php';
//includo il loop in $mess
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/project/postlist.html.php';

$core->getTPL()->assign($vals);
$core->getTPL()->draw('project/postlist');

?>
