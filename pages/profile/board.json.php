<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/messages.class.php';
$core = new messages();

if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));
 
if(!$core->refererControl())
    die($core->jsonResponse('error','CSRF'));

switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
    case 'add':            
        $to = intval(isset($_POST['to']) ? $_POST['to'] : 0);
        if($to <= 0)
            $to = $_SESSION['nerdz_id'];
    
        $message = trim(isset($_POST['message']) ? $_POST['message'] : '');
    
        if($to != $_SESSION['nerdz_id'] && $core->isInBlacklist($_SESSION['nerdz_id'],$to))
            die($core->jsonResponse('error','Blacklisted'));
            
        $retval = $core->addMessage($to,$message);
        if($retval === 0)
        {
            require_once $_SERVER['DOCUMENT_ROOT'].'/class/flood.class.php';
            $flood = new flood();
            die($core->jsonResponse('error','Flood! '.$core->lang('WAIT').': '.($_SESSION['nerdz_ProfileFlood'] + $flood::PROFILE_POST_TIMEOUT - time().'s')));
        }
        else if($retval === false)
            die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
    
    case 'del':
        if(    !isset($_SESSION['nerdz_delpost']) || empty($_POST['hpid']) || ($_SESSION['nerdz_delpost'] != $_POST['hpid']) || !$core->deleteMessage($_POST['hpid']) )
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
            !($o = $core->getMessage($_POST['hpid'],$edit = true))
          )
            die($core->jsonResponse('error',$core->lang('ERROR').'2'));
    break;
    
    case 'edit':
        if(    empty($_POST['hpid']) || !$core->editMessage($_POST['hpid'],$_POST['message']) )
            die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
    default:
        die($core->jsonResponse('error',$core->lang('ERROR').'3'));
    break;
}
die($core->jsonResponse('ok',isset($o) ? $o->message : 'OK'));
?>
