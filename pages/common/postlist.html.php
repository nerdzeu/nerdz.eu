<?php
//TODO: merge function interfaces and use this page to fetch every post list
// do the same the same thing for comments
// fix this page
// require $prj, $miniature, $type variables
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/comments.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';

ob_start(array('phpCore','minifyHtml'));

$core     = new phpCore();
$messages = new messages();
$comments = new comments();

$logged = $core->isLogged();

if($logged && is_numeric(strpos($_SERVER['REQUEST_URI'],'refresh.html.php')) && (true === $core->isInBlacklist($_SESSION['nerdz_id'],$id)))
    die('Hax0r c4n\'t fuck nerdz pr00tectionz');

$limit      = isset($_POST['limit']) ? $core->limitControl($_POST['limit'],10)     : 10;
$beforeHpid = isset($_POST['hpid']) && is_numeric($_POST['hpid']) ? $_POST['hpid'] : false;
$id         = isset($_POST['id'])   && is_numeric($_POST['id']))  ? $_POST['id']   : false;

switch($path) {
case 'home':
    $func[0] = 'getNLatestBeforeHpid';
    $func[1] = 'getLatests';
    if(!$logged)
        die(); // no registered users can't see the homepage
    break;

default:
    $func[0] = 'getNMessagesBeforeHpid';
    $func[1] = 'getMessages';
    if(!$id)
        die($core->lang('ERROR'));
    break;
}

$mess = $beforeHpid 
    ? $messages->getNMessagesBeforeHpid($limit,$beforeHpid,$id, $prj, false) 
    : $messages->getMessages($id,$limit, $prj , false);

if(!$mess || (!$logged && $beforeHpid))
    die(); //empty so javascript client code stop making requests

$vals = [];
$vals['count_n'] = count($mess);
$vals['list_a'] = $messages->getPostList($mess,$prj);
$core->getTPL()->assign($vals);
$core->getTPL()->draw($path.'/postlist');
?>
