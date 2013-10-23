<?php
$limit = $core->limitControl(isset($_GET['lim']) ? $_GET['lim'] : 0 ,20) ? $_GET['lim'] : 20;

$prj = isset($_GET['project']);

switch(isset($_GET['orderby']) ? trim(strtolower($_GET['orderby'])) : '')
{
    case 'preview':
        $orderby = 'message';
    break;

    default:
        $orderby = 'time';
    break;
}

$order = isset($_GET['asc']) && $_GET['asc'] == 1 ? 'ASC' : 'DESC';

$vals = array();

$vals['project_b'] = $prj;

$vals['url'] = 'URL';
$vals['preview'] = $core->lang('PREVIEW');

$q = empty($_GET['q']) ? '' : htmlentities($_GET['q'],ENT_QUOTES,'UTF-8');

if($prj)
{
    $orderby = $orderby == 'time' ? 'groups_bookmarks.time' : $orderby; //per non svelare struttura database
    $query = empty($q)
        ?
         array(
                'SELECT groups_bookmarks.hpid, EXTRACT(EPOCH FROM groups_bookmarks.time) AS time, groups_posts.message, groups_posts.to, groups_posts.pid FROM "groups_bookmarks" INNER JOIN "groups_posts" ON groups_posts.hpid = groups_bookmarks.hpid WHERE groups_bookmarks.from = ? ORDER BY '.$orderby.' '.$order.' LIMIT '.$limit,
                array($_SESSION['nerdz_id'])
             )
        :
        array(
                "SELECT groups_bookmarks.hpid, EXTRACT(EPOCH FROM groups_bookmarks.time) AS time, groups_posts.message, groups_posts.to, groups_posts.pid FROM groups_bookmarks INNER JOIN groups_posts ON groups_posts.hpid = groups_bookmarks.hpid WHERE groups_bookmarks.from = ? AND {$orderby} LIKE ? ORDER BY {$orderby} {$order} LIMIT {$limit}",
                array($_SESSION['nerdz_id'],"%{$q}%")
             );

    $linkMethod = 'projectLink';
    $nameMethod = 'getProjectName';
}
else
{
    $orderby = $orderby == 'time' ? 'bookmarks.time' : $orderby;
    $query = empty($q)
        ?
         array(
                 "SELECT bookmarks.hpid, EXTRACT(EPOCH FROM bookmarks.time) AS time, posts.message, posts.to, posts.pid FROM bookmarks INNER JOIN posts ON posts.hpid = bookmarks.hpid WHERE bookmarks.from = ? ORDER BY {$orderby} {$order} LIMIT {$limit}",
                 array($_SESSION['nerdz_id'])
             )
        :
        array(
                "SELECT bookmarks.hpid, EXTRACT(EPOCH FROM bookmarks.time) AS time, posts.message, posts.to, posts.pid FROM bookmarks INNER JOIN posts ON posts.hpid = bookmarks.hpid WHERE bookmarks.from = ? AND {$orderby} LIKE ? ORDER BY {$orderby} {$order} LIMIT {$limit}",
                array($_SESSION['nerdz_id'],"%{$q}%")
             );

    $linkMethod = 'userLink';
    $nameMethod = 'getUserName';
}

$vals['list_a'] = array();

$vals['when'] = $core->lang('WHEN');

if(($r = $core->query($query,db::FETCH_STMT)))
{
    $i = 0;
    while(($o = $r->fetch(PDO::FETCH_OBJ)))
    {
        $vals['list_a'][$i]['datetime_n'] = $core->getDateTime($o->time);
        $vals['list_a'][$i]['pid_n'] = $o->pid;
        $vals['list_a'][$i]['hpid_n'] = $o->hpid;
        $vals['list_a'][$i]['name_n'] = $core->$nameMethod($o->to);
        $vals['list_a'][$i]['preview_n'] = $core->bbcode(htmlentities(substr(html_entity_decode($o->message,ENT_QUOTES,'UTF-8'),0,256),ENT_QUOTES,'UTF-8').'...',true);
        $vals['list_a'][$i]['link_n'] = '/'.phpCore::$linkMethod($vals['list_a'][$i]['name_n']).$o->pid;

        ++$i;
    }
}

$desc = $order == 'DESC' ? '1' : '0';
$url = "?orderby={$orderby}&amp;desc={$desc}&amp;q={$q}".($prj ? '&amp;project=1' : '');
if(is_numeric($limit))
{
    $vals['prev_url'] = '';
    $vals['next_url'] = count($vals['list_a']) == 20 ? $url.'&amp;lim=20,20' : '';
}
else
{
    if(2 == sscanf($limit,"%d,%d",$a,$b))
    {
        $next =  $a+20;
        $prev = $a-20;
        $limitnext = "{$next},20";
        $limitprev = $prev >0 ? "{$prev},20" : '20';
    }

    $vals['next_url'] = count($vals['list_a']) == 20 ? $url."&amp;lim={$limitnext}" : '';
    $vals['prev_url'] = $url."&amp;lim={$limitprev}";
}

$vals['user_menu_m']= ('<div class="title">'.$core->lang('USER_MENU').'</div><div class="box_menu"> <ul><a href="/"><li><img src="tpl/1/base/images/home-dark.png">Home</li></a><a href="/'.phpCore::userLink($core->getUserName()).'"><li><img src="tpl/1/base/images/prof.png">'.$core->lang('PROFILE').'</li></a><a href="/preferences.php"><li><img src="tpl/1/base/images/settings.png">'.$core->lang('PREFERENCES').'</li></a><a href="/" id="logout" data-tok="'.$core->getCsrfToken().'"><li><img src="tpl/1/base/images/exit.png">'.$core->lang('LOGOUT').'</li></a></ul></div>');

$core->getTPL()->assign($vals);
$core->getTPL()->draw('profile/bookmarks');
?>
