<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/Messages.class.php';
$core = new Messages();

if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('LOGIN')));

if(empty($_POST['id']) || !is_numeric($_POST['id']))
    die($core->jsonResponse('error',$core->lang('ERROR')));
    
switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
    case 'del':
        if(Db::NO_ERRNO != $core->query(array('DELETE FROM "follow" WHERE "from" = :me AND "to" = :id',array(':me' => $_SESSION['id'],':id' => $_POST['id'])),Db::FETCH_ERRNO))
            die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
    case 'add':
        if($core->query(array('SELECT "from" FROM "follow" WHERE "from" = :me AND "to" = :id',array(':me' => $_SESSION['id'],':id' => $_POST['id'])),Db::ROW_COUNT) == 0)
        {
            if(Db::NO_ERRNO != $core->query(array('INSERT INTO "follow"("from","to","time") VALUES (:me, :id,NOW())',array(':me' => $_SESSION['id'],':id' => $_POST['id'])),Db::FETCH_ERRNO))
                die($core->jsonResponse('error',$core->lang('ERROR')));
        }
        else
            die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
    default:
        die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
}
die($core->jsonResponse('ok','OK'));
?>
