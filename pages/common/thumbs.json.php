<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\Db;
use NERDZ\Core\User;

$user = new User();

if(isset($_POST['comment'])) {
    $message = new NERDZ\Core\Comments();
    if(!isset($_POST['hcid']) || !is_numeric($_POST['hcid'])) 
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': no hcid'));
    $id = $_POST['hcid'];
}
else {
    $message = new NERDZ\Core\Messages();
    if(!isset($_POST['hpid']) || !is_numeric($_POST['hpid'])) 
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': no hpid'));
    $id = $_POST['hpid'];
}

if (!$user->isLogged()) {
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('REGISTER')));
}

if (isset($_POST['thumb']) && is_numeric($_POST['thumb'])) {   
    $thumb = (int) $_POST['thumb'];

    $dbResponse = $message->setThumbs($id, $thumb, isset($prj));
    if($dbResponse != Db::NO_ERRSTR)
        die(NERDZ\Core\Utils::jsonDbResponse($dbResponse));

}
else {
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': 3'));
}

die(NERDZ\Core\Utils::jsonResponse('thumbs', $message->getThumbs($id, isset($prj))));

?>
