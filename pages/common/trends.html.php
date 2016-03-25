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
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Trend;
use NERDZ\Core\Utils;
use NERDZ\Core\Config;

if(!isset($user)) die('$user required');

$func = function() use ($user) {
    $vals = [];
    $cache = 'trends'.Config\SITE_HOST;
    if(!($trends = Utils::apc_get($cache)))
        $trends = Utils::apc_set($cache, function() {
            $trend = new Trend();
            $ret = [];
            $ret['popular'] = $trend->getPopular();
            $ret['newest']  = $trend->getNewest();
            return $ret;
        },300);

    $vals['popular_a'] = $trends['popular'];
    $vals['newest_a']  = $trends['newest'];
    $user->getTPL()->assign($vals);
};

$func();
