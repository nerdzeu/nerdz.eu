<?php

ob_start('ob_gzhandler');

require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\User;
use NERDZ\Core\Client\Pushed;

function jsonResponse($object) {
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode($object, JSON_UNESCAPED_UNICODE));
}

$core = new User();
try {
    
    if(!$core->isLogged()) {
        jsonResponse(['ERROR' => 'Not logged']);
    }

    if(!isset($_GET['action'])) {
        jsonResponse(['ERROR' => 'Action not set']);
    }

    $thisUser = $core->getUserId();

    if(!$core->floodPushRegControl($thisUser)) {
        die('NO SPAM');
    }

    $pushed = Pushed::connectIp(PUSHED_PORT,PUSHED_IP6);

    $resp = [];

    switch($_GET['action']) {    

        case 'subscribe':
            if (!isset($_POST['service']) || !isset($_POST['deviceId'])) {
                jsonResponse(['ERROR' => 'Field not set']);
            }

            $core->setPush($thisUser, true);
                 
            if(!$pushed->exists($thisUser)){
                if($pushed->addUser($thisUser)[0] !== Pushed::$ACCEPTED) {
                    jsonResponse(['ERROR' => 'Request rejected']);
                }
            }

            if($pushed->subscribe($thisUser, $_POST['service'], $_POST['deviceId'])[0] !== Pushed::$ACCEPTED) {
                jsonResponse(['ERROR' => 'Request rejected']);
            }

            $resp = ['ACCEPTED' => 'Ok'];

            break;

        case 'unsubscribe': {

            if (!isset($_POST['service']) || !isset($_POST['deviceId'])) {
                jsonResponse(['ERROR' => 'Field not set']);
            }

            $core->setPush($thisUser, true);
                 
            if(!$pushed->exists($thisUser)){
                jsonResponse(['ERROR' => 'No push for this user']);
            }

            if($pushed->unsubscribe($thisUser, $_POST['service'], $_POST['deviceId'])[0] !== Pushed::$ACCEPTED) {
                jsonResponse(['ERROR' => 'Request rejected']);
            }

            $resp = ['ACCEPTED' => 'Ok'];

            break;
        }

        default: {
            jsonResponse(['ERROR' => "Unknown request: '".$_GET['action']."'"]);
        }        

    }
} catch (PushedException $e) {
    $resp = ['ERROR' => 'Internal Server Error'];
}

jsonResponse($resp);

?>
 
