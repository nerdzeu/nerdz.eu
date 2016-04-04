<?php
/*
Copyright (C) 2016 Paolo Galeone <nessuno@nerdz.eu>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/Autoload.class.php';
use NERDZ\Core\Project;
use NERDZ\Core\User;

$user = new User();
$project = new Project();
ob_start(array('NERDZ\\Core\\Utils', 'minifyHTML'));

$id = isset($_POST['id']) && is_numeric($_POST['id']) ? $_POST['id'] : false;

if (!$user->isLogged() || !$id || !($info = $project->getObject($id)) || $project->getOwner($id) != $_SESSION['id']) {
    die($user->lang('ERROR'));
}

$vals = [];

function sortbyusername($a, $b)
{
    return (strtolower($a) < strtolower($b)) ? -1 : 1;
}

$vals['photo_n'] = $info->photo;
$vals['website_n'] = $info->website;
$vals['name_n'] = $info->name;

$mem = $project->getMembers($info->counter);

$vals['members_n'] = count($mem);
$vals['members_a'] = [];

foreach ($mem as &$uid) {
    $uid = User::getUsername($uid);
}

$vals['members_a'] = $mem;

usort($vals['members_a'], 'sortbyusername');

$vals['id_n'] = $info->counter;

$vals['description_a'] = explode("\n", $info->description);
foreach ($vals['description_a'] as &$val) {
    $val = trim($val);
}

$vals['goal_a'] = explode("\n", $info->goal);
foreach ($vals['goal_a'] as &$val) {
    $val = trim($val);
}

$vals['openproject_b'] = $project->isOpen($info->counter);
$vals['visibleproject_b'] = $info->visible;
$vals['privateproject_b'] = $info->private;

$user->getTPL()->assign($vals);
$user->getTPL()->draw('preferences/projects/manage');
