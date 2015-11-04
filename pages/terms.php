<?php
$lang = $user->getLanguage();

$vals = [];
$vals['terms_n'] = file_get_contents("{$_SERVER['DOCUMENT_ROOT']}/tpl/{$user->getTemplate()}/langs/{$lang}/terms.html");
$user->getTPL()->assign($vals);
$user->getTPL()->draw('base/terms');
