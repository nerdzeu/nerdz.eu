<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/javascript.class.php';
ob_start('ob_gzhandler');
$filename = $_SERVER['DOCUMENT_ROOT'].'static/js/jclass.js';
header('Content-type: application/javascript');
header('Vary: Accept-Encoding'); //pagespeed di google docet
header('Etag: '.md5_file($filename));
die(Javascript::optimize($filename));
?>
