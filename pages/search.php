<?php
use NERDZ\Core\Trend;
use NERDZ\Core\Utils;
use NERDZ\Core\Config;

$vals = [];
$vals['querystring_n'] = $q;
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
$user->getTPL()->draw('search/layout');
?>
