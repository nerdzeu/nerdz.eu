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

		if (!$from || !$to || !in_array ($_SESSION['nerdz_id'], array ($from, $to)))
			die($core->lang('ERROR'));

		$conv = null;
		if (isset ($_POST['start']) && isset ($_POST['num']) && is_numeric ($_POST['start']) && is_numeric ($_POST['num']))
			$conv = $core->readConversation ($from, $to, false, $_POST['num'], $_POST['start']);
		else if (isset ($_POST['pmid']) && is_numeric ($_POST['pmid']))
			$conv = $core->readConversation ($from, $to, $_POST['pmid']);
		else
			die ($core->lang ('ERROR'));
		$doShowForm = !isset ($_POST['pmid']) && (!isset ($_POST['start']) || (isset ($_POST['start']) && $_POST['start'] == 0));
		if (!$doShowForm && empty ($conv))
			die();
		$vals['to_n'] = ( $_SESSION['nerdz_id'] != $to ? $core->getUserName ($to) : $core->getUserName ($from) );
		if (!$vals['to_n']) die ($core->lang ('ERROR'));
		//die ("dbg -> to " . $vals['to_n'] . ", from " . $_SESSION['nerdz_id']);
		$vals['list_a'] = $conv;
		$vals['morebtn_label'] = $core->lang ('MORE_MSGS');
		$vals['bottombtn_label'] = $core->lang ('BACK_TO_THE_BOTTOM');
		$vals['needmorebtn_b'] = count ($conv) == 10 && ( !isset ($_POST['start']) || ( isset ($_POST['start']) && $_POST['start'] == 0 )) && !isset ($_POST['pmid']);
		$vals['showform_b'] = $doShowForm;
		$tpl->assign($vals);
		$tpl->draw('pm/conversation');
	break;
	default:
		die($core->lang('ERROR'));
	break;
}
?>
