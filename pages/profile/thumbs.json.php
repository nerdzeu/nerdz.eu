<?php
ob_start('ob_gzhandler');

if(isset($_POST['comment'])) {
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/comments.class.php';
    $core = new Comments();
    if(!isset($_POST['hcid']) || !is_numeric($_POST['hcid'])) 
        die($core->jsonResponse('error',$core->lang('ERROR').': no hcid'));
    $id = $_POST['hcid'];
}
else
{
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/Messages.class.php';
    $core = new Messages();
    if(!isset($_POST['hpid']) || !is_numeric($_POST['hpid'])) 
        die($core->jsonResponse('error',$core->lang('ERROR').': no hpid'));
    $id = $_POST['hpid'];
}

if (!$core->isLogged()) {
    die($core->jsonResponse('error',$core->lang('REGISTER')));
}

if ( isset($_POST['thumb']) && is_numeric($_POST['thumb']) ) {
    if(!$core->setThumbs($id, $_POST['thumb'])) {
        die($core->jsonResponse('error',$core->lang('ERROR').': 2'));
    }
}   
else {
    die($core->jsonResponse('error',$core->lang('ERROR').': 3'));
}

die($core->jsonResponse('thumbs', $core->getThumbs($id)));
?>
