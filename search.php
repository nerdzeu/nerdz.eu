<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\User;

$user = new NERDZ\Core\User();
$tplcfg = $user->getTemplateCfg();
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));
$q = !empty($_GET['q']) ? trim(htmlspecialchars($_GET['q'], ENT_QUOTES,'UTF-8')) : '';
?>
    <!DOCTYPE html>
    <html lang="<?php echo $user->getBoardLanguage();?>">
    <head>
    <meta name="author" content="Paolo Galeone" />
    <title><?=NERDZ\Core\Utils::getSiteName(); ?> - Search <?php
    if(!empty($q)) echo '⇒ ', $q;?></title>
<?php
$headers = $tplcfg->getTemplateVars('search');
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/jscssheaders.php';
?>
    </head>
    <?php ob_flush(); ?>
<body>
    <div id="body">
<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/header.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/search.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/footer.php';
?>
    </div>
    </body>
    </html>
