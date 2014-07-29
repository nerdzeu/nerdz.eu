<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
$core = new Core();
ob_start(array('Core','minifyHtml'));

if(!$core->isLogged())
    die($core->lang('REGISTER'));

if(!($obj = $core->query(array('SELECT * FROM "profiles" WHERE "counter" = ?',array($_SESSION['id'])),Db::FETCH_OBJ)))
    die($core->lang('ERROR'));

$vals = [];
$vals['interests_a'] = explode("\n",$obj->interests);
foreach($vals['interests_a'] as &$val)
    $val = trim($val);

$vals['biography_n'] = $obj->biography;

$vals['quotes_a'] = explode("\n",$obj->quotes);
foreach($vals['quotes_a'] as &$val)
    $val = trim($val);

$vals['website_n'] = $obj->website;
$vals['jabber_n'] = $obj->jabber;
$vals['yahoo_n'] = $obj->yahoo;
$vals['facebook_n'] = $obj->facebook;
$vals['twitter_n'] = $obj->twitter;
$vals['steam_n'] = $obj->steam;
$vals['skype_n'] = $obj->skype;
$vals['github_n'] = $obj->github;
$vals['userscript_n'] = $obj->userscript;
$vals['closedprofile_b'] = $core->closedProfile($_SESSION['id']);
$vals['canshowwhitelist_b'] = $vals['closedprofile_b'];
$wl = $core->getWhitelist($_SESSION['id']);
$i = 0;
foreach($wl as &$val)
    $vals['whitelist_a'][$i++] = $core->getUsername($val);

$vals['tok_n'] = $core->getCsrfToken('edit');
$vals['dateformat_n'] = $obj->dateformat;

$core->getTPL()->assign($vals);
$core->getTPL()->draw('preferences/profile');
?>
