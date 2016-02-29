<?php
if(!isset($id))
    die('$id required');

require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Project;
use NERDZ\Core\User;
use NERDZ\Core\Db;
$prj    = isset($prj);
$entity = $prj ? new Project() : new User();
$limit  = isset($_GET['lim']) ? NERDZ\Core\Security::limitControl($_GET['lim'], 20) : 20;
$users  = $entity->getFollowers($id, $limit);
$total  = $entity->getFollowersCount($id);
$type   = 'followers';
$user   = new User();
$dateExtractor = function($friendId) use ($id,$user, $prj) {
    $profileId = $id;
    $since = Db::query(
        [
            'SELECT EXTRACT(EPOCH FROM time) AS time
            FROM "'.($prj ? 'groups_' : '').'followers"
            WHERE "to" = :id AND "from" = :fid',
            [
                ':id'  => $profileId,
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
