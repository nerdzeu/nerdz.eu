<?php
/*
 * Classe di configurazione di base.
 * Imposta ogni variabile del sistema software
 */
final class confClass
{
    // TODO: migrate to an array-based configuration ($cfg['option'])
    public $postgresql_user;
    public $postgresql_pass;
    public $postgresql_host;
    public $postgresql_db;
    public $captcha_level;
    public $length_user;
    public $length_pass;
    public $length_name;
    public $length_surname;
    public $site_host;
    public $SMTP;
    public $smtp_port;
    public $static_domain;
    // The following variables are set to a default of
    // true to allow the original NERDZ to run without
    // any additional config options.
    public $redis_enabled = true;
    public $do_minification = true;
    public $js_min_cmd = 'uglifyjs %path% -c unused=false';
    // TODO: wrap to 80 columns this huge thing
    public $css_min_cmd = 'csstidy %path% --allow_html_in_templates=false --compress_colors=true --compress_font-weight=true --remove_last_\;=true --remove_bslash=true --template=highest --preserve_css=true --silent=true';
    
    public function __construct()
    {
        require_once $_SERVER['DOCUMENT_ROOT'].'/class/config/index.php';
        if(isset($Rpostgresql_user, $Rpostgresql_host, $Rpostgresql_pass, $Rpostgresql_db,$Rsite_host))
        {
            $this->postgresql_user = $Rpostgresql_user;
            $this->postgresql_pass = $Rpostgresql_pass;
            $this->postgresql_host = $Rpostgresql_host;
            $this->postgresql_db   = $Rpostgresql_db;
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
                    // optional config options
                    // TODO: find a best approach than 10k issets (arrays?)
                    if(isset ($Rredis_enabled))
                        $this->redis_enabled = $Rredis_enabled;
                    if(isset ($Rdo_minification))
                        $this->do_minification = $Rdo_minification;
                    if(isset ($Rjs_minify_cmd))
                        $this->js_min_cmd = $Rjs_minify_cmd;
                    if(isset ($Rcss_minify_cmd))
                        $this->css_min_cmd = $Rcss_minify_cmd;
                    return;
                }
            }
        }
        die('SYSTEM ERROR');
    }
}
?>
