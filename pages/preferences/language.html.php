<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
$core = new phpCore();
ob_start(array('phpCore','minifyHtml'));

if(!$core->isLogged())
    die($core->lang('REGISTER'));
    
$vals = [];
$vals['tok_n'] = $core->getCsrfToken('edit');
$longlangs  = $core->availableLanguages(1);

$vals['langs_a'] = [];
$i = 0;
foreach($longlangs as $id => $val)
{
    $vals['langs_a'][$i]['longlang_n'] = $val;
    $vals['langs_a'][$i]['shortlang_n'] = $id;
    ++$i;
}
$vals['mylang_n'] = $core->getUserLanguage($_SESSION['nerdz_id']);
$vals['myboardlang_n'] = $core->getBoardLanguage($_SESSION['nerdz_id']);

$core->getTPL()->assign($vals);
$core->getTPL()->draw('preferences/language');
?>
