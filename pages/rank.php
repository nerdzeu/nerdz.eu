<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Config;
use NERDZ\Core\Utils;
use NERDZ\Core\Stuff;
use NERDZ\Core\Db;
use NERDZ\Core\User;

$mo = empty($_GET['top']);

$path = Config\SITE_HOST. ($mo ? 'r_month.json' : 'rank.json');

if(!($ret = Utils::apc_get($path)))
    $ret = Utils::apc_set($path, function() use($mo) {

        $un_ti = ' AND ("time" + INTERVAL \'28 days\') > NOW()';

        $res = Db::query(
            'SELECT COUNT("hcid") AS cc,"from"
            FROM "comments"
            WHERE "from" <> (SELECT counter FROM special_users WHERE role = \'DELETED\')'.(!$mo ? $un_ti : '').
            ' GROUP BY "from"
            ORDER BY cc DESC LIMIT 100',Db::FETCH_STMT);

        $rank = [];

        while(($o = $res->fetch(PDO::FETCH_OBJ)))
        {
            $gc = Db::query(
                [
                    'SELECT COUNT("hcid") AS cc FROM "groups_comments" WHERE "from" = :from '.(!$mo ? $un_ti : ''),
                    [
                        ':from' => $o->from
                    ]
                ],Db::FETCH_OBJ);

            $us = User::getUsername($o->from);
            $n  = $o->cc + $gc->cc;
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
            $ret[$i]['username4link_n'] =  Utils::userLink($username);
            $ret[$i]['username_n'] = $username;
            $ret[$i]['comments_n'] = $val;
            $ret[$i]['stupidstuff_n'] = $ss[$username];
            ++$i;
        }

        return $ret;
    }, 3600); 

$vals['list_a'] = $ret;
$vals['monthly_b'] = !$mo;
$vals['lastupdate_n'] = $user->getDateTime(Utils::apc_getLastModified($path));

$user->getTPL()->assign($vals);
$user->getTPL()->draw('base/rank');
