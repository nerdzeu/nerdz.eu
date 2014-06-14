<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/comments.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';

ob_start(array('phpCore','minifyHtml'));

$core     = new phpCore();
$messages = new messages();
$comments = new comments();

$gid = (isset($_POST['id']) && is_numeric($_POST['id'])) ? $_POST['id'] : false;

if(!$gid)
    die($core->lang('ERROR'));

$limit = isset($_POST['limit']) ? $core->limitControl($_POST['limit'],10) : 10;

$beforeHpid = isset($_POST['hpid']) && is_numeric($_POST['hpid']) ? $_POST['hpid'] : false;

$logged = $core->isLogged();

$mess = $beforeHpid ? $messages->getNMessagesBeforeHpid($limit,$beforeHpid,$gid, true) : $messages->getMessages($gid,$limit, true);
if(!$mess || (!$logged && $beforeHpid))
    die(); //empty so javascript client code stop making requests

$vals = [];
//variable $mess is mandatory because the code below will work with that
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/project/postlist.html.php';
$core->getTPL()->assign($vals);
$core->getTPL()->draw('project/postlist');
?>
