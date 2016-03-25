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
namespace NERDZ\Core;

class Autoloader
{
    public static function load($class) {
        // base directory for the namespace prefix
        $base_dir = __DIR__;

        // does the class use the namespace prefix?
        $len = strlen(__NAMESPACE__);
        if (strncmp(__NAMESPACE__, $class, $len) !== 0) {
            // no, move to the next registered autoloader
            return;
        }

        // get the relative class name
        $relative_class = substr($class, $len);

        // replace the namespace prefix with the base directory, replace namespace
        // separators with directory separators in the relative class name, set to lower case,
        // append with .class.php
        $file = $base_dir . strtolower(str_replace('\\', '/', $relative_class)) . '.class.php';

        // if the file is readable and exists, require_once it
        if (is_readable($file)) {
            require_once $file;
        }
    }
}

spl_autoload_register(__NAMESPACE__ . '\\Autoloader::load');

// Define NERDZ constants
Config::init();

// even session_set_cookie_params is shit, thus we have to set session cookie
// parameters (secure and httponly) after session_start().
// We use session_set_cookie_params only to set the same domain
session_set_cookie_params(0, '/', System::getSafeCookieDomainName());
// Start session
if(Config\REDIS_HOST !== '' && Config\REDIS_PORT !== '')
    new RedisSessionHandler(Config\REDIS_HOST, Config\REDIS_PORT);
else
    session_start();

// lifetime = 0 (until the browser is closed)
// path = /, domain = System::getSafeCookieDomainName()
// secure = false, httponly = true
setcookie(session_name(),session_id(), 0, '/', System::getSafeCookieDomainName(), false, true);
