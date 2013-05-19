<?php
ob_start('ob_gzhandler');

require_once $_SERVER['DOCUMENT_ROOT'].'/class/messages.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';

function xmlentity($str)
{
	$str = html_entity_decode($str,ENT_QUOTES,'UTF-8');
	return str_replace('<','&lt;',str_replace('>','&gt;',str_replace("'",'&apos;',str_replace('"','&quot;',str_replace('&','&amp;',$str)))));
}

header('Content-type: application/rss+xml');
$ww = 0;
$xml = 'Hy h4x0r, h0w 4r3 u t0d4y?';

if(isset($_GET['id']) && is_numeric($_GET['id']) && !isset($_GET['project']))
{
	$core = new messages();
	if(!($p = $core->query(array('SELECT `private` FROM `users` WHERE `counter` = ?',array($_GET['id'])),db::FETCH_OBJ)))
		die('Unexpected error');
	if($p->private && !$core->isLogged())
		die('Closed profile');
	$ww = 1;
	if(is_array($m = $core->getMessages($_GET['id'],10)))
	{
		$us = $core->getUserName($_GET['id']);
		$urluser = phpCore::userLink($us);
		$xml = '<?xml version="1.0" encoding="UTF-8" ?><rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'."<channel><atom:link href=\"http://".SITE_HOST."/feed.php?id={$_GET['id']}\" rel=\"self\" type=\"application/rss+xml\" /><title>".xmlentity($us)."</title><description>".xmlentity($us)." NERDZ RSS</description><link>http://".SITE_HOST."/{$urluser}</link>";
		$i = 0;
		foreach($m as $v)
		{
			$fr = xmlentity($core->getUserName($m[$i]['from']));
			$xml.="<item><title>{$fr} =&gt; ".xmlentity($us)." - {$m[$i]['pid']}</title>".
			"<description>".substr(xmlentity($m[$i]['message']),0,170)."...</description>".
			"<link>http://".SITE_HOST.'/'.$urluser."{$m[$i]['pid']}</link>".
			"<pubDate>".date("r",$m[$i]['cmp'])."</pubDate>".
			"<guid>http://".SITE_HOST.'/'.$urluser."{$m[$i]['pid']}</guid></item>";
			++$i;
		}
	}
	else
		die('User not found');
}
elseif(isset($_GET['id']) && is_numeric($_GET['id']) && isset($_GET['project']))
{
	$core = new project();
	if(!($p = $core->query(array('SELECT `private`,`owner` FROM `groups` WHERE `counter` = ?',array($_GET['id'])),db::FETCH_OBJ)))
		die('Unexpected error');
	$mem = $core->getMembers($_GET['id']);
	if($p->private && (!$core->isLogged() || (!in_array($_SESSION['nerdz_id'],$mem) && $_SESSION['nerdz_id'] != $p->owner)))
		die('Closed project');

	$ww = 1;
	if(is_array($m = $core->getProjectMessages($_GET['id'],15)))
	{
		$us = $core->getProjectName($_GET['id']);
		$urluser = phpCore::projectLink($us);
		$xml = '<?xml version="1.0" encoding="UTF-8" ?><rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>'."<atom:link href=\"http://".SITE_HOST."/feed.php?id={$_GET['id']}&amp;project=1\" rel=\"self\" type=\"application/rss+xml\" /><title>".xmlentity($us)." project</title><description>".xmlentity($us)." project NERDZ RSS</description><link>http://".SITE_HOST."/{$urluser}</link>";
		$i = 0;
		foreach($m as $v)
		{
			$fr = xmlentity($core->getUserName($m[$i]['from']));
			$xml.="<item><title>{$fr} =&gt; ".xmlentity($us)." - {$m[$i]['pid']}</title>".
			"<description>".substr(xmlentity($m[$i]['message']),0,170)."...</description>".
			"<link>http://".SITE_HOST.'/'.$urluser."{$m[$i]['pid']}</link>".
			"<pubDate>".date("r",$m[$i]['cmp'])."</pubDate>".
			"<guid>http://".SITE_HOST.'/'.$urluser."{$m[$i]['pid']}</guid></item>";
			++$i;
		}
	}
	else die('Project not found');
}
elseif(!isset($_GET['id']) && !isset($_GET['project']))
{
	$core = new messages();
	if(!$core->isLogged())
		die('login');
	$ww = 1;
	$m = $core->getLatests(15);
	foreach($m as $v)
	{
		$xml = '<?xml version="1.0" encoding="UTF-8" ?><rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>'."<atom:link href=\"http://".SITE_HOST."/feed.php\" rel=\"self\" type=\"application/rss+xml\" /><title>NERDZ =&gt; home</title><description>NERDZ RSS</description><link>http://".SITE_HOST."/home.php</link>";
		$i = 0;
		foreach($m as $v)
		{
			$fr = xmlentity($core->getUserName($m[$i]['from']));
			$us = $core->getUserName($v['to']);
			$urluser = phpCore::userLink($us);
			$xml.="<item><title>{$fr} =&gt; ".xmlentity($us)." - {$m[$i]['pid']}</title>".
			"<description>".substr(xmlentity($m[$i]['message']),0,170)."...</description>".
			"<pubDate>".date("r",$m[$i]['cmp'])."</pubDate>".
			"<link>http://".SITE_HOST.'/'.$urluser."{$m[$i]['pid']}</link><guid>http://".SITE_HOST.'/'.$urluser."{$m[$i]['pid']}</guid></item>";
			++$i;
		}
	}
}	
elseif(!isset($_GET['id']) && isset($_GET['project']))
{
	$core = new project();
	if(!$core->isLogged())
		die('login');
	$ww = 1;
	$m = $core->getLatests(15,db::FETCH_OBJ);
	foreach($m as $v)
	{
		$xml = '<?xml version="1.0" encoding="UTF-8" ?><rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'."<channel><atom:link href=\"http://".SITE_HOST."/feed.php?project=1\" rel=\"self\" type=\"application/rss+xml\" /><title>NERDZ =&gt; projests home</title><description>NERDZ Projects RSS</description><link>http://".SITE_HOST."/home.php</link>";
		$i = 0;
		foreach($m as $v)
		{
			$fr = xmlentity($core->getUserName($m[$i]['from']));
			$us = $core->getProjectName($v['to']);
			$urluser = phpCore::projectLink($us);
			$xml.="<item><title>{$fr} =&gt; ".xmlentity($us)." - {$m[$i]['pid']}</title>".
			"<description>".substr(xmlentity($m[$i]['message']),0,170)."...</description>".
			"<pubDate>".date("r",$m[$i]['cmp'])."</pubDate>".
			"<link>http://".SITE_HOST.'/'.$urluser."{$m[$i]['pid']}</link><guid>http://".SITE_HOST.'/'.$urluser."{$m[$i]['pid']}</guid></item>";
			++$i;
		}
	}
}
else
	die('Wrong GET parameters');
unset($core);

die($xml.($ww ? '</channel></rss>': ''));
?>
