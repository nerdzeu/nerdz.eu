<?php
//necessita di $hpid e $draw SEMPRE
//questa pagina viene sempre inclusa, quindi non necessita di ob_start e altri include che tanto fanno gli altri file (ma tanto usiamo require once che è meglio per star sicuri)
require_once $_SERVER['DOCUMENT_ROOT'].'/class/comments.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';
ob_start(array('phpCore','minifyHtml'));

$core = new project();
$comments = new comments();

if(
    empty($hpid) ||
    !($o = $core->getProjectMessage($hpid,$edit = false))
  )
    die($core->lang('ERROR'));

if(!($from = $core->getUserName($o->from)))
    $from = '';
if(!($to = $core->getProjectName($o->to)))
    $to =  '';
if(!($own = $core->getOwnerByGid($o->to)))
    $own = 0;

$members = $core->getMembers($o->to);

$singlepostvals = array();
$singlepostvals['remove'] = $core->lang('REMOVE');
$singlepostvals['edit'] = $core->lang('EDIT');
$singlepostvals['comments'] = $core->lang('COMMENTS');
$singlepostvals['areyousure'] = $core->lang('ARE_YOU_SURE');
$singlepostvals['receivenotifications'] = $core->lang('REVC_NOTIFY');
$singlepostvals['dontreceivenotifications'] = $core->lang('NOT_RECV_NOTIFY');
$singlepostvals['pid_n'] = $o->pid;
$singlepostvals['from4link_n'] = phpCore::userLink($from);
$singlepostvals['to4link_n'] = phpCore::projectLink($to);
$singlepostvals['fromid_n'] = $o->from;
$singlepostvals['toid_n'] = $o->to;
$singlepostvals['from_n'] = $from;
$singlepostvals['to_n'] = $to;
$singlepostvals['datetime_n'] = $core->getDateTime($o->time);
$singlepostvals['cmp_n'] = $o->time;

$singlepostvals['canremovepost_b'] = $core->canRemoveProjectPost((array)$o,$own);
$singlepostvals['caneditpost_b'] = $core->canEditProjectPost((array)$o,$own,$members);
$singlepostvals['canshowlock_b'] = $core->canShowLockForProjectPost((array)$o);
$singlepostvals['lock_b'] = $core->hasLockedProjectPost((array)$o);

$singlepostvals['canshowlurk_b'] = $core->isLogged() ? !$singlepostvals['canshowlock_b'] : false;
$singlepostvals['lurk_b'] = $core->hasLurkedProjectPost((array)$o);

$singlepostvals['canshowbookmark_b'] = $core->isLogged();
$singlepostvals['bookmark_b'] = $core->hasBookmarkedProjectPost((array)$o);

$singlepostvals['message_n'] = in_array($o->from,$core->getBlacklist()) ? 'Blacklist' : $core->bbcode($o->message,true,'g',$singlepostvals['pid_n'],$singlepostvals['toid_n']);
$singlepostvals['postcomments_n'] = $comments->countProjectComments($o->hpid);
$singlepostvals['hpid_n'] = $o->hpid;
$singlepostvals['news_b'] = $o->news;

$tpl->assign($singlepostvals);
    
if($draw)
    $tpl->draw('project/post');
else
    $singlepost = $tpl->draw('project/post',true);

?>
