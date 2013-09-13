<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
$tpl->configure('tpl_dir',$_SERVER['DOCUMENT_ROOT'].'/tpl/0/');
ob_start(array('phpCore','minifyHtml'));

$core = new phpCore();
if(!$core->isLogged())
    die($core->lang('REGISTER'));

if(!($obj = $core->query(array('SELECT * FROM `profiles` WHERE `counter` = ?',array($_SESSION['nerdz_id'])),db::FETCH_OBJ)))
    die($core->lang('ERROR'));

$vals = array();
$vals['description'] = $core->lang('PROFILE_DESCR');
$vals['interests'] = $core->lang('INTERESTS');
$vals['oneperline'] = $core->lang('ONE_PER_LINE');
$vals['interests_a'] = explode("\n",$obj->interests);
foreach($vals['interests_a'] as &$val)
    $val = trim($val);
$vals['biography'] = $core->lang('BIOGRAPHY');
$vals['biographysub'] = $core->lang('BIOGRAPHY_SUB');
$vals['biography_n'] = $obj->biography;
$vals['quotes'] = $core->lang('QUOTES');
$vals['quotes_a'] = explode("\n",$obj->quotes);
foreach($vals['quotes_a'] as &$val)
    $val = trim($val);
$vals['website'] = $core->lang('WEBSITE');
$vals['website_n'] = $obj->website;
$vals['jabber'] = $core->lang('JABBER');
$vals['jabber_n'] = $obj->jabber;
$vals['yahoo'] = $core->lang('YAHOO');
$vals['yahoo_n'] = $obj->yahoo;
$vals['facebook'] = 'Facebook';
$vals['facebook_n'] = $obj->facebook;
$vals['twitter'] = 'Twitter';
$vals['twitter_n'] = $obj->twitter;
$vals['steam'] = 'Steam';
$vals['steam_n'] = $obj->steam;
$vals['skype'] = 'Skype';
$vals['skype_n'] = $obj->skype;
$vals['photo'] = $core->lang('PHOTO');
$vals['inserturl'] = $core->lang('INSERT_URL');
$vals['photo_n'] = $obj->photo;
$vals['userscript'] = 'Userscript';
$vals['userscript_n'] = $obj->userscript;
$vals['closedprofile'] = $core->lang('CLOSED_PROFILE');
$vals['closedprofile_b'] = $core->closedProfile($_SESSION['nerdz_id']);
$vals['whitelist'] = 'White list';
$vals['canshowwhitelist_b'] = $vals['closedprofile_b'];
$wl = $core->getWhitelist($_SESSION['nerdz_id']);
$i = 0;
foreach($wl as &$val)
    $vals['whitelist_a'][$i++] = $core->getUsername($val);
$vals['gravatar_b'] = $core->hasGravatarEnabled($_SESSION['nerdz_id']);
$vals['edit'] = $core->lang('EDIT');
$vals['tok_n'] = $core->getCsrfToken('edit');
$vals['dateformat'] = $core->lang('DATE_FORMAT');
$vals['dateformat_descr'] = $core->lang('DATE_FORMAT_DESCR');
$vals['dateformat_n'] = $obj->dateformat;

$tpl->assign($vals);
$tpl->draw('preferences/profile');
?>
