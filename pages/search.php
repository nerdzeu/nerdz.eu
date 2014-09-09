<?php
use NERDZ\Core\Trend;

$vals = [];
$vals['querystring_n'] = $q;
$trend = new Trend();

$vals['popular_a'] = $trend->getPopular();
$vals['newest_a']  = $trend->getNewest();

$user->getTPL()->assign($vals);
$user->getTPL()->draw('search/layout');
?>
