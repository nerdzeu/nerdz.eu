<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';
$tpl->configure('tpl_dir',$_SERVER['DOCUMENT_ROOT'].'/tpl/0/');
ob_start(array('phpCore','minifyHtml'));

$id = isset($_POST['id']) && is_numeric($_POST['id']) ? $_POST['id'] : false;

$core = new project();
if(!$core->isLogged() || !$id || !($info = $core->getProjectObject($id)) || $info->owner != $_SESSION['nerdz_id'] )
    die($core->lang('ERROR'));
    
$vals = array();

function sortbyusername($a, $b)
{
    return (strtolower($a) < strtolower($b)) ? -1 : 1;
}

$vals['photo_n'] = $info->photo;
$vals['photo'] = $core->lang('PHOTO');

$vals['website_n'] = $info->website;
$vals['website'] = $core->lang('WEBSITE');

$vals['id'] = 'ID';
$vals['id_n'] = $info->counter;

$vals['name'] = $core->lang('PROJECT_NAME');
$vals['name_n'] = $info->name;

$mem = $core->getMembers($info->counter);
$vals['members'] = $core->lang('MEMBERS');
$vals['members_n'] = count($mem);
$vals['members_a'] = array();

foreach($mem as &$uid)
    $uid = $core->getUserName($uid);

$vals['members_a'] = $mem;

usort($vals['members_a'],'sortbyusername');

$vals['tok_n'] = $core->getCsrfToken('edit');
$vals['id_n'] = $info->counter;

$vals['description'] = $core->lang('DESCRIPTION');
$vals['description_a'] = explode("\n",$info->description);
foreach($vals['description_a'] as &$val)
    $val = trim($val);

$vals['goal'] = $core->lang('GOAL');
$vals['goal_a'] = explode("\n",$info->goal);
foreach($vals['goal_a'] as &$val)
    $val = trim($val);

$vals['oneperline'] = $core->lang('ONE_PER_LINE');

$vals['inserturl'] = $core->lang('INSERT_URL');
$vals['privateproject'] = $core->lang('PRIVATE_PROJECT');
$vals['privateproject_b'] = $info->private === 1;

$vals['openproject'] = $core->lang('OPEN_PROJECT');
$vals['openproject_b'] = $core->isProjectOpen($info->counter);

$vals['visibleproject'] = $core->lang('VISIBLE_PROJECT');
$vals['visibleproject_b'] = $info->visible;

$vals['privateproject'] = $core->lang('PRIVATE_PROJECT');
$vals['privateproject_b'] = $info->private;

$vals['edit'] = $core->lang('EDIT');
$vals['delete'] = $core->lang('DELETE');

$vals['captcha'] = $core->lang('CAPTCHA');
$vals['reloadcaptcha'] = $core->lang('RELOAD_CAPTCHA');

$tpl->assign($vals);
$tpl->draw('preferences/projects/manage');
?>
