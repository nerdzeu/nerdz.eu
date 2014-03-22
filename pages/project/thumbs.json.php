<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/messages.class.php';
$core = new messages();

if (!$core->isLogged()) {
    die($core->jsonResponse('error',$core->lang('REGISTER')));
}

if (isset($_POST['hpid']) && is_numeric($_POST['hpid'])) {
    
    if (isset($_POST['thumb'])){
        if(is_numeric($_POST['thumb'])) {
            $thumb = (int) $_POST['thumb'];

            if ($thumb > 1 || $thumb < -1) {
                die($core->jsonResponse('error1',$core->lang('ERROR')));
            }

            if (!$core->setThumbs($_POST['hpid'], $thumb, true)) {
                die($core->jsonResponse('error2',$core->lang('ERROR')));
            }
        } else {
            die($core->jsonResponse('error3',$core->lang('ERROR')));
        }
    }

    die($core->jsonResponse('thumbs', $core->getThumbs($_POST['hpid'], true)));

} else {
    die($core->jsonResponse('error4',$core->lang('ERROR')));
}

?>
