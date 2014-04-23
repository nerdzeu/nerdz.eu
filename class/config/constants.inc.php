<?php
/**
 * constants.inc.php 
 * automatically defines constants and checks options in config/index.php
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/config/index.php';
if (!isset ($configuration) || !is_array ($configuration))
    trigger_error ('Invalid configuration: missing $configuration variable', E_USER_ERROR);

$CONSTANTS = [
    'MINIFICATION_ENABLED' => true,
    'REDIS_ENABLED'        => true,
    'PUSHED_ENABLED'       => true,
    'PUSHED_IP6'           => true,
    'PUSHED_PORT'          => 5667,
    'MINIFICATION_JS_CMD'  => 'uglifyjs %path% -c unused=false',
    'MINIFICATION_CSS_CMD' => 'csstidy %path% --allow_html_in_templates=false --compress_colors=true --compress_font-weight=true --remove_last_\;=true --remove_bslash=true --template=highest --preserve_css=true --silent=true',
    'POSTGRESQL_HOST'      => -1, // null does not work since isset() is a faget
    'POSTGRESQL_DATA_NAME' => -1,
    'POSTGRESQL_USER'      => -1,
    'POSTGRESQL_PASS'      => -1,
    'POSTGRESQL_PORT'      => -1,
    'MIN_LENGTH_USER'      => -1,
    'MIN_LENGTH_PASS'      => -1,
    'MIN_LENGTH_NAME'      => -1,
    'MIN_LENGTH_SURNAME'   => -1,
    'CAPTCHA_LEVEL'        => -1,
    'SITE_HOST'            => -1,
    'MOBILE_HOST'          => -1,
    'STATIC_DOMAIN'        => -1,
    'USERS_NEWS'           => -1,
    'DELETED_USERS'        => -1,
    'ISSUE_BOARD'          => -1,
    'ISSUE_GIT_KEY'        => -1,
    'SMTP_SERVER'          => -1,
    'SMTP_PORT'            => -1,
    'SMTP_USER'            => -1,
    'SMTP_PASS'            => -1,
    'CAMO_KEY'             => -1,
    'LOGIN_SSL_ONLY'       => -1
];

foreach ($configuration as $const_key => $const_val)
{
    if (!isset ($CONSTANTS[$const_key]))
        trigger_error ('Unknown constant: ' . $const_key, E_USER_ERROR);
    define ($const_key, $const_val);
    unset ($CONSTANTS[$const_key]);
}

// second (and last) iteration
foreach ($CONSTANTS as $rkey => $rval)
{
    if ($rval === -1)
        trigger_error ('Missing constant from your config: ' . $rkey, E_USER_ERROR);
    define ($rkey, $rval);
}

define ('POSTGRESQL_DUP_KEY', 7);
unset ($CONSTANTS, $configuration, $rkey, $rval, $const_key, $const_val);

?>
