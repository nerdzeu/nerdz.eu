<?php
// Used for minification of JS/CSS files. Also used to minify
// template files by templatecfg.class
// constants.inc.php: for MINIFICATION_*_CMD
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/config/constants.inc.php';
final class Minification
{
    const PATH_VAR = '%path%'; // private constants do not exist :(

    public static function minifyJs ($path)
    {
        return shell_exec (str_ireplace (self::PATH_VAR, $path, MINIFICATION_JS_CMD));
    }

    public static function minifyCss ($path)
    {
        return shell_exec (str_ireplace (self::PATH_VAR, $path, MINIFICATION_CSS_CMD));
    }

    public static function minifyTemplateFile ($mTimeFile, $fileToMinify, $targetMinifiedFile, $ext)
    {
        file_put_contents ($targetMinifiedFile, ( $ext == 'js' ? self::minifyJs ($fileToMinify) : self::minifyCss ($fileToMinify)) );
        chmod ($targetMinifiedFile, 0775);
        file_put_contents ($mTimeFile, filemtime ($fileToMinify));
        chmod ($mTimeFile, 0775);
    }
}
?>
