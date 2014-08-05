<?php
//necessita di $hpid e $draw SEMPRE
//questa pagina viene sempre inclusa, quindi non necessita di ob_start e altri include che tanto fanno gli altri file (ma tanto usiamo require_once once che è meglio per star sicuri)
require_once $_SERVER['DOCUMENT_ROOT'].'/class/comments.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/Messages.class.php';
ob_start(array('Core','minifyHtml'));

$core = new Project();
$comments = new Comments();
$Messages = new Messages();

if(
    empty($hpid) ||
    !($o = $Messages->getMessage($hpid, true))
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
$singlepostvals['thumbs_n'] = $Messages->getThumbs($hpid, true);
$singlepostvals['revisions_n'] = $Messages->getRevisionsNumber($hpid, true);
$singlepostvals['uthumb_n'] = $Messages->getUserThumb($hpid, true); 
$singlepostvals['pid_n'] = $o->pid;
$singlepostvals['from4link_n'] = NERDZ\Core\Core::userLink($from);
$singlepostvals['to4link_n'] = NERDZ\Core\Core::projectLink($to);
$singlepostvals['fromid_n'] = $o->from;
$singlepostvals['toid_n'] = $o->to;
$singlepostvals['from_n'] = $from;
$singlepostvals['to_n'] = $to;
$singlepostvals['datetime_n'] = $core->getDateTime($o->time);
$singlepostvals['cmp_n'] = $o->time;

$singlepostvals['canremovepost_b'] = $Messages->canRemovePost((array)$o,$own, true);
$singlepostvals['caneditpost_b'] = $Messages->canEditPost((array)$o, true);
$singlepostvals['canshowlock_b'] = $Messages->canShowLockForPost((array)$o, true);
$singlepostvals['lock_b'] = $Messages->hasLockedPost((array)$o, true);

$singlepostvals['canshowlurk_b'] = $core->isLogged() ? !$singlepostvals['canshowlock_b'] : false;
$singlepostvals['lurk_b'] = $Messages->hasLurkedPost((array)$o, true);

$singlepostvals['canshowbookmark_b'] = $core->isLogged();
$singlepostvals['bookmark_b'] = $Messages->hasBookmarkedPost((array)$o, true);

$singlepostvals['message_n'] = in_array($o->from,$core->getBlacklist()) ? 'Blacklist' : $Messages->bbcode($o->message,true,'g',$singlepostvals['pid_n'],$singlepostvals['toid_n']);
$singlepostvals['postcomments_n'] = $comments->countComments($o->hpid, true);
$singlepostvals['hpid_n'] = $o->hpid;
$singlepostvals['news_b'] = $o->news;

$core->getTPL()->assign($singlepostvals);
    
if($draw)
    $core->getTPL()->draw('project/post');
else
    $singlepost = $core->getTPL()->draw('project/post',true);

?>
