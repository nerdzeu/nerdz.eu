<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

$user = new NERDZ\Core\User();
$tplcfg = $user->getTemplateCfg();

ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));
?>
    <!DOCTYPE html>
    <html lang="<?php echo $user->getBoardLanguage();?>">
    <head>
    <meta name="author" content="Paolo Galeone" />
    <meta name="keywords" content="nerdz, share system" />
    <meta name="description" content="nerdz share system, tha share web pages from other sites" />
    <meta name="robots" content="index,follow" />
    <title><?= NERDZ\Core\Utils::getSiteName(), ' - ', $user->lang('SHARE');?></title>
<?php
$headers = $tplcfg->getTemplateVars('share');
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/jscssheaders.php';
?>
    </head>
    <?php ob_flush(); ?>
<body>
    <div id="body">
<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/share.php';
?>
    </div>
    </body>
    </html>
