<?php
if(!isset($hpid))
    die('$hpid required');

require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

use NERDZ\Core\Project;
use NERDZ\Core\Comments;
use NERDZ\Core\Messages;

$prj = isset($prj);

$core = new Project();
$comments = new Comments();
$messages = new Messages();

if(
    empty($hpid) ||
    !($o = $messages->getMessage($hpid, $prj))
  )
    die($core->lang('ERROR'));

if(!($from = $core->getUsername($o->from)))
    $from = '';
if(!($to = $core->getProjectName($o->to)))
    $to =  '';
if(!($own = $core->getOwner($o->to)))
    $own = 0;

$members = $core->getMembers($o->to);

$singlepostvals = [];
$singlepostvals['thumbs_n'] = $messages->getThumbs($hpid, $prj);
$singlepostvals['revisions_n'] = $messages->getRevisionsNumber($hpid, $prj);
$singlepostvals['uthumb_n'] = $messages->getUserThumb($hpid, $prj); 
$singlepostvals['pid_n'] = $o->pid;
$singlepostvals['from4link_n'] = \NERDZ\Core\Utils::userLink($from);
$singlepostvals['to4link_n'] = \NERDZ\Core\Utils::projectLink($to);
$singlepostvals['fromid_n'] = $o->from;
$singlepostvals['toid_n'] = $o->to;
$singlepostvals['from_n'] = $from;
$singlepostvals['to_n'] = $to;
$singlepostvals['datetime_n'] = $core->getDateTime($o->time);
$singlepostvals['cmp_n'] = $o->time;

$singlepostvals['canremovepost_b'] = $messages->canRemovePost((array)$o,$own, $prj);
$singlepostvals['caneditpost_b'] = $messages->canEditPost((array)$o, $prj);
$singlepostvals['canshowlock_b'] = $messages->canShowLockForPost((array)$o, $prj);
$singlepostvals['lock_b'] = $messages->hasLockedPost((array)$o, $prj);

$singlepostvals['canshowlurk_b'] = $core->isLogged() ? !$singlepostvals['canshowlock_b'] : false;
$singlepostvals['lurk_b'] = $messages->hasLurkedPost((array)$o, $prj);

$singlepostvals['canshowbookmark_b'] = $core->isLogged();
$singlepostvals['bookmark_b'] = $messages->hasBookmarkedPost((array)$o, $prj);

$singlepostvals['message_n'] = $messages->bbcode($o->message,true, $prj ? 'g' : 'u', $singlepostvals['pid_n'],$singlepostvals['toid_n']);
$singlepostvals['postcomments_n'] = $comments->countComments($o->hpid, $prj);
$singlepostvals['hpid_n'] = $o->hpid;
$singlepostvals['news_b'] = $o->news;

$core->getTPL()->assign($singlepostvals);
    
if($draw)
    $core->getTPL()->draw(($prj ? 'project' : 'profile').'/post');
else
    $singlepost = $core->getTPL()->draw(($prj ? 'project' : 'profile').'/post', true);

?>
