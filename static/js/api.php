<?php
// ob_gzhandler should always be on the top, otherwise
// compression errors may happen
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Minification;
use NERDZ\Core\Config;

$filename = $_SERVER['DOCUMENT_ROOT'].'/static/js/jclass.js';
$tmpFile  = $_SERVER['DOCUMENT_ROOT'].'/tmp/japi.js';
$tmpFileTime = $_SERVER['DOCUMENT_ROOT'].'/tmp/japi.js.time';

$updateTime = 0;

if(!file_exists ($tmpFileTime) ||
    intval(file_get_contents($tmpFileTime)) < ($updateTime = filemtime($filename))) {
    $content = Minification::minifyJs($filename);
    file_put_contents($tmpFile, $content);
    file_put_contents($tmpFileTime, $updateTime);
    chmod($tmpFile, 0775);
    chmod($tmpFileTime, 0775);
}

$content = file_get_contents($tmpFile);

header('Content-type: application/javascript');
header('Vary: Accept-Encoding');
header('Etag: '.md5_file($filename));


die($content);
