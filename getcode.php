<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
$core = new phpCore();

header('Content-Type: text/plain; charset=utf-8');

$ncode = isset($_GET['ncode']) && is_numeric($_GET['ncode']) ? intval($_GET['ncode']) : 1;

if(isset($_GET['id']) && is_numeric($_GET['id']))
	$id = intval($_GET['id']);
else
	if(isset($_GET['gid']) && is_numeric($_GET['gid']))
		$gid = intval($_GET['gid']);

if(!isset($id) || !isset($gid))
{
	if(isset($_GET['pcid']) && is_numeric($_GET['pcid']))
		$pcid = intval($_GET['pcid']);
	
	if(isset($_GET['gcid']) && is_numeric($_GET['gcid']))
		$gcid = intval($_GET['gcid']);
}

if((isset($id) || isset($gid)) && isset($_GET['pid']) && is_numeric($_GET['pid']))
	$pid = intval($_GET['pid']);

if((isset($id) || isset($gid)) && isset($pid))
{
	$new = isset($id) ? $id : $gid;
	if(!($o = $core->query(array('SELECT `message` FROM `'.(isset($id) ? '' : 'groups_').'posts` WHERE `pid` = :pid AND `to` = :new',array(':pid' => $pid, ':new' => $new)),db::FETCH_OBJ)))
		die('Error');
}
elseif(isset($pcid) || isset($gcid))
{
	$new = isset($pcid) ? $pcid : $gcid;
	if(!($o = $core->query(array('SELECT `message` FROM `'.(isset($pcid) ? '' : 'groups_').'comments` WHERE `hcid` = ?',array($new)),db::FETCH_OBJ)))
		die('error');
}
else
	die();

$str = $o->message;
$start[0] = strpos(strtolower($str),'[code=',0);
$end[0] = strpos(strtolower($str),'[/code]',0);
$key = 0;
if((false === $start[$key]) || (false === $end[$key]))
	die();

for($key = 1;$key<$ncode;++$key)
{
	$start[$key] = strpos(strtolower($str),'[code=',$end[$key-1]+6);
	$end[$key] = strpos(strtolower($str),'[/code]',$end[$key-1]+7);
}
$code = '';
if(!isset($_GET['ncode']) || $_GET['ncode'] == 1)
	$key = 0;
else
	--$key;

if((false !== $start[$key]) && (false !== $end[$key]))
{
	$start[$key]+=6;
	for($i=$start[$key];$i<=$end[$key];++$i)
		if($str[$i] == ']')
		{
			$etag = $i;
			break;
		}
	if(isset($etag))
		$code = html_entity_decode(substr($str,$etag+1,$end[$key]-$etag-1),ENT_QUOTES,'UTF-8');
}
die($code);
?>
