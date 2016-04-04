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
require_once $_SERVER['DOCUMENT_ROOT'].'/class/Autoload.class.php';
use NERDZ\Core\Config;
use NERDZ\Core\Utils;
use NERDZ\Core\Project;
use NERDZ\Core\User;
use NERDZ\Core\Messages;

$user = new User();
$project = new Project();

if (!$user->isLogged()) {
    die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('REGISTER')));
}

foreach ($_POST as &$val) {
    $val = trim($val);
}

if (empty($_POST['description']) || !is_string($_POST['description'])) { //always required
    die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('MUST_COMPLETE_FORM')."\n\n".$user->lang('MISSING').":\n".$user->lang('DESCRIPTION')));
}

$projectData = [];

$projectData['description'] = $_POST['description'];
$projectData['owner'] = $_SESSION['id'];

//required for creation
if (isset($create)) {
    if (empty($_POST['name']) || !is_string($_POST['name'])) {
        die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('MUST_COMPLETE_FORM')."\n\n".$user->lang('MISSING').":\n".$user->lang('NAME')));
    }

    $projectData['name'] = $_POST['name'];

    if ($project->getId($projectData['name']) !== 0) {
        die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('USERNAME_EXISTS')));
    }

    if (is_numeric($projectData['name'])) {
        die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('USERNAME_NUMBER')));
    }

    if (preg_match('#^~#', $projectData['name'])) {
        die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('WRONG_USERNAME')));
    }

    if (is_numeric(strpos($projectData['name'], '#'))) {
        die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('WRONG_USERNAME')."\n".$user->lang('CHAR_NOT_ALLOWED').': #'));
    }

    if (is_numeric(strpos($projectData['name'], '+'))) {
        die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('WRONG_USERNAME')."\n".$user->lang('CHAR_NOT_ALLOWED').': +'));
    }

    if (is_numeric(strpos($projectData['name'], '&'))) {
        die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('WRONG_USERNAME')."\n".$user->lang('CHAR_NOT_ALLOWED').': &'));
    }

    if (is_numeric(strpos($projectData['name'], '%'))) {
        die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('WRONG_USERNAME')."\n".$user->lang('CHAR_NOT_ALLOWED').': %'));
    }

    if (mb_strlen($projectData['name'], 'UTF-8') < Config\MIN_LENGTH_USER) {
        die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('USERNAME_SHORT')."\n".$user->lang('MIN_LENGTH').': '.Config\MIN_LENGTH_USER));
    }

    if ($projectData['name'] !== Messages::stripTags($projectData['name'])) {
        die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('WRONG_USERNAME')."\n".$user->lang('CHAR_NOT_ALLOWED').': BBCode or [ ]'));
    }
}

if (isset($create)) {
    if (mb_strlen($projectData['name'], 'UTF-8') >= 30) {
        die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('USERNAME_LONG')));
    }
}

if (!isset($_POST['goal'])) {
    $_POST['goal'] = '';
}

if (!isset($_POST['website'])) {
    $_POST['website'] = '';
}

if (!empty($_POST['website']) && !Utils::isValidURL($_POST['website'])) {
    die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('WEBSITE').': '.$user->lang('INVALID_URL')));
}

if (!empty($_POST['photo'])) {
    if (!Utils::isValidURL($_POST['photo'])) {
        die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('PHOTO').': '.$user->lang('INVALID_URL')));
    }

    if (!($head = get_headers($_POST['photo'], 1)) || !isset($head['Content-Type'])) {
        die(NERDZ\Core\Utils::jsonResponse('error', 'Something wrong with your project image'));
    }

    if (false === strpos($head['Content-Type'], 'image')) {
        die(NERDZ\Core\Utils::jsonResponse('error', 'Your project image, is not a photo or is protected, change it'));
    }
} else {
    $_POST['photo'] = '';
}

foreach ($projectData as &$value) {
    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

//htmlspecialchars empty return values FIX
if (count(array_filter($projectData)) != count($projectData)) {
    die(NERDZ\Core\Utils::jsonResponse('error', $user->lang('ERROR').': INVALID UTF-8'));
}

$projectData['photo'] = htmlspecialchars($_POST['photo'], ENT_QUOTES, 'UTF-8');
$projectData['website'] = htmlspecialchars($_POST['website'], ENT_QUOTES, 'UTF-8');
$projectData['goal'] = htmlspecialchars($_POST['goal'], ENT_QUOTES, 'UTF-8');

$projectData['visible'] = isset($_POST['visible']) && $_POST['visible'] == 1 ? '1' : '0';
$projectData['open'] = isset($_POST['open'])    && $_POST['open']    == 1 ? '1' : '0';
$projectData['private'] = isset($_POST['private']) && $_POST['private'] == 1 ? '1' : '0';
