<?php
    ob_start('ob_gzhandler');
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
    
    $core = new NERDZ\Core\Core();
    $tplcfg = $core->getTemplateCfg();

    ob_start(array('NERDZ\\Core\\Core','minifyHTML'));
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="description" content="NERDZ is a mix between a social network and a forum. You can share your code, enjoy information technology, talk about nerd stuff and more. Join in!" />
        <title><?= $core->getSiteName(), ' - ', $core->lang('TERMS') ?></title>
<?php
    $headers = $tplcfg->getTemplateVars('terms');
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/jscssheaders.php';
?>
    </head>
<?php ob_flush(); ?>
    <body>
        <div id="body">
<?php
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/header.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/terms.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/footer.php';
?>
        </div>
    </body>
</html>
