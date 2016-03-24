<?php
/**
 * automatically defines constants and checks options in config/index.php
 */
namespace NERDZ\Core;

class Config
{
    private static $instance;

    private function __construct()
    {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'config'. DIRECTORY_SEPARATOR. 'index.php';

        if (!is_array (Config\Variables::$data))
            trigger_error ('Invalid configuration: missing Config\\Variables::$data variable', E_USER_ERROR);

        $CONSTANTS = [
            'MINIFICATION_ENABLED' => false,
            'REDIS_HOST'           => '',
            'REDIS_PORT'           => '',

            'PUSHED_ENABLED'       => false,
            'PUSHED_IP6'           => true,
            'PUSHED_PORT'          => 5667,

            'MIN_LENGTH_USER'      => 2,
            'MIN_LENGTH_PASS'      => 6,
            'MIN_LENGTH_NAME'      => 2,
            'MIN_LENGTH_SURNAME'   => 2,
            'CAPTCHA_LEVEL'        => 5,

            'CAMO_KEY'             => '',
            'MEDIA_HOST'            => '',

            'HTTPS_DOMAIN'         => '',
            'STATIC_DOMAIN'        => '',
            'LOGIN_SSL_ONLY'       => false,
            'MINIFICATION_JS_CMD'  => 'uglifyjs %path% -c unused=false',

            'POSTGRESQL_HOST'      => -1, // null does not work since isset() is a faget
            'POSTGRESQL_DATA_NAME' => -1,
            'POSTGRESQL_USER'      => -1,
            'POSTGRESQL_PASS'      => -1,
            'POSTGRESQL_PORT'      => -1,
            'SITE_HOST'            => -1,
            'SITE_NAME'            => -1,
            'MOBILE_HOST'          => -1,
            'ISSUE_GIT_KEY'        => '',
            'SMTP_SERVER'          => -1,
            'SMTP_PORT'            => -1,
            'SMTP_USER'            => -1,
            'SMTP_PASS'            => -1,
            'TRUSTED_PROXIES'      => [],
        ];

        foreach (Config\Variables::$data as $const_key => $const_val)
        {
            if (!isset ($CONSTANTS[$const_key]))
                trigger_error ('Unknown constant: ' . $const_key, E_USER_ERROR);

            Config::add($const_key, $const_val);
            unset ($CONSTANTS[$const_key]);
        }

        // second (and last) iteration
        foreach ($CONSTANTS as $rkey => $rval)
        {
            if ($rval === -1)
                trigger_error ('Missing constant from your config: ' . $rkey, E_USER_ERROR);

            Config::add($rkey, $rval);
        }
    }

    public static function init()
    {
        if(empty($instance))
            $instance = new Config();
    }

    public static function add($key, $value)
    {
        define(__NAMESPACE__.'\\Config\\'.$key, $value);
    }
}
