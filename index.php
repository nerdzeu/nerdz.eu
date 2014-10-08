<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\User;

$user = new NERDZ\Core\User();
$tplcfg = $user->getTemplateCfg();

if($user->isLogged())
    die(header('Location: home.php'));

ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

?>
    <!DOCTYPE html>
    <html lang="<?php echo $user->getBoardLanguage();?>">
    <head>
    <meta name="author" content="Paolo Galeone" />
    <meta name="keywords" content="nerdz, social network, user profile, paste, source code, programming" />
    <meta name="description" content="NERDZ is a mix between a social network and a forum. You can share your code, enjoy information technology, talk about nerd stuff and more. Join in!" />
    <meta name="robots" content="index,follow" />
    <meta name="google-site-verification" content="dRirpMHbSmUiPDrNohR5kmUyUnrii1fkWmTADXmksQY" />
    <title><?= NERDZ\Core\Utils::getSiteName(); ?></title>
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
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/register.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/footer.php';
?>
    </div>
    </body>
    </html>
