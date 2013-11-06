<?php
//TEMPLATE: OK
$lang = $core->getBrowserLanguage();
$presentation = file_get_contents($_SERVER['DOCUMENT_ROOT']."/data/presentation/{$lang}.txt");
$presentation = nl2br(htmlentities($presentation,ENT_QUOTES,'UTF-8'));

$vals = array();
$vals['presentation_n'] = $presentation;
$vals['username'] = $core->lang('USERNAME');
$vals['name'] = $core->lang('NAME');
$vals['surname'] = $core->lang('SURNAME');
$vals['email'] = $core->lang('EMAIL');
$vals['password'] = $core->lang('PASSWORD');
$vals['gender'] = $core->lang('GENDER');
$vals['male'] = $core->lang('MALE');
$vals['female'] = $core->lang('FEMALE');
$vals['birthdate'] = $core->lang('BIRTH_DATE');
$vals['month'] = $core->lang('MONTH');
$vals['year'] = $core->lang('YEAR');
$vals['day'] = $core->lang('DAY');
$vals['captcha'] = $core->lang('CAPTCHA');
$vals['captchaurl_n'] = '/static/images/captcha.php';
$vals['reloadcaptcha'] = $core->lang('RELOAD_CAPTCHA');
$vals['register'] = $core->lang('REGISTER');
$vals['timezone'] = 'Time zone';

    $vals['remember'] = $core->lang('REMEMBER_ME');
    $vals['forgot'] = $core->lang('FORGOT_PASSWORD');
    $vals['hidestatus'] = $core->lang('HIDE_STATUS');
    $vals['username'] = $core->lang('USERNAME');
    $vals['login'] = $core->lang('LOGIN');
    
$now = intval(date('o'));

$vals['years_a'] = range($now-100,$now-1);
$vals['years_a'] = array_reverse($vals['years_a']);

$vals['months_a'] = range(1,12);
$vals['days_a'] = range(1,31);

$vals['timezones_a'] = DateTimeZone::listIdentifiers();

if(!isset($included))
{
    $core->getTPL()->assign($vals);
    $core->getTPL()->draw('base/register');
}
?>
