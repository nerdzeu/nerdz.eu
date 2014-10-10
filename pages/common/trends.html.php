<?php
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
?>
