<?php
    ob_start('ob_gzhandler');
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
    
    $user = new NERDZ\Core\Messages();
    if(!$user->isLogged())
        die(header('Location: /'));

    $tplcfg = $user->getTemplateCfg();
    
    ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="author" content="Paolo Galeone" />
    <meta name="keywords" content="nerdz, social network, user profile, paste, source code, programming" />
    <meta name="robots" content="index,follow" />
    <title><?= NERDZ\Core\Utils::getSiteName(); ?> - Bookmarks </title>
<?php
    $headers = $tplcfg->getTemplateVars('bookmarks');
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/jscssheaders.php';
?>
</head>
<?php ob_flush(); ?>
<body>
<div id="body">
<?php
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/header.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/bookmarks.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/footer.php';
?>
</div>
</body>
</html>
