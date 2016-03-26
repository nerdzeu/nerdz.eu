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
if (!isset($id)) {
    die('$id required');
}

require_once $_SERVER['DOCUMENT_ROOT'].'/class/Autoload.class.php';
use NERDZ\Core\Project;
use NERDZ\Core\User;
use NERDZ\Core\Utils;

$user = new User();
$prj = isset($prj);
$entity = $prj ? new Project() : new User();
$limit = isset($_GET['lim']) ? NERDZ\Core\Security::limitControl($_GET['lim'], 20) : 20;
$order = isset($_GET['desc']) && $_GET['desc'] == 1 ? 'DESC' : 'ASC';

$myvals = [];
$myvals['me_n'] = $_SESSION['id'];
$myvals['list_a'] = $entity->getInteractions($id, $limit);
if ($prj) {
    $myvals['to_n'] = Project::getName($id);
    $myvals['to4link_n'] = Utils::projectLink($myvals['to_n']);
} else {
    $myvals['to_n'] = $myvals['to4link_n'] = '';
}

$validFields = ['time'];

NERDZ\Core\Security::setNextAndPrevURLs($myvals, $limit,
    [
        'order' => $order,
        'field' => empty($_GET['orderby']) ? '' : $_GET['orderby'],
        'validFields' => $validFields,
    ]);

$user->getTPL()->assign($myvals);

return $user->getTPL()->draw(($prj ? 'project' : 'profile').'/interactions', true);
