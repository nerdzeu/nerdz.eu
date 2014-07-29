<?php
ob_start('ob_gzhandler');

if(isset($_POST['comment'])) {
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/comments.class.php';
    $core = new Comments();
    if(!isset($_POST['hcid']) || !is_numeric($_POST['hcid'])) 
        die($core->jsonResponse('error',$core->lang('ERROR').': no hcid'));
    $id = $_POST['hcid'];
}
else {
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/Messages.class.php';
    $core = new Messages();
    if(!isset($_POST['hpid']) || !is_numeric($_POST['hpid'])) 
        die($core->jsonResponse('error',$core->lang('ERROR').': no hpid'));
    $id = $_POST['hpid'];
}

$revNo = isset($_POST['revNo']) && is_numeric($_POST['revNo']) && $_POST['revNo'] >= 1 ? $_POST['revNo'] : 0;

if(!$revNo)
    die($core->jsonResponse('error',$core->lang('ERROR').': invalid revNo'));

if (!$core->isLogged()) {
    die($core->jsonResponse('error',$core->lang('REGISTER')));
}

$rev = $core->getRevision($id, $revNo, true);

die(is_object($rev) ?
    $core->jsonResponse(
    [
        'datetime' => $core->getDateTime($rev->time),
        'message'  => $core->bbcode($rev->message)
    ]) :
   $core->jsonResponse('error',$core->lang('ERROR')));
?>
