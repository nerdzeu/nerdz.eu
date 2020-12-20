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

require_once $_SERVER['DOCUMENT_ROOT'].'/class/Autoload.class.php';
use NERDZ\Core\Db;
use NERDZ\Core\Utils;
use NERDZ\Core\User;

$user = new User();

if (!NERDZ\Core\Security::refererControl()) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': referer'));
}

if (!NERDZ\Core\Security::csrfControl(isset($_POST['tok']) ? $_POST['tok'] : 0)) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': token'));
}

if (!$user->isLogged()) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('REGISTER')));
}

$userData['biography'] = isset($_POST['biography'])  ? trim($_POST['biography'])               : '';
$userData['quotes'] = isset($_POST['quotes'])     ? trim($_POST['quotes'])                     : '';
$userData['website'] = isset($_POST['website'])    ? strip_tags(trim($_POST['website']))       : '';
$userData['jabber'] = isset($_POST['jabber'])     ? trim($_POST['jabber'])                     : '';
$userData['telegram'] = isset($_POST['telegram'])      ? trim($_POST['telegram'])                       : '';
$userData['facebook'] = isset($_POST['facebook'])   ? trim($_POST['facebook'])                 : '';
$userData['twitter'] = isset($_POST['twitter'])    ? trim($_POST['twitter'])                   : '';
$userData['steam'] = isset($_POST['steam'])      ? trim($_POST['steam'])                       : '';
$userData['skype'] = isset($_POST['skype'])      ? trim($_POST['skype'])                       : '';
$userData['github'] = isset($_POST['github'])     ? trim($_POST['github'])                     : '';
$userData['userscript'] = isset($_POST['userscript']) ? strip_tags(trim($_POST['userscript'])) : '';
$userData['dateformat'] = isset($_POST['dateformat']) ? trim($_POST['dateformat'])             : '';

foreach ($userData as $key => $val) {
    $userData[$key] = trim(htmlspecialchars($val, ENT_QUOTES, 'UTF-8'));
}

$closed = isset($_POST['closed']);
$flag = true;

if (!empty($userData['website']) && !Utils::isValidURL($userData['website'])) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('WEBSITE').': '.$user->lang('INVALID_URL')));
}

if (!empty($userData['userscript']) && !Utils::isValidURL($userData['userscript'])) {
    die(NERDZ\Core\Utils::JSONResponse('error', 'Userscript: '.$user->lang('INVALID_URL')));
}

if (!empty($userData['github']) && !preg_match('#^https?://(www\.)?github\.com/[a-z0-9]+$#i', $userData['github'])) {
    die(NERDZ\Core\Utils::JSONResponse('error', 'GitHub: '.$user->lang('INVALID_URL')));
}

if (false == ($obj = $user->getObject($_SESSION['id']))) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
}

if (!empty($userData['jabber']) && (false == filter_var($userData['jabber'], FILTER_VALIDATE_EMAIL))) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('JABBER').': '.$user->lang('MAIL_NOT_VALID')));
}

if (empty($userData['dateformat'])) {
    $userData['dateformat'] = 'd/m/Y, H:i';
}

if (!empty($userData['facebook']) &&
    (
        !preg_match('#^https?://(([a-z]{2}\-[a-z]{2})|www)\.facebook\.com/people/[^/]+/([a-z0-9_\-]+)#i', $userData['facebook']) &&
    !preg_match('#^https?://(([a-z]{2}\-[a-z]{2})|www)\.facebook\.com/profile\.php\?id\=([0-9]+)#i', $userData['facebook']) &&
    !preg_match('#^https?://(([a-z]{2}\-[a-z]{2})|www)\.facebook\.com/([a-z0-9_\-\.]+)#i', $userData['facebook'])
    )
  ) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': Facebook URL'));
}

if (!empty($userData['twitter']) && !preg_match('#^https?://twitter.com/([a-z0-9_]+)#i', $userData['twitter'])) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': Twitter URL'));
}

if (!empty($userData['steam']) && strlen($userData['steam']) > 35) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': Steam'));
}

