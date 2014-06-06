<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/comments.class.php';
//pagina da includere in home(profili) e in refresh dei profili, fornendogli la variabile $vals = array()
//e da includere dopo aver creato $mess 
$comments = new comments();

$vals['count_n'] = $count = count($mess);

$ret = array();
for($i=0;$i<$count;++$i)
{
    if(!($from = $core->getUserName($mess[$i]['from'])))
        $from = '';
    if(!($to = $core->getUserName($mess[$i]['to'])))
        $to =  '';

    $vals['news_b'] = $mess[$i]['news'];
    $vals['pid_n'] = $mess[$i]['pid'];
    $vals['revisions_n'] = $core->getRevisionsNumber($mess[$i]['hpid']);
    $vals['thumbs_n'] = $core->getThumbs($mess[$i]['hpid']);
    $vals['uthumb_n'] = $core->getUserThumb($mess[$i]['hpid']);
    $vals['from4link_n'] = phpCore::userLink($from);
    $vals['to4link_n'] = phpCore::userLink($to);
    $vals['fromid_n'] = $mess[$i]['from'];
    $vals['toid_n'] = $mess[$i]['to'];
    $vals['from_n'] = $from;
    $vals['to_n'] = $to;
    $vals['datetime_n'] = $mess[$i]['datetime'];
    $vals['canremovepost_b'] = $core->canRemovePost($mess[$i]);
    $vals['caneditpost_b'] = $core->canEditPost($mess[$i]);
    $vals['canshowlock_b'] = $core->canShowLockForPost($mess[$i]);
    $vals['canshowlurk_b'] = $core->isLogged() ? !$vals['canshowlock_b'] : false;
    $vals['lurk_b'] = $core->hasLurkedPost($mess[$i]);
    $vals['lock_b'] = $core->hasLockedPost($mess[$i]);
    $vals['cmp_n'] = $mess[$i]['cmp'];

    $vals['canshowbookmark_b'] = $core->isLogged();
    $vals['bookmark_b'] = $core->hasBookmarkedPost($mess[$i]);

    //miniature è settato quando è incluso da home
    $vals['message_n'] = $core->bbcode($mess[$i]['message'],isset($miniature),'u',$vals['pid_n'],$vals['toid_n']);
    $vals['postcomments_n'] = $comments->countComments($mess[$i]['hpid']);
    $vals['hpid_n'] = $mess[$i]['hpid'];

    $ret[$i] = $vals;
}
$vals['list_a'] = $ret;
?>
