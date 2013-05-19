<?php
//TEMPLATE: OK 
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'class/comments.class.php';
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

		$afterHcid = isset($_POST['hcid']) && is_numeric($_POST['hcid']) ? $_POST['hcid'] : false;

		$vals = array();
		$vals['gravatar_b'] = $core->hasGravatarEnabled($_SESSION['nerdz_id']);
		$vals['onerrorimgurl_n'] = STATIC_DOMAIN.'/static/images/red_x.png';
		$vals['list_a'] = $afterHcid ? $core->getProjectCommentsAfterHcid($hpid,$afterHcid) : $core->getProjectComments($hpid);
		$vals['showform_b'] = !$afterHcid;
		$vals['hpid_n'] = $hpid;
		$vals['nerdzit'] = $core->lang('NERDZ_IT');
		$vals['preview'] = $core->lang('PREVIEW');
		$vals['areyousure'] = $core->lang('ARE_YOU_SURE');
		$vals['receivenotifications'] = $core->lang('REVC_NOTIFY');
		$vals['dontreceivenotifications'] = $core->lang('NOT_RECV_NOTIFY');
		$tpl->assign($vals);
		$tpl->draw('project/comments');
	break;
default:
    die($core->lang('ERROR'));
break;
}
