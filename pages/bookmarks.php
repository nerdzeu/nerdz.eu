<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;
use NERDZ\Core\Project;
use NERDZ\Core\Utils;
use NERDZ\Core\Messages;
use \PDO;

$messages = new Messages();

$limit = isset($_GET['lim']) ? NERDZ\Core\Security::limitControl($_GET['lim'], 20) : 20;

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

$vals = [];
$vals['project_b'] = $prj;

$q = empty($_GET['q']) ? '' : htmlspecialchars($_GET['q'],ENT_QUOTES,'UTF-8');

if($prj)
{
    $orderby = $orderby == 'time' ? 'groups_bookmarks.time' : $orderby;
    $query = empty($q)
        ?
        array(
            'SELECT p.*, EXTRACT(EPOCH FROM groups_bookmarks.time) AS time FROM "groups_bookmarks" INNER JOIN "groups_posts" p ON p.hpid = groups_bookmarks.hpid WHERE groups_bookmarks.from = ? ORDER BY '.$orderby.' '.$order.' LIMIT '.$limit,
            array($_SESSION['id'])
        )
        :
        array(
            "SELECT p.*, EXTRACT(EPOCH FROM groups_bookmarks.time) AS time FROM groups_bookmarks INNER JOIN groups_posts p ON p.hpid = groups_bookmarks.hpid WHERE groups_bookmarks.from = ? AND CAST({$orderby} AS TEXT) LIKE ? ORDER BY {$orderby} {$order} LIMIT {$limit}",
            array($_SESSION['id'],"%{$q}%")
        );

    $linkMethod = 'projectLink';
    $nameMethod = 'getName';
    $object     = new Project();
}
else
{
    $orderby = $orderby == 'time' ? 'bookmarks.time' : $orderby;
    $query = empty($q)
        ?
        array(
            "SELECT p.*, EXTRACT(EPOCH FROM bookmarks.time) AS time FROM bookmarks INNER JOIN posts p ON p.hpid = bookmarks.hpid WHERE bookmarks.from = ? ORDER BY {$orderby} {$order} LIMIT {$limit}",
            array($_SESSION['id'])
        )
        :
        array(
            "SELECT p.*, EXTRACT(EPOCH FROM bookmarks.time) AS time FROM bookmarks INNER JOIN posts p ON p.hpid = bookmarks.hpid WHERE bookmarks.from = ? AND CAST({$orderby} AS TEXT) LIKE ? ORDER BY {$orderby} {$order} LIMIT {$limit}",
            array($_SESSION['id'],"%{$q}%")
        );

    $linkMethod = 'userLink';
    $nameMethod = 'getUsername';
    $object     = $user;
}

$vals['list_a'] = [];

if(($r = Db::query($query,Db::FETCH_STMT)))
{
    $i = 0;
    while(($o = $r->fetch(PDO::FETCH_OBJ)))
    {
        $vals['list_a'][$i] = $messages->getPost($o,
            [
                'project'  => $prj,
                'truncate' => true
            ]);

        $vals['list_a'][$i]['name_n']    = $object->$nameMethod($o->to);
        $vals['list_a'][$i]['preview_n'] = $messages->bbcode(htmlspecialchars(substr(html_entity_decode($o->message,ENT_QUOTES,'UTF-8'),0,256),ENT_QUOTES,'UTF-8').'...',true);
        $vals['list_a'][$i]['link_n']    = '/'.Utils::$linkMethod($vals['list_a'][$i]['name_n']).$o->pid;
        ++$i;
    }
}

$desc = $order == 'DESC' ? '1' : '0';
$url = "?orderby={$orderby}&amp;desc={$desc}&amp;q={$q}".($prj ? '&amp;project=1' : '');
if(is_numeric($limit))
{
    $vals['prev_url_n'] = '';
    $vals['next_url_n'] = count($vals['list_a']) == 20 ? $url.'&amp;lim=20,20' : '';
}
else
{
    if(2 == sscanf($_GET['lim'],"%d,%d",$a,$b))
    {
        $next = $a+20;
        $prev = $a-20;
        $limitnext = "{$next},20";
        $limitprev = $prev >0 ? "{$prev},20" : '20';
    }

    $vals['next_url_n'] = count($vals['list_a']) == 20 ? $url."&amp;lim={$limitnext}" : '';
    $vals['prev_url_n'] = $url."&amp;lim={$limitprev}";
}

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';

$user->getTPL()->assign($vals);
$user->getTPL()->draw('profile/bookmarks');
?>
