<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Config;
use NERDZ\Core\Utils;
use NERDZ\Core\Stuff;
use NERDZ\Core\Db;
use NERDZ\Core\User;
use \PDO;

$mo = empty($_GET['top']);
$un_ti = ' AND ("time" + INTERVAL \'28 days\') > NOW()';
$path = Config\SITE_HOST. ($mo ? 'r_month.json' : 'rank.json');

if(!apc_exists($path))
{
    $res = Db::query('SELECT COUNT("hcid") AS cc,"from" FROM "comments" WHERE "from" <> '.Config\DELETED_USERS.(!$mo ? $un_ti : '').' GROUP BY "from" ORDER BY cc DESC LIMIT 100',Db::FETCH_STMT);
    $rank = [];

    while(($o = $res->fetch(PDO::FETCH_OBJ)))
    {
        $gc = Db::query(array('SELECT COUNT("hcid") AS cc FROM "groups_comments" WHERE "from" = :from '.(!$mo ? $un_ti : ''),array(':from' => $o->from)),Db::FETCH_OBJ);
        $us = User::getUsername($o->from);
        $n = $o->cc + $gc->cc;
        $rank[$us] = $n;
        $stupid = Stuff::stupid($n);
        $ss[$us] = $stupid['now'];
    }
    
    asort($rank);
    $rank = array_reverse($rank,true);

    $i = 0;
    $ret = [];
    
    foreach($rank as $username => $val)
    {
        $ret[$i]['position_n'] = $i+1;
        $ret[$i]['username4link_n'] =  \NERDZ\Core\Utils::userLink($username);
        $ret[$i]['username_n'] = $username;
        $ret[$i]['comments_n'] = $val;
        $ret[$i]['stupidstuff_n'] = $ss[$username];
        ++$i;
    }

    @apc_store($path,serialize(json_encode($ret)),3600);
}
else
    $ret = json_decode(unserialize(apc_fetch($path)),true);

$vals['list_a'] = $ret;
$vals['monthly_b'] = !$mo;
$vals['lastupdate_n'] = $core->getDateTime(Utils::apc_getLastModified($path));

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';
$core->getTPL()->assign($vals);
$core->getTPL()->draw('base/rank');
?>
