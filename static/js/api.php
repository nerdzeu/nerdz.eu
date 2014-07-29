<?php
// ob_gzhandler should always be on the top, otherwise
// compression errors may happen
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/minification.class.php';
$filename = $_SERVER['DOCUMENT_ROOT'].'/static/js/jclass.js';
header('Content-type: application/javascript');
header('Vary: Accept-Encoding'); //pagespeed di google docet
header('Etag: '.md5_file($filename));
die(\NERDZ\Config\MINIFICATION_ENABLED ? Minification::minifyJs ($filename) : file_get_contents ($filename));
?>
