<?php
if(!isset($gid, $user, $project))
    die('$id & user required');

require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;

$limit = isset($_GET['lim']) ? NERDZ\Core\Security::limitControl($_GET['lim'], 20) : 20;
$users = $project->getMembers($gid, $limit);
$total = $project->getMembersCount($gid);
$type  = 'members';
$dateExtractor = function($memberId) use ($gid,$user) {
    $projectId = $gid;
    $since = Db::query(
        [
            'SELECT EXTRACT(EPOCH FROM time) AS time
            FROM "groups_members"
            WHERE "from" = :fid AND "to" = :id',
            [
                ':id' => $projectId,
                ':fid' => $memberId
            ]
        ],Db::FETCH_OBJ);
    if(!$since) {
        $since = new StdClass();
        $since->time = 0;
    }
    return $user->getDateTime($since->time);
};
return require $_SERVER['DOCUMENT_ROOT'].'/pages/common/userslist.html.php';
?>
