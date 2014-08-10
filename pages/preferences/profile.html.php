<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Db;
use NERDZ\Core\User;

$user = new User();
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

if(!$user->isLogged())
    die($user->lang('REGISTER'));

if(!($obj = Db::query(array('SELECT * FROM "profiles" WHERE "counter" = ?',array($_SESSION['id'])),Db::FETCH_OBJ)))
    die($user->lang('ERROR'));

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
$vals['closedprofile_b'] = $user->hasClosedProfile($_SESSION['id']);
$vals['canshowwhitelist_b'] = $vals['closedprofile_b'];
$wl = $user->getWhitelist($_SESSION['id']);
$i = 0;
foreach($wl as &$val)
    $vals['whitelist_a'][$i++] = User::getUsername($val);

$vals['tok_n'] = $user->getCsrfToken('edit');
$vals['dateformat_n'] = $obj->dateformat;

$user->getTPL()->assign($vals);
$user->getTPL()->draw('preferences/profile');
?>
