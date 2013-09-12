<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/comments.class.php';
//pagina da includere in home(profili) e in refresh dei profili, fornendogli la variabile $vals = array()
//e da includere dopo aver creato $mess 
$comments = new comments();
$vals['count_n'] = $count = count($mess);

$members = $ret = array();
for($i=0;$i<$count;++$i)
{
    if(!($from = $core->getUserName($mess[$i]['from'])))
        $from = '';
    if(!($to = $core->getProjectName($mess[$i]['to'])))
        $to =  '';
    if(!($own = $core->getOwnerByGid($mess[$i]['to'])))
        $own = 0;

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

    $vals['canremovepost_b'] = $core->canRemoveProjectPost($mess[$i],$own);
    if(!isset($members[$mess[$i]['to']]))
        $members[$mess[$i]['to']] = $core->getMembers($mess[$i]['to']);

    $vals['caneditpost_b'] = $core->canEditProjectPost($mess[$i],$own,$members[$mess[$i]['to']]);
    $vals['canshowlock_b'] = $core->canShowLockForProjectPost($mess[$i]);
    $vals['lock_b'] = $core->hasLockedProjectPost($mess[$i]);

    $vals['canshowlurk_b'] = $core->isLogged() ? !$vals['canshowlock_b'] : false;
    $vals['lurk_b'] = $core->hasLurkedProjectPost($mess[$i]);
    
    $vals['canshowbookmark_b'] = $core->isLogged();
    $vals['bookmark_b'] = $core->hasBookmarkedProjectPost($mess[$i]);

    //miniature è settato quando è incluso da home
    $vals['message_n'] = $core->bbcode($mess[$i]['message'],isset($miniature),'g',$vals['pid_n'],$vals['toid_n']);
    $vals['postcomments_n'] = $comments->countProjectComments($mess[$i]['hpid']);
    $vals['hpid_n'] = $mess[$i]['hpid'];

    $ret[$i] = $vals;
}
$vals['list_a'] = $ret;
?>
