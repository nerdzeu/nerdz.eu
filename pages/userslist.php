<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;

$limit = isset($_GET['lim']) ? $user->limitControl($_GET['lim'], 20) : 20;

switch(isset($_GET['orderby']) ? trim(strtolower($_GET['orderby'])) : '')
{
case 'name':
    $orderby = 'name';
    break;

case 'surname':
    $orderby = 'surname';
    break;

case 'username':
    $orderby = 'username';
    break;

case 'birthdate':
    $orderby = 'birth_date';
    break;

case 'online':
    $orderby = 'last';
    break;

case 'id':
default:
    $orderby = 'counter';
    break;
}

$order = isset($_GET['desc']) && $_GET['desc'] == 1 ? 'DESC' : 'ASC';

$vals = [];

$q = empty($_GET['q']) ? '' : htmlspecialchars($_GET['q'],ENT_QUOTES,'UTF-8');

$query = empty($q) ?
    "SELECT name,surname,username, counter, birth_date, EXTRACT(EPOCH FROM last) AS last FROM users ORDER BY {$orderby} {$order} LIMIT {$limit}" :
    array("SELECT name,surname,username, counter,birth_date,EXTRACT(EPOCH FROM last) AS last FROM users WHERE CAST({$orderby} AS TEXT) ILIKE ? ORDER BY {$orderby} {$order} LIMIT {$limit}",array("%{$q}%"));

$vals['list_a'] = [];

if(($r = Db::query($query,Db::FETCH_STMT)))
{
    $i = 0;
    while($o = $r->fetch(PDO::FETCH_OBJ))
    {
        $vals['list_a'][$i]['id_n'] = $o->counter;
        $vals['list_a'][$i]['birthdate_n'] = preg_replace('#(00.00)#','',$user->getDateTime(strtotime($o->birth_date)));
        $vals['list_a'][$i]['name_n'] = $o->name;
        $vals['list_a'][$i]['surname_n'] = $o->surname;
        $vals['list_a'][$i]['username_n'] = $o->username;
        $vals['list_a'][$i]['username4link_n'] = \NERDZ\Core\Utils::userLink($o->username);
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

$user->getTPL()->assign($vals);
$user->getTPL()->draw('base/userslist');
?>
