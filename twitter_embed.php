<?php
header('Content-type: application/json');

$cachedir = "./tmp/twitter_cache/";
$cacheage = 86400;

if(!file_exists($cachedir) && ! mkdir($cachedir))
    die('{"errors":[{"message":"I/O Error", "code": -1}]}');

if(!isset($_GET['twit']) || empty($_GET['twit']))
    die('{"errors":[{"message":"No Tweet Specified", "code":0}]}');

$ID = strip_tags(urldecode($_GET['twit']));
if(!preg_match('#^\d+$#', $ID)) {
    $j = parse_url($ID);
    $tmpID = explode('/',$j['path']);
    $ID = end($tmpID);

    if(!preg_match('#^\d+$#', $ID))
        die('{"errors":[{"message":"Invalid ID", "code": 1}]}');
}

$file = "{$cachedir}{$ID}.json";
if(file_exists($file)) {
    if(time() - filemtime($file)>$cacheage)
        unlink($file);
    else
        die(file_get_contents($file));
}

$url = "https://api.twitter.com/1/statuses/oembed.json?id={$ID}";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$json = curl_exec($ch);
file_put_contents($file, $json);
die($json);
?>
