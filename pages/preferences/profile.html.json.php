<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
$tpl->configure('tpl_dir',$_SERVER['DOCUMENT_ROOT'].'/tpl/0/');

$core = new phpCore();
if(!$core->refererControl())
	die($core->jsonResponse('error',$core->lang('ERROR').': referer'));
	
if(!$core->csrfControl(isset($_POST['tok']) ? $_POST['tok'] : 0,'edit'))
	die($core->jsonResponse('error',$core->lang('ERROR').': token'));
	
if(!$core->isLogged())
	die($core->jsonResponse('error',$core->lang('REGISTER')));
	
$user['interests']  = isset($_POST['interests'])  ? trim($_POST['interests']) 	: '';
$user['biography']  = isset($_POST['biography'])  ? trim($_POST['biography']) 	: '';
$user['quotes']	    = isset($_POST['quotes'])	  ? trim($_POST['quotes']) 		: '';
$user['website']    = isset($_POST['website']) 	  ? strip_tags(trim($_POST['website'])) 	: '';
$user['jabber']	    = isset($_POST['jabber']) 	  ? trim($_POST['jabber']) 		: '';
$user['yahoo']	    = isset($_POST['yahoo']) 	  ? trim($_POST['yahoo']) 		: '';
$user['facebook']   = isset($_POST['facebook'])   ? trim($_POST['facebook']) 		: '';
$user['twitter']    = isset($_POST['twitter']) 	  ? trim($_POST['twitter']) 		: '';
$user['steam']	    = isset($_POST['steam']) 	  ? trim($_POST['steam']) 		: '';
$user['skype']	    = isset($_POST['skype']) 	  ? trim($_POST['skype']) 		: '';
$user['photo']	    = isset($_POST['photo']) 	  ? trim($_POST['photo']) 		: '';
$user['userscript'] = isset($_POST['userscript']) ? strip_tags(trim($_POST['userscript']))  : '';
$user['dateformat'] = isset($_POST['dateformat']) ? trim($_POST['dateformat'])  : '';
$closed				= isset($_POST['closed']);
$gravatar			= isset($_POST['gravatar']);
$flag = true;

if(!empty($user['website']) && !$core->isValidURL($user['website']))
	die($core->jsonResponse('error',$core->lang('WEBSITE').': '.$core->lang('INVALID_URL')));
	
if(!empty($user['userscript']) && !$core->isValidURL($user['userscript']))
	die($core->jsonResponse('error','Userscript: '.$core->lang('INVALID_URL')));

if(!empty($user['photo']))
{
    if(!$core->isValidURL($user['photo']))
		die($core->jsonResponse('error',$core->lang('PHOTO').':'.$core->lang('INVALID_URL')));

    if(!($head = get_headers($_POST['photo'],db::FETCH_OBJ)) || !isset($head['Content-Type']))
		die($core->jsonResponse('error','Something wrong with your profile image'));
		
	if(!is_string($head['Content-Type']) || false === strpos($head['Content-Type'],'image'))
    	die($core->jsonResponse('error','Your profile image, is not a photo or is protected, change it'));
}

if(false == ($obj = $core->getUserObject($_SESSION['nerdz_id'])))
    die($core->jsonResponse('error',$core->lang('ERROR')));
	
if(!empty($user['jabber']) && (false == filter_var($user['jabber'],FILTER_VALIDATE_EMAIL)))
	die($core->jsonResponse('error',$core->lang('JABBER').': '.$core->lang('MAIL_NOT_VALID')));
	
if(empty($user['dateformat']))
	$user['dateformat'] = 'd/m/Y, H:i';

if(!empty($user['facebook']) &&
		( !preg_match('#^https?://(([a-z]{2}\-[a-z]{2})|www)\.facebook\.com/people/[^/]+/([a-z0-9_\-]+)#i',$user['facebook']) &&
	      !preg_match('#^https?://(([a-z]{2}\-[a-z]{2})|www)\.facebook\.com/profile\.php\?id\=([0-9]+)#i',$user['facebook']) &&
		  !preg_match('#^https?://(([a-z]{2}\-[a-z]{2})|www)\.facebook\.com/([a-z0-9_\-\.]+)#i',$user['facebook'])
		)
  )
	die($core->jsonResponse('error',$core->lang('ERROR').': Facebook URL'));


