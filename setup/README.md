The ultimate guide on how to setup NERDZ
=================================
Introduction
------------

After a lot of swearing, I finally managed to run NERDZ successfully on my local PC.
This README refers to the PostgreSQL branch. 

And here's a tutorial. <3

About the node.js part
----------------------

I'm sorry but until now I only managed to run the PHP part of NERDZ. If someone else
wants to contribute on the node.js part of this README, feel free to do it.

Requirements
------------

- PHP >= 5.4
- PHP and PDO drivers for PostgreSQL. Under Arch linux, type `pacman -S php-pgsql`. Remember to uncomment the right pdo connector in php.ini.
- PostgreSQL 9.2 or newer
- A webserver. Recommended: lighttpd, I'll explain later why
- php-apcu extension (not included by default in PHP, you have to install it manually - on Arch is `pacman -S php-apcu`, on other systems you can use 'pecl install apcu', on windows Windows see [here](http://dev.freshsite.pl/php-accelerators/apc.html))
- Optional: Predis for session sharing (follow the instructions [here](http://pear.nrk.io/))

Setup
-----

First, ensure that the document root in your webserver is set to NERDZ source directory (blame nessuno for that).

Then:

- Run setup/init_postgres.sh from directory /setup. You'll need an up and running database, and an user with admin rights (usually postgres).
  ```sh
  ./init_postgres.sh <adminuser>
  ```
  
OR

- If your are not on a POSIX system (i.e you're on windows), install Cygwin, add PostgreSQL binaries to PATH and repeat again. 
NERDZ does not work on pure Windows, and you'll need Cygwin for running a webserver capable of parsing our rewrite rules. 
Sorry about that. 

REMEMBER TO SET timezone = 'UTC' IN postgresql.conf OR NOTHING WILL WORK!
  
  After a bit of output, you'll be left with a fully initialized database.
- Move into 'static/js/' directory and download the following file:
    - http://static.nerdz.eu/static/js/gistBlogger.jsmin.js
  
  You can safely delete 'gistBlogger.js' or avoid to download gistBlogger.jsmin.js copying gistBlogger.js to gistBlogger.jsmin.js
- Configure NERDZ properly: copy setup/config.skel.php to class/config/index.php and edit the vars.
  Don't forget to disable minification if you haven't got csstidy / uglifyjs.
- Enable the following rewrite rules on your webserver.

Example for Lighttpd (recommended, nginx do not parse rewriterules correctly):

```lighttpd
url.rewrite-once = (
                        "^/(.+?)\.$" => "/profile.php?id=$1",
                        "^/(.+?):$" => "/project.php?gid=$1",
                        "^/(.+?)\.(\d+)$" => "/profile.php?id=$1&pid=$2",
                        "^/(.+?):(\d+)$" => "/project.php?gid=$1&pid=$2",
)
```
    

If you have problems with Lighttpd, you can try with nginx, but remember that there are still issues with it (rewrite rules are not working propertly if an user or group have spaces in its name):

```nginx
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
```
    
- Start everything and load your local NERDZ, then create your account (by registering).

Or

- If you already have a MySQL NERDZ database, you can run setup/nerdz_my2pg.groovy to migrate it.
  You need groovy, MySQL Java Connector and PostgreSQL JDBCv4 Driver.
  
  Then run:
  `$ groovy -cp "path/to/jdbc/mysql/postgresql/drivers/jars/*" nerdz_my2pg.groovy myuser mypass mydb myport pguser pgpass pgdb pgport`

- If you have an old version of the database (with gravatar table yet), please remove this table and related trigger in this way: `psql -h localhost -d nerdz -U nerdz -f setup/gravatar_migration.sql`.
  Assuming that your database name is nerdz and your user is nerdz also.

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

### Something is not working! I see "Error"s everywhere! >:(

We can reduce this to two cases:

1. You have messed with database schema/permissions and php can't access correctly to postgres. Try looking into data/errlog.txt; if present, this indicates that some query has failed.
See your httpd log too for PHP errors.
2. You've stubled upon some feature still not ported from MySQL. ~~No problem, this is expected given that this is a test branch and this port is not completely finished.~~ This should be no more the case, since the branch is now marked as stable.
Please open an issue and use data/errlog.txt and PHP logs to report what is not working.

### I got some apc_* errors

Install php-apc properly.

### I got some ob_gzhandler errors

Install php-zlib extension.

### The rewrite rules for the profiles doesn't work. I'm sure I wrote them correctly!

If you are using Linux, then double check the rules.

If you are using Windows, then you need to install a cygwin port of your webserver. This is because
Windows doesn't like paths ending with points and it ignores them (see [here](http://forum.nginx.org/read.php?2,239445,239451#msg-239451)). This rule is valid with every webserver.

I used [this build](http://kevinworthington.com/nginx-for-windows/) of nginx and [cygwinports'](http://sourceware.org/cygwinports/) build of PHP.

There are known problems with usernames and groupnames with spaces in it and nginx; you may find convenient to use lighttpd instead (if you care).

### Errors on Top 100/Monthly 100/others

Nessuno is using some C binaries for that, so we can't help you. Sorry :(

### I'm getting some weird exceptions from the Groovy script, something about timestamps.

Have you set timezone to UTC?

### I got some other problem!

Just open an issue, we could help you. ( ~~at a small price~~ )
