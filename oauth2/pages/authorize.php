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
use NERDZ\Core\Db;

$client_id = isset($_POST['client_id']) && is_numeric($_POST['client_id']) ? $_POST['client_id']  : false;
$redirect_uri = isset($_POST['redirect_uri']) ? $_POST['redirect_uri']  : false;
$response_type = isset($_POST['response_type']) ? $_POST['response_type']  : false;
$scope = isset($_POST['scope']) ? $_POST['scope']  : false;

if (!$client_id || !$redirect_uri || !$response_type || !$scope) {
    echo $user->lang('MISSING'), 'client_id, redirect_uri, response_type, scope';
} else {
    $vals = [];
    $vals['client_n'] = Db::Query('SELECT * FROM oauth2_clients WHERE id = :client_id',
        [
            'client_id' => $client_id
        ], Db::FETCH_OBJ);

    if(!$vals['client_n']) {
        die($user->lang('ERROR'));
    }

    $vals['scopes_a'] = [];

    $user->getTPL()->draw('oauth2/authorize');
}
