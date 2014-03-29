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
    // Length of the CAPTCHA string (in characters)
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

    // Domain configuration. The protocol should not be included.
    // Your NERDZ hostname. If you are running NERDZ on your
    // PC, use 'localhost'.
    'SITE_HOST'              => 'example.com',
    // The domain used to serve static data. If you are running
    // NERDZ on your PC, put an empty string.
    'STATIC_DOMAIN'          => 'static.example.com',
    // The domain used to serve the mobile version of NERDZ.
    'MOBILE_HOST'            => 'mobile.example.com',

    // Minification configuration
    // NERDZ uses an automatic template minification system, this
    // means that every static file of a template is automagically
    // minified. This could lead to problems if you haven't a
    // proper installation of uglifyjs and csstidy. Disable the
    // minification if you don't need it or don't want to install
    // uglifyjs and csstidy. Enabled by default.
    'MINIFICATION_ENABLED'   => false,
    // Specify the command used to minify JS/CSS files.
    // %path% will be replaced with the file to be minified.
    // Comment these options if the default commands are okay for you.
    //'MINIFICATION_CSS_CMD' => 'something-css %path%',
    //'MINIFICATION_JS_CMD'  => 'something-js  %path%',

    // Misc configuration
    // True if you want to enable Redis session sharing. Disable it
    // if you don't have predis or a Redis server. Enabled by default.
    'REDIS_ENABLED'          => true,
    // IDs of special profiles/project.
    // NERDZ will work if those IDs do not exist, until something
    // which requires them is used. (like changing a nick, or deleting users)
    'USERS_NEWS'             => 2,   // Used to show nick changes
    'DELETED_USERS'          => 3,   // Contains the posts of deleted users
    'ISSUE_BOARD'            => 106, // ID of the board used to report bugs
    // The key used to post issues to GitHub. Get it from your GitHub account.
    'ISSUE_GIT_KEY'          => '...',
    // True if you want to connect to pushed (http://git.io/hJ9-rg)
    // to serve push notifications to client apps like NerdzMessenger
    // (http://git.io/29fYbg)
    'PUSHED_ENABLED'         => true,
    // PHP client supports only local pushed instances on IP.
    // This parameter indicates IP version to use to connect to pushed.
    'PUSHED_IP6'             => true,
    'PUSHED_PORT'            => '5667'
];
?>
