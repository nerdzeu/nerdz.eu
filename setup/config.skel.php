<?php
// NERDZ master configuration
// If you don't wanna lose the epicness of this configuration,
// please configure your IDE to use spaces instead of tabs.
// This should be put in '/class/config/index.php'.

$configuration = [
    // Database configuration
    // PostgreSQL hostname
    'POSTGRESQL_HOST'        => 'sql.example.com',
    // PostgreSQL port
    'POSTGRESQL_PORT'        => '5432',
    // PostgreSQL database/scheme name
    'POSTGRESQL_DATA_NAME'   => 'example_db',
    // PostgreSQL username
    'POSTGRESQL_USER'        => 'example_user',
    // PostgreSQL password
    'POSTGRESQL_PASS'        => 'wow',

    // Configuration of various requirements
    // Minimum username length (in characters)
    'MIN_LENGTH_USER'        => 2,
    // Minimum password length (in characters)
    'MIN_LENGTH_PASS'        => 6,
    // Minimum realname length (in characters)
    'MIN_LENGTH_NAME'        => 2,
    // Minimum surname  length (in characters)
    'MIN_LENGTH_SURNAME'     => 2,
    // Length of the CAPTCHA string (in chars)
    'CAPTCHA_LEVEL'          => 5,

    // Mail configuration
    // SMTP server username
    'SMTP_SERVER'            => 'mail.example.com',
    // SMTP server port
    'SMTP_PORT'              => '465',
    // SMTP server username
    'SMTP_USER'              => 'bob',
    // SMTP server password
    'SMTP_PASS'              => 'KFC',

    // Domain configuration
    // Your NERDZ hostname. If you are running NERDZ on your
    // PC, use 'localhost'. Do NOT put the protocol (http/https).
    'SITE_HOST'              => 'example.com',
    // The domain used to serve static data. If you are running
    // NERDZ on your PC, put an empty string.
    'STATIC_DOMAIN'          => 'static.example.com',
    // The domain for the mobile version
    // The rules defined above also apply in this case
    'MOBILE_HOST'            => 'mobile.example.com',

    // Minification configuration
    // NERDZ uses an automatic template minification system, this
    // means that every static file of a template is automagically
    // minified. This could lead to problems if you haven't a
    // proper installation of uglifyjs and csstidy. Disable the
    // minification if you don't need it or don't want to install
    // uglifyjs and csstidy.
    'MINIFICATION_ENABLED'   => false, // Default value: true
    // Specify the command used to minify JS/CSS files.
    // %path% will be replaced with the file to be minified.
    // Comment these options if the default commands are okay for you.
    //'MINIFICATION_CSS_CMD' => 'something-css %path%',
    //'MINIFICATION_JS_CMD'  => 'something-js  %path%',

    // Misc configuration
    // True if you want to enable Redis session sharing. Disable it
    // if you don't have predis or a Redis server.
    'REDIS_ENABLED'          => true, // Default value: true
    // Put the IDs for the special profiles 'users news' and
    // 'deleted users'. NERDZ will work fine even if those
    // IDs do not exist, until someone changes nicks or deletes himself.
    'USERS_NEWS'             => 2,
    'DELETED_USERS'          => 3,
    'ISSUE_BOARD'            => 106,
    'ISSUE_GIT_KEY'          => 'Get the key from your GitHub account',
    // Now NERDZ features an automatic versioning system based on 
    // GIT revision hashes. However, you need to specify the path
    // to the git executable if you want to enable it. 
    // Feel free to put false (or comment this) if you don't need it.
    'GIT_PATH'               => '/usr/bin/git',
    // True if you want to connect to pushed (github.com/mcilloni/pushed) to serve push notifications to client apps like 
    // NerdzMessenger (github.com/mcilloni/NerdzMessenger).
    'PUSHED_ENABLED'         => true,
    // PHP client supports only local pushed instances on IP (no UNIX sockets right now)
    // This parameter indicates IP version to use to connect to pushed (default: 6)
    'PUSHED_IP6'             => true,
    'PUSHED_PORT'            => '5667'
];
?>
