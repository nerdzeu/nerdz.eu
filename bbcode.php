<?php
    ob_start('ob_gzhandler');
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
    
    $core = new NERDZ\Core\Core();
    $tplcfg = $core->getTemplateCfg();
    
    ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta name="author" content="Paolo Galeone" />
        <meta name="keywords" content="nerdz, social network, user profile, paste, source code, programming" />
        <meta name="description" content="NERDZ is a mix between a social network and a forum. You can share your code, enjoy information technology, talk about nerd stuff and more. Join in!" />
        <meta name="robots" content="index,follow" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title><?= $core->getSiteName(); ?> - BBCode</title>
<?php
    $headers = $tplcfg->getTemplateVars('bbcode');
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/jscssheaders.php';
    ob_flush();
?>
    </head>
<body>
    <div id="body">
<?php
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/header.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/bbcode.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/footer.php';
?>
    </div>
</body>
</html>
