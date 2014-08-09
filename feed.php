<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

ob_start('ob_gzhandler');
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));
header('Content-type: application/rss+xml');

$feed = new NERDZ\Core\Feed();

if(isset($_GET['id']) && is_numeric($_GET['id']) && !isset($_GET['project']))
    echo $feed->getProfileFeed($_GET['id']);

elseif(isset($_GET['id']) && is_numeric($_GET['id']) && isset($_GET['project']))
    echo $feed->getProjectFeed($_GET['id']);

elseif(!isset($_GET['id']) && !isset($_GET['project']))
    echo $feed->getHomeProfileFeed();

elseif(!isset($_GET['id']) && isset($_GET['project']))
    echo $feed->getHomeProjectFeed();
else
    echo $feed->error('Wrong GET parameters');
?>
