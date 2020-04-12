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
use NERDZ\Core\Messages;
use NERDZ\Core\Utils;
use NERDZ\Core\User;

$user = new User();
$messages = new Messages();

if (!$user->isLogged()) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('REGISTER')));
}

if (!NERDZ\Core\Security::refererControl()) {
    die(NERDZ\Core\Utils::JSONResponse('error', 'No SPAM/BOT'));
}

$url = empty($_POST['url'])     ? false : trim($_POST['url']);
$comment = empty($_POST['comment']) ? false : trim($_POST['comment']);
$to = empty($_POST['to'])      ? false : trim($_POST['to']);

if (!$url || !Utils::isValidURL($url)) {
    die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('INVALID_URL')));
}

if ($to) {
    if (!User::getUsername($to)) {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('USER_NOT_FOUND')));
    }
} else {
    $to = $_SESSION['id'];
}

if ($_SESSION['id'] != $to) {
    if ($user->hasClosedProfile($to)) {
        die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('CLOSED_PROFILE_DESCR')));
    }
}

$share = function ($to, $url, $message = null) use ($user, $messages) {
    if (!preg_match('#(^http:\/\/|^https:\/\/|^ftp:\/\/)#i', $url)) {
        $url = "http://{$url}";
    }

    if (preg_match('#(.*)youtube.com\/watch\?v=(.{11})#Usim', $url) || preg_match('#http:\/\/youtu.be\/(.{11})#Usim', $url)) {
        $message = "[youtube]{$url}[/youtube] ".$message;

        return $messages->add($to, $message);
    }

    if (preg_match('#http://sprunge.us/([a-z0-9\.]+)\?(.+?)#i', $url, $res)) {
        $file = file_get_contents('http://sprunge.us/'.$res[1]);
        $message = "[code={$res[2]}]{$file}[/code]".$message;

        return $messages->add($to, $message);
    }

    $h = @get_headers($url, Db::FETCH_OBJ);
    if (false === $h) {
        return false;
    }

    foreach ((array) $h['Content-Type'] as $ct) {
        if (preg_match('#(image)#i', $ct)) {
            $message = "[img]{$url}[/img]".$message;

            return $messages->add($to, $message);
        }

        if (preg_match('#(htm)#i', $ct)) {
            $file = file_get_contents($url);
            $arr = explode('<img src="', $file);
            $flag = false;
            if (!empty($arr[0])) {
                foreach ($arr as $val) {
                    $img = trim(strstr($val, '"', true));
                    $img = str_replace('"', '', $img);
                    if (filter_var($img, FILTER_VALIDATE_URL)) {
                        $flag = true;
                        break;
                    }
                }
            }
            $message = $flag ? "[url={$url}][img]{$img}[/img][/url]".$message : "[url]{$url}[/url] ".$message;

            return $messages->add($to, $message);
        }
    }
};

if ($share($to, $url, $comment)) {
    die(NERDZ\Core\Utils::JSONResponse('ok', 'OK'));
}

die(NERDZ\Core\Utils::JSONResponse('error', $user->lang('ERROR')));
