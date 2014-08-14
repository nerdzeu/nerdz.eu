<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Notification;
use NERDZ\Core\Db;
use NERDZ\Core\Config;
use NERDZ\Core\RedisSessionHandler;
use NERDZ\Core\Utils;

$user = new Notification();

header("Content-Type: text/event-stream\n\n");

$push = function($event, $status, $message) use ($user) {
    echo 'event: ', $event, "\n",
        'data: ',  Utils::toJsonResponse($status,$message), "\n\n";
    ob_flush();
    flush();
};

$dontSendCacheLimiter = function() {
    // http://stackoverflow.com/a/12315542
    ini_set('session.use_only_cookies', false);
    ini_set('session.use_cookies', false);
    ini_set('session.use_trans_sid', false);
    ini_set('session.cache_limiter', null);
    if(Config\REDIS_ENABLED) {
        new RedisSessionHandler();
    }
    else
        session_start();
};


if(!$user->isLogged()) {
    $push('notification', 'error', $user->lang('REGISTER'));
    $push('pm', 'error', $user->lang('REGISTER'));
} else {
    //outside of the loop, to send first events as fast as possible
    $notification = $user->count(false,true);
    $push('notification', 'ok', $notification);

    $pm = $user->countPms();
    $push('pm', 'ok', $pm);

    session_write_close(); //unlock $_SESSION (other scripts can now run)

    sleep(5);
    $viewonline = empty($_SESSION['mark_offline']) ? '1' : '0';

    while(1) {
        $newNotifications = $user->count(false,true);
        if($newNotifications != $notification) {
            $notification = $newNotifications;
            $push('notification', 'ok', $notification);
        }

        $newPm = $user->countPms();
        if($newPm != $pm) {
            $pm = $newPm;
            $push('pm', 'ok', $pm);
        }

        Db::query(
            [
                'UPDATE "users" SET "last" = NOW(), "viewonline" = :on WHERE "counter" = :id',
                    [
                        ':on' => $viewonline,
                        ':id' => $_SESSION['id']
                    ]
                ], Db::NO_RETURN);

        if(($o = Db::query(
            [
                'SELECT "remote_addr","http_user_agent" FROM "users" WHERE "counter" = :id',
                [
                    ':id' => $_SESSION['id']
                ]
            ], Db::FETCH_OBJ)))
        {
            if(empty($o->remote_addr) || empty($_SESSION['remote_addr']) || 
                $o->remote_addr != $_SERVER['REMOTE_ADDR']) {
                Db::query(
                    [
                        'UPDATE "users" SET "remote_addr" = :addr WHERE "counter" = :id',
                        [
                            ':addr' => $_SERVER['REMOTE_ADDR'],
                            ':id'   => $_SESSION['id']
                        ]
                    ] ,Db::NO_RETURN);

                $dontSendCacheLimiter();
                $_SESSION['remote_addr'] = $_SERVER['REMOTE_ADDR'];
                session_write_close();
            }

            if(empty($o->http_user_agent) || empty($_SESSION['http_user_agent']) || 
                $o->http_user_agent != $_SERVER['HTTP_USER_AGENT']) {
                Db::query(
                    [
                        'UPDATE "users" SET "http_user_agent" = :uag WHERE "counter" = :id',
                        [
                            ':uag' => htmlspecialchars($_SERVER['HTTP_USER_AGENT'],ENT_QUOTES,'UTF-8'),
                                ':id'  => $_SESSION['id']
                            ]
                        ], Db::NO_RETURN);

                $dontSendCacheLimiter();
                $_SESSION['http_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                session_write_close();
            }
        }

        sleep(5);

    }//while 1

}// else
