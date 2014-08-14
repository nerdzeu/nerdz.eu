<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

$user = new NERDZ\Core\User();
$tplcfg = $user->getTemplateCfg();

ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta name="author" content="Paolo Galeone" />
    <meta name="keywords" content="nerdz, social network, user profile, paste, source code, programming" />
    <meta name="description" content="NERDZ is a mix between a social network and a forum. You can share your code, enjoy information technology, talk about nerd stuff and more. Join in!" />
    <meta name="robots" content="index,follow" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?= NERDZ\Core\Utils::getSiteName(); ?> - F.A.Q.</title>
<?php
$headers = $tplcfg->getTemplateVars('faq');
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/jscssheaders.php';
?>
    </head>
    <?php ob_flush(); ?>
<body>
    <div id="body">
<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/header.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/faq.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/footer.php';
?>
    </div>
    </body>
    </html>
