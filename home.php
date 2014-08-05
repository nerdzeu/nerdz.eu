<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\Core;

$core = new NERDZ\Core\Core();
$tplcfg = $core->getTemplateCfg();
ob_start(array('NERDZ\Core\Core','minifyHtml'));
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta name="author" content="Paolo Galeone" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title><?=$core->getSiteName(); ?> - Homepage</title>
<?php
    $headers = $tplcfg->getTemplateVars('home');
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/jscssheaders.php';
?>
    </head>
<?php ob_flush(); ?>
<body>
    <div id="body">
<?php
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/header.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/main.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/footer.php';
?>
    </div>
</body>
</html>
