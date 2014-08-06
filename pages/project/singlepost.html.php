<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
ob_start(array('NERDZ\\Core\\Core','minifyHtml'));

use NERDZ\Core\Project;
use NERDZ\Core\Comments;
use NERDZ\Core\Messages;

$core = new Project();
$comments = new Comments();
$messages = new Messages();

if(
    empty($hpid) ||
    !($o = $messages->getMessage($hpid, true))
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
$singlepostvals['thumbs_n'] = $messages->getThumbs($hpid, true);
$singlepostvals['revisions_n'] = $messages->getRevisionsNumber($hpid, true);
$singlepostvals['uthumb_n'] = $messages->getUserThumb($hpid, true); 
$singlepostvals['pid_n'] = $o->pid;
$singlepostvals['from4link_n'] = \NERDZ\Core\Core::userLink($from);
$singlepostvals['to4link_n'] = \NERDZ\Core\Core::projectLink($to);
$singlepostvals['fromid_n'] = $o->from;
$singlepostvals['toid_n'] = $o->to;
$singlepostvals['from_n'] = $from;
$singlepostvals['to_n'] = $to;
$singlepostvals['datetime_n'] = $core->getDateTime($o->time);
$singlepostvals['cmp_n'] = $o->time;

$singlepostvals['canremovepost_b'] = $messages->canRemovePost((array)$o,$own, true);
$singlepostvals['caneditpost_b'] = $messages->canEditPost((array)$o, true);
$singlepostvals['canshowlock_b'] = $messages->canShowLockForPost((array)$o, true);
$singlepostvals['lock_b'] = $messages->hasLockedPost((array)$o, true);

$singlepostvals['canshowlurk_b'] = $core->isLogged() ? !$singlepostvals['canshowlock_b'] : false;
$singlepostvals['lurk_b'] = $messages->hasLurkedPost((array)$o, true);

$singlepostvals['canshowbookmark_b'] = $core->isLogged();
$singlepostvals['bookmark_b'] = $messages->hasBookmarkedPost((array)$o, true);

$singlepostvals['message_n'] = in_array($o->from,$core->getBlacklist()) ? 'Blacklist' : $messages->bbcode($o->message,true,'g',$singlepostvals['pid_n'],$singlepostvals['toid_n']);
$singlepostvals['postcomments_n'] = $comments->countComments($o->hpid, true);
$singlepostvals['hpid_n'] = $o->hpid;
$singlepostvals['news_b'] = $o->news;

$core->getTPL()->assign($singlepostvals);
    
if($draw)
    $core->getTPL()->draw('project/post');
else
    $singlepost = $core->getTPL()->draw('project/post',true);

?>
