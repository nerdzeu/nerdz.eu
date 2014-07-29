<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
$core = new Core();

if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));
if(!$core->refererControl())
    die($core->jsonResponse('error',$core->lang('ERROR').': referer'));

$hpid  = isset($_POST['hpid'])  && is_numeric($_POST['hpid'])  ? $_POST['hpid']  : false;

if(!$hpid)
    die($core->jsonResponse('error',$core->lang('ERROR')));
    
$to = $_SESSION['id'];

switch(isset($_GET['action']) ? strtolower(trim($_GET['action'])) : '')
{
    case 'add':
        if(!in_array($core->query(array('INSERT INTO "groups_bookmarks"("from","hpid","time") VALUES(:to,:hpid,NOW())',array(':to' => $to, ':hpid' => $hpid)),Db::FETCH_ERRNO),array(Db::NO_ERRNO,POSTGRESQL_DUP_KEY)))
            die($core->jsonResponse('error',$core->lang('ERROR')));    
    break;
    case 'del':
        if(Db::NO_ERRNO != $core->query(array('DELETE FROM "groups_bookmarks" WHERE "from" = :to AND "hpid" = :hpid',array(':to' => $to, ':hpid' => $hpid)),Db::FETCH_ERRNO))
            die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
    default:
        die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
}
die($core->jsonResponse('ok','OK'));
?>
