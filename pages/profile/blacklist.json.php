<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\Messages;
use NERDZ\Core\Db;

$core = new Messages();

if(
    !$core->isLogged() ||
    empty($_POST['id']) || !is_numeric($_POST['id'])
  )
    die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('LOGIN')));
        
switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
    case 'del':
        if(Db::NO_ERRNO != Db::query(array('DELETE FROM "blacklist" WHERE "from" = :me AND "to" = :to',array(':me' => $_SESSION['id'],':to' => $_POST['id'])),Db::FETCH_ERRNO))
            die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));
    break;
    
    case 'add':
        $motivation = empty($_POST['motivation']) ? '' : htmlspecialchars(trim($_POST['motivation']),ENT_QUOTES,'UTF-8');
        if(!($core->isInBlacklist($_POST['id'],$_SESSION['id'])))
        {
            if(Db::NO_ERRNO != Db::query(array('INSERT INTO "blacklist"("from","to","motivation") VALUES (:me,:to,:motivation)',array(':me' => $_SESSION['id'],':to' => $_POST['id'],':motivation' => $motivation)),Db::FETCH_ERRNO))
                die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR')));
        }
        else
            die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR').'1'));
    break;
    
    default:
        die(NERDZ\Core\Utils::jsonResponse('error',$core->lang('ERROR').'2'));
    break;
}
die(NERDZ\Core\Utils::jsonResponse('ok','OK'));
?>
