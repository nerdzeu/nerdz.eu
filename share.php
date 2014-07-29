<?php
    ob_start('ob_gzhandler');
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
        
    $core = new Core();
    $tplcfg = $core->getTemplateCfg();
        
    ob_start(array('Core','minifyHtml'));
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta name="author" content="Paolo Galeone" />
        <meta name="keywords" content="nerdz, share system" />
        <meta name="description" content="nerdz share system, tha share web pages from other sites" />
        <meta name="robots" content="index,follow" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title><?= $core->getSiteName(), ' - ', $core->lang('SHARE');?></title>
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
