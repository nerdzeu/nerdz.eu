<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\Project;
use NERDZ\Core\Db;

$core = new Project();

if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));

if(empty($_POST['id'])||!is_numeric($_POST['id']))
    die($core->jsonResponse('error',$core->lang('ERROR')));

switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
    case 'del':
        if(Db::NO_ERRNO != Db::query(array('DELETE FROM "groups_followers" WHERE "to" = :id AND "from" = :me',array(':id' => $_POST['id'],':me' => $_SESSION['id'])),Db::FETCH_ERRNO))
            die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
    case 'add':
        if(Db::NO_ERRNO != Db::query(array('INSERT INTO "groups_followers"("to","from") VALUES (:id,:me)',array(':id' => $_POST['id'],':me' => $_SESSION['id'])),Db::FETCH_ERRNO))
            die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
    default:
        die($core->jsonResponse('error',$core->lang('ERROR')));
    break;
}

die($core->jsonResponse('ok','OK'));
?>
