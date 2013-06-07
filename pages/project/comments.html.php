<?php
//TEMPLATE: OK 
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/comments.class.php';
$core = new comments();
ob_start(array('phpCore','minifyHtml'));

if(!$core->isLogged())
	die($core->lang('REGISTER'));

switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
    case 'show':
		$hpid  = isset($_POST['hpid']) && is_numeric($_POST['hpid']) ? $_POST['hpid']  : false;
		if(!$hpid )
			die($core->lang('ERROR'));
		$_list = null;
		if (isset ($_POST['start']) && isset ($_POST['num']) &&
		    is_numeric ($_POST['start']) && is_numeric ($_POST['num']))
		    $_list = $core->getProjectLastComments ($hpid, $_POST['num'], $_POST['start']);
		else if (isset ($_POST['hcid']) && is_numeric ($_POST['hcid']))
			$_list = $core->getProjectCommentsAfterHcid ($hpid, $_POST['hcid']);
		else
			die ($core->lang('ERROR'));
		$doShowForm = !isset ($_POST['hcid']) && (!isset ($_POST['start']) || (isset ($_POST['start']) && $_POST['start'] == 0));
		if (empty ($_list) && !$doShowForm)
			die();
		$vals = array();
		$vals['gravatar_b'] = $core->hasGravatarEnabled($_SESSION['nerdz_id']);
		$vals['onerrorimgurl_n'] = STATIC_DOMAIN.'/static/images/red_x.png';
		$vals['list_a'] = $_list;
		$vals['showform_b'] = $doShowForm;
		$vals['hpid_n'] = $hpid;
		$vals['nerdzit'] = $core->lang('NERDZ_IT');
		$vals['preview'] = $core->lang('PREVIEW');
		$vals['areyousure'] = $core->lang('ARE_YOU_SURE');
		$vals['receivenotifications'] = $core->lang('REVC_NOTIFY');
		$vals['dontreceivenotifications'] = $core->lang('NOT_RECV_NOTIFY');
		$vals['morebtn_label'] = $core->lang ('MORE_COMMENTS');
		$vals['bottombtn_label'] = $core->lang ('BACK_TO_THE_BOTTOM');
		$vals['needmorebtn_b'] = count ($_list) == 10 && ( !isset ($_POST['start']) || ( isset ($_POST['start']) && $_POST['start'] == 0 )) && !isset ($_POST['hcid']) && $core->countProjectComments ($hpid) > 10;
		$tpl->assign($vals);
		$tpl->draw('project/comments');
	break;
default:
    die($core->lang('ERROR'));
break;
}
