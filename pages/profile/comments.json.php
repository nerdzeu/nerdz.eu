<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/comments.class.php';
$core = new comments();

if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));

if(!$core->refererControl())
    die($core->jsonResponse('error','CSRF'));

switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
    case 'add':
        //dati del post
        $hpid  = isset($_POST['hpid']) && is_numeric($_POST['hpid']) ? $_POST['hpid']  : false;
        //testo del commento al tal post

        if(!$hpid)
            die($core->jsonResponse('error',$core->lang('ERROR')));

        if ($core->query(array('SELECT * FROM BLACKLIST, (SELECT "from", "to" FROM POSTS WHERE hpid = ?) AS X WHERE BLACKLIST."from" NOT IN (X."from", X."to") AND BLACKLIST."to" = ?'), array($hpid,$_SESSION['nerdz_id']), db::ROW_COUNT))
            die($core->jsonResponse('error', $core->lang('ERROR')));

        $r = $core->addComment($hpid,$_POST['message']);

        if($r === false)
            die($core->jsonResponse('error',$core->lang('ERROR')));
        elseif($r === null)
            die($core->jsonResponse('error','Flood'));
    break;

    case 'del':
        $hcid = isset($_POST['hcid']) && is_numeric($_POST['hcid']) ? $_POST['hcid'] : false;

        if(!$hcid || !$core->delComment($hcid))
            die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
default:
    die($core->jsonResponse('error',$core->lang('ERROR')));
break;
}
die($core->jsonResponse('ok','OK'));
?>
