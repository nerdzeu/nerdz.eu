<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/messages.class.php';
$core = new messages();

if(
	!$core->isLogged() ||
	empty($_POST['id']) || !is_numeric($_POST['id'])
  )
    die($core->jsonResponse('error',$core->lang('LOGIN')));
		
switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
	case 'del':
		if(db::NO_ERR != $core->query(array('DELETE FROM `blacklist` WHERE `from` = :me AND `to` = :to',array(':me' => $_SESSION['nerdz_id'],':to' => $_POST['id'])),db::FETCH_ERR))
			die($core->jsonResponse('error',$core->lang('ERROR')));
	break;
	
	case 'add':
		$motivation = empty($_POST['motivation']) ? '' : htmlentities(trim($_POST['motivation']),ENT_QUOTES,'UTF-8');
		if(!($core->isInBlacklist($_POST['id'],$_SESSION['nerdz_id'])))
		{
			if(db::NO_ERR != $core->query(array('INSERT INTO `blacklist`(`from`,`to`,`motivation`) VALUES (:me,:to,:motivation)',array(':me' => $_SESSION['nerdz_id'],':to' => $_POST['id'],':motivation' => $motivation)),db::FETCH_ERR))
				die($core->jsonResponse('error',$core->lang('ERROR')));
		}
		else
			die($core->jsonResponse('error',$core->lang('ERROR').'1'));
	break;
	
	default:
		die($core->jsonResponse('error',$core->lang('ERROR').'2'));
	break;
}
die($core->jsonResponse('ok','OK'));
?>
