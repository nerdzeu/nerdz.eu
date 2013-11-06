<?php
    ob_start('ob_gzhandler');
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/templatecfg.class.php';
    
    $core = new phpCore();
    $tplcfg = new templateCfg($core);
    
    ob_start(array('phpCore','minifyHtml'));
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="description" content="NERDZ is a mix between a social network and a forum. You can share your code, enjoy information technology, talk about nerd stuff and more. Join in!" />
        <title><?= $core->getSiteName(); ?> - <?php echo $core->lang('STATS');?></title>
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
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/stats.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/footer.php';
?>
        </div>
    </body>
</html>
