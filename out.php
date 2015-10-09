<?php
/* out.php is used for avoid tabnabbing attacks*/
if(empty($_GET['url']))
    die(header('Location: /'));

$url = trim($_GET['url']);

ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';

use NERDZ\Core\User;

$user = new User();
$tplcfg = $user->getTemplateCfg();

if($user->isLogged()) {
    // TODO: collect stats
}
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));
?>
    <!DOCTYPE html>
    <html>
    <head>
    <title><?= NERDZ\Core\Utils::getSiteName(); ?></title>
<?php
$headers = $tplcfg->getTemplateVars('out');
require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/jscssheaders.php';
?>
    <meta http-equiv="refresh" content="0;URL='<?= addslashes(str_replace('"','',$url)); ?>'" />    
    </head>
    <?php ob_flush(); ?>
<body>
    <div id="body">
        <h2 style="width:100%;text-align:center"><?= $user->lang('WAIT'); ?></h2>
    </div>
</body>
</html>
