The ultimate guide on how to setup NERDZ
=================================
Introduction
------------

After a lot of swearing, I finally managed to run NERDZ successfully on my local PC.

And here's a tutorial. <3

About the node.js part
----------------------

I'm sorry but until now I only managed to run the PHP part of NERDZ. If someone else
wants to contribute on the node.js part of this README, feel free to do it.

Requirements
------------

- PHP >= 5.4
- MySQLd/MariaDB > 5.x (tested successfully on 5.5)
- A webserver. Recommended: nginx, I'll explain later why
- php-apc extension (not included by default in PHP, you have to install it manually - usually 'pecl install apc' is okay, Windows [here](http://dev.freshsite.pl/php-accelerators/apc.html))
- Optional: Predis for session sharing (follow the instructions [here](http://pear.nrk.io/))

Setup
-----

First, ensure that the document root in your webserver is set to NERDZ source directory (blame nessuno for that).

Then:

- Create a database named 'nerdz' (other names *should* work too) and run the queries into the 'db_structure.sql' file.
- Move into 'static/js/' directory and download the following files:
    - http://static.nerdz.eu/static/js/gistBlogger.jsmin.js
    - http://static.nerdz.eu/static/js/sh.jsmin.js
  
  You can safely delete 'gistBlogger.js'.
- Configure NERDZ properly: copy setup/config.skel.php to class/config/index.php and edit the vars.
  Don't forget to disable minification if you haven't got csstidy / uglifyjs.
- Enable the following rewrite rules on your webserver. Example for nginx:
    """nginx
    location / {
        index index.html index.htm index.php;
        try_files $uri $uri/ @rewriterules;
    }
    location @rewriterules {
        rewrite ^/(.+?)\.$ /profile.php?id=$1 last;
        rewrite ^/(.+?):$ /project.php?gid=$1 last;
        rewrite ^/(.+?)\.(\d+)$ /profile.php?id=$1&pid=$2 last;
        rewrite ^/(.+?):(\d+)$ /project.php?gid=$1&pid=$2 last;
    }
    """
- Start everything and load your local NERDZ, then create your account (by registering).
- It works? Yay! It doesn't work? See the next section.

Troubleshooting
---------------

Instead of NERDZ you got a 'KABOOM' page? We're here for you.

### I really got a 'KABOOM' page!

Please, get out of Iran and try again.

### All I see is text without a style / In my tpl/0/js/ dir there are some *.jsmin.js empty files

Disable minification from the config, or install uglifyjs and csstidy.

### PHP blames about something wrong in template.values / offsets errors

Update to the last commits.

### I can't register! It says 'Errore1' when I click register! >:(

This fix is quite ridicolous. Find your my.{ini,cnf} and delete the 'sql-mode' line.
NERDZ uses some queries which the strict mode of MySQL doesn't like.

### I got some apc_* errors

Install php-apc properly.

### I got some ob_gzhandler errors

Install php-zlib extension.

### The rewrite rules for the profiles doesn't work. I'm sure I wrote them correctly!

If you are using Linux, then double check the rules.

If you are using Windows, then you need to install a cygwin port of your webserver. This is because
Windows doesn't like paths ending with points and it ignores them (see [here](http://forum.nginx.org/read.php?2,239445,239451#msg-239451)). This rule is valid with every webserver.

I used [this build](http://kevinworthington.com/nginx-for-windows/) of nginx and [cygwinports'](http://sourceware.org/cygwinports/) build of PHP.

### Errors on Top 100/Monthly 100/others

Nessuno is using some C scripts for that, so we can't help you. Sorry :(

### I got some other problem!

Open an issue!