foreach ($user as &$value) {
    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$par = [
    ':biography' => $userData['biography'],
    ':quotes' => $userData['quotes'],
    ':website' => $userData['website'],
    ':dateformat' => $userData['dateformat'],
    ':github' => $userData['github'],
    ':jabber' => $userData['jabber'],
    ':telegram' => $userData['telegram'],
    ':userscript' => $userData['userscript'],
    ':facebook' => $userData['facebook'],
    ':twitter' => $userData['twitter'],
    ':steam' => $userData['steam'],
    ':skype' => $userData['skype'],
    ':counter' => $obj->counter,
];

if (
    Db::NO_ERRNO != Db::query(
        [
            'UPDATE profiles SET 
            "biography"   = :biography,
            "quotes"      = :quotes,
            "website"     = :website,
            "dateformat"  = :dateformat,
            "github"      = :github,
            "jabber"      = :jabber,
            "telegram"    = :telegram,
            "userscript"  = :userscript,
            "facebook"    = :facebook,
            "twitter"     = :twitter,
            "steam"       = :steam,
            "skype"       = :skype
            WHERE "counter" = :counter',
            $par,
        ],
        Db::FETCH_ERRNO
    )
    ) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
}

if ($closed) {
    if (!$user->hasClosedProfile($_SESSION['id'])) {
        if (Db::NO_ERRNO != Db::query(
            [
                'UPDATE "profiles" SET "closed" = :closed WHERE "counter" = :counter',
                [
                    ':closed' => 'true',
                    ':counter' => $_SESSION['id'],
                ],
            ],
            Db::FETCH_ERRNO
        )
        ) {
            die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
        }
    }
} else {
    if (Db::NO_ERRNO != Db::query(
        [
            'UPDATE "profiles" SET "closed" = :closed WHERE "counter" = :counter',
            [
                ':closed' => 'false',
                ':counter' => $_SESSION['id'],
            ],
        ],
        Db::FETCH_ERRNO
    )) {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
    }
}

$_SESSION['dateformat'] = $userData['dateformat'];

$userData['interests'] = isset($_POST['interests'])     ? trim($_POST['interests'])            : '';
if (isset($userData["interests"])) {
    $old = $user->getInterests($_SESSION['id']);
    $new = [];
    foreach (explode("\n", $userData["interests"]) as $val) {
        $value = htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
        if (!empty($value)) {
            $new[] = $value;
        }
    }

    $removed = array_diff($old, $new);
    $added = array_diff($new, $old);

    foreach ($removed as $interest) {
        $user->deleteInterest($interest);
    }
    foreach ($added as $interest) {
        $user->addInterest($interest);
    }
}

if (isset($_POST['whitelist'])) {
    $oldlist = $user->getWhitelist($_SESSION['id']);

    $m = array_filter(array_unique(explode("\n", $_POST['whitelist'])));
    $newlist = [];
    foreach ($m as $v) {
        $uid = $user->getId(trim($v));
        if (is_numeric($uid) && $uid > 0) {
            if (Db::NO_ERRNO != Db::query(
                [
                    'INSERT INTO "whitelist"("from","to")
                    SELECT :id, :uid
                    WHERE NOT EXISTS (SELECT 1 FROM "whitelist" WHERE "from" = :id AND "to" = :uid)',
                        [
                            ':id' => $_SESSION['id'],
                            ':uid' => $uid,
                        ],
                    ],
                Db::FETCH_ERRNO
            )) {
                die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').'1'));
            }
            $newlist[] = $uid;
        } else {
            die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').': Invalid user - '.$v));
        }
    }
    $toremove = [];
    foreach ($oldlist as $val) {
        if (!in_array($val, $newlist)) {
            $toremove[] = $val;
        }
    }

    foreach ($toremove as $val) {
        if (Db::NO_ERRNO != Db::query(
            [
                'DELETE FROM "whitelist" WHERE "from" = :id AND "to" = :val',
                [
                    ':id' => $_SESSION['id'],
                    ':val' => $val,
                ],
            ],
            Db::FETCH_ERRNO
        )) {
            die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR').'4'));
        }
    }
}

die(NERDZ\Core\Utils::JSONResponse('ok', 'OK'));
