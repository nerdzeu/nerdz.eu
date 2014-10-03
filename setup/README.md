The ultimate guide on how to setup NERDZ v2
=================================

Introduction
------------

Running NERDZ is not hard, but without help you won't be able to go anywhere. That's why we are here.

Requirements
------------

- PHP >= 5.4
- PHP and PDO drivers for PostgreSQL. Under Arch linux, run `pacman -S php-pgsql`; the package should be similarly named on other Linux distributions: on Debian it's called `php5-pgsql`, for example).
Remember to uncomment the right PDO connector in `php.ini`. You will also need `php-gd`. On Windows, it should be built-in. If you installed PHP using cygwin, you should install `php-gd`, `php-json`, `php-pdo_pgsql`, `php-session`, `php-zlib`
- PostgreSQL 9.2 or newer
- A webserver. We recommend [lighttpd](http://www.lighttpd.net/) but [nginx](http://nginx.org/) works good too (however note that nginx is harder to set up on Windows). If you only want to try nerdz for some quick edit, you can skip this step and use the PHP's built-in _development_ web server, running `/www/start.sh`
- `php-apcu` extension. It is not included by default in PHP, so you have to install it manually. On Arch Linux you need to install it (`pacman -S php-apcu`) and uncomment the `/etc/php/conf.d/acpu.ini` file, otherwise you may need to compile it with PECL. This command will work for most distributions: `pecl install apcu`. If you are using the standard Windows build of PHP, you can find a binary of APCu [here](http://pecl.php.net/package/APCu). For cygwin, a precompiled build is available [here](http://robertof.nwa.xyz/mirror/apcu-cygwin/). Instructions are provided inside.
- [Composer](https://getcomposer.org/)
- Optional: Predis for session sharing (follow the instructions [here](http://pear.nrk.io/)). Details on how to setup Redis/Predis are not included here.

Setup
-----

- First, ensure that the document root in your webserver is set to the cloned GIT repository path.
- Run `setup/init_postgres.sh "admin_account_name"`. You'll need an up and running PostgreSQL installation along with an admin user (`postgres` is okay). On Windows, place `setup/init_postgres.bat` in your PostgreSQL `bin` directory and execute it. It will use the `postgres` account.

  **IMPORTANT**: Remember to set `timezone = 'UTC'` in your `postgresql.conf`.
- Copy `setup/config.skel.php` to `class/config/index.php` and edit the variables accordingly. The configuration is well-documented so you _probably_ don't need any explanation.
  However, don't forget to disable the minification if you haven't got csstidy/uglifyjs installed and remeber to set the proper static domain
- Enable our rewrite rules on your webserver. We only provide examples for the recommended webservers.

  Lighttpd:

  Please go to `servers/lighttpd` to see a full working virtual host configuration.

  Nginx:

  Please go to `servers/nginx` to see a full working virtual host configuration.

  Apache:

  Please go to `servers/apache` to see a full working virtual host configuration.

- Move to the document root and install dependencies using composer.
  ```sh
  composer.phar install
  ```
- Setup your tracking script if you need it by writing your js tracking code to `/data/tracking.js` **without the script HTML tag**.
- Setup your ads, if you need them. Create the file `/data/banner.list` using the syntax explained in `/setup/banner.list`.
- Start everything and load your local NERDZ, then create your account (by registering).
- Create two other users: one for the global news and the other for deleted users.
- Create two project: one is the issue board, the other is the global news for project.
- Insert the id and the roles in the db, running the script `setup/specialIds.sh`
- It works? Yay! It doesn't work? See the next section.

Troubleshooting
---------------

Instead of NERDZ you got a 'KABOOM' page? We're here for you.

### I really got a 'KABOOM' page!

Please, get out of Iran and try again.

### I am a lazy user which still has not migrated to PostgreSQL and I can't find instructions

Here you are. To run our migration script, you need [Groovy](http://groovy.codehaus.org/), the [MySQL Java Connector](https://dev.mysql.com/downloads/connector/j/) and the [PostgreSQL JDBCv4](http://jdbc.postgresql.org/download.html) driver.

Run the script editing the parameters accordingly:

`groovy -cp "path/to/jdbc/mysql/postgresql/drivers/jars/*" setup/nerdz_my2pg.groovy myuser mypass mydb myport pguser pgpass pgdb pgport`

The script should do the dirty work for you.

### I am another lazy user which still has the `gravatar` table in the database. What should I do?

You can remove the table and the related trigger by running the following command:

`psql -h localhost -d db_name -U db_user -f setup/gravatar_migration.sql`

### All I see is text without a style!

Check your static domain settings in the config or see below 

### In my tpl/0/js/ dir there are some *.jsmin.js empty files!

Disable minification from the config, or install uglifyjs and csstidy.

### Something is not working! I see "Error" everywhere! >:(

Please check `data/errlog.txt` (if present) for error debugging. Checking your webserver log may also be helpful.

If you think you have found a bug, please open an issue in our repository.

### I got `error1` while registering

The database is not working properly. Remember to run the `init_postgres.sh` script from the `setup` directory.
If it doesn't work, you will need to create the database manually (otherwise open an issue on this repository)

### I got some apc_* errors

Install `php-apcu` properly, as explained in the `Setup` section.

### I got some ob_gzhandler errors

Install `php-zlib`.

### The rewrite rules for profiles don't work. I'm sure I wrote them correctly!

Please double check your configuration.

If you are using Windows and nginx, you need to switch to a cygwin-based build of nginx. The author of this guide used [this](http://kevinworthington.com/nginx-for-windows/), but the [cygwinports'](https://sourceware.org/cygwinports/) one will work too. The problem doesn't exist with lighttpd since the provided Windows build **already** is cygwin-based.

You may experience some problems with nginx and spaces in URLs. This is a known issue and it's going to be fixed very soon. For now, don't use nginx and projects/profiles with spaces.

### Errors on Top 100/Monthly 100/others

We are using some C binaries for that, and using/compiling those binaries is not covered here.

### I'm getting some weird exceptions from the Groovy script, something about timestamps.

Remember to set your timezone to UTC. If it still doesn't work, please open an issueon this repository.

### HTTPS does not work on nginx. It does not load the assets!

We are using the `$_SERVER['HTTPS']` variable to check if the user is using HTTPS to browse the website. Unfortunately, this variable is not set when using nginx (and this leads to the errors you are encountering).

However, the fix is quite simple. Locate the HTTPS block in your configuration, and find the lines starting with `fastcgi_`. Add this below:

```nginx
fastcgi_param HTTPS on;
```

### I got some other problem!

Just open an issue in this repository, we may be able to help you.
