<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/project.class.php';
$core = new project();
ob_start(array('phpCore','minifyHtml'));

$id = isset($_POST['id']) && is_numeric($_POST['id']) ? $_POST['id'] : false;

if(!$core->isLogged() || !$id || !($info = $core->getProjectObject($id)) || $info->owner != $_SESSION['nerdz_id'] )
    die($core->lang('ERROR'));
    
$vals = [];

function sortbyusername($a, $b)
{
    return (strtolower($a) < strtolower($b)) ? -1 : 1;
}

$vals['photo_n'] = $info->photo;
$vals['website_n'] = $info->website;
$vals['name_n'] = $info->name;

$mem = $core->getMembers($info->counter);

$vals['members_n'] = count($mem);
$vals['members_a'] = [];

foreach($mem as &$uid)
    $uid = $core->getUsername($uid);

$vals['members_a'] = $mem;

usort($vals['members_a'],'sortbyusername');

$vals['tok_n'] = $core->getCsrfToken('edit');
$vals['id_n'] = $info->counter;

$vals['description_a'] = explode("\n",$info->description);
foreach($vals['description_a'] as &$val)
    $val = trim($val);

$vals['goal_a'] = explode("\n",$info->goal);
foreach($vals['goal_a'] as &$val)
    $val = trim($val);

$vals['openproject_b'] = $core->isProjectOpen($info->counter);
$vals['visibleproject_b'] = $info->visible;
$vals['privateproject_b'] = $info->private;

$core->getTPL()->assign($vals);
$core->getTPL()->draw('preferences/projects/manage');
?>
