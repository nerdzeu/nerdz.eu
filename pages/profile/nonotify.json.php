<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
$core = new NERDZ\Core\Core();

if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));
if(!$core->refererControl())
    die($core->jsonResponse('error',$core->lang('ERROR').': referer'));

$from = isset($_POST['from']) && is_numeric($_POST['from']) ? $_POST['from'] : 0; //l'intero post
$hpid  = isset($_POST['hpid'])  && is_numeric($_POST['hpid'])  ? $_POST['hpid']  : false;

if(!$hpid)
    die($core->jsonResponse('error',$core->lang('ERROR')));
    
$to = $_SESSION['id'];

switch(isset($_GET['action']) ? strtolower(trim($_GET['action'])) : '')
{
    case 'add':
        $retcode = array(Db::NO_ERRNO,POSTGRESQL_DUP_KEY);
        if(!$from) //intero post
        {
            if(!in_array(Db::query(array('INSERT INTO "posts_no_notify"("user","hpid","time") VALUES(:to,:hpid,NOW())',array(':to' => $to,':hpid' => $hpid)),Db::FETCH_ERRNO),$retcode))
                die($core->jsonResponse('error',$core->lang('ERROR')));
        }
        else
            if(!in_array(Db::query(array('INSERT INTO "comments_no_notify"("from","to","hpid","time") VALUES(:from,:to,:hpid,NOW())',array(':from' => $from, ':to' => $to, ':hpid' => $hpid)),Db::FETCH_ERRNO),$retcode))
                die($core->jsonResponse('error',$core->lang('ERROR')));
        
    break;
    case 'del':
        if(!$from) //intero post
        {
            if(Db::NO_ERRNO != Db::query(array('DELETE FROM "posts_no_notify" WHERE "user" = :to AND "hpid" = :hpid',array(':to' => $to, ':hpid' => $hpid)),Db::FETCH_ERRNO))
                die($core->jsonResponse('error',$core->lang('ERROR')));
        }
        else
            if(Db::NO_ERRNO != Db::query(array('DELETE FROM "comments_no_notify" WHERE "from" = :from AND "to" = :to AND "hpid" = :hpid',array(':from' => $from, ':to' => $to, ':hpid' => $hpid)),Db::FETCH_ERRNO))
                die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
    default:
        die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
}
die($core->jsonResponse('ok','OK'));
?>
