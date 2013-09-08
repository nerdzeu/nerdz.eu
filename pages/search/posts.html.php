<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';
ob_start(array('phpCore','minifyHtml'));

$core = new project();

if(!$core->isLogged())
	die(header('Location: /'));

$limit = $core->limitControl(isset($_POST['limit']) ? $_POST['limit'] : '',10) ? $_POST['limit'] : 10;

if(
	(empty($_POST['q']) || !$core->isLogged()) || 
 	(!empty($_GET['specific']) && empty($_POST['id']))
  )
	die();

if(isset($_POST['id']) && !is_numeric($_POST['id']))
	die('2');

$txt = trim(htmlentities($_POST['q'],ENT_QUOTES,'UTF-8'));

$blist = $core->getBlacklist();
$afterHpid = isset($_POST['hpid']) && is_numeric($_POST['hpid']) ? $_POST['hpid'] : false;

$vals = array();
$group = false;
$specific = isset($_GET['specific']);
$query_param = array_merge(array(':like' => '%'.$txt.'%') ,$specific ? array(':to' => $_POST['id']) : array(), $afterHpid ? array (':hpid' => $afterHpid) : array());

switch(isset($_GET['action']) ? trim(strtolower($_GET['action'])) : '')
{
	case 'profile':
		if(empty($blist))
			$glue = '';
		else
		{
			$imp_blist = implode(',',$blist);
			$glue = "AND "posts"."from" NOT IN ({$imp_blist}) AND "posts"."to" NOT IN ({$imp_blist})";
		}

		if(!($k = $core->query(
					array('SELECT "from","to","pid","message","time","hpid" FROM "posts" WHERE "message" LIKE :like '.$glue.($specific ? ' AND "to" = :to' : '').($afterHpid ? ' AND "hpid" < :hpid' : '').' ORDER BY "hpid" DESC LIMIT '.$limit,
						$query_param
				),db::FETCH_STMT))
			)
			die($core->lang('ERROR'));
	break;
	
	case 'project':
		$group = true;
		if(empty($blist))
			$glue = '';
		else
		{
			$imp_blist = implode(',',$blist);
			$glue = "AND "groups_posts"."from" NOT IN ({$imp_blist})";
		}

		if(!($k = $core->query(
					array('SELECT "from","to","pid","message","time","hpid" FROM "groups_posts" WHERE "message" LIKE :like '.$glue.($specific ? ' AND "to" = :to' : '').($afterHpid ? ' AND "hpid" < :hpid' : '').' ORDER BY "hpid" DESC LIMIT '.$limit,
					$query_param
				),db::FETCH_STMT))
			)
			die($core->lang('ERROR'));
	break;
	default:
		die($core->lang('ERROR'));
	break;
}

//variabile $mess necessaria per le pagine sotto
$mess = $core->getPostsArray($k,$group);

//includo file per variabili di lingua comuni
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/postlist.lang.php';
//per le miniature!
$miniature = true;
if($group)
	//come sotto
	require_once $_SERVER['DOCUMENT_ROOT'].'/pages/project/postlist.html.php';
else
	//fa tutto il loop e assegna tutte le variabili corrette in $vals, comprese quelle di lingua comuni a entrabbi i loops
	require_once $_SERVER['DOCUMENT_ROOT'].'/pages/profile/postlist.html.php';

$tpl->assign($vals);
$tpl->draw('home/postlist');
?>
