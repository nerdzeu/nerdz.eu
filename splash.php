<?php
    $referer = $_SESSION['referer'];
    if(isset($_REQUEST["mobile"]))
    {
      unset($_SESSION["referer"]);
      setcookie("mobile-splash","mobile",2000000000,"/","nerdz.eu",false,true);
      header("location: http://mobile.nerdz.eu".$referer);
    }
    if(isset($_REQUEST["desktop"]))
    {
      unset($_SESSION["referer"]);
      setcookie("mobile-splash","desktop",2000000000,"/","nerdz.eu",false,true);
      if(!$referer||$referer==$_SERVER["PHP_SELF"])
        header("location: /");
      else
        header("location: /".$referer);
    }
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
        <meta name="description" content="NERDZ is a mix between a social network and a forum. You can share your code, enjoy information technology, talk about nerd stuff and more. Join in!" />
        <meta name="robots" content="index,follow" />
        <meta name="google-site-verification" content="dRirpMHbSmUiPDrNohR5kmUyUnrii1fkWmTADXmksQY" />
        <title><?= $core->getSiteName(); ?> - Mobile Redirect</title>
<?php
    $headers = $tplcfg->getTemplateVars('index');
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/jscssheaders.php';
?>
    </head>
<?php ob_flush(); ?>
    <body>
        <div id="body">
<?php
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/header.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/splash.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/pages/footer.php';
?>
        </div>
    </body>
</html>

