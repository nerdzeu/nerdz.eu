<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\User;
use NERDZ\Core\Db;
$user = new User();

if(!$user->isLogged())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('REGISTER')));
if(!$user->refererControl())
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR').': referer'));

$hpid  = isset($_POST['hpid'])  && is_numeric($_POST['hpid'])  ? $_POST['hpid']  : false;

if(!$hpid)
    die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
    
$to = $_SESSION['id'];
$table = (isset($prj) ? 'groups_' : '').'lurkers';

switch(isset($_GET['action']) ? strtolower(trim($_GET['action'])) : '')
{
    case 'add':
        if(Db::NO_ERRNO != Db::query(
                [
                    'INSERT INTO "'.$table.'"("from","hpid")
                    SELECT :to,:hpid
                    WHERE NOT EXISTS ( SELECT 1 FROM "'.$table.'" WHERE "from" = :to AND "hpid" = :hpid)',
                    [
                        ':to'   => $to,
                        ':hpid' => $hpid
                    ]
                ],Db::FETCH_ERRNO))
            die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));    
    break;

    case 'del':
        if(Db::NO_ERRNO != Db::query(
                [
                    'DELETE FROM "'.$table.'" WHERE "from" = :to AND "hpid" = :hpid',
                    [
                        ':to'   => $to,
                        ':hpid' => $hpid
                    ]
                ],Db::FETCH_ERRNO))
            die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
    break;
    default:
        die(NERDZ\Core\Utils::jsonResponse('error',$user->lang('ERROR')));
    break;
}
die(NERDZ\Core\Utils::jsonResponse('ok','OK'));
?>
