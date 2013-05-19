<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/comments.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';
ob_start(array('phpCore','minifyHtml'));

$core = new project();
$comments = new comments();

if(!$core->isLogged())
    die($core->lang('REGISTER'));
 
if(!$core->refererControl())
	die($core->lang('ERROR'));

switch(isset($_GET['action']) ? strtolower($_GET['action']) : '')
{
	case 'get':
		//fa tutto lei compresa la gestione di $_POST[hpid]
		$hpid = isset($_POST['hpid']) ? $_POST['hpid'] : -1;
		$draw = true;
		require_once $_SERVER['DOCUMENT_ROOT'].'/pages/profile/singlepost.html.php';
	break;
	
	default:
		die($core->lang('ERROR'));
	break;
}
?>
