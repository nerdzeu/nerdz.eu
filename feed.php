<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/feed.class.php';
ob_start('ob_gzhandler');
ob_start(array('phpCore','minifyHtml'));
header('Content-type: application/rss+xml');

$feed = new feed();

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
