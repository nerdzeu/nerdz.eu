<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Messages;
use NERDZ\Core\User;

$messages = new Messages();
$user     = new User();
$prj = isset($prj);

if(!$user->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('REGISTER')));

if(!$user->refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error','CSRF'));
    
switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
    case 'add':
        
        if(empty($_POST['to']))
        {
            if($prj)
                die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').'a'));
            else
                $_POST['to'] = $_SESSION['id'];
        }

        die(NERDZ\Core\Utils::jsonDbResponse(
            $messages->add(
                $_POST['to'],
                isset($_POST['message']) ? $_POST['message'] : '',
                [
                    'news' => isset($_POST['news']),
                    'project' => $prj
                ]))
            );
    break;
    
    case 'del':

        if(!isset($_SESSION['delpost']) || empty($_POST['hpid']) || ($_SESSION['delpost'] != $_POST['hpid']) || !$messages->delete($_POST['hpid'], $prj))
            die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
        unset($_SESSION['delpost']);
    break;

    case 'delconfirm':

        $_SESSION['delpost'] = isset($_POST['hpid']) ? $_POST['hpid'] : -1;
        die(NERDZ\Core\Utils::jsonResponse('ok',$user->lang('ARE_YOU_SURE')));
    break;
    
    case 'get':

        if(
            empty($_POST['hpid']) ||
            !($o = $messages->getMessage($_POST['hpid'], $prj))
          )
            die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').'2'));
        die(NERDZ\Core\Utils::jsonResponse('ok', $o->message));
    break;
    
    case 'edit':

        if(empty($_POST['hpid']) || empty($_POST['message']))
            die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));

        die(NERDZ\Core\Utils::jsonDbResponse(
                $messages->edit($_POST['hpid'],$_POST['message'], $prj)
                )
           );
    break;

    default:

        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').' Wrong request'));
    break;
}

die(NERDZ\Core\Utils::jsonResponse('ok', 'OK'));
?>
