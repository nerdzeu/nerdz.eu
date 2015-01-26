<?php
$lang = $user->getLanguage();

$vals = [];
$vals['faq_n'] = file_get_contents("{$_SERVER['DOCUMENT_ROOT']}/tpl/{$user->getTemplate()}/langs/{$lang}/faq.html");

require_once $_SERVER['DOCUMENT_ROOT'].'/pages/common/vars.php';
$user->getTPL()->assign($vals);
$user->getTPL()->draw('base/faq');
