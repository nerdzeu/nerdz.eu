<?php
    ob_start('ob_gzhandler');
    require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';

    if(isset($_GET['goto']) && ($_GET['goto'] == "mobile" || $_GET['goto'] == "desktop"))
    {
      $referer = (isset($_GET['ref']) && $_GET['ref']!="") ? str_replace('%2E', '.', $_GET['ref']) : '/';
      function extractRootDomain($str) {
        $x = explode('.',$str);
        $c = count($x);
        if($c <= 2)
          return $str;
        return $x[$c-2] . '.' . $x[$c-1];
      }
      $domain = extractRootDomain(SITE_HOST);
      setcookie('mobile-splash',$_GET['goto'],2000000000,'/',$domain,false,true);
      die(header('Location: http://'.($_GET['goto']=="mobile"?MOBILE_HOST:SITE_HOST).$referer));
    }

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

