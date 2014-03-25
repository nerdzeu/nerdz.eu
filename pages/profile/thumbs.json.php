<?php
ob_start('ob_gzhandler');

if(isset($_POST['comment'])) {
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/comments.class.php';
    $core = new comments();
    if(!isset($_POST['hcid']) || !is_numeric($_POST['hcid'])) 
        die($core->jsonResponse('error',$core->lang('ERROR').': no hcid'));
    $id = $_POST['hcid'];
}
else
{
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/messages.class.php';
    $core = new messages();
    if(!isset($_POST['hpid']) || !is_numeric($_POST['hpid'])) 
        die($core->jsonResponse('error',$core->lang('ERROR').': no hpid'));
    $id = $_POST['hpid'];
}

if (!$core->isLogged()) {
    die($core->jsonResponse('error',$core->lang('REGISTER')));
}

if ( isset($_POST['thumb']) && is_numeric($_POST['thumb']) ) {    
    $thumb = (int) $_POST['thumb'];

    if ($thumb > 1 || $thumb < -1) {
        die($core->jsonResponse('error',$core->lang('ERROR').': 1'));
    }

    if (!$core->setThumbs($id, $thumb)) {
        die($core->jsonResponse('error',$core->lang('ERROR').': 2'));
    }
}   
else {
    die($core->jsonResponse('error',$core->lang('ERROR').': 3'));
}


die($core->jsonResponse('thumbs', $core->getThumbs($id)));

?>
