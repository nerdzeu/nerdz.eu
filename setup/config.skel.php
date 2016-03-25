<?php
/*
Copyright (C) 2016 Paolo Galeone <nessuno@nerdz.eu>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
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

        // General configuration
        // Your website name
        'SITE_NAME'              => 'NERDZ TEST',

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
        // proper installation of uglifyjs. Disable the
        // minification if you don't need it or don't want to install
        // uglifyjs and csstidy.
        'MINIFICATION_ENABLED'   => false,
        // Specify the command used to minify JS files.
        // %path% will be replaced with the file to be minified.
        // Comment these options if the default commands are okay for you.
        //'MINIFICATION_JS_CMD'  => 'something-js  %path%',

        // Misc configuration
        // Set host and port if you want to enable Redis session sharing. Disable it
        // if you don't have predis or a Redis server.
        // 'REDIS_HOST'          => 'redis.somewhere.com',
        // 'REDIS_PORT'          => 4545,
 
        // The key used to post issues to GitHub. Get it from your GitHub account.
        // 'ISSUE_GIT_KEY'          => '...',

        // True if you want to connect to pushed (http://git.io/hJ9-rg)
        // to serve push notifications to client apps like NerdzMessenger
        // (http://git.io/29fYbg)
        //'PUSHED_ENABLED'         => false,
        // PHP client supports only local pushed instances on IP.
        // This parameter specifies the IP version to use to connect to pushed.
        // Default false
        //'PUSHED_IP6'             => true, // if listen on local ipv6
        //'PUSHED_PORT'            => 5667,

        // SSL configuration
        // If you have configured a Camo SSL proxy server (http://git.io/YuvCpQ)
        // then you must put your Camo private key here, follwed by the host.
        // Requests will be routed to:
        // https://MEDIA_HOST/camo/HASH?url=ENCODED_URL to Camo,
        // By default it is set to an empty string and your images will be
        // routed to our trusted public SSL proxy.
        //'CAMO_KEY'                => "THAT-CAMO-KEY",
        //'MEDIA_HOST'              => 'media.somewhere.com'
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
        //'HTTPS_DOMAIN'           => 'secure.example.com',
        //
        // Proxy configuration
        // If you find yourself behind some sort of proxy - like a load balancer - then
        // certain header information may be sent to you using special X-Forwarded-* headers.
        // When you're behind a proxy, the true host may be stored in a X-Forwarded-Host header.
        // Since HTTP headers can be spoofed, we do not trust these proxy headers by default.
        // If you are behind a proxy, you should manually whitelist your proxy.
        // NERDZ will trust the request only if the ip address is in that list
        // and will extract the real IP.
        // 'TRUSTED_PROXIES'       => []
        // You can specify a single ip (v4 or v6), you can use CIDR notation, or you can
        // set more than one proxy in any format (even combine ipv4 and ipv6 ips and subnets)
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
