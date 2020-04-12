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
require_once $_SERVER['DOCUMENT_ROOT'].'/class/Autoload.class.php';
use NERDZ\Core\Messages;

$lang = $user->getLanguage();
$presentation = file_get_contents($_SERVER['DOCUMENT_ROOT']."/data/langs/{$lang}/presentation.txt");
$presentation = htmlspecialchars($presentation, ENT_QUOTES, 'UTF-8');

$vals = [];
$vals['presentation_n'] = $presentation;

$now = intval(date('o'));

$vals['years_a'] = range($now - 100, $now - 1);
$vals['years_a'] = array_reverse($vals['years_a']);
$vals['months_a'] = range(1, 12);
$vals['days_a'] = range(1, 31);

$vals['timezones_a'] = DateTimeZone::listIdentifiers();

$messages = new Messages();
$vals['list_a'] = $messages->getLatestMessages();

if (!isset($included)) {
    $user->getTPL()->assign($vals);
    $user->getTPL()->draw('base/register');
}
