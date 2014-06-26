<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/notify.class.php';
$core = new notify();

header("Content-Type: text/event-stream\n\n");

if(!$core->isLogged()) {
    echo "event: notification\n",
         "data: ", $core->toJsonResponse('error',$core->lang('REGISTER')), "\n\n";

    echo "event: pm\n",
        "data: ", $core->toJsonResponse('error',$core->lang('REGISTER')), "\n\n";
    die();
}

$fistCicle = true;

while(1) {
    //notifications
    echo "event: notification\n",
        "data: ", $core->toJsonResponse('ok',$core->count(false,true)), "\n\n";
    ob_flush();
    flush();

    // on page load, show as fast as possible both notification counters
    if(!$fistCicle) {
        sleep(1);
    } else{
        $fistCicle = false;
    }

    //pm notifications
    echo "event: pm\n",
        "data: ", $core->toJsonResponse('ok',$core->countPms()), "\n\n";
    ob_flush();
    flush();
    sleep(5);
    // set user online
    $viewonline = empty($_SESSION['nerdz_mark_offline']) ? '1' : '0';

    $core->query(
        [
            'UPDATE "users" SET "last" = NOW(), "viewonline" = :on WHERE "counter" = :id',
                [
                    ':on' => $viewonline,
                    ':id' => $_SESSION['nerdz_id']
                ]
        ], db::NO_RETURN);

    if(($o = $core->query(
        [
            'SELECT "remote_addr","http_user_agent" FROM "users" WHERE "counter" = :id',
            [
                ':id' => $_SESSION['nerdz_id']
            ]
        ], db::FETCH_OBJ)))
    {
        if(empty($o->remote_addr) || empty($_SESSION['nerdz_remote_addr']) || 
            $o->remote_addr != $_SERVER['REMOTE_ADDR']) {
            $core->query(
                 [
                     'UPDATE "users" SET "remote_addr" = :addr WHERE "counter" = :id',
                     [
                         ':addr' => $_SERVER['REMOTE_ADDR'],
                         ':id'   => $_SESSION['nerdz_id']
                     ]
                ] ,db::NO_RETURN);
            
            $_SESSION['nerdz_remote_addr'] = $_SERVER['REMOTE_ADDR'];
        }
   
        if(empty($o->http_user_agent) || empty($_SESSION['nerdz_http_user_agent']) || 
            $o->http_user_agent != $_SERVER['HTTP_USER_AGENT']) {
            $core->query(
                [
                    'UPDATE "users" SET "http_user_agent" = :uag WHERE "counter" = :id',
                    [
                        ':uag' => htmlspecialchars($_SERVER['HTTP_USER_AGENT'],ENT_QUOTES,'UTF-8'),
                        ':id'  => $_SESSION['nerdz_id']
                    ]
                ], db::NO_RETURN);

            $_SESSION['nerdz_http_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        }
    }

}//while 1
