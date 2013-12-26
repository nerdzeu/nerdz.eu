<?php
    ob_start('ob_gzhandler');
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
    
    $core = new phpCore();
    $tplcfg = $core->getTemplateCfg();
    
    ob_start(array('phpCore','minifyHtml'));
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="author" content="Paolo Galeone" />
        <meta name="keywords" content="nerdz, social network, user profile, paste, source code, programming" />
        <meta name="robots" content="index,follow" />
        <title><?= $core->getSiteName(), ' - ', $core->lang('ERROR');?></title>
<?php
    $headers = $tplcfg->getTemplateVars('error');
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/jscssheaders.php';
?>
    </head>
<?php ob_flush(); ?>
    <body>
        <div id="body">
<?php
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/header.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/error.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/footer.php';
?>
        </div>
    </body>
</html>
