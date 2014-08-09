<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

if(isset($_POST['comment'])) {
    $core = new NERDZ\Core\Comments();
    if(!isset($_POST['hcid']) || !is_numeric($_POST['hcid'])) 
        die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR').': no hcid'));
    $id = $_POST['hcid'];
}
else {
    $core = new NERDZ\Core\Messages();
    if(!isset($_POST['hpid']) || !is_numeric($_POST['hpid'])) 
        die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR').': no hpid'));
    $id = $_POST['hpid'];
}

if (!$core->isLogged()) {
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('REGISTER')));
}

if (isset($_POST['thumb']) && is_numeric($_POST['thumb'])) {   
    $thumb = (int) $_POST['thumb'];

    if (!$core->setThumbs($id, $thumb, isset($prj))) {
        die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR').': 2'));
    }
}
else {
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR').': 3'));
}

die(NERDZ\Core\Utils::jsonResponse('thumbs', $core->getThumbs($id, isset($prj))));

?>
