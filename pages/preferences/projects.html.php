<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
$tpl->configure('tpl_dir',$_SERVER['DOCUMENT_ROOT'].'/tpl/0/');
ob_start(array('phpCore','minifyHtml'));

$core = new phpCore();
	
if(!$core->refererControl())
	die($core->lang('ERROR'));
	
if(!$core->isLogged())
	die($core->lang('REGISTER'));
	
$vals = array();
$vals['description'] = $core->lang('PROJECTS_DESCR');
$vals['create'] =  $core->lang('CREATE');
$vals['listdescription'] = $core->lang('PROJECT_LIST');
$vals['manage'] = $core->lang('MANAGE');
$vals['tok_n'] = $core->getCsrfToken('edit');

if(!($r = $core->query(array('SELECT `name`,`counter` FROM `groups` WHERE `owner` = ?',array($_SESSION['nerdz_id'])),db::FETCH_STMT)))
	$vals['myprojects_a'] = array();
else
{
	$i = 0;
	while(($o = $r->fetch(PDO::FETCH_OBJ)))
	{
		$vals['myprojects_a'][$i]['name_n'] = $o->name;
		$vals['myprojects_a'][$i]['name4link_n'] = phpCore::projectLink($o->name);
		$vals['myprojects_a'][$i]['id_n'] = $o->counter;
		++$i;
	}
}
$tpl->assign($vals);
$tpl->draw('preferences/projects');
?>
