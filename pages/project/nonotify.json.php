<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
$core = new phpCore();

if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));

if(!$core->refererControl())
    die($core->jsonResponse('error',$core->lang('ERROR').': referer'));
    
$from = isset($_POST['from']) && is_numeric($_POST['from']) ? $_POST['from'] : 0; //l'intero post
$hpid =    isset($_POST['hpid'])  && is_numeric($_POST['hpid'])  ? $_POST['hpid']  : false;

if(!$hpid)
    die($core->jsonResponse('error',$core->lang('ERROR')));

$to = $_SESSION['nerdz_id'];

switch(isset($_GET['action']) ? strtolower(trim($_GET['action'])) : '')
{
    case 'add':
        $retcode = array(db::NO_ERR,1062);
        if(!$from) //intero post
        {
            if(!in_array($core->query(array('INSERT INTO "groups_posts_no_notify"("user","hpid","time") VALUES(:to,:hpid,NOW())',array(':to' => $to, ':hpid' => $hpid)),db::FETCH_ERR),$retcode))
                die($core->jsonResponse('error',$core->lang('ERROR')));
        }
        else
            if(!in_array($core->query(array('INSERT INTO "groups_comments_no_notify"("from","to","hpid","time") VALUES(:from,:to,:hpid,NOW())',array(':from' => $from, ':to' => $to, ':hpid' => $hpid)),db::FETCH_ERR),$retcode))
                die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
    case 'del':
        if(!$from) //intero post
        {
            if(db::NO_ERR != $core->query(array('DELETE FROM "groups_posts_no_notify" WHERE "user" = :to AND "hpid" = :hpid',array(':to' => $to, ':hpid' => $hpid)),db::FETCH_ERR))
                die($core->jsonResponse('error',$core->lang('ERROR')));
        }
        else
            if(db::NO_ERR != $core->query(array('DELETE FROM "groups_comments_no_notify" WHERE "from" = :from AND "to" = :to AND "hpid" = :hpid',array(':from' => $from, ':to' => $to, ':hpid' => $hpid)),db::FETCH_ERR))
                die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
    default:
        die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
}
die($core->jsonResponse('ok','OK'));
?>