if(!empty($user['twitter']) && !preg_match('#^https?://twitter.com/([a-z0-9_]+)#i',$user['twitter']))
	die($core->jsonResponse('error',$core->lang('ERROR').': Twitter URL'));

if(!empty($user['steam']) && strlen($user['steam']) > 35)
	die($core->jsonResponse('error',$core->lang('ERROR').': Steam'));
	
foreach($user as &$value)
    $value = htmlentities($value,ENT_QUOTES,'UTF-8');

$par = array(':interests' => $user['interests'],
			 ':biography' => $user['biography'],
			 ':quotes'  => $user['quotes'],
			 ':website' => $user['website'],
			 ':dateformat' => $user['dateformat'],
			 ':photo' => $user['photo'],
			 ':jabber' => $user['jabber'],
			 ':yahoo' => $user['yahoo'],
			 ':userscript' => $user['userscript'],
			 ':facebook' => $user['facebook'],
			 ':twitter' => $user['twitter'],
			 ':steam' => $user['steam'],
			 ':skype' => $user['skype'],
			 ':counter' => $obj->counter);
    
if(
	db::NO_ERR != $core->query(array('UPDATE profiles SET "interests" = :interests, "biography" = :biography, "quotes" = :quotes, "website" = :website, "dateformat" = :dateformat,
      "photo" = :photo, "jabber" = :jabber, "yahoo" = :yahoo,
	  "userscript" = :userscript, "facebook" = :facebook, "twitter" = :twitter, "steam" = :steam, "skype" = :skype WHERE "counter" = :counter',$par),db::FETCH_ERR)
 )
	die($core->jsonResponse('error',$core->lang('ERROR')));

if($closed)
{
	if(!$core->closedProfile($_SESSION['nerdz_id']))
		if(db::NO_ERR != $core->query(array('INSERT INTO "closed_profiles"("counter") VALUES(?)',array($_SESSION['nerdz_id'])),db::FETCH_ERR))
			die($core->jsonResponse('error',$core->lang('ERROR')));
}
else
	if( db::NO_ERR != $core->query(array('DELETE FROM "closed_profiles" WHERE "counter" = ?',array($_SESSION['nerdz_id'])),db::FETCH_ERR) )
		die($core->jsonResponse('error',$core->lang('ERROR')));

if($gravatar)
{
	if(!$core->hasGravatarEnabled($_SESSION['nerdz_id']))
		if(db::NO_ERR != $core->query(array('INSERT INTO "gravatar_profiles"("counter") VALUES(?)',array($_SESSION['nerdz_id'])),db::FETCH_ERR))
			die($core->jsonResponse('error',$core->lang('ERROR')));

	$_SESSION['nerdz_gravatar'] = true;
}
else
{
	if(db::NO_ERR != $core->query(array('DELETE FROM "gravatar_profiles" WHERE "counter" = ?',array($_SESSION['nerdz_id'])),db::FETCH_ERR))
		die($core->jsonResponse('error',$core->lang('ERROR')));
	$_SESSION['nerdz_gravatar'] = false;
}
$_SESSION['nerdz_dateformat'] = $user['dateformat'];

if(isset($_POST['whitelist']))
{
	$oldlist = $core->getWhitelist($_SESSION['nerdz_id']);

	$m = array_filter(array_unique(explode("\n",$_POST['whitelist'])));
	$newlist = array();
	foreach($m as $v)
	{
		$uid = $core->getUserId(trim($v));
		if(is_numeric($uid))
		{
			if(!in_array($core->query(array('INSERT INTO "whitelist"("from","to") VALUES(:id,:uid)',array(':id' => $_SESSION['nerdz_id'], ':uid' => $uid)),db::FETCH_ERR),array(db::NO_ERR,1062)))
				die($core->jsonResponse('error',$core->lang('ERROR').'1'));
			$newlist[] = $uid;
		}
		else
			die($core->jsonResponse('error',$core->lang('ERROR').': Invalid user - '.$v));
	}
	$toremove = array();
	foreach($oldlist as $val)
		if(!in_array($val,$newlist))
			$toremove[] = $val;

	foreach($toremove as $val)
		if(db::NO_ERR != $core->query(array('DELETE FROM whitelist WHERE "from" = :id AND "to" = :val',array(':id' => $_SESSION['nerdz_id'], ':val' => $val)),db::FETCH_ERR))
			die($core->jsonResponse('error',$core->lang('ERROR').'4'));
}
		
die($core->jsonResponse('ok','OK'));
?>
