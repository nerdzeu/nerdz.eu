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
    !($o = $core->getMessage($hpid,$edit = false))
  )
    die($core->lang('ERROR'));

if(!($from = $core->getUserName($o->from)))
    $from = '';
if(!($to = $core->getUserName($o->to)))
    $to =  '';

$singlepostvals = array();
$singlepostvals['remove'] = $core->lang('REMOVE');
$singlepostvals['edit'] = $core->lang('EDIT');
$singlepostvals['comments'] = $core->lang('COMMENTS');
$singlepostvals['areyousure'] = $core->lang('ARE_YOU_SURE');
$singlepostvals['receivenotifications'] = $core->lang('REVC_NOTIFY');
$singlepostvals['dontreceivenotifications'] = $core->lang('NOT_RECV_NOTIFY');
$singlepostvals['pid_n'] = $o->pid;
$singlepostvals['from4link_n'] = phpCore::userLink($from);
$singlepostvals['to4link_n'] = phpCore::userLink($to);
$singlepostvals['fromid_n'] = $o->from;
$singlepostvals['toid_n'] = $o->to;
$singlepostvals['from_n'] = $from;
$singlepostvals['to_n'] = $to;
$singlepostvals['datetime_n'] = $core->getDateTime($o->time);

$singlepostvals['canremovepost_b'] = $core->canRemovePost((array)$o);
$singlepostvals['caneditpost_b'] = $core->canEditPost((array)$o);
$singlepostvals['canshowlock_b'] = $core->canShowLockForPost((array)$o);
$singlepostvals['lock_b'] = $core->hasLockedPost((array)$o);
$singlepostvals['cmp_n'] = $o->time;

$singlepostvals['canshowlurk_b'] = $core->isLogged() ? !$singlepostvals['canshowlock_b'] : false;
$singlepostvals['lurk_b'] = $core->hasLurkedPost((array)$o);

$singlepostvals['canshowbookmark_b'] = $core->isLogged();
$singlepostvals['bookmark_b'] = $core->hasBookmarkedPost((array)$o);

$blisted = in_array($o->from,$core->getBlacklist());

$singlepostvals['message_n'] = $blisted ? 'Blacklist' : $core->parseNewsMessage($core->bbcode($o->message,false,'u',$singlepostvals['pid_n'],$singlepostvals['toid_n']));
$singlepostvals['postcomments_n'] = $blisted ? '0' : $comments->countComments($o->hpid);
$singlepostvals['hpid_n'] = $o->hpid;
$singlepostvals['news_b'] = isset($o->news) ? $o->news : false;

$tpl->assign($singlepostvals);
    
if($draw)
    $tpl->draw('profile/post');
else
    $singlepost = $tpl->draw('profile/post',true);

?>
