<?php
/*
Copyright (C) 2010-2020 Paolo Galeone <nessuno@nerdz.eu>

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

class Autoload
{
    public static function load($class)
    {
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

        // if there's a subdir eg: NERDZ\Core\Error\Image -> /class/error/Image.class.php
        // lowercase directory name
        if (substr_count($relative_class, '\\') > 1) {
            $last_pos = strrpos($relative_class, '\\');
            $relative_class = strtolower(substr($relative_class, 0, $last_pos)).substr($relative_class, $last_pos);
        }

        // replace the namespace prefix with the base directory, replace namespace
        // separators with directory separators in the relative class name and append '.class.php'
        $file = $base_dir.str_replace('\\', '/', $relative_class).'.class.php';

        // if the file is readable and exists, require_once it
        if (is_readable($file)) {
            require_once $file;
        }
    }
}

spl_autoload_register(__NAMESPACE__.'\\Autoload::load');

// Define NERDZ constants
Config::init();

// even session_set_cookie_params is shit, thus we have to set session cookie
// parameters (secure and httponly) after session_start().
// We use session_set_cookie_params only to set the same domain
session_set_cookie_params(0, '/', System::getSafeCookieDomainName());

// Start session
session_start();


// lifetime = 0 (until the browser is closed)
// path = /, domain = System::getSafeCookieDomainName()
// secure = false, httponly = true
setcookie(session_name(), session_id(), 0, '/', System::getSafeCookieDomainName(), false, true);
