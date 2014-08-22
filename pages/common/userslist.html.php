<?php
if(!isset($id, $users, $type))
    die('$id & $users required');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\User;
use NERDZ\Core\Utils;
use NERDZ\Core\Db;

$limit   = isset($_GET['lim']) ? NERDZ\Core\Security::limitControl($_GET['lim'], 20) : 20;

$user = new User();

$i = 0;
$ret = [];
foreach($users as $fid)
{
    $ret[$i] = $user->getBasicInfo($fid);
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

$myvals = [];
$myvals['list_a'] = $ret;
$myvals['type_n'] = $type;
NERDZ\Core\Security::setNextAndPrevURLs($myvals, $limit, $options = []);
$user->getTPL()->assign($myvals);
return $user->getTPL()->draw('base/userslist', true);
?>
