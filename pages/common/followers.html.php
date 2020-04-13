<?php
/*
Copyright (C) 2010-2020 Paolo Galeone <nessuno@nerdz.eu>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
if (!isset($id)) {
    die('$id required');
}

require_once $_SERVER['DOCUMENT_ROOT'].'/class/Autoload.class.php';
use NERDZ\Core\Project;
use NERDZ\Core\User;
use NERDZ\Core\Db;

$prj = isset($prj);
$entity = $prj ? new Project() : new User();
$limit = isset($_GET['lim']) ? NERDZ\Core\Security::limitControl($_GET['lim'], 20) : 20;
$users = $entity->getFollowers($id, $limit);
$total = $entity->getFollowersCount($id);
$type = 'followers';
$user = new User();
$dateExtractor = function ($friendId) use ($id, $user, $prj) {
    $profileId = $id;
    $since = Db::query(
        [
            'SELECT EXTRACT(EPOCH FROM time) AS time
            FROM "'.($prj ? 'groups_' : '').'followers"
            WHERE "to" = :id AND "from" = :fid',
            [
                ':id' => $profileId,
                ':fid' => $friendId,
            ],
        ],
        Db::FETCH_OBJ
    );
    if (!$since) {
        $since = new StdClass();
        $since->time = 0;
    }

    return $user->getDate($since->time);
};

return require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/userslist.html.php';
