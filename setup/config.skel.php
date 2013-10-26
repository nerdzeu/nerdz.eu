<?php
// Basic configuration
// This should be put in '/class/config/index.php'.

// PostgreSQL hostname.
$Rpostgresql_host = 'localhost';
// PostgreSQL port.
$Rpostgresql_port = '5432';
// Desidered PostgreSQL database to use.
$Rpostgresql_db = 'nerdz';
// PostgreSQL username/password to use.
$Rpostgresql_user = 'nerdz';
$Rpostgresql_pass = '';
// Length of the string generated in the captcha.
$Rcaptcha_level = 5;
// Site hostname. Change to 'localhost' if you are running
// NERDZ on your PC. NOTE: do not put the protocol!
$Rsite_host = 'www.nerdz.eu';
// Minimum length of usernames.
$Rlength_user = 2;
// Minimum length of passwords.
$Rlength_pass = 6;
// Minimum length of realnames.
$Rlength_name = 2;
// Minimum length of surnames.
$Rlength_surname = 2;
// SMTP server/port/user/password used to send mails.
$Rsmtp = 'smtp.gmail.com';
$Rsmtp_port = 465;
$Rsmtp_user = 'mail user';
$Rsmtp_pass = 'mail pass';
// A domain used for serving of static data.
// Use an empty string if you have no static domains.
// NOTE: be sure to check $Rdo_minification and similar.
// You can have a lot of problems if you misconfigure them.
$Rstatic_domain = 'http://static.doma.in/';
// Specifies if Redis session sharing is enabled or not.
// Disable if you haven't a Redis server.
// Default value: TRUE
//$Rredis_enabled = false;

// -- Minification options --
// NERDZ uses an automatic template minification system, this
// means that every static file of a template is
// automagically (by default) minified. This could lead to problems
// if you haven't a proper installation of uglifyjs and csstidy.
// Disable minification if you don't need it/you don't want to install
// uglifyjs and csstidy.

// Enables or not the minification of static JS/CSS files.
// Default value: TRUE
//$Rdo_minification = false;

// Specifies the commands to use for the minification.
// Any '%path%' in the string will automatically be replaced by the
// desidered file to minify.
// The default commands are stored in '/class/config.class.php'.
//$Rjs_minify_cmd = 'something-js %path%';
//$Rcss_minify_cmd = 'something-css %path%';
?>
