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
$pages = [
    '\.' => [$_SERVER['DOCUMENT_ROOT'].'/profile.php', 'id'],
    ':' => [$_SERVER['DOCUMENT_ROOT'].'/project.php', 'gid'],
];

foreach ($pages as $separator => $elements) {
    $page = $elements[0];
    $id = $elements[1];

    if (preg_match("#^/(.+?){$separator}$#", $_SERVER['SCRIPT_NAME'], $matches)) {
        $_GET = array($id => $matches[1]);

        return include $page;
    } elseif (preg_match("#^/(.+?){$separator}(\d+)$#", $_SERVER['SCRIPT_NAME'], $matches)) {
        $_GET = array($id => $matches[1], 'pid' => $matches[2]);

        return include $page;
    } elseif (preg_match("#^/(.+?){$separator}(friends|members|followers|following|interactions)$#", $_SERVER['SCRIPT_NAME'], $matches)) {
        $_GET = array($id => $matches[1], 'action' => $matches[2]);

        return include $page;
    } elseif (preg_match("#^/(.+?){$separator}(friends|members|followers|following|interactions)\?(.*)$#", $_SERVER['SCRIPT_NAME'], $matches)) {
        $_GET = array($id => $matches[1], 'action' => $matches[2]);
        $parameters = explode('&', $matches[3]);
        foreach ($parameters as $parameter) {
            $parameter = explode('=', $parameter);
            $_GET[$parameter[0]] = $parameter[1];
        }

        return include $page;
    }
}

return false;
