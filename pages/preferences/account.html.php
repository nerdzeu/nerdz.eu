<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
$core = new Core();
ob_start(array('NERDZ\\Core\\Core','minifyHtml'));

if(!$core->isLogged())
    die($core->lang('REGISTER'));

if(!($obj = $core->query(array('SELECT * FROM "users" WHERE "counter" = ?',array($_SESSION['id'])),Db::FETCH_OBJ)))
    die($core->lang('ERROR'));
    
$vals = [];
$vals['username_n'] = $obj->username;
$vals['name_n'] = $obj->name;
$vals['surname_n'] = $obj->surname;
$vals['timezone_n'] = $obj->timezone;
$vals['ismale_b'] = $obj->gender == 1;
$vals['email_n'] = $obj->email;
$now = date('o');
$vals['years_a'] = array_reverse(range($now-100,$now-1));
$vals['months_a'] = range(1,12);
$vals['days_a'] = range(1,31);
$date = explode('-',$obj->birth_date);
$vals['year_n'] = $date[0];
$vals['month_n'] = $date[1];
$vals['day_n'] = $date[2];
$vals['timezones_a'] = DateTimeZone::listIdentifiers();
$vals['tok_n'] = $core->getCsrfToken('edit');

$core->getTPL()->assign($vals);
$core->getTPL()->draw('preferences/account');

?>
