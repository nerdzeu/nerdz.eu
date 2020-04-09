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
// Used for minification of JS/CSS files. Also used to minify
// template files by templatecfg.class
// constants.inc.php: for MINIFICATION_*_CMD

namespace NERDZ\Core;

require_once __DIR__.'/Autoload.class.php';
require_once __DIR__.'/vendor/autoload.php';
use MatthiasMullie\Minify;

final class Minification
{
    const PATH_VAR = '%path%';

    public static function minifyJs($path)
    {
        $minifier = new Minify\JS($path);
        return $minifier->minify();
    }

    public static function minifyCss($path)
    {
        $minifier = new Minify\CSS($path);
        return $minifier->minify();
    }

    public static function minifyTemplateFile($mTimeFile, $fileToMinify, $targetMinifiedFile, $ext)
    {
        file_put_contents($targetMinifiedFile, ($ext == 'js' ? static::minifyJs($fileToMinify) : static::minifyCss($fileToMinify)));
        file_put_contents($mTimeFile, filemtime($fileToMinify));
    }
}
