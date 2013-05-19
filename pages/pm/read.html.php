<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/pm.class.php';
ob_start(array('phpCore','minifyHtml'));

$core = new pm();
if(!$core->isLogged())
	die($core->lang('REGISTER'));

$vals = array();
$vals['from'] = $core->lang('FROM');
$vals['to'] = $core->lang('TO');
$vals['send'] = $core->lang('SEND');
$vals['preview'] = $core->lang('PREVIEW');
$vals['message'] = $core->lang('MESSAGE');
$tpl->assign($vals);
$vals = array();

switch(isset($_GET['action']) ? trim(strtolower($_GET['action'])) : '')
{
	case 'conversation':
		$from = isset($_POST['from']) && is_numeric($_POST['from']) ? $_POST['from'] : false;
		$to   = isset($_POST['to']) && is_numeric($_POST['to']) ? $_POST['to'] : false;

		if(!$from || !$to)
			die($core->lang('ERROR'));

		$afterPmId = isset($_POST['pmid']) && is_numeric($_POST['pmid']) ? $_POST['pmid'] : false;
		
		$conv = $core->readConversation($from, $to, $afterPmId);
	
		$i = 0;
		$to = 0;
		while(isset($conv[$i]['from_n']))
		{
			if($conv[$i]['fromid_n'] != $_SESSION['nerdz_id'])
			{
				$to = $conv[$i]['from_n'];
				break;
			}
			++$i;
		}
		$vals['to_n'] = $to;
		$vals['list_a'] = $conv;
		$vals['showform_b'] = !$afterPmId;
		$tpl->assign($vals);
		$tpl->draw('pm/conversation');
	break;
	default:
		die($core->lang('ERROR'));
	break;
}
?>
