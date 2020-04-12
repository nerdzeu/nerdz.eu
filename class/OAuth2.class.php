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

namespace NERDZ\Core;

require_once __DIR__.'/Autoload.class.php';

class OAuth2
{
    const SCOPES = [
        'BASE',
        'FOLLOWERS',
        'FOLLOWING',
        'FRIENDS',
        'MESSAGES',
        'NOTIFICATIONS',
        'PMS',
        'PROFILE_COMMENTS',
        'PROFILE_MESSAGES',
        'PROFILE',
        'PROJECTS',
        'PROJECT_COMMENTS',
        'PROJECT_MESSAGES',
    ];
    const PERMISSIONS = ['READ', 'WRITE'];

    public static function getScopes(string $scopesString) : array
    {
        $ret = [];
        $scopes = explode(' ', $scopesString);
        foreach ($scopes as $s) {
            $parts = explode(':', $s);
            if (count($parts) != 2) {
                continue;
            }
            $name = strtoupper($parts[0]);
            if (!in_array($name, self::SCOPES)) {
                continue;
            }
            $rw = array_map('strtoupper', explode(',', $parts[1]));
            foreach ($rw as $permission) {
                if (in_array($permission, self::PERMISSIONS)) {
                    $ret[] = "OAUTH2_{$name}_{$permission}";
                }
            }
        }

        return $ret;
    }
}
