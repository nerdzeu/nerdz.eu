<?php
$lang = $user->getLanguage();

$vals = [];
$vals['bbcode_n'] = file_get_contents("{$_SERVER['DOCUMENT_ROOT']}/tpl/{$user->getTemplate()}/langs/{$lang}/bbcode.html");
$user->getTPL()->assign($vals);
$user->getTPL()->draw('base/bbcode');
