<?php
/*
 * Classe di configurazione di base.
 * Imposta ogni variabile del sistema software
 */
final class confClass
{
	public $mysql_user;
    public $mysql_pass;
    public $mysql_host;
    public $mysql_db;
    public $captcha_level;
    public $length_user;
    public $length_pass;
    public $length_name;
    public $length_surname;
    public $site_host;
    public $SMTP;
    public $smtp_port;
    public $static_domain;
    
    public function __construct()
    {
		require_once $_SERVER['DOCUMENT_ROOT'].'class/config/index.php';
		if(isset($Rmysql_user, $Rmysql_host, $Rmysql_pass, $Rmysql_db,$Rsite_host))
		{
			$this->mysql_user = $Rmysql_user;
			$this->mysql_pass = $Rmysql_pass;
			$this->mysql_host = $Rmysql_host;
			$this->mysql_db   = $Rmysql_db;
			$this->site_host  = $Rsite_host;
			if(isset($Rcaptcha_level, $Rlength_user, $Rlength_pass, $Rlength_name, $Rlength_surname))
			{
				$this->captcha_level  = $Rcaptcha_level;
				$this->length_user    = $Rlength_user;
				$this->length_pass    = $Rlength_pass;
				$this->length_name    = $Rlength_name;
				$this->length_surname = $Rlength_surname;
				if(isset($Rsmtp, $Rsmtp_port, $Rsmtp_user, $Rsmtp_pass, $Rstatic_domain))
				{
					$this->SMTP = $Rsmtp;
					$this->smtp_port = $Rsmtp_port;
					$this->smtp_user = $Rsmtp_user;
					$this->smtp_pass = $Rsmtp_pass;
					$this->static_domain = $Rstatic_domain;
					return;
				}
			}
		}
		die('SYSTEM ERROR');
	}
}
?>
