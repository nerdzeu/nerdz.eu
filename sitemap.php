<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
use NERDZ\Core\Config;
use NERDZ\Core\User;
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

header('Content-type: application/xml');
$sitemap = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR .'sitemap.xml');

die(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off'
    ? str_replace('http://'.Config\SITE_HOST, 'https://'.Config\HTTPS_DOMAIN, $sitemap)
    : (
          (new User())->isOnMobileHost()
          ? str_replace('http://'.Config\SITE_HOST, 'http://'.Config\MOBILE_HOST, $sitemap)
          : $sitemap
      )
);
?>
