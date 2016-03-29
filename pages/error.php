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
$code = isset($_GET['code']) && !is_array($_GET['code']) && is_numeric($_GET['code']) ? htmlspecialchars($_GET['code'], ENT_QUOTES, 'UTF-8') : false;
$errmsg[400] = 'Bad Request';
$errmsg[401] = 'Authorization required';
$errmsg[403] = 'Forbidden';
$errmsg[404] = 'Page not found';
$errmsg[500] = 'Internal server error';
$errmsg[501] = 'Not Implemented';
$errmsg[502] = 'Bad Gateway';
$vals = [];
if ($code) {
    if (isset($errmsg[$code])) {
        $vals['error_n'] = $errmsg[$code];
    } else {
        $vals['error_n'] = 'Undefined error';
    }
    $vals['errorcode_n'] = $code;
    $vals['ip_n'] = NERDZ\Core\IpUtils::getIp();
    $vals['useragent_n'] = isset($_SERVER['HTTP_USER_AGENT']) ? htmlspecialchars($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8') : '';
    $vals['referrer_n'] = isset($_SERVER['HTTP_REFERRER']) ? htmlspecialchars($_SERVER['HTTP_REFERRER'], ENT_QUOTES, 'UTF-8') : 'Direct';
} else {
    $vals['error_n'] = $vals['errorcode_n'] = $vals['ip_n'] = $vals['useragent_n'] = $vals['referrer_n'] = 'Undefined Error';
}

$user->getTPL()->assign($vals);
$user->getTPL()->draw('base/error');
