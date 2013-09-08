<?php
//TEMPLATE: OK
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/core.class.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/class/captcha.class.php';
$core = new phpCore();
$cptcka = new Captcha();
$captcha = isset($_POST['captcha']) ? $_POST['captcha'] : false;
if(!$captcha)
    die($core->jsonResponse('error',$core->lang('MISSING').': '.$core->lang('CAPTCHA')));

if(!$cptcka->check($captcha))
    die($core->jsonResponse('error',$core->lang('WRONG_CAPTCHA')));	

$l = "\x00\t\n\r\x0B \x7F\x81\x8D\x8F\x90\x9D\xA0\xAD";

$user['username'] = isset($_POST['username']) ? trim($_POST['username'],$l) : false;
$user['name']	  = isset($_POST['name'])     ? trim($_POST['name'],$l)		: false;
$user['surname']  = isset($_POST['surname'])  ? trim($_POST['surname'],$l)  : false;
$user['email']	  = isset($_POST['email'])    ? trim($_POST['email'],$l)    : false;
$user['timezone'] = isset($_POST['timezone']) ? trim($_POST['timezone'],$l) : false;
$user['password'] = isset($_POST['password']) ? $_POST['password'] 			: false;

$user['gender']	  		= isset($_POST['gender']) && is_numeric($_POST['gender']) && $_POST['gender'] >0 && $_POST['gender'] <= 2  	? $_POST['gender'] : false;
$birth['birth_day']		= isset($_POST['birth_day'])	&& is_numeric($_POST['birth_day'])   && $_POST['birth_day']  >0 ? $_POST['birth_day']  		   : false;
$birth['birth_month']	= isset($_POST['birth_month'])  && is_numeric($_POST['birth_month']) && $_POST['birth_month']>0 ? $_POST['birth_month'] 	   : false;
$birth['birth_year']	= isset($_POST['birth_year'])   && is_numeric($_POST['birth_year'])  && $_POST['birth_year'] >0 ? $_POST['birth_year'] 		   : false;

$user_flag  = !in_array(false,$user);
$birth_flag = !in_array(false,$birth);

if(!$user_flag||!$birth_flag)
{
    $msg = $core->lang('MUST_COMPLETE_FORM')."\n\n".$core->lang('MISSING').':';

    if(!$user_flag)
		foreach($user as $id => $val)
			if(!$val)
			{
				$msg.= "\n";
				switch($id)
				{
					case 'username':
						$msg.=$core->lang('USERNAME');
					break;
					case 'name':
						$msg.=$core->lang('NAME');
					break;
					case 'surname':
						$msg.=$core->lang('SURNAME');
					break;
					case 'password':
						$msg.=$core->lang('PASSWORD');
					break;
					case 'gender':
						$msg.=$core->lang('GENDER');
					break;
					case 'timezone':
						$msg.='Time zone';
					break;
				}
			}

    if(!$birth_flag)
		foreach($birth as $id => $val)
			if(!$val)
			{
				$msg.= "\n";
				switch($id)
				{
					case 'birth_day':
						$msg.=$core->lang('DAY');
					break;
					case 'birth_month':
						$msg.=$core->lang('MONTH');
					break;
					case 'birth_year':
						$msg.=$core->lang('YEAR');
					break;
				}
			}

    die($core->jsonResponse('error',$msg));
}

if($core->getUserId($user['username']))
	die($core->jsonResponse('error',$core->lang('USERNAME_EXISTS')));

foreach($user as $id => $value)
	if($id == 'password')
		$user[$id] = $value;
	else
		$user[$id] = htmlentities($value,ENT_QUOTES,'UTF-8');

//htmlentities empty return values FIX
if(count(array_filter($user)) != count($user))
	die($core->jsonResponse('error',$core->lang('ERROR').': INVALID UTF-8'));

if(isset($user['email'][350]))
    die($core->jsonResponse('error',$core->lang('MAIL_NOT_VALID')));
if(isset($user['name'][60]))
    die($core->jsonResponse('error',$core->lang('NAME_LONG')));
if(isset($user['surname'][60]))
    die($core->jsonResponse('error',$core->lang('SURNAME_LONG')));
if(isset($user['username'][30]))
    die($core->jsonResponse('error',$core->lang('USERNAME_LONG')));
	
if(isset($user['password'][300]))
    die($core->jsonResponse('error',$core->lang('PASSWORD_LONG')));

if(!in_array($user['timezone'],DateTimeZone::listIdentifiers()))
	die($core->jsonResponse('error',$core->lang('ERROR').': Time zone'));

if(!checkdate($birth['birth_month'],$birth['birth_day'],$birth['birth_year']))
    die($core->jsonResponse('error',$core->lang('DATE_NOT_VALID')));

$birth['date'] = $birth['birth_year'].'/'.$birth['birth_month'].'/'.$birth['birth_day'];

if(is_numeric($user['username']))
	die($core->jsonResponse('error',$core->lang('USERNAME_NUMBER')));

if($core->query(array('SELECT "counter" FROM "users" WHERE "email" = :email',array(':email' => $user['email'])),db::ROW_COUNT) > 0)
	die($core->jsonResponse('error',$core->lang('MAIL_EXISTS')."\n".$core->lang('FORGOT_PASSWORD').'?'));

