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
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?= NERDZ\Core\Utils::getSiteName(), ' - ', $user->lang('PREFERENCES') ?></title>
<?php
$headers = $tplcfg->getTemplateVars('preferences');
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/jscssheaders.php';
?>
    </head>
    <?php ob_flush(); ?>
<body>
    <div id="body">
<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/header.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/preferences/main.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/footer.php';
?>
    </div>
    </body>
    </html>
