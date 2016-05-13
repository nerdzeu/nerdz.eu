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
use NERDZ\Core\Utils;
use NERDZ\Core\Config;
use NERDZ\Core\Db;

$user = new NERDZ\Core\User();
$vals = [];

$cache = 'nerdz_stats'.Config\SITE_HOST;
if (!($ret = Utils::apcu_get($cache))) {
    $ret = Utils::apcu_set($cache, function () use ($cache) {
        function createArray(&$ret, $query, $position)
        {
            if (!($o = Db::query($query, Db::FETCH_OBJ))) {
                $ret[$position] = -1;
            } else {
                $ret[$position] = $o->cc;
            }
        };

        $queries = [
            0 => 'SELECT COUNT(counter)     AS cc FROM users',
            1 => 'SELECT COUNT(hpid)        AS cc FROM posts',
            2 => 'SELECT COUNT(hcid)        AS cc FROM comments',
            3 => 'SELECT COUNT(counter)     AS cc FROM groups',
            4 => 'SELECT COUNT(hpid)        AS cc FROM groups_posts',
            5 => 'SELECT COUNT(hcid)        AS cc FROM groups_comments',
            6 => 'SELECT COUNT(counter)     AS cc FROM users  WHERE last > (NOW() - INTERVAL \'4 MINUTES\') AND viewonline IS TRUE',
            7 => 'SELECT COUNT(counter)     AS cc FROM users  WHERE last > (NOW() - INTERVAL \'4 MINUTES\') AND viewonline IS FALSE',
            8 => 'SELECT COUNT(remote_addr) AS cc FROM guests WHERE last > (NOW() - INTERVAL \'4 MINUTES\')',
        ];

        foreach ($queries as $position => $query) {
            createArray($ret, $query, $position);
        }

        if (!($bots = Utils::apcu_get($cache.'bots'))) {
            $bots = Utils::apcu_set($cache.'bots', function () {
                $txt = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/data/bots.json');

                return json_decode(preg_replace('#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t](//).*)#', '', $txt), true);
            }, 86400);
        }

        $ret[9] = 0;
        $ret[10] = [];
        if (($uas = Db::query('SELECT http_user_agent FROM guests WHERE last > (NOW() - INTERVAL \'4 MINUTES\')', DB::FETCH_OBJ, true))) {
            foreach ($uas as $ua) {
                foreach ($bots as $bot) {
                    if (preg_match('#'.$bot['regex'].'#', $ua->http_user_agent)) {
                        $ret[10][$ret[9]]['name_n'] = $bot['name'];
                        ++$ret[9];
                        break;
                    }
                }
            }
        }

        return $ret;
    }, 900);
}

$vals['totusers_n'] = $ret[0];
$vals['totpostsprofiles_n'] = $ret[1];
$vals['totcommentsprofiles_n'] = $ret[2];
$vals['totprojects_n'] = $ret[3];
$vals['totpostsprojects_n'] = $ret[4];
$vals['totcommentsprojects_n'] = $ret[5];
$vals['totonlineusers_n'] = $ret[6];
$vals['tothiddenusers_n'] = $ret[7];
$vals['totonlineguests_n'] = $ret[8] - $ret[9];
$vals['totonlinebots_n'] = $ret[9];
$vals['bots_a'] = $ret[10];
$vals['lastupdate_n'] = $user->getDate(Utils::apcu_getLastModified($cache));

$user->getTPL()->assign($vals);

if (isset($draw)) {
    $user->getTPL()->draw('base/stats');
}
