<?php
if(!isset($id, $user))
    die('$id & user required');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;

$limit   = isset($_GET['lim']) ? NERDZ\Core\Security::limitControl($_GET['lim'], 20) : 20;

$users = $user->getFriends($id, $limit);
$total = $user->getFriendsCount($id);
$type = 'friends';
$dateExtractor = function($friendId) use ($id,$user) {
    $profileId = $id;
    $since = Db::query(
        [
            'SELECT EXTRACT(EPOCH FROM T.cc) AS time
            FROM (
                    SELECT MAX("time") AS cc FROM "followers"
                    WHERE ("from" = :id AND "to" = :fid) OR ("from" = :fid AND "to" = :id)
                ) AS T',
            [
                ':id' => $profileId,
                ':fid' => $friendId
            ]
        ],Db::FETCH_OBJ);
     if(!$since) {
        $since = new StdClass();
        $since->time = 0;
    }
    return $user->getDate($since->time);
};

return require $_SERVER['DOCUMENT_ROOT'].'/pages/common/userslist.html.php';
