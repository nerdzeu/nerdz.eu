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
require_once $_SERVER['DOCUMENT_ROOT'].'/class/Autoload.class.php';


ob_start(array('NERDZ\\Core\\Utils', 'minifyHTML'));
header('Content-type: application/rss+xml');

$feed = new NERDZ\Core\Feed();

if (isset($_GET['id']) && is_numeric($_GET['id']) && !isset($_GET['project'])) {
    echo $feed->getProfileFeed($_GET['id']);
} elseif (isset($_GET['id']) && is_numeric($_GET['id']) && isset($_GET['project'])) {
    echo $feed->getProjectFeed($_GET['id']);
} elseif (!isset($_GET['id']) && !isset($_GET['project'])) {
    echo $feed->getHomeProfileFeed();
} elseif (!isset($_GET['id']) && isset($_GET['project'])) {
    echo $feed->getHomeProjectFeed();
} else {
    echo $feed->error('Wrong GET parameters');
}