if(preg_match('#^~#',$user['username']))
    die($core->jsonResponse('error',$core->lang('WRONG_USERNAME')));

if(is_numeric(strpos(html_entity_decode($user['username'],ENT_QUOTES,'UTF-8'),'#')))
    die($core->jsonResponse('error',$core->lang('WRONG_USERNAME').": {$user['username']}\n".$core->lang('CHAR_NOT_ALLOWED').': #'));

if(is_numeric(strpos($user['username'],'+')))
    die($core->jsonResponse('error',$core->lang('WRONG_USERNAME')."\n".$core->lang('CHAR_NOT_ALLOWED').': +'));

if(is_numeric(strpos($user['username'],'&amp;')))
    die($core->jsonResponse('error',$core->lang('WRONG_USERNAME')."\n".$core->lang('CHAR_NOT_ALLOWED').': &'));

if(is_numeric(strpos($user['username'],'%')))
    die($core->jsonResponse('error',$core->lang('WRONG_USERNAME')."\n".$core->lang('CHAR_NOT_ALLOWED').': %'));

if(isset($user['username'][MIN_LENGTH_USER]))
{
    if(isset($user['password'][MIN_LENGTH_PASS]))
    {
		if(isset($user['name'][MIN_LENGTH_NAME]))
		{
			if(isset($user['surname'][MIN_LENGTH_SURNAME]))
			{
				if(false !== filter_var($user['email'],FILTER_VALIDATE_EMAIL))
				{
					$lang = $core->getBrowserLanguage();
					//start transaction
					$db = $core->getDB();
					try
					{
						$db->beginTransaction();

						$par = array( ':username' => $user['username'],
							  ':password' => $user['password'],
							  ':name' => $user['name'],
							  ':surname' => $user['surname'],
							  ':email' => $user['email'],
							  ':gender' => $user['gender'],
							  ':date' => $birth['date'],
							  ':lang1' => $lang,
							  ':lang2' => $lang,
							  ':timezone' => $user['timezone']
							);

						$stmt = $db->prepare('INSERT INTO users ("username","password","name","surname","email","gender","birth_date","lang","board_lang","timezone")
						VALUES (:username,ENCODE(DIGEST(:password, \'SHA1\'), \'HEX\'), :name, :surname, :email, :gender,:date,:lang1,:lang2,:timezone)');

						$stmt->execute($par);

						$num_row = $db->exec('INSERT INTO profiles ("website", "quotes", "biography", "interests", "photo") VALUES ("","","","","")');

						$db->commit(); //end transaction
					}
					catch(PDOException $e)
					{
						$db->rollBack();
						$path = $_SERVER['DOCUMENT_ROOT'].'/data/errlog.txt';
                                                file_put_contents($path,$e->getMessage());
                                                chmod($path,0775);
						die($core->jsonResponse('error',$core->lang('ERROR').'1')); //fail transaction
					}
					
					$user = $user['username'];
					$pass = sha1($_POST['password']);
	
					if(!($o = $core->query(array('SELECT "counter" FROM "users" WHERE "username" = :user AND "password" = :pass',array(':user' => $user, ':pass' => $pass)),db::FETCH_OBJ)))
						die($core->jsonResponse('error',$core->lang('ERROR')));

					setcookie('nerdz_id',$o->counter,time()+60*60*24*30,'/','.'.SITE_HOST,false,true);
					setcookie('nerdz_u',md5($pass),time()+60*60*24*30,'/','.'.SITE_HOST,false,true);
					$_SESSION['nerdz_logged'] = true;
					$_SESSION['nerdz_id'] = $o->counter;
					$_SESSION['nerdz_username'] = $user;
					$_SESSION['nerdz_lang'] = $core->getUserLanguage($o->counter);
					$_SESSION['nerdz_board_lang'] = $core->getBoardLanguage($o->counter);
					//gravatar enabled by default
					//return value is useless, because user is just registred
					$core->query(array('INSERT INTO "gravatar_profiles"("counter") VALUES(?)',array($o->counter)),db::NO_RETURN);

					die($core->jsonResponse('ok',$core->lang('LOGIN_OK')));

				}
				else
					die($core->jsonResponse('error',$core->lang('MAIL_NOT_VALID')));
			}
			else
				die($core->jsonResponse('error',$core->lang('SURNAME_SHORT')."\n".$core->lang('MIN_LENGTH').': '.(MIN_LENGTH_SURNAME +1)));
		}
		else
			die($core->jsonResponse('error',$core->lang('NAME_SHORT')."\n".$core->lang('MIN_LENGTH').': '.(MIN_LENGTH_NAME +1)));
		}
	else
		die($core->jsonResponse('error',$core->lang('PASSWORD_SHORT')."\n".$core->lang('MIN_LENGTH').': '.(MIN_LENGTH_PASS +1)));
}
else
	die($core->jsonResponse('error',$core->lang('USERNAME_SHORT')."\n".$core->lang('MIN_LENGTH').': '.(MIN_LENGTH_USER +1)));
?>
