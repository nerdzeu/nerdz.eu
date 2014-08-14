<?php
ob_start('ob_gzhandler');

require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\FastFetch;
use NERDZ\Core\FFException;
use NERDZ\Core\FFErrCode;

function jsonResponse($object) {
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode($object, JSON_UNESCAPED_UNICODE));
}

function bakeError(FFException $exception) {   
    $code = $exception->code;
    return ['error' => $code];
}

$response = NULL;

$ff = new FastFetch();
try {   
    if(!$ff->isLogged()) {
        throw new FFException(FFErrCode::NOT_LOGGED);
    }

    if(!isset($_GET['action'])) {
        throw new FFException(FFErrCode::NO_ACTION);
    }

    switch($_GET['action']) {

    case 'conversations': 
        $response = $ff->fetchConversations();
        break;

    case 'messages': {

        if (!isset($_GET['otherid']) || !is_numeric($_GET['otherid'])) {
            throw new FFException(FFErrCode::NO_OTHER_ID);
            }

            $start = 0;
            $limit = 10;

            if (isset($_GET['start']) ^ isset($_GET['limit'])) {
                throw new FFException(FFErrCode::WRONG_REQUEST);
            }

            if (isset($_GET['start']) && isset($_GET['limit'])) {
                $start = $_GET['start'];
                $limit = $_GET['limit'];
                if(!is_numeric($start) || !is_numeric($limit)) {
                    throw new FFException(FFErrCode::WRONG_REQUEST);
                }
            }

            $response = $ff->fetchMessages($_GET['otherid'], $start, $limit);
            break;
        }

case 'getid': {
    if(!isset($_GET['username'])) {
        throw new FFException(FFErrCode::WRONG_REQUEST);
            }

            $response = $ff->getIdFromUsername($_GET['username']);           
            break;
        }

default:
    throw new FFException(FFErrCode::INVALID_ACTION);
    }
} catch (FFException $e) {
    $response = bakeError($e);
}

jsonResponse($response);

?>

