<?php
if(!isset($id, $user))
    die('$id & user required');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;

$limit   = isset($_GET['lim']) ? NERDZ\Core\Security::limitControl($_GET['lim'], 20) : 20;
$users = $user->getFollowing($id, $limit);
$total = $user->getFollowingCount($id);
$type = 'following';
$dateExtractor = function($friendId) use ($id,$user) {
    $profileId = $id;
    $since = Db::query(
        [
            'SELECT EXTRACT(EPOCH FROM time) AS time
            FROM "followers"
            WHERE "from" = :id AND "to" = :fid',
            [
                ':id' => $profileId,
                ':fid' => $friendId
            ]
        ],Db::FETCH_OBJ);
    if(!$since) {
        $since = new StdClass();
        $since->time = 0;
    }
    return $user->getDateTime($since->time);
};
return require $_SERVER['DOCUMENT_ROOT'].'/pages/common/userslist.html.php';
