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

final class Minification
{
    const PATH_VAR = '%path%';

    public static function minifyJs($path)
    {
        return shell_exec(str_ireplace(static::PATH_VAR, $path, Config\MINIFICATION_JS_CMD));
    }

    public static function minifyCss($path)
    {
        require_once __DIR__.'/vendor/autoload.php';
        $css = new \csstidy();
        $css->set_cfg('allow_html_in_templates', false);
        $css->set_cfg('compress_colors', true);
        $css->set_cfg('compress_font-weight', true);
        $css->set_cfg('remove_last_', true);
        $css->set_cfg('remove_bslash', true);
        $css->set_cfg('template', 'highest');
        $css->set_cfg('preserve_css', true);
        $css->set_cfg('silent', true);
        $css->parse(file_get_contents($path));

        return $css->print->plain();
    }

    public static function minifyTemplateFile($mTimeFile, $fileToMinify, $targetMinifiedFile, $ext)
    {
        if (file_put_contents($targetMinifiedFile, ($ext == 'js' ? static::minifyJs($fileToMinify) : static::minifyCss($fileToMinify))) !== false) {
            chmod($targetMinifiedFile, 0775);
        }

        if (file_put_contents($mTimeFile, filemtime($fileToMinify)) !== false) {
            chmod($mTimeFile, 0775);
        }
    }
}
