<?php
// NERDZ master configuration
// If you don't wanna lose the epicness of this configuration,
// please configure your IDE to use spaces instead of tabs.
// This should be put in '/class/config/index.php'.

namespace NERDZ\Core\Config;

class Variables
{
    public static $data = [
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
        'SITE_HOST'              => 'www.example.com',
        // The domain used to serve static data. If you are running
        // NERDZ on your PC, put an empty string.
        'STATIC_DOMAIN'          => 'static.example.com',
        // The domain used to serve the mobile version of NERDZ.
        // It is recommended to set this to a subdomain of the root
        // domain from the SITE_HOST variable. This means that if in
        // SITE_HOST you have www.example.com (so your root domain is
        // example.com), then you should have something like mobile.example.com.
        // Otherwise, your users will need to perform the login again when
        // switching from the desktop version to the mobile one.
        'MOBILE_HOST'            => 'mobile.example.com',

        // Minification configuration
        // NERDZ uses an automatic template minification system, this
        // means that every static file of a template is automagically
        // minified. This could lead to problems if you haven't a
        // proper installation of uglifyjs and csstidy. Disable the
        // minification if you don't need it or don't want to install
        // uglifyjs and csstidy. Enabled by default.
        'MINIFICATION_ENABLED'   => false,
        // Specify the command used to minify JS files.
        // %path% will be replaced with the file to be minified.
        // Comment these options if the default commands are okay for you.
        //'MINIFICATION_JS_CMD'  => 'something-js  %path%',

        // Misc configuration
        // True if you want to enable Redis session sharing. Disable it
        // if you don't have predis or a Redis server. Enabled by default.
        'REDIS_ENABLED'          => true,
        // The key used to post issues to GitHub. Get it from your GitHub account.
        'ISSUE_GIT_KEY'          => '...',
        // True if you want to connect to pushed (http://git.io/hJ9-rg)
        // to serve push notifications to client apps like NerdzMessenger
        // (http://git.io/29fYbg)
        'PUSHED_ENABLED'         => true,
        // PHP client supports only local pushed instances on IP.
        // This parameter specifies the IP version to use to connect to pushed.
        'PUSHED_IP6'             => true,
        'PUSHED_PORT'            => '5667',
        // SSL configuration
        // If you have configured a Camo SSL proxy server (http://git.io/YuvCpQ)
        // and set up your webserver to proxy requests from
        // https://SITE_HOST/secure/image/HASH?url=ENCODED_URL to Camo,
        // then you must put your Camo private key here.
        // By default it is set to an empty string and your images will be
        // routed to our trusted public SSL proxy.
        //'CAMO_KEY'               => "THAT-CAMO-KEY",
        // True if every login request should be sent via HTTPS.
        // This works via some CORS magic. You need to enable CORS on the HTTP
        // and mobile domains, and also you need to send the
        // Access-Control-Allow-Credentials: true header. Otherwise your cookies
        // won't be saved.
        // It is set to false by default.
        //'LOGIN_SSL_ONLY'         => true,
        // If your SSL certificate is for just one domain (example.com),
        // you should put the only secure domain name here. Otherwise, we
        // will assume that you have a wildcard certificate that will work
        // for every subdomain (*.example.com).
        //'HTTPS_DOMAIN'           => 'secure.example.com'
    ];
}

    // Hint: you want to test SSL login locally, but you don't have the time.
    // Here's a little help for you. First, create an alias in your system's
    // hosts file for 127.0.0.1 (localhost won't work, sorry). Something like
    // dev.nerdz or similar (I don't recommend using *.nerdz.eu, otherwise
    // something on the remote side will explode).
    // Then, edit your configuration properly, and uncomment this snippet
    // to magically handle everything CORS-related. Enjoy.
    /*
    if ($_SERVER['PHP_SELF'] == '/pages/profile/login.json.php' &&
        isset ($_SERVER['HTTP_ORIGIN']) && (
            $_SERVER['HTTP_ORIGIN'] == 'http://' . $configuration['SITE_HOST'] ||
            $_SERVER['HTTP_ORIGIN'] == 'http://' . $configuration['MOBILE_HOST']
        ))
    {
        header ('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        header ('Access-Control-Allow-Credentials: true');
    }
    */
?>
