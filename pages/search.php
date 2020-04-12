<?php
/*
Copyright (C) 2010-2020 Paolo Galeone <nessuno@nerdz.eu>

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
use NERDZ\Core\Utils;
use NERDZ\Core\User;
use NERDZ\Core\Project;

$vals = [];
$vals['searchterm_n'] = $q;
$vals['type_n'] = !preg_match('/^#[a-z][a-z0-9]{0,33}$/i', $q) && isset($_GET['type'])
    ? (
        $_GET['type'] == 'profile'
        ? 'profile'
        : 'project'
    ) : 'tag';

if ($vals['type_n'] == 'tag') {
    $vals['where_n'] = 'home';
    $vals['toid_n'] = $vals['to_n'] = $vals['to4link_n'] = '';
} else {
    $prj = $vals['type_n'] == 'project';

    $vals['where_n'] = isset($_GET['location'])
        ? (
            $_GET['location'] == 'home'
            ? 'home'
            : (
                $_GET['location'] == 'profile'
                ? 'profile'
                : 'project'
            )
        ) : 'home';

    $vals['toid_n'] = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : false;
    if ($vals['toid_n']) {
        if ($prj) {
            $vals['to_n'] = Project::getName($vals['toid_n']);
            $vals['to4link_n'] = Utils::projectLink($vals['to_n']);
        } else {
            $vals['to_n'] = User::getUsername($vals['toid_n']);
            $vals['to4link_n'] = Utils::userLink($vals['to_n']);
        }
    } else {
        $vals['toid_n'] = $vals['to_n'] = $vals['to4link_n'] = '';
    }
}

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/trends.html.php';

$user->getTPL()->assign($vals);
$user->getTPL()->draw('search/layout');
