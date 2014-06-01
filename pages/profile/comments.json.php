<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/comments.class.php';
$core = new comments();

if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));

if(!$core->refererControl())
    die($core->jsonResponse('error','CSRF'));

switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
    case 'add':
        $hpid  = isset($_POST['hpid']) && is_numeric($_POST['hpid']) ? $_POST['hpid']  : false;

        if(!$hpid)
            die($core->jsonResponse('error',$core->lang('ERROR')));

        die($core->jsonDbResponse($core->addComment($hpid,$_POST['message'])));
    break;
    
    case 'del':
        $hcid = isset($_POST['hcid']) && is_numeric($_POST['hcid']) ? $_POST['hcid'] : false;
        
        if(!$hcid || !$core->delComment($hcid))
            die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
default:
    die($core->jsonResponse('error',$core->lang('ERROR')));
break;
}
die($core->jsonResponse('ok','OK'));
?>
