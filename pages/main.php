<?php
if(!$user->isLogged())
    die(header('Location: /'));

use NERDZ\Core\Banners;
use NERDZ\Core\Db;
use NERDZ\Core\User;
use NERDZ\Core\System;
$vals = [];

$banners = (new Banners())->getBanners();
$vals['banners_a'] = [];
shuffle($banners);
foreach($banners as $ban)
    $vals['banners_a'][$ban[1]] = $ban[2];

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';
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
            if(!($o = Db::query(array('SELECT "birth_date" FROM "users" WHERE "counter" = :id',array(':id' => $l[$i])),Db::FETCH_OBJ)))
            {
                echo $user->lang('ERROR');
                break;
            }
            $myarray[$i]['id_n'] = $l[$i];
            $myarray[$i]['username_n'] = User::getUsername($l[$i]);
            $myarray[$i]['username4link_n'] = \NERDZ\Core\Utils::userLink($myarray[$i]['username_n']);
            $myarray[$i]['online_b'] = $user->isOnline($l[$i]);
            if($myarray[$i]['online_b'])
                ++$c;
            $myarray[$i]['birthday_b'] = date('d-m',strtotime($o->birth_date)) == date('d-m',time());
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
    $vals['ownerof_a'][$i]['name4link_n'] = \NERDZ\Core\Utils::projectLink($o->name);
    ++$i;
}

if(!($r = Db::query(array('SELECT "name" FROM "groups" INNER JOIN "groups_members" ON "groups"."counter" = "groups_members"."to" WHERE "from" = :id',array(':id' => $_SESSION['id'])),Db::FETCH_STMT)))
    die($user->lang('ERROR'));

$vals['memberof_a'] = [];
$i = 0;
while(($o = $r->fetch(PDO::FETCH_OBJ)))
{
    $vals['memberof_a'][$i]['name_n'] = $o->name;
    $vals['memberof_a'][$i]['name4link_n'] = \NERDZ\Core\Utils::projectLink($o->name);
    ++$i;
}

$user->getTPL()->assign($vals);
$user->getTPL()->draw('home/layout');
?>
