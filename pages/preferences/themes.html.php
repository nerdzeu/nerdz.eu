<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
$core = new Core();
ob_start(array('Core','minifyHtml'));

if(!$core->isLogged())
    die($core->lang('REGISTER'));
    
$vals = [];
$vals['tok_n'] = $core->getCsrfToken('edit');

$vals['themes_a'] = [];
$i = 0;
$templates = $core->getAvailableTemplates();

foreach($templates as $val)
{
    $vals['themes_a'][$i]['tplno_n'] = $val['number'];
    $vals['themes_a'][$i]['tplname_n'] = $val['name'];
    ++$i;
}
$vals['mytplno_n'] = $core->getTemplate($_SESSION['id']);
$vals['mobile_b'] = $_SERVER['HTTP_HOST'] == MOBILE_HOST;

$core->getTPL()->assign($vals);
$core->getTPL()->draw('preferences/themes');
?>
