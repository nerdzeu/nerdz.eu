<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Messages;

$core = new Messages();
$prj = isset($prj);

if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));

if(!$core->refererControl())
    die($core->jsonResponse('error','CSRF'));
    
switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
    case 'add':
        
        if(empty($_POST['to']))
        {
            if($prj)
                die($core->jsonResponse('error',$core->lang('ERROR').'a'));
            else
                $_POST['to'] = $_SESSION['id'];
        }

        die($core->jsonDbResponse(
            $core->addMessage(
                $_POST['to'],
                isset($_POST['message']) ? $_POST['message'] : '',
                [
                    'news' => isset($_POST['news']),
                    'project' => $prj
                ]))
            );
    break;
    
    case 'del':

        if(!isset($_SESSION['delpost']) || empty($_POST['hpid']) || ($_SESSION['delpost'] != $_POST['hpid']) || !$core->deleteMessage($_POST['hpid'], $prj))
            die($core->jsonResponse('error',$core->lang('ERROR')));
        unset($_SESSION['delpost']);
    break;

    case 'delconfirm':

        $_SESSION['delpost'] = isset($_POST['hpid']) ? $_POST['hpid'] : -1;
        die($core->jsonResponse('ok',$core->lang('ARE_YOU_SURE')));
    break;
    
    case 'get':

        if(
            empty($_POST['hpid']) ||
            !($o = $core->getMessage($_POST['hpid'], $prj))
          )
            die($core->jsonResponse('error',$core->lang('ERROR').'2'));
        die($core->jsonResponse('ok', $o->message));
    break;
    
    case 'edit':

        if(empty($_POST['hpid']) || empty($_POST['message']))
            die($core->jsonResponse('error',$core->lang('ERROR')));

        die($core->jsonDbResponse(
                $core->editMessage($_POST['hpid'],$_POST['message'], $prj)
                )
           );
    break;

    default:

        die($core->jsonResponse('error',$core->lang('ERROR').' Wrong request'));
    break;
}

die($core->jsonResponse('ok', 'OK'));
?>
