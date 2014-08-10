<?php
$lang = $user->getBrowserLanguage();
$presentation = file_get_contents($_SERVER['DOCUMENT_ROOT']."/data/presentation/{$lang}.txt");
$presentation = nl2br(htmlspecialchars($presentation,ENT_QUOTES,'UTF-8'));

$vals = [];
$vals['presentation_n'] = $presentation;
$vals['captchaurl_n'] = '/static/images/captcha.php';
$vals['tok_n'] = $user->getCsrfToken();

$now = intval(date('o'));

$vals['years_a'] = range($now-100,$now-1);
$vals['years_a'] = array_reverse($vals['years_a']);

$vals['months_a'] = range(1,12);
$vals['days_a'] = range(1,31);

$vals['timezones_a'] = DateTimeZone::listIdentifiers();

if(!isset($included))
{
    $user->getTPL()->assign($vals);
    $user->getTPL()->draw('base/register');
}
?>
