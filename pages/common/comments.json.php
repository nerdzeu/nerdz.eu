<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
$user = new NERDZ\Core\User;
$comments = new NERDZ\Core\Comments;

if(!$user->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('REGISTER')));

if(!$user->refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error','CSRF'));

$prj = isset($prj);

switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
    case 'add':
        $hpid  = isset($_POST['hpid']) && is_numeric($_POST['hpid']) ? $_POST['hpid']  : false;

        if(!$hpid)
            die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));

        die(NERDZ\Core\Utils::jsonDbResponse($comments->add($hpid,$_POST['message'], $prj)));
    break;
    
    case 'del':
        $hcid = isset($_POST['hcid']) && is_numeric($_POST['hcid']) ? $_POST['hcid'] : false;
        
        if(!$hcid || !$comments->delete($hcid, $prj))
            die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
    break;

    case 'get':
        if(empty($_POST['hcid']) || !($o = $comments->getMessage($_POST['hcid'], $prj)))
            die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
        die($o->message);
    break;

    case 'edit':
        if(empty($_POST['hcid']) || empty($_POST['message']) || !$comments->editComment($_POST['hcid'], $_POST['message'], $prj))
            die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
    break;
default:
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
break;
}
die(NERDZ\Core\Utils::jsonResponse('ok','OK'));
?>
