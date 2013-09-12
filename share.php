<?php
    ob_start('ob_gzhandler');
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/templatecfg.class.php';
    
    $core = new phpCore();
    $tplcfg = new templateCfg();
    
    ob_start(array('phpCore','minifyHtml'));
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta name="author" content="Paolo Galeone" />
        <meta name="keywords" content="nerdz, share system" />
        <meta name="description" content="nerdz share system, tha share web pages from other sites" />
        <meta name="robots" content="index,follow" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>NERDZ <?php echo $core->lang('SHARE');?></title>
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
