<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/comments.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';
//pagina da includere in home(profili) e in refresh dei profili, fornendogli la variabile $vals = array()
//e da includere dopo aver creato $mess 
$comments = new comments();
$project = new project();
$vals['count_n'] = $count = count($mess);

$members = $ret = array();
for($i=0;$i<$count;++$i)
{
    if(!($from = $core->getUsername($mess[$i]['from'])))
        $from = '';
    if(!($to = $core->getProjectName($mess[$i]['to'])))
        $to =  '';
    if(!($own = $project->getOwner($mess[$i]['to'])))
        $own = 0;

    $vals['thumbs_n'] = $messages->getThumbs($mess[$i]['hpid'], true);
    $vals['revisions_n'] = $messages->getRevisionsNumber($mess[$i]['hpid'], true);
    $vals['uthumb_n'] = $messages->getUserThumb($mess[$i]['hpid'], true);
    $vals['pid_n'] = $mess[$i]['pid'];
    $vals['news_b'] = $mess[$i]['news'];
    $vals['from4link_n'] = phpCore::userLink($from);
    $vals['to4link_n'] = phpCore::projectLink($to);
    $vals['fromid_n'] = $mess[$i]['from'];
    $vals['toid_n'] = $mess[$i]['to'];
    $vals['from_n'] = $from;
    $vals['to_n'] = $to;
    $vals['datetime_n'] = $mess[$i]['datetime'];
    $vals['cmp_n'] = $mess[$i]['cmp'];

    $vals['canremovepost_b'] = $messages->canRemovePost($mess[$i],$own, true);
    if(!isset($members[$mess[$i]['to']]))
        $members[$mess[$i]['to']] = $project->getMembers($mess[$i]['to']);

    $vals['caneditpost_b'] = $messages->canEditPost($mess[$i],$own,$members[$mess[$i]['to']], true);
    $vals['canshowlock_b'] = $messages->canShowLockForPost($mess[$i], true);
    $vals['lock_b'] = $messages->hasLockedPost($mess[$i], true);

    $vals['canshowlurk_b'] = $core->isLogged() ? !$vals['canshowlock_b'] : false;
    $vals['lurk_b'] = $messages->hasLurkedPost($mess[$i], true);
    
    $vals['canshowbookmark_b'] = $core->isLogged();
    $vals['bookmark_b'] = $messages->hasBookmarkedPost($mess[$i], true);

    //miniature è settato quando è incluso da home
    $vals['message_n'] = $messages->bbcode($mess[$i]['message'],isset($miniature),'g',$vals['pid_n'],$vals['toid_n']);
    $vals['postcomments_n'] = $comments->countComments($mess[$i]['hpid'], true);
    $vals['hpid_n'] = $mess[$i]['hpid'];

    $ret[$i] = $vals;
}
$vals['list_a'] = $ret;
?>
