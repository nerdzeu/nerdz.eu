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
// ob_gzhandler should always be on the top, otherwise
// compression errors may happen

require_once $_SERVER['DOCUMENT_ROOT'].'/class/Autoload.class.php';
use NERDZ\Core\Minification;

$filename = $_SERVER['DOCUMENT_ROOT'].'/static/js/jclass.js';
$tmpFile = $_SERVER['DOCUMENT_ROOT'].'/tmp/japi.js';
$tmpFileTime = $_SERVER['DOCUMENT_ROOT'].'/tmp/japi.js.time';

$updateTime = 0;

if (!file_exists($tmpFileTime) ||
    intval(file_get_contents($tmpFileTime)) < ($updateTime = filemtime($filename))) {
    $content = Minification::minifyJs($filename);
    file_put_contents($tmpFile, $content);
    file_put_contents($tmpFileTime, $updateTime);
}

$content = file_get_contents($tmpFile);

header('Content-type: application/javascript');
header('Vary: Accept-Encoding');
header('Etag: '.md5_file($filename));

die($content);
