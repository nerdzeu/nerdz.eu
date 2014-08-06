<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
$core = new NERDZ\Core\Comments();

if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));

if(!$core->refererControl())
    die($core->jsonResponse('error','CSRF'));

$prj = !empty($prj);

switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
    case 'add':
        $hpid  = isset($_POST['hpid']) && is_numeric($_POST['hpid']) ? $_POST['hpid']  : false;

        if(!$hpid)
            die($core->jsonResponse('error',$core->lang('ERROR')));

        die($core->jsonDbResponse($core->addComment($hpid,$_POST['message']), $prj));
    break;
    
    case 'del':
        $hcid = isset($_POST['hcid']) && is_numeric($_POST['hcid']) ? $_POST['hcid'] : false;
        
        if(!$hcid || !$core->delComment($hcid, $prj))
            die($core->jsonResponse('error',$core->lang('ERROR')));
    break;

    case 'get':
        if(empty($_POST['hcid']) || !($o = $core->getComment($_POST['hcid'], $prj)))
            die($core->jsonResponse('error',$core->lang('ERROR')));
        die($o->message);
    break;

    case 'edit':
        if(empty($_POST['hcid']) || empty($_POST['message']) || !$core->editComment($_POST['hcid'], $_POST['message'], $prj))
            die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
default:
    die($core->jsonResponse('error',$core->lang('ERROR')));
break;
}
die($core->jsonResponse('ok','OK'));
?>
