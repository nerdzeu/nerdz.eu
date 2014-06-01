<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';
$core = new project();

if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));

if(!$core->refererControl())
    die($core->jsonResponse('error','CSRF'));
    
switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
    case 'add':
        
        if(empty($_POST['to']))
            die($core->jsonResponse('error',$core->lang('ERROR').'a'));

        die($core->jsonDbResponse(
            $core->addProjectMessage(
                $_POST['to'],
                isset($_POST['message']) ? $_POST['message'] : '',
                isset($_POST['news'])
            )
        ));
    break;
    
    case 'del':
        if(!isset($_SESSION['nerdz_delpost']) || empty($_POST['hpid']) || ($_SESSION['nerdz_delpost'] != $_POST['hpid']) || !$core->deleteProjectMessage($_POST['hpid']) )
            die($core->jsonResponse('error',$core->lang('ERROR')));
        unset($_SESSION['nerdz_delpost']);
    break;

    case 'delconfirm':
        $_SESSION['nerdz_delpost'] = isset($_POST['hpid']) ? $_POST['hpid'] : -1;
        die($core->jsonResponse('ok',$core->lang('ARE_YOU_SURE')));
    break;
    
    case 'get':
        if(
            empty($_POST['hpid']) ||
            !($o = $core->getProjectMessage($_POST['hpid'],$edit = true))
          )
            die($core->jsonResponse('error',$core->lang('ERROR').'2'));
    break;
    
    case 'edit':
        if(    empty($_POST['hpid']) || empty($_POST['message']) || !$core->editProjectMessage($_POST['hpid'],$_POST['message']) )
            die($core->jsonResponse('error',$core->lang('ERROR')));
    break;

    default:
        die($core->jsonResponse('error',$core->lang('ERROR').'aa'));
    break;
}
die($core->jsonResponse('ok', isset($o) ? $o->message : 'OK'));
?>
