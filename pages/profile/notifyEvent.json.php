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
require_once $_SERVER['DOCUMENT_ROOT'].'/class/Autoload.class.php';
use NERDZ\Core\Notification;
use NERDZ\Core\Db;
use NERDZ\Core\Config;
use NERDZ\Core\RedisSessionHandler;
use NERDZ\Core\Utils;
use NERDZ\Core\IpUtils;

$user = new Notification();

header("Content-Type: text/event-stream\n\n");

$push = function ($event, $status, $message) use ($user) {
    echo 'event: ', $event, "\n",
        'data: ',  Utils::toJsonResponse($status, $message), "\n\n";
    ob_flush();
    flush();
};

$dontSendCacheLimiter = function () {
    // http://stackoverflow.com/a/12315542
    ini_set('session.use_only_cookies', false);
    ini_set('session.use_cookies', false);
    ini_set('session.use_trans_sid', false);
    ini_set('session.cache_limiter', null);

    if (Config\REDIS_HOST !== '' && Config\REDIS_PORT !== '') {
        new RedisSessionHandler(Config\REDIS_HOST, Config\REDIS_PORT);
    } else {
        session_start();
    }
};

if (!$user->isLogged()) {
    $push('notification', 'error', $user->lang('REGISTER'));
    $push('pm', 'error', $user->lang('REGISTER'));
} else {
    //outside of the loop, to send first events as fast as possible
    $notification = $user->count(false, true);
    $push('notification', 'ok', $notification);

    $pm = $user->countPms();
    $push('pm', 'ok', $pm);

    session_write_close(); //unlock $_SESSION (other scripts can now run)

    sleep(5);
    $viewonline = empty($_SESSION['mark_offline']) ? '1' : '0';

    while (1) {
        $newNotifications = $user->count(false, true);
        if ($newNotifications != $notification) {
            $notification = $newNotifications;
            $push('notification', 'ok', $notification);
        }

        $newPm = $user->countPms();
        if ($newPm != $pm) {
            $pm = $newPm;
            $push('pm', 'ok', $pm);
        }

        Db::query(
            [
                'UPDATE "users" SET "last" = NOW(), "viewonline" = :on WHERE "counter" = :id',
                    [
                        ':on' => $viewonline,
                        ':id' => $_SESSION['id'],
                    ],
                ], Db::NO_RETURN);

        if (($o = Db::query(
            [
                'SELECT "remote_addr","http_user_agent" FROM "users" WHERE "counter" = :id',
                [
                    ':id' => $_SESSION['id'],
                ],
            ], Db::FETCH_OBJ))) {
            if (empty($o->remote_addr) || empty($_SESSION['remote_addr']) ||
                $o->remote_addr != IpUtils::getIp()) {
                Db::query(
                    [
                        'UPDATE "users" SET "remote_addr" = :addr WHERE "counter" = :id',
                        [
                            ':addr' => IpUtils::getIp(),
                            ':id' => $_SESSION['id'],
                        ],
                    ], Db::NO_RETURN);

                $dontSendCacheLimiter();
                $_SESSION['remote_addr'] = IpUtils::getIp();
                session_write_close();
            }

            if (empty($o->http_user_agent) || empty($_SESSION['http_user_agent']) ||
                $o->http_user_agent != $_SERVER['HTTP_USER_AGENT']) {
                Db::query(
                    [
                        'UPDATE "users" SET "http_user_agent" = :uag WHERE "counter" = :id',
                        [
                            ':uag' => htmlspecialchars($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8'),
                                ':id' => $_SESSION['id'],
                            ],
                        ], Db::NO_RETURN);

                $dontSendCacheLimiter();
                $_SESSION['http_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                session_write_close();
            }
        }

        sleep(5);
    }//while 1
}// else
