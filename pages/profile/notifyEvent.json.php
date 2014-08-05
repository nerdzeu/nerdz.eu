<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Notification;
use NERDZ\Core\Db;

$core = new Notification();

header("Content-Type: text/event-stream\n\n");

$push = function($event, $status, $message) use ($core) {
    echo 'event: ', $event, "\n",
         'data: ',  $core->toJsonResponse($status,$message), "\n\n";
    ob_flush();
    flush();
};

if(!$core->isLogged()) {
    $push('notification', 'error', $core->lang('REGISTER'));
    $push('pm', 'error', $core->lang('REGISTER'));
} else {
    //outside of the loop, to send first events as fast as possible
    $notification = $core->count(false,true);
    $push('notification', 'ok', $notification);

    $pm = $core->countPms();
    $push('pm', 'ok', $pm);

    sleep(5);
    $viewonline = empty($_SESSION['mark_offline']) ? '1' : '0';

    while(1) {
        $newNotifications = $core->count(false,true);
        if($newNotifications != $notification) {
            $notification = $newNotifications;
            $push('notification', 'ok', $notification);
        }

        $newPm = $core->countPms();
        if($newPm != $pm) {
            $pm = $newPm;
            $push('pm', 'ok', $pm);
        }

        $core->query(
            [
                'UPDATE "users" SET "last" = NOW(), "viewonline" = :on WHERE "counter" = :id',
                    [
                        ':on' => $viewonline,
                        ':id' => $_SESSION['id']
                    ]
            ], Db::NO_RETURN);

        if(($o = $core->query(
            [
                'SELECT "remote_addr","http_user_agent" FROM "users" WHERE "counter" = :id',
                [
                    ':id' => $_SESSION['id']
                ]
            ], Db::FETCH_OBJ)))
        {
            if(empty($o->remote_addr) || empty($_SESSION['remote_addr']) || 
                $o->remote_addr != $_SERVER['REMOTE_ADDR']) {
                $core->query(
                     [
                         'UPDATE "users" SET "remote_addr" = :addr WHERE "counter" = :id',
                         [
                             ':addr' => $_SERVER['REMOTE_ADDR'],
                             ':id'   => $_SESSION['id']
                         ]
                    ] ,Db::NO_RETURN);
                
                $_SESSION['remote_addr'] = $_SERVER['REMOTE_ADDR'];
            }
       
            if(empty($o->http_user_agent) || empty($_SESSION['http_user_agent']) || 
                $o->http_user_agent != $_SERVER['HTTP_USER_AGENT']) {
                $core->query(
                    [
                        'UPDATE "users" SET "http_user_agent" = :uag WHERE "counter" = :id',
                        [
                            ':uag' => htmlspecialchars($_SERVER['HTTP_USER_AGENT'],ENT_QUOTES,'UTF-8'),
                            ':id'  => $_SESSION['id']
                        ]
                    ], Db::NO_RETURN);

                $_SESSION['http_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            }
        }

        sleep(5);

    }//while 1

}// else
