<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Utils;
use NERDZ\Core\Config;
use NERDZ\Core\Db;

$ret = [];
$vals = [];

$cache = 'nerdz_stats'.Config\SITE_HOST;

function createArray(&$user, &$ret, $query, $position) {

    if(!($o = Db::query($query, Db::FETCH_OBJ)))
        $ret[$position] = -1;
    else
        $ret[$position] = $o->cc;
}

if(apc_exists('nerdz_stats'))
    $ret = unserialize(apc_fetch('nerdz_stats'));
else
{
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
    $user = new NERDZ\Core\User();

    $queries = [
        0 => 'SELECT COUNT(counter) AS cc FROM users',
            1 => 'SELECT COUNT(hpid)    AS cc FROM posts',
            2 => 'SELECT COUNT(hcid)    AS cc FROM comments',
            3 => 'SELECT COUNT(counter) AS cc FROM groups',
            4 => 'SELECT COUNT(hpid)    AS cc FROM groups_posts',
            5 => 'SELECT COUNT(hcid)    AS cc FROM groups_comments',
            6 => 'SELECT COUNT(counter) AS cc FROM users WHERE last > (NOW() - INTERVAL \'4 MINUTES\') AND viewonline IS TRUE',
            7 => 'SELECT COUNT(counter) AS cc FROM users WHERE last > (NOW() - INTERVAL \'4 MINUTES\') AND viewonline IS FALSE',
        ];
    foreach($queries as $position => $query) {
        createArray($user, $ret, $query, $position);
    }
    @apc_store($cache,serialize($ret),900); //15 min
}

$vals['totusers_n']             = $ret[0];
$vals['totpostsprofiles_n']     = $ret[1];
$vals['totcommentsprofiles_n']  = $ret[2];
$vals['totprojects_n']          = $ret[3];
$vals['totpostsprojects_n']     = $ret[4];
$vals['totcommentsprojects_n']  = $ret[5];
$vals['totonlineusers_n']       = $ret[6];
$vals['tothiddenusers_n']       = $ret[7];
$vals['lastupdate_n']           = $user->getDateTime(Utils::apc_getLastModified($cache));

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';

$user->getTPL()->assign($vals);

if(isset($draw))
    $user->getTPL()->draw('base/stats');
?>
