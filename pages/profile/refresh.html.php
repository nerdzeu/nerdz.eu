<?php
//TEMPLATE: Ok
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/messages.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/comments.class.php';

ob_start(array('phpCore','minifyHtml'));

$core = new messages();
$comments = new comments();

if(!($id = isset($_POST['id']) && is_numeric($_POST['id']) ? $_POST['id'] : false))
	die($core->lang('ERROR').'1');


$_POST['limit'] = $core->limitControl(isset($_POST['limit']) ? $_POST['limit'] : 10,10) ? $_POST['limit'] : 10;

$logged = $core->isLogged();

if($logged && is_numeric(strpos($_SERVER['REQUEST_URI'],'refresh.html.php')) && (true === $core->isInBlacklist($_SESSION['nerdz_id'],$id)))
	die('Hax0r c4n\'t fuck nerdz pr00tectionz');

$afterHpid = isset($_POST['hpid']) && is_numeric($_POST['hpid']) ? $_POST['hpid'] : false;

if(!($mess = $afterHpid ?
		$core->getNMessagesBeforeHpid($_POST['limit'],$afterHpid,$id)
		:
		$core->getMessages($id,$_POST['limit'])
	)
  )
	die(); //vuoto affinché i caricamenti automatici non mostrino nulla

if(!$logged && !is_numeric($_POST['limit']))
    die(); //vuoto così automaticamente il javascript non fa altre chiamate
else
{
	$vals = array();
	//includo file per variabili di lingua comuni
	require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/postlist.lang.php';
	//includo il loop in $mess
	require_once $_SERVER['DOCUMENT_ROOT'].'/pages/profile/postlist.html.php';
	$tpl->assign($vals);
	$tpl->draw('profile/postlist');
}
?>
