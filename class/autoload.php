<?php
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
?>
