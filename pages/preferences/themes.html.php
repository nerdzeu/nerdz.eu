<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
$core = new phpCore();
ob_start(array('phpCore','minifyHtml'));

if(!$core->isLogged())
    die($core->lang('REGISTER'));
    
$vals = array();
$vals['tok_n'] = $core->getCsrfToken('edit');

$vals['themes_a'] = array();
$i = 0;
$templates = $core->getAvailableTemplates();

foreach($templates as $val)
{
    $vals['themes_a'][$i]['tplno_n'] = $val['number'];
    $vals['themes_a'][$i]['tplname_n'] = $val['name'];
    ++$i;
}
$vals['mytplno_n'] = $core->getTemplate($_SESSION['nerdz_id']);

$core->getTPL()->assign($vals);
$core->getTPL()->draw('preferences/themes');
?>
