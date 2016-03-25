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
if(!isset($users, $type, $dateExtractor, $total))
    die('$users & $type & $dateExtractor & $total required');

require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\User;
use NERDZ\Core\Utils;

$validFields = [ 'username', 'name', 'surname', 'birth_date', 'last', 'counter', 'registration_time' ];

$limit   = isset($_GET['lim']) ? NERDZ\Core\Security::limitControl($_GET['lim'], 20) : 20;
$order   = isset($_GET['desc']) && $_GET['desc'] == 1 ? 'DESC' : 'ASC';
$q       = empty($_GET['q']) ? '' : htmlspecialchars($_GET['q'],ENT_QUOTES,'UTF-8');
$orderby = isset($_GET['orderby']) ? NERDZ\Core\Security::fieldControl($_GET['orderby'], $validFields, 'username') : 'username';

$user = new User();

$i = 0;
$ret = [];
foreach($users as $fid)
{
    $ret[$i] = $user->getBasicInfo($fid);
    $ret[$i]['since_n'] = $dateExtractor($fid, $ret[$i]['since_n']);
    ++$i;
}

usort($ret, 'NERDZ\\Core\\Utils::sortByUsername');

$myvals = [];
$myvals['list_a']           = $ret;
$startFrom = 0;
if(!is_numeric($limit)) {
    $matches = [];
    preg_match('/\d+$/',$limit, $matches);
    if(isset($matches[0]))
        $startFrom = $matches[0];
}
$myvals['displayedusers_n'] = count($ret) + $startFrom;
$myvals['totalusers_n']     = $total;
$myvals['type_n']           = $type;

NERDZ\Core\Security::setNextAndPrevURLs($myvals, $limit,
    [
        'order' => $order,
        'query' => $q,
        'field' => empty($_GET['orderby']) ? '' : $_GET['orderby'],
        'validFields' => $validFields
    ]);

$user->getTPL()->assign($myvals);
return $user->getTPL()->draw('base/userslist', true);
