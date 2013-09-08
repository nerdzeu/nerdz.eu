<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
$tpl->configure('tpl_dir',$_SERVER['DOCUMENT_ROOT'].'/tpl/0/');
ob_start(array('phpCore','minifyHtml'));

$core = new phpCore();
if(!$core->isLogged())
	die($core->lang('REGISTER'));

if(!($obj = $core->query(array('SELECT * FROM "users" WHERE "counter" = ?',array($_SESSION['nerdz_id'])),db::FETCH_OBJ)))
    die($core->lang('ERROR'));
    
$vals = array();
$vals['description'] = $core->lang('PREFERENCES_DESCR');
$vals['username'] = $core->lang('USERNAME');
$vals['username_n'] = $obj->username;
$vals['name'] = $core->lang('NAME');
$vals['name_n'] = $obj->name;
$vals['surname'] = $core->lang('SURNAME');
$vals['surname_n'] = $obj->surname;
$vals['timezone_n'] = $obj->timezone;
$vals['gender'] = $core->lang('GENDER');
$vals['male'] = $core->lang('MALE');
$vals['female'] = $core->lang('FEMALE');
$vals['gender_n'] = $obj->gender == 1 ? $vals['male'] : $vals['female'];
$vals['email'] = $core->lang('EMAIL');
$vals['email_n'] = $obj->email;
$vals['birthdate'] = $core->lang('BIRTH_DATE');
$vals['day'] = $core->lang('DAY');
$vals['timezone'] = 'Time zone';

$now = date('o');
$vals['years_a'] = array_reverse(range($now-100,$now-1));
$vals['months_a'] = range(1,12);
$vals['days_a'] = range(1,31);
$date = explode('-',$obj->birth_date);
$vals['year_n'] = $date[0];
$vals['month_n'] = $date[1];
$vals['day_n'] = $date[2];

$vals['timezones_a'] = DateTimeZone::listIdentifiers();

$vals['month'] = $core->lang('MONTH');
$vals['year'] = $core->lang('YEAR');
$vals['password'] =  $core->lang('PASSWORD');
$vals['emptypass'] = $core->lang('LEAVE_EMPTY_PASS');
$vals['edit'] = $core->lang('EDIT');
$vals['tok_n'] = $core->getCsrfToken('edit');

$tpl->assign($vals);
$tpl->draw('preferences/account');

?>
