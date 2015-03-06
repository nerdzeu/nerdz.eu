<?php
// ob_gzhandler should always be on the top, otherwise
// compression errors may happen
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Minification;
use NERDZ\Core\Config;

$filename = $_SERVER['DOCUMENT_ROOT'].'/static/js/jclass.js';
$tmpFile = $_SERVER['DOCUMENT_ROOT'].'/tmp/japi.js';

header('Content-type: application/javascript');
header('Vary: Accept-Encoding');
header('Etag: '.md5_file($filename));

if(is_readable($tmpFile))
    $content = file_get_contents($tmpFile);
else {
    $content = Minification::minifyJs($filename);
    file_put_contents($tmpFile, $content);
    chmod ($tmpFile, 0775);
}
die($content);
