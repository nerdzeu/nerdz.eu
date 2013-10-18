<?php
    ob_start('ob_gzhandler');
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/templatecfg.class.php';
    
    $core = new phpCore();
    $tplcfg = new templateCfg($core);
    
    ob_start(array('phpCore','minifyHtml'));
    $core->getTPL()->configure('tpl_dir',$_SERVER['DOCUMENT_ROOT'].'/tpl/0/');
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta name="author" content="Paolo Galeone" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>NERDZ - <?php echo $core->lang('PREFERENCES') ?></title>
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
