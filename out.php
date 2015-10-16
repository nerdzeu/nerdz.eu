<?php
/* out.php is used for avoid tabnabbing attacks*/
if(empty($_GET['url'])) {
    die(header('Location: /'));
}


require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\User;
use NERDZ\Core\Config;
use NERDZ\Core\Utils;

$user = new User();
$tplcfg = $user->getTemplateCfg();

if($user->isLogged()) {
    // TODO: collect stats
}

$url  = trim(html_entity_decode($_GET['url'],ENT_QUOTES, 'UTF-8'));
$hmac = !empty($_GET['hmac']) ? Utils::getHMAC($url, Config\CAMO_KEY) === $_GET['hmac'] : false;
if($hmac) {
    die(header("Location: {$url}"));
}
