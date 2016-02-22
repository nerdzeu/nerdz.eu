<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Project;
use NERDZ\Core\User;
use NERDZ\Core\Utils;

$user = new User();
$project = new Project();
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

$id = isset($_POST['id']) && is_numeric($_POST['id']) ? $_POST['id'] : false;

if(!$user->isLogged() || !$id || !($info = $project->getObject($id)) || $project->getOwner($id) != $_SESSION['id'])
    die($user->lang('ERROR'));

$vals = [];

function sortbyusername($a, $b)
{
    return (strtolower($a) < strtolower($b)) ? -1 : 1;
}

$vals['photo_n']   = $info->photo;
$vals['website_n'] = $info->website;
$vals['name_n']    = $info->name;

$mem = $project->getMembers($info->counter);

$vals['members_n'] = count($mem);
$vals['members_a'] = [];

foreach($mem as &$uid)
    $uid = User::getUsername($uid);

$vals['members_a'] = $mem;

usort($vals['members_a'],'sortbyusername');

$vals['id_n']  = $info->counter;

$vals['description_a'] = explode("\n",$info->description);
foreach($vals['description_a'] as &$val)
    $val = trim($val);

$vals['goal_a'] = explode("\n",$info->goal);
foreach($vals['goal_a'] as &$val)
    $val = trim($val);

$vals['openproject_b']   = $project->isOpen($info->counter);
$vals['visibleproject_b'] = $info->visible;
$vals['privateproject_b'] = $info->private;

$user->getTPL()->assign($vals);
$user->getTPL()->draw('preferences/projects/manage');
