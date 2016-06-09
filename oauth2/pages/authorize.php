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
use NERDZ\Core\OAuth2;
use NERDZ\Core\Config;

$client_id = isset($_GET['client_id']) && is_numeric($_GET['client_id']) ? trim($_GET['client_id'])  : false;
$redirect_uri = isset($_GET['redirect_uri']) ? trim($_GET['redirect_uri'])  : false;
$response_type = isset($_GET['response_type']) ? trim($_GET['response_type'])  : false;
$scopes = isset($_GET['scope']) ? trim($_GET['scope'])  : false;

if (!$client_id || !$redirect_uri || !$response_type || !$scopes) {
    $vals['error_n'] = "{$user->lang('MISSING')}: client_id, redirect_uri, response_type, scopes";
} else {
    $vals = [];
    $client = Db::query(
        [
            'SELECT name, redirect_uri FROM oauth2_clients WHERE id = :id',
            [
                ':id' => $client_id,
            ],
        ], Db::FETCH_OBJ);

    if (!$client) {
        $vals['error_n'] = "{$user->lang('ERROR')}: client not found";
    } else {
        $vals['scopes_a'] = array_map([$user, 'lang'], OAuth2::getScopes($scopes));
        if (empty($vals['scopes_a'])) {
            $vals['error_n'] = "{$user->lang('MISSING')}: valid scopes";
        } else {
            if ($client->redirect_uri !== $redirect_uri) {
                $vals['error_n'] = 'redirect_uri: mismatch';
            } else {
                $vals['client_n'] = $client->name;
                $vals['authorizeurl_n'] = Config\API_URL.'/authorize';
                $vals['responsetype_n'] = htmlspecialchars($_GET['response_type'], ENT_QUOTES, 'UTF-8');
                $vals['clientid_n'] = $client_id;
                $vals['redirecturi_n'] = htmlspecialchars($_GET['redirect_uri'], ENT_QUOTES, 'UTF-8');
                $vals['scope_n'] = htmlspecialchars($_GET['scope'], ENT_QUOTES, 'UTF-8');
                $vals['authorized_n'] = $_SESSION['id'];
                $vals['authorizedcode_n'] = $user->UUID($_SESSION['id']);
            }
        }
    }
}
$user->getTPL()->assign($vals);
$user->getTPL()->draw('oauth2/authorize');
