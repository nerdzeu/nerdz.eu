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
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="description" content="NERDZ is a mix between a social network and a forum. You can share your code, enjoy information technology, talk about nerd stuff and more. Join in!" />
    <title><?= NERDZ\Core\Utils::getSiteName(), ' - ', $user->lang('STATS');?></title>
<?php
$headers = $tplcfg->getTemplateVars('stats');
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/jscssheaders.php';
?>
    </head>
    <?php ob_flush(); ?>
<body>
    <div id="body">
<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/header.php';
$draw = true;
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/stats.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/footer.php';
?>
    </div>
    </body>
    </html>
