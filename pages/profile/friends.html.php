<?php
if(!isset($id))
    die('$id required');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\User;
use NERDZ\Core\Utils;
use NERDZ\Core\Db;

$user = new User();

$friends = $user->getFriends($id);
$i = 0;
$ret = [];
foreach($friends as $fid)
{
    $ret[$i]['username_n']      = User::getUsername($fid);
    $ret[$i]['username4link_n'] = Utils::userLink($ret[$i]['username_n']);
    $ret[$i]['id_n']            = $fid;
    $ret[$i]['gravatarurl_n']   = $user->getGravatar($fid);
    $ret[$i]['canifollow_b']    = !$user->isFollowing($fid);
    
    $since = Db::query(
        [
            'SELECT EXTRACT(EPOCH FROM T.cc) AS time
            FROM (
                SELECT MAX("time") AS cc FROM "followers"
                WHERE ("from" = :id AND "to" = :fid) OR ("from" = :fid AND "to" = :id)
               ) AS T',
            [
                ':id'  => $id,
                ':fid' => $fid
            ]
        ],Db::FETCH_OBJ);
    $since = $since ? $since : new StdClass();

    $ret[$i]['since_n'] = $user->getDateTime($since->time);
    ++$i;
}

usort($ret, 'NERDZ\\Core\\Utils::sortByUsername');

$user->getTPL()->assign('list_a', $ret);
return $user->getTPL()->draw('profile/friends', true);
?>
