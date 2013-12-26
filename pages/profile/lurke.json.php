<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
$core = new phpCore();

if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));
if(!$core->refererControl())
    die($core->jsonResponse('error',$core->lang('ERROR').': referer'));

$hpid  = isset($_POST['hpid'])  && is_numeric($_POST['hpid'])  ? $_POST['hpid']  : false;

if(!$hpid)
    die($core->jsonResponse('error',$core->lang('ERROR')));
    
$to = $_SESSION['nerdz_id'];

switch(isset($_GET['action']) ? strtolower(trim($_GET['action'])) : '')
{
    case 'add':
        //il trigger mi fa il controllo sul fatto che l'utente abbia postato o no (lo aggiunge solo se non ha postato)
        if(!in_array($core->query(array('INSERT INTO "lurkers"("user","post","time") VALUES(:to,:hpid,NOW())',array(':to' => $to, ':hpid' => $hpid)),db::FETCH_ERR),array(db::NO_ERR,POSTGRESQL_DUP_KEY)))
            die($core->jsonResponse('error',$core->lang('ERROR')));    
    break;
    case 'del':
        if(db::NO_ERR != $core->query(array('DELETE FROM "lurkers" WHERE "user" = :to AND "post" = :hpid',array(':to' => $to,':hpid' => $hpid)),db::FETCH_ERR))
            die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
    default:
        die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
}
die($core->jsonResponse('ok','OK'));
?>
