<?php
// ob_gzhandler should always be on the top, otherwise
// compression errors may happen
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Minification;
use NERDZ\Core\Config;

$filename = $_SERVER['DOCUMENT_ROOT'].'/static/js/jclass.js';
header('Content-type: application/javascript');
header('Vary: Accept-Encoding');
header('Etag: '.md5_file($filename));

die(Config\MINIFICATION_ENABLED ? Minification::minifyJs($filename) : file_get_contents($filename));
