<?php
  $cachedir = "./tmp/twitter_cache/";
  $cacheage = 86400;
  
  function jsonResponse($message) {
    header('Content-type: application/json');
    return $message;
  }
  if(!file_exists($cachedir))
    if(! mkdir($cachedir) )
      die(jsonResponse('{"errors":[{"message":"I/O Error", "code": -1}]}'));
  if(!isset($_GET['twit']) || empty($_GET['twit']))
    die(jsonResponse('{"errors":[{"message":"No Tweet Specified", "code":0}]}'));
  $ID = strip_tags(urldecode($_GET['twit']));
  if(!preg_match("#^\d+$#", $ID)) {
    $j = parse_url($ID);
    $ID = end(explode("/",$j['path']));
    if(!preg_match("#^\d+$#", $ID))
      die(jsonResponse('{"errors":[{"message":"Invalid ID", "code": 1}]}'));
  }
  $file = $cachedir.$ID.".json";
    if(file_exists($file)) {
    if(time() - filemtime($file)>$cacheage)
      unlink($file);
    else
      die(jsonResponse(file_get_contents($file)));
  }
  $url = "https://api.twitter.com/1/statuses/oembed.json?id=$ID";
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $json = curl_exec($ch);
  file_put_contents($file, $json);
  die(jsonResponse($json));
?>
