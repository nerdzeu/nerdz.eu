<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;

$limit = isset($_GET['lim']) ? $core->limitControl($_GET['lim'],20) : 20;

switch(isset($_GET['orderby']) ? trim(strtolower($_GET['orderby'])) : '')
{
    case 'name':
        $orderby = 'name';
    break;

    case 'description':
        $orderby = 'description';
    break;

    case 'id':
    default:
        $orderby = 'counter';
    break;
}

$order = isset($_GET['desc']) && $_GET['desc'] == 1 ? 'DESC' : 'ASC';

$vals = [];

$q = empty($_GET['q']) ? '' : htmlspecialchars($_GET['q'],ENT_QUOTES,'UTF-8');

if(empty($q))
    $query = "SELECT name, description,counter FROM groups ORDER BY {$orderby} {$order} LIMIT {$limit}";
else
{
    $orderbycounter = $orderby == 'counter';
    $query = array("SELECT name,description, counter FROM groups WHERE CAST({$orderby} AS TEXT) ".($orderbycounter ? '=' : 'ILIKE')." ? ORDER BY {$orderby} {$order} LIMIT {$limit}",
            $orderbycounter ? array($q) : array("%{$q}%"));
}

$vals['list_a'] = [];

if(($r = Db::query($query,Db::FETCH_STMT)))
{
    $i = 0;
    while(($o = $r->fetch(PDO::FETCH_OBJ)))
    {
        $vals['list_a'][$i]['id_n'] = $o->counter;
        $vals['list_a'][$i]['name_n'] = $o->name;
        $vals['list_a'][$i]['description_n'] = $o->description;
        $vals['list_a'][$i]['name4link_n'] = \NERDZ\Core\Utils::projectLink($o->name);
        ++$i;
    }
}

$desc = $order == 'DESC' ? '1' : '0';
$url = "?orderby={$orderby}&amp;desc={$desc}&amp;q={$q}";
if(is_numeric($limit))
{
    $vals['prev_url_n'] = '';
    $vals['next_url_n'] = count($vals['list_a']) == 20 ? $url.'&amp;lim=20,20' : '';
}
else
{
    if(2 == sscanf($_GET['lim'],"%d,%d",$a,$b))
    {
        $next =  $a+20;
        $prev = $a-20;
        $limitnext = "{$next},20";
        $limitprev = $prev >0 ? "{$prev},20" : '20';
    }

    $vals['next_url_n'] = count($vals['list_a']) == 20 ? $url."&amp;lim={$limitnext}" : '';
    $vals['prev_url_n'] = $url."&amp;lim={$limitprev}";
}

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';

$core->getTPL()->assign($vals);
$core->getTPL()->draw('base/projectslist');
?>
