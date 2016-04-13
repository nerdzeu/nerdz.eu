<?php
/*
Copyright (C) 2016 Paolo Galeone <nessuno@nerdz.eu>

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
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/Autoload.class.php';

use NERDZ\Core\Project;
use NERDZ\Core\User;
use NERDZ\Core\Captcha;
use NERDZ\Core\Db;

$user = new User();
$project = new Project();

if (!$user->isLogged()) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('REGISTER')));
}

$id = $_POST['id'] = isset($_POST['id']) && is_numeric($_POST['id']) ? trim($_POST['id']) : false;

if ($_SESSION['id'] != $project->getOwner($id) || !NERDZ\Core\Security::refererControl()) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
}

if (!NERDZ\Core\Security::csrfControl(isset($_POST['tok']) ? $_POST['tok'] : 0)) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': token'));
}

switch (isset($_GET['action']) ? strtolower($_GET['action']) : '') {
case 'del':
    $capt = new Captcha();

    if (!($capt->check(isset($_POST['captcha']) ? $_POST['captcha'] : ''))) {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': '.$user->lang('CAPTCHA')));
    }

    if (Db::NO_ERRNO != Db::query(
        [
            'DELETE FROM "groups" WHERE "counter" = :id',
            [
                ':id' => $id,
            ],
        ], Db::FETCH_ERRNO)) {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
    }
    break;

case 'update':
    //validate fields
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/validateproject.php';

    // Members
    $_POST['members'] = isset($_POST['members']) ? $_POST['members'] : '';

    $oldmem = $project->getMembers($id);

    $m = array_filter(array_unique(explode("\n", $_POST['members'])));
    $newmem = [];
    $userMap = [];
    foreach ($m as $v) {
        $username = trim($v);
        $uid = $user->getId($username);
        if (is_numeric($uid) && $uid > 0) {
            $newmem[] = $uid;
            $userMap[$uid] = $username;
        } else {
            die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': Invalid member - '.$v));
        }
    }

        //members to add
        $toadd = array_diff($newmem, $oldmem);
        foreach ($toadd as $uid) {
            $ret = Db::query(
                [
                    'INSERT INTO "groups_members"("to","from") VALUES(:project,:user)',
                        [
                            ':project' => $id,
                            ':user' => $uid,
                        ],
                    ], Db::FETCH_ERRSTR);

            if ($ret != Db::NO_ERRSTR) {
                die(NERDZ\Core\Utils::jsonDbResponse($ret, $userMap[$uid]));
            }
        }

        // members to remove
        $toremove = array_diff($oldmem, $newmem);
        foreach ($toremove as $val) {
            if (Db::NO_ERRNO != Db::query(
                [
                    'DELETE FROM groups_members WHERE "to" = :project AND "from" = :user',
                    [
                        ':project' => $id,
                        ':user' => $val,
                    ],
                ], Db::FETCH_ERRNO)) {
                die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').'4'));
            }
        }

        if (Db::NO_ERRNO != Db::query(
            [
                'UPDATE "groups" SET "description" = :desc, "website" = :website, "photo" = :photo,
                "private" = :private, "open" = :open, "goal" = :goal, "visible" = :visible WHERE "counter" = :id',
                [
                    ':desc' => $projectData['description'],
                    ':website' => $projectData['website'],
                    ':photo' => $projectData['photo'],
                    ':private' => $projectData['private'],
                    ':open' => $projectData['open'],
                    ':goal' => $projectData['goal'],
                    ':visible' => $projectData['visible'],
                    ':id' => $id,
                ],
            ], Db::FETCH_ERRNO)
        ) {
            die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
        }
        break;
default:
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
    break;
}
die(NERDZ\Core\Utils::JSONResponse('ok', 'OK'));
