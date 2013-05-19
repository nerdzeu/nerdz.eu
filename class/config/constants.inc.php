<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/class/config.class.php';
$class = new confClass();

#begin mysql constants
define('MYSQL_HOST',$class->mysql_host);
define('MYSQL_DATA_NAME',$class->mysql_db);
define('MYSQL_USER',$class->mysql_user);
define('MYSQL_PASS',$class->mysql_pass);
#end mysql constants

#begin user constants
    #length
define('MIN_LENGTH_USER',$class->length_user);
define('MIN_LENGTH_PASS',$class->length_pass);
define('MIN_LENGTH_NAME',$class->length_name);
define('MIN_LENGTH_SURNAME',$class->length_surname);

    #captcha constant
define('CAPTCHA_LEVEL',$class->captcha_level);

    #site features
define('SITE_HOST',$class->site_host);
define('STATIC_DOMAIN',$class->static_domain);

    #mail features
define('SMTP_SERVER',$class->SMTP);
define('SMTP_PORT',$class->smtp_port);
define('SMTP_USER',$class->smtp_user);
define('SMTP_PASS',$class->smtp_pass);

	#special profiles
define('USERS_NEWS',1643);
define('DELETED_USERS',1644);

?>
