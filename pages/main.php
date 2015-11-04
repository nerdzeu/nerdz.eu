<?php
if(!$user->isLogged())
    die(header('Location: /'));

use NERDZ\Core\Db;
use NERDZ\Core\User;
use NERDZ\Core\System;

$vals = [];
$vals['canwriteissue_b'] = $vals['canwritenews_b'] = false;
$vals['id_n'] = $_SESSION['id'];

$l = $user->getFollowing($_SESSION['id']);
$tot = count($l);

if($tot>0)
{
    if(!empty($l[0]))
    {
        $myarray = [];
        $c = 0;
        for($i=0;$i<$tot;++$i)
        {
            $myarray[$i] = $user->getBasicInfo($l[$i]);
            if($myarray[$i]['online_b'])
                ++$c;
        }

        function sortbyonlinestatus($a,$b)
        {
            if(($a['online_b'] && $b['online_b']) || (!$a['online_b'] && !$b['online_b']))
                return \NERDZ\Core\Utils::sortByUsername($a,$b);

            return $b['online_b'] ? 1 : -1;
        }

        usort($myarray,'sortbyonlinestatus');
    }
    $vals['followed_a'] = $myarray;
}
else
    $c = 0;

$vals['followedtot_n'] = $tot;
$vals['followedonlinetot_n'] = $c;

if(!($r = Db::query(
    [
        'SELECT "name" FROM "groups" g INNER JOIN "groups_owners" go
        ON go."to" = g.counter
        WHERE go."from" = :id',
        [
            ':id' => $_SESSION['id']
        ]
    ],Db::FETCH_STMT)))
    die($user->lang('ERROR'));

$vals['ownerof_a'] = [];
$i = 0;
while(($o = $r->fetch(PDO::FETCH_OBJ)))
{
    $vals['ownerof_a'][$i]['name_n'] = $o->name;
    $vals['ownerof_a'][$i]['username_n'] = $o->name;
    $vals['ownerof_a'][$i]['name4link_n'] = \NERDZ\Core\Utils::projectLink($o->name);
    ++$i;
}

usort($vals['ownerof_a'],'\\NERDZ\\Core\\Utils::sortByUsername');

if(!($r = Db::query(array('SELECT "name" FROM "groups" INNER JOIN "groups_members" ON "groups"."counter" = "groups_members"."to" WHERE "from" = :id',array(':id' => $_SESSION['id'])),Db::FETCH_STMT)))
    die($user->lang('ERROR'));

$vals['memberof_a'] = [];
$i = 0;
while(($o = $r->fetch(PDO::FETCH_OBJ)))
{
    $vals['memberof_a'][$i]['name_n'] = $o->name;
    $vals['memberof_a'][$i]['username_n'] = $o->name;
    $vals['memberof_a'][$i]['name4link_n'] = \NERDZ\Core\Utils::projectLink($o->name);
    ++$i;
}
usort($vals['memberof_a'],'\\NERDZ\\Core\\Utils::sortByUsername');

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/trends.html.php';

$user->getTPL()->assign($vals);
$user->getTPL()->draw('home/layout');
