<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';

$core = new project();
if(!$core->isLogged())
    die($core->jsonResponse('error',$core->lang('REGISTER')));

if(empty($_POST['id'])||!is_numeric($_POST['id']))
	die($core->jsonResponse('error',$core->lang('ERROR')));

switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
	case 'del':
		if(db::NO_ERR != $core->query(array('DELETE FROM `groups_followers` WHERE `group` = :id AND `user` = :me',array(':id' => $_POST['id'],':me' => $_SESSION['nerdz_id'])),db::FETCH_ERR))
			die($core->jsonResponse('error',$core->lang('ERROR')));
	break;
	case 'add':
		if(db::NO_ERR != $core->query(array('INSERT INTO `groups_followers`(`group`,`user`) VALUES (:id,:me)',array(':id' => $_POST['id'],':me' => $_SESSION['nerdz_id'])),db::FETCH_ERR))
			die($core->jsonResponse('error',$core->lang('ERROR')));
	break;
	default:
		die($core->jsonResponse('error',$core->lang('ERROR')));
	break;
}

die($core->jsonResponse('ok','OK'));
?>
