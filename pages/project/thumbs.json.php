<?php
ob_start('ob_gzhandler');

if(isset($_POST['comment']) && $_POST['comment']) {
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/comments.class.php';
    $core = new comments();
    if(!isset($_POST['hcid']) || !is_numeric($_POST['hcid'])) 
        die($core->jsonResponse('error_no_hcid',$core->lang('ERROR')));
    $id = $_POST['hcid'];
}
else {
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/messages.class.php';
    $core = new messages();
    if(!isset($_POST['hpid']) || !is_numeric($_POST['hpid'])) 
        die($core->jsonResponse('error_no_hpid',$core->lang('ERROR')));
    $id = $_POST['hpid'];
}

if (!$core->isLogged()) {
    die($core->jsonResponse('error',$core->lang('REGISTER')));
}

if (isset($_POST['thumb']) && is_numeric($_POST['thumb'])) {   
    $thumb = (int) $_POST['thumb'];

    if ($thumb > 1 || $thumb < -1) {
        die($core->jsonResponse('error1',$core->lang('ERROR')));
    }

    if (!$core->setThumbs($id, $thumb, true)) {
        die($core->jsonResponse('coddio',$core->lang('ERROR')));
    }
}
else {
    die($core->jsonResponse('error3',$core->lang('ERROR')));
}

die($core->jsonResponse('thumbs', $core->getThumbs($id, true)));

?>
