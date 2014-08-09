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

$revNo = isset($_POST['revNo']) && is_numeric($_POST['revNo']) && $_POST['revNo'] >= 1 ? $_POST['revNo'] : 0;

if(!$revNo)
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR').': invalid revNo'));

if (!$core->isLogged()) {
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('REGISTER')));
}

$rev = $core->getRevision($id, $revNo, isset($prj));

die(is_object($rev) ?
    NERDZ\Core\Utils::jsonResponse(
    [
        'datetime' => $core->getDateTime($rev->time),
        'message'  => $core->bbcode($rev->message)
    ]) :
   NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));
?>
