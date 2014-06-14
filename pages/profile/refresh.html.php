<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/messages.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/comments.class.php';

ob_start(array('phpCore','minifyHtml'));

$core = new messages();
$comments = new comments();

if(!($id = isset($_POST['id']) && is_numeric($_POST['id']) ? $_POST['id'] : false))
    die($core->lang('ERROR'));


$limit = isset($_POST['limit']) ? $core->limitControl($_POST['limit'],10) : 10;

$logged = $core->isLogged();

if($logged && is_numeric(strpos($_SERVER['REQUEST_URI'],'refresh.html.php')) && (true === $core->isInBlacklist($_SESSION['nerdz_id'],$id)))
    die('Hax0r c4n\'t fuck nerdz pr00tectionz');

$beforeHpid = isset($_POST['hpid']) && is_numeric($_POST['hpid']) ? $_POST['hpid'] : false;

$mess = $beforeHpid ? $core->getNMessagesBeforeHpid($limit,$beforeHpid,$id) : $core->getMessages($id,$limit);


if(!$mess || (!$logged && $beforeHpid))
    die(); //empty so javascript client code stop making requests

$vals = [];
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/profile/postlist.html.php';
$core->getTPL()->assign($vals);
$core->getTPL()->draw('profile/postlist');

?>
